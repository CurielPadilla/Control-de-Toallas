// public/js/operate.js — versión robusta
'use strict';

let matcher = null;
let currentMember = null;
let catalogo = [];
let modelsLoaded = false;

const RECOG_THRESHOLD = 0.6;  // 0.5 más estricto, 0.7 más laxo
const SCAN_INTERVAL_MS = 700; // menor = más CPU
let lastScanAt = 0;

// -------- utilidades UI --------
function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, (ch) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
}

function showStatus(message, isError = false) {
  const el = document.getElementById('asociado');
  if (!el) return;
  const color = isError ? '#b00020' : '#0a5';
  if (currentMember) {
    el.innerHTML =
      '<b>' + escapeHtml(currentMember.nombre) + '</b> ' +
      '(<span class="badge">' + escapeHtml(currentMember.membresia) + '</span>)' +
      '<br><span style="color:' + color + ';font-size:12px">' + escapeHtml(message) + '</span>';
  } else {
    el.innerHTML = 'No identificado<br><span style="color:' + color + ';font-size:12px">' + escapeHtml(message) + '</span>';
  }
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
  const videoEl = document.getElementById('cam');
  const cameraSel = document.getElementById('cameraSelect');
  if (!videoEl) return;

  if (!isSecureContextOk()) {
    console.warn('Para usar cámara, abre por HTTPS o http://localhost');
  }

  try {
    let cams = await getVideoInputs();
    if (cams.length === 0) {
      try { await navigator.mediaDevices.getUserMedia({ video: true, audio: false }); } catch (_) {}
    }
    cams = await getVideoInputs();
    if (cams.length === 0) throw new Error('No se encontraron cámaras.');

    // Poblar selector
    if (cameraSel) {
      cameraSel.innerHTML = cams.map(c => `<option value="${c.deviceId}">${c.label || ('Cámara ' + c.deviceId.slice(-4))}</option>`).join('');
    }

    // Última cámara usada o primera
    let deviceId = localStorage.getItem('op_lastCamId');
    if (!cams.find(c => c.deviceId === deviceId)) deviceId = cams[0].deviceId;

    // Cambios en caliente
    if (cameraSel) {
      cameraSel.value = deviceId;
      cameraSel.onchange = async () => {
        const newId = cameraSel.value;
        localStorage.setItem('op_lastCamId', newId);
        try {
          await startCamWithDevice(videoEl, newId);
        } catch (err) {
          console.error('No se pudo cambiar de cámara', err);
          alert('No se pudo cambiar de cámara: ' + err.message);
        }
      };
    }

    await startCamWithDevice(videoEl, deviceId);
    localStorage.setItem('op_lastCamId', deviceId);
  } catch (e1) {
    console.warn('Fallo con deviceId. Reintentando con video:true', e1);
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
      const videoEl = document.getElementById('cam');
      videoEl.srcObject = stream;
      await new Promise(resolve => {
        if (videoEl.readyState >= 2 && videoEl.videoWidth > 0) return resolve();
        videoEl.onloadedmetadata = () => resolve();
      });
      await videoEl.play();
    } catch (e2) {
      console.error('No se pudo abrir la cámara en ningún modo', e1, e2);
      showStatus('No se pudo abrir la cámara. Verifica permisos/HTTPS/otra app usando la cámara.', true);
    }
  }
}

function stopCam() {
  const videoEl = document.getElementById('cam');
  if (videoEl && videoEl.srcObject) {
    try { videoEl.srcObject.getTracks().forEach(t => t.stop()); } catch (_) {}
    videoEl.srcObject = null;
  }
}

// -------- modelos y descriptores --------
async function ensureModelsLoaded() {
  if (modelsLoaded) return true;
  try {
    await loadFaceModels(); // provisto por /js/face.js
    modelsLoaded = true;
    showStatus('Modelos cargados. Buscando rostro…');
    return true;
  } catch (err) {
    console.error('Error cargando face-api.js', err);
    showStatus('Error cargando modelos. Revisa MODEL_URL en /js/face.js.', true);
    return false;
  }
}

async function loadDescriptors() {
  try {
    const res = await fetch('/api/faces/descriptors');
    const arr = await res.json();

    if (!Array.isArray(arr) || arr.length === 0) {
      matcher = null;
      showStatus('No hay asociados registrados aún.');
      return;
    }

    const labeled = arr.map(p => new faceapi.LabeledFaceDescriptors(
      String(p.member_id),
      (p.descriptors || []).map(d => new Float32Array(d))
    ));
    matcher = new faceapi.FaceMatcher(labeled, RECOG_THRESHOLD);
    showStatus('Descriptores listos. Mira a la cámara.');
  } catch (err) {
    console.error('Error cargando descriptores', err);
    matcher = null;
    showStatus('No se pudieron cargar descriptores.', true);
  }
}

async function loadCatalogo() {
  try {
    const res = await fetch('/api/towel_types');
    catalogo = await res.json();
  } catch (err) {
    console.warn('Error cargando catálogo, usando respaldo estático', err);
    catalogo = [
      { id: 1, codigo: 'BC', nombre: 'Blanca chica' },
      { id: 2, codigo: 'BG', nombre: 'Blanca grande' },
      { id: 3, codigo: 'AA', nombre: 'Azul alberca' },
      { id: 4, codigo: 'TF', nombre: 'Facial' }
    ];
  }
  renderTipos({ pendientes: [] });
}

// -------- UI --------
function renderAsociado(m) {
  currentMember = m || null;
  const el = document.getElementById('asociado');
  if (!el) return;
  if (!m) el.innerHTML = 'No identificado';
  else el.innerHTML = '<b>' + escapeHtml(m.nombre) + '</b> (<span class="badge">' + escapeHtml(m.membresia) + '</span>)';
}

