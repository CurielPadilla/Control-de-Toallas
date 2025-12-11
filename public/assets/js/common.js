
// public/assets/js/common.js
const FACEAPI_CDN = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js';

async function loadFaceApiOnce() {
  if (window.faceapi) return true;
  await new Promise((resolve, reject) => {
    const s = document.createElement('script');
    s.src = FACEAPI_CDN; // Cambia a '/lib/face-api.min.js' si trabajas offline
    s.onload = resolve;
    s.onerror = () => reject(new Error('No se pudo cargar face-api.js'));
    document.head.appendChild(s);
  });
  return true;
}

async function loadModels(modelsPath = '/control-toallas-xampp/public/models') {
  await faceapi.nets.tinyFaceDetector.loadFromUri(modelsPath);
  await faceapi.nets.faceLandmark68Net.loadFromUri(modelsPath);
  await faceapi.nets.faceRecognitionNet.loadFromUri(modelsPath);
}

function startWebcam(videoEl) {
  return navigator.mediaDevices.getUserMedia({ video: true, audio: false })
    .then(stream => { videoEl.srcObject = stream; return new Promise(r => videoEl.onloadedmetadata = r); })
}

async function captureImageFromVideo(videoEl) {
  const canvas = document.createElement('canvas');
  canvas.width = videoEl.videoWidth; canvas.height = videoEl.videoHeight;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(videoEl, 0, 0);
  return canvas.toDataURL('image/jpeg', 0.9);
}

function dataURLtoBlob(dataurl) {
  const arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1];
  const bstr = atob(arr[1]); let n = bstr.length; const u8arr = new Uint8Array(n);
  while (n--) u8arr[n] = bstr.charCodeAt(n);
  return new Blob([u8arr], { type: mime });
}

async function computeDescriptorFromVideo(videoEl) {
  const detection = await faceapi.detectSingleFace(videoEl, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptor();
  if (!detection) return null;
  return Array.from(detection.descriptor);
}

function averageDescriptors(descriptors) {
  if (!descriptors.length) return null;
  const len = descriptors[0].length;
  const avg = new Array(len).fill(0);
  descriptors.forEach(d => d.forEach((v,i)=> avg[i]+=v));
  return avg.map(v=> v/descriptors.length);
}

async function loadLabeledDescriptors() {
  const res = await fetch('..//api/recognition/labels.php');
  const json = await res.json();
  if (!json.success) throw new Error('No se pudieron cargar descriptores');
  return json.data; // [{membresia, nombre, descriptor: number[]}]
}

function buildFaceMatcher(data, distance=0.55) {
  const labeled = data.map(d => new faceapi.LabeledFaceDescriptors(`${d.membresia}||${d.nombre}`, [new Float32Array(d.descriptor)]));
  return new faceapi.FaceMatcher(labeled, distance);
}

function parseLabel(label) {
  const [membresia, nombre] = label.split('||');
  return { membresia, nombre };
}
