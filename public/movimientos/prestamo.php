<?php require_once __DIR__.'/../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Préstamo</title>
  <link rel="stylesheet" href="../assets/css/styles.css" />
</head>
<body>
<header>
  <div class="title">Préstamo de Toallas</div>
  <nav class="nav">
    <a href="../index.php">Inicio</a>
    <a class="active" href="./prestamo.php">Préstamo</a>
  </nav>
</header>
<main class="container">
  <div class="row">
    <div class="col">
      <h3>Verificación facial</h3>
      <video id="video" autoplay muted playsinline style="width:100%"></video>
      <div id="match" class="notice" style="margin-top:10px">Esperando reconocimiento...</div>
    </div>
    <div class="col">
      <h3>Detalle del préstamo</h3>
      <div class="row">
        <div class="col">
          <label>Nombre</label>
          <input type="text" id="nombre" readonly />
        </div>
        <div class="col">
          <label>Membresía</label>
          <input type="text" id="membresia" readonly />
        </div>
      </div>

      <div class="card">
        <div class="flex">
          <select id="tipo">
            <option value="1">Blanca chica</option>
            <option value="2">Blanca grande</option>
            <option value="3">Azul alberca</option>
            <option value="4">Facial</option>
          </select>
          <input type="number" id="cantidad" min="1" value="1" style="max-width:120px" />
          <button id="addItem">Agregar</button>
        </div>
        <ul id="items" class="list"></ul>
      </div>
      <div class="toolbar">
        <button id="guardar" disabled>Registrar préstamo</button>
      </div>
      <div id="msg"></div>
    </div>
  </div>
</main>
<script src="../assets/js/common.js"></script>
<script>
(async () => {
  await loadFaceApiOnce();
  await loadModels('../models');
  const video = document.getElementById('video');
  await startWebcam(video);
  const matchEl = document.getElementById('match');
  const nombreEl = document.getElementById('nombre');
  const membresiaEl = document.getElementById('membresia');
  const guardarBtn = document.getElementById('guardar');

  const data = await loadLabeledDescriptors();
  const matcher = buildFaceMatcher(data, 0.55);

  let current = null;
  async function loop() {
    const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptor();
    if (det) {
      const best = matcher.findBestMatch(det.descriptor);
      if (best.label !== 'unknown' && best.distance < 0.6) {
        const { membresia, nombre } = parseLabel(best.label);
        matchEl.innerHTML = `<div class="success">Reconocido: <strong>${nombre}</strong> (<span class="badge">${membresia}</span>) — distancia ${best.distance.toFixed(2)}</div>`;
        nombreEl.value = nombre; membresiaEl.value = membresia; current = { membresia, nombre };
        guardarBtn.disabled = false;
      } else {
        matchEl.innerHTML = '<div class="notice">Rostro detectado, sin coincidencia confiable...</div>';
      }
    } else {
      matchEl.innerHTML = 'Esperando reconocimiento...';
    }
    requestAnimationFrame(loop);
  }
  loop();

  // Items de préstamo
  const items = [];
  const tipoSel = document.getElementById('tipo');
  const cantInp = document.getElementById('cantidad');
  const itemsUl = document.getElementById('items');
  document.getElementById('addItem').onclick = () => {
    const tipo = parseInt(tipoSel.value, 10);
    const cantidad = parseInt(cantInp.value, 10);
    if (!cantidad || cantidad <= 0) return;
    const map = {1:'Blanca chica',2:'Blanca grande',3:'Azul alberca',4:'Facial'};
    items.push({ tipo_toalla_id: tipo, cantidad });
    const li = document.createElement('li'); li.textContent = map[tipo] + ' — ' + cantidad; itemsUl.appendChild(li);
  };

  document.getElementById('guardar').onclick = async () => {
    if (!current || !items.length) { alert('Falta reconocimiento o items.'); return; }
    const msg = document.getElementById('msg'); msg.innerHTML='';
    try {
      const res = await fetch('../api/movimientos/prestar.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ membresia: current.membresia, items })});
      const json = await res.json();
      if (!json.success) throw new Error(json.message || 'Error');
      msg.innerHTML = `<div class="success">Préstamo registrado. Folio #${json.data.movimiento_id}</div>`;
    } catch (e) {
      msg.innerHTML = `<div class="error">${e.message}</div>`;
    }
  };
})();
</script>
</body>
</html>