async function cargarPendientes(member) {
  if (!member || !member.id) return;
  try {
    const res = await fetch('/api/members/' + member.id);
    const det = await res.json();
    member.pendientes = det.pendientes || [];
  } catch (err) {
    console.error('Error pendientes', err);
    member.pendientes = [];
  }
}

function renderTipos(m) {
  const div = document.getElementById('tiposToalla');
  if (!div) return;
  const pendientes = (m && m.pendientes) || [];
  let html = '';
  for (let i = 0; i < catalogo.length; i++) {
    const t = catalogo[i];
    const p = pendientes.find(x => x.id === t.id);
    const pTxt = p ? ' (pendientes: ' + p.pendientes + ')' : '';
    html += '<div>' + escapeHtml(t.codigo) + ' - ' + escapeHtml(t.nombre) + pTxt +
            ' <input type="number" min="0" value="0" id="qty_' + t.id + '" style="width:70px"></div>';
  }
  div.innerHTML = html;
}

// -------- reconocimiento --------
function snap(video) {
  const c = document.createElement('canvas');
  const w = video.videoWidth || 640;
  const h = video.videoHeight || 480;
  c.width = w; c.height = h;
  c.getContext('2d').drawImage(video, 0, 0, w, h);
  return c;
}

async function recognizeTick() {
  const now = Date.now();
  if (now - lastScanAt < SCAN_INTERVAL_MS) return;
  lastScanAt = now;

  const v = document.getElementById('cam');
  if (!v || !v.srcObject || v.videoWidth === 0) return;
  if (!modelsLoaded || !matcher) return;

  try {
    const c = snap(v);
    const det = await faceapi
      .detectSingleFace(c, getTinyOptions())
      .withFaceLandmarks()
      .withFaceDescriptor();

    if (!det) {
      showStatus('Buscando rostro…');
      return;
    }

    const best = matcher.findBestMatch(det.descriptor);
    if (best && best.label !== 'unknown' && best.distance < RECOG_THRESHOLD) {
      const member_id = parseInt(best.label, 10);
      if (!currentMember || currentMember.id !== member_id) {
        const r = await fetch('/api/members/' + member_id);
        const m = await r.json();
        renderAsociado(m);
        await cargarPendientes(m);
        renderTipos(m);
        showStatus('Identificado (distancia: ' + best.distance.toFixed(3) + ')');
      }
    } else {
      if (currentMember) showStatus('Rostro no coincide. Verifica iluminación/posición.', true);
      else showStatus('No identificado. Acércate o mejora la iluminación.');
    }
  } catch (err) {
    console.error('Error reconocimiento', err);
    showStatus('Error en reconocimiento. Revisa consola.', true);
  }
}

function loop() {
  recognizeTick();
  requestAnimationFrame(loop);
}

// -------- operaciones --------
function getItemsSeleccionados() {
  const items = [];
  for (let i = 0; i < catalogo.length; i++) {
    const t = catalogo[i];
    const el = document.getElementById('qty_' + t.id);
    const v = el ? parseInt(el.value || '0', 10) : 0;
    if (v > 0) items.push({ towel_type_id: t.id, cantidad: v });
  }
  return items;
}

function limpiarCantidades() {
  for (let i = 0; i < catalogo.length; i++) {
    const t = catalogo[i];
    const el = document.getElementById('qty_' + t.id);
    if (el) el.value = 0;
  }
}

// -------- init --------
async function init() {
  await startCam();             // Abre cámara primero
  ensureModelsLoaded();         // Carga modelos en paralelo
  await loadCatalogo();         // Catálogo
  await loadDescriptors();      // Matcher

  const btnRetry = document.getElementById('btnReintentar');
  if (btnRetry) {
    btnRetry.onclick = function () {
      renderAsociado(null);
      renderTipos({ pendientes: [] });
      showStatus('Reintentando… mira a la cámara.');
      lastScanAt = 0;
    };
  }

  const btnConfirmar = document.getElementById('btnConfirmar');
  if (btnConfirmar) {
    btnConfirmar.onclick = async function () {
      if (!currentMember || !currentMember.id) {
        alert('Identifica primero al asociado.');
        return;
      }
      const tipoSel = document.getElementById('tipoAccion');
      const tipoAccion = (tipoSel && tipoSel.value) ? tipoSel.value : 'prestamo';
      if (tipoAccion !== 'prestamo' && tipoAccion !== 'devolucion') {
        alert('Acción inválida.');
        return;
      }
      const items = getItemsSeleccionados();
      if (items.length === 0) {
        alert('Indica cantidades a registrar.');
        return;
      }

      try {
        const res = await fetch('/api/events', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ event_type: tipoAccion, member_id: currentMember.id, items })
        });
        const data = await res.json();
        if (data.event_id) {
          alert('Evento ' + tipoAccion + ' registrado #' + data.event_id);
          limpiarCantidades();
          await cargarPendientes(currentMember);
          renderTipos(currentMember);
          showStatus('Evento registrado correctamente.');
        } else {
          console.warn('/api/events respuesta inesperada', data);
          showStatus('No se pudo registrar el evento: ' + (data.error || 'Error desconocido'), true);
        }
      } catch (err) {
        console.error('Error registrando evento', err);
        showStatus('Error registrando evento. Revisa el servidor.', true);
      }
    };
  }

  window.addEventListener('beforeunload', stopCam);
  loop();
}

document.addEventListener('DOMContentLoaded', () => {
  init().catch(err => {
    console.error('Init error', err);
    showStatus('No se pudo iniciar la página de operación.', true);
  });
});
