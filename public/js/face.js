// public/js/face.js
// -------------------------------------------------------------
// Carga y configuración de modelos para face-api.js
// - Origen preferido: 'cdn' (recomendado) o 'local'
// - Fallback automático al otro origen si el preferido falla
// - Expone: window.loadFaceModels(), window.getTinyOptions()
// -------------------------------------------------------------
'use strict';

// 👉 CONFIGURA AQUÍ EL ORIGEN PREFERIDO DE LOS MODELOS
//    'cdn'  -> usa CDN (más simple para pruebas y evita rutas)
//    'local'-> usa /models (si descargaste los pesos en public/models)
const PREFERRED_SOURCE = 'cdn';

// Rutas de modelos
const LOCAL_MODEL_URL = '/models'; // coloca los pesos dentro de /public/models
const CDN_MODEL_URL   = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights';

// Estado interno (promesa compartida para no recargar modelos)
let __faceModelsPromise = null;
let __modelUrlUsed = null;

// Utilidad: intenta cargar modelos desde una URL dada
async function __loadFrom(url) {
  // Verificación básica
  if (typeof faceapi === 'undefined' || !faceapi?.nets?.tinyFaceDetector) {
    throw new Error('face-api.js no está cargado. Asegúrate de incluir https://cdn.jsdelivr.net/npm/face-api.js</script> antes de este archivo.');
  }

  // Carga de redes necesarias para detección y reconocimiento
  await Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri(url),
    faceapi.nets.faceLandmark68Net.loadFromUri(url),
    faceapi.nets.faceRecognitionNet.loadFromUri(url)
  ]);

  __modelUrlUsed = url;
  console.log('[face.js] Modelos cargados desde:', url);
  return url;
}

// API pública: carga modelos con origen preferido + fallback
async function loadFaceModels() {
  if (__faceModelsPromise) return __faceModelsPromise; // ya en curso / cargado

  // Define orden de intento según preferencia
  const order = (PREFERRED_SOURCE === 'local')
    ? [LOCAL_MODEL_URL, CDN_MODEL_URL]
    : [CDN_MODEL_URL, LOCAL_MODEL_URL];

  __faceModelsPromise = (async () => {
    let lastError = null;
    for (const url of order) {
      try {
        await __loadFrom(url);
        return true;
      } catch (err) {
        console.warn(`[face.js] Falló carga de modelos desde ${url}`, err);
        lastError = err;
      }
    }
    // Si ninguno funcionó, propaga error
    throw lastError || new Error('No fue posible cargar los modelos de face-api.js.');
  })();

  return __faceModelsPromise;
}

// API pública: opciones del TinyFaceDetector
function getTinyOptions() {
  // inputSize: 160/192/224/320 → mayor = más precisión (y más CPU)
  // scoreThreshold: 0.5 aprox es buen balance
  return new faceapi.TinyFaceDetectorOptions({
    inputSize: 224,
    scoreThreshold: 0.5
  });
}

// (Opcional) Utilidad para consultar desde consola cuál origen quedó activo
function getModelUrlUsed() {
  return __modelUrlUsed;
}

// Exponer funciones al ámbito global
window.loadFaceModels   = loadFaceModels;
window.getTinyOptions   = getTinyOptions;
window.getModelUrlUsed  = getModelUrlUsed;

// Nota: no iniciamos la carga aquí para dar control a las páginas
// (register.js / operate.js llaman a loadFaceModels() cuando convenga).
