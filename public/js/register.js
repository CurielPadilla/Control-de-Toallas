// public/js/register.js — versión robusta
'use strict';

let memberId = null;
let descriptors = [];
let snapshotBlobs = [];
let modelsLoaded = false;

// -------- utilidades UI --------
function setStatus(msg, isError = false) {
  const el = document.getElementById('res');
  if (!el) return;
  el.textContent = msg;
  el.style.color = isError ? '#b00020' : '#0a5';
}

function isSecureContextOk() {
  const h = location.hostname;
  return location.protocol === 'https:' || h === 'localhost' || h === '127.0.0.1';
}

// -------- manejo de cámara --------
async function getVideoInputs() {
  const devices = await navigator.mediaDevices.enumerateDevices();
  return devices.filter(d => d.kind === 'videoinput');
}

async function startCamWithDevice(videoEl, deviceId = null) {
  if (videoEl.srcObject) {
    try { videoEl.srcObject.getTracks().forEach(t => t.stop()); } catch (_) {}
    videoEl.srcObject = null;
  }
  const constraints = deviceId
    ? { video: { deviceId: { exact: deviceId }, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false }
    : { video: { width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false };

  const stream = await navigator.mediaDevices.getUserMedia(constraints);
  videoEl.srcObject = stream;

  await new Promise(resolve => {
    if (videoEl.readyState >= 2 && videoEl.videoWidth > 0) return resolve();
    videoEl.onloadedmetadata = () => resolve();
  });
  await videoEl.play();
}

async function startCam() {
  const videoEl = document.getElementById('video');
  const cameraSel = document.getElementById('cameraSelect');
  if (!videoEl) return;

  if (!isSecureContextOk()) {
    console.warn('Para usar cámara, abre por HTTPS o http://localhost');
  }

  try {
    let cams = await getVideoInputs();
    if (cams.length === 0) {
      // a veces necesita una primera llamada
      try { await navigator.mediaDevices.getUserMedia({ video: true, audio: false }); } catch (_) {}
      cams = await getVideoInputs();
    }
    if (cams.length === 0) throw new Error('No se encontraron cámaras.');

    // Poblar selector
    if (cameraSel) {
      cameraSel.innerHTML = cams.map(c => `<option value="${c.deviceId}">${c.label || ('Cámara ' + c.deviceId.slice(-4))}</option>`).join('');
    }

    // Última cámara usada o la primera
    let deviceId = localStorage.getItem('reg_lastCamId');
    if (!cams.find(c => c.deviceId === deviceId)) deviceId = cams[0].deviceId;

    // Cambios en caliente
    if (cameraSel) {
      cameraSel.value = deviceId;
      cameraSel.onchange = async () => {
        const newId = cameraSel.value;
        localStorage.setItem('reg_lastCamId', newId);
        try {
          await startCamWithDevice(videoEl, newId);
        } catch (err) {
          console.error('No se pudo cambiar de cámara', err);
          alert('No se pudo cambiar de cámara: ' + err.message);
        }
      };
    }

    // Arrancar
    await startCamWithDevice(videoEl, deviceId);
    localStorage.setItem('reg_lastCamId', deviceId);
  } catch (e1) {
    console.warn('Fallo con deviceId. Reintentando con video:true', e1);
    try {
      const videoEl = document.getElementById('video');
      const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
      videoEl.srcObject = stream;
      await new Promise(resolve => {
        if (videoEl.readyState >= 2 && videoEl.videoWidth > 0) return resolve();
        videoEl.onloadedmetadata = () => resolve();
      });
      await videoEl.play();
    } catch (e2) {
      console.error('No se pudo abrir la cámara de ninguna forma', e1, e2);
      setStatus('No se pudo abrir la cámara. Verifica permisos/HTTPS/otra app usando la cámara.', true);
    }
  }
}

function stopCam() {
  const videoEl = document.getElementById('video');
  if (videoEl && videoEl.srcObject) {
    try { videoEl.srcObject.getTracks().forEach(t => t.stop()); } catch (_) {}
    videoEl.srcObject = null;
  }
}

// -------- reconocimiento y registro --------
function videoToCanvas(video) {
  const c = document.createElement('canvas');
  const w = video.videoWidth || 640;
  const h = video.videoHeight || 480;
  c.width = w; c.height = h;
  c.getContext('2d').drawImage(video, 0, 0, w, h);
  return c;
}

async function ensureModelsLoaded() {
  if (modelsLoaded) return true;
  try {
    await loadFaceModels(); // provisto por /js/face.js
    modelsLoaded = true;
    setStatus('Modelos cargados. Puedes capturar muestras.');
    return true;
  } catch (err) {
    console.error('Error cargando modelos de face-api.js', err);
    setStatus('Error cargando modelos. Revisa MODEL_URL en /js/face.js.', true);
    return false;
  }
}

// -------- init y eventos --------
async function init() {
  await startCam();        // abre cámara primero
  ensureModelsLoaded();    // carga modelos en paralelo

  document.getElementById('btnCrear').onclick = async () => {
    const nombre = document.getElementById('nombre').value.trim();
    const membresia = document.getElementById('membresia').value.trim();
    if (!nombre || !membresia) {
      alert('Nombre y membresía son obligatorios');
      return;
    }
    try {
      const res = await fetch('/api/members', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nombre, membresia })
      });
      const data = await res.json();
      if (data.id) {
        memberId = data.id;
        setStatus('Asociado creado #' + memberId);
      } else {
        setStatus('No se pudo crear asociado: ' + (data.error || 'Error desconocido'), true);
      }
    } catch (err) {
      console.error('Error creando asociado', err);
      setStatus('Error creando asociado. Revisa el servidor.', true);
    }
  };

  document.getElementById('btnCapturar').onclick = async () => {
    if (!memberId) return alert('Primero crea el asociado');

    if (!modelsLoaded) {
      const ok = await ensureModelsLoaded();
      if (!ok) return alert('Aún no cargan los modelos. Intenta de nuevo en unos segundos.');
    }

    const video = document.getElementById('video');
    if (!video || !video.srcObject) {
      setStatus('La cámara no está lista. Recarga la página.', true);
      return;
    }
    if (video.videoWidth === 0) {
      await new Promise(r => setTimeout(r, 200));
    }
    if (video.videoWidth === 0) {
      setStatus('La cámara aún no está lista. Intenta de nuevo.', true);
      return;
    }

    try {
      const c = videoToCanvas(video);
      const det = await faceapi
        .detectSingleFace(c, getTinyOptions())
        .withFaceLandmarks()
        .withFaceDescriptor();

      if (!det) {
        alert('No se detectó rostro. Mejora iluminación/posición e intenta de nuevo.');
        return;
      }

      descriptors.push(Array.from(det.descriptor));
      await new Promise(resolve => c.toBlob(b => {
        snapshotBlobs.push(b);
        const img = new Image();
        img.src = URL.createObjectURL(b);
        img.width = 160;
        document.getElementById('muestras').appendChild(img);
        resolve();
      }, 'image/jpeg', 0.9));

      setStatus(`Muestra capturada. Total: ${descriptors.length}`);
    } catch (err) {
      console.error('Error en captura/detección', err);
      alert('Error al detectar el rostro. Revisa la consola.');
    }
  };

  document.getElementById('btnGuardar').onclick = async () => {
    if (!memberId) return alert('Crea el asociado primero');
    if (descriptors.length === 0) return alert('Captura al menos 1 muestra');

    try {
      let okCount = 0;
      for (let i = 0; i < descriptors.length; i++) {
        const form = new FormData();
        form.append('member_id', memberId);
        form.append('descriptor_json', JSON.stringify(descriptors[i]));
        form.append('image', snapshotBlobs[i] || snapshotBlobs[snapshotBlobs.length - 1], `muestra_${i + 1}.jpg`);

        const res = await fetch('/api/faces', { method: 'POST', body: form });
        const data = await res.json();
        if (data.id) okCount++;
        else console.warn('Error guardando muestra', i + 1, data);
      }
      setStatus(`Muestras guardadas: ${okCount}/${descriptors.length}`);
      alert('Muestras guardadas: ' + okCount);

      // Limpieza
      descriptors = [];
      snapshotBlobs = [];
      document.getElementById('muestras').innerHTML = '';
    } catch (err) {
      console.error('Error guardando muestras', err);
      setStatus('Error guardando muestras. Revisa el servidor.', true);
    }
  };

  window.addEventListener('beforeunload', stopCam);
}

document.addEventListener('DOMContentLoaded', () => {
  init().catch(err => {
    console.error('Init error', err);
    setStatus('No se pudo iniciar el registro.', true);
  });
});
