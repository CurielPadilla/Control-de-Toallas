<?php require_once __DIR__.'/../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Devolución</title>
  <link rel="stylesheet" href="../assets/css/styles.css" />
</head>
<body>
<header>
  <div class="title">Devolución de Toallas</div>
  <nav class="nav">
    <a href="../index.php">Inicio</a>
    <a class="active" href="./devolucion.php">Devolución</a>
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
      <h3>Préstamos pendientes</h3>
      <div id="pendientes" class="card">—</div>
      <div class="toolbar">
        <button id="guardar" disabled>Registrar devolución</button>
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
  const pendientesEl = document.getElementById('pendientes');
  const guardarBtn = document.getElementById('guardar');

  const data = await loadLabeledDescriptors();
  const matcher = buildFaceMatcher(data, 0.55);

  let current = null;
  let pendientes = [];

  async function cargarPendientes(asociado_id) {
    const res = await fetch('../api/historial/pending.php?asociado_id=' + asociado_id);
    const json = await res.json();
    if (json.success) {
      pendientes = json.data; // [{tipo_toalla_id, descripcion, pendiente}]
      renderPendientes();
    } else {
      pendientesEl.innerHTML = '<div class="error">No se pudieron cargar pendientes</div>';
    }
  }

  function renderPendientes() {
    if (!pendientes.length) { pendientesEl.innerHTML = '<div class="notice">No hay pendientes.</div>'; guardarBtn.disabled = true; return; }
    const wrap = document.createElement('div');
    pendientesEl.innerHTML = '';
    pendientes.forEach(p => {
      const row = document.createElement('div'); row.className='flex';
      row.innerHTML = `<div style="min-width:160px"><strong>${p.descripcion}</strong></div>
        <div>Pendiente: <span class="badge">${p.pendiente}</span></div>
        <div><input type="number" min="0" max="${p.pendiente}" value="0" data-tipo="${p.tipo_toalla_id}" style="width:90px"/></div>`;
      wrap.appendChild(row);
    });
    pendientesEl.appendChild(wrap);
    guardarBtn.disabled = false;
  }

  async function loop() {
    const det = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptor();
    if (det) {
      const best = matcher.findBestMatch(det.descriptor);
      if (best.label !== 'unknown' && best.distance < 0.6) {
        const { membresia, nombre } = parseLabel(best.label);
        matchEl.innerHTML = `<div class="success">Reconocido: <strong>${nombre}</strong> (<span class="badge">${membresia}</span>)</div>`;
        // Obtener asociado_id vía búsqueda
        const res = await fetch('../api/asociados/search.php?membresia=' + encodeURIComponent(membresia));
        const json = await res.json();
        if (json.success && json.data) {
          current = json.data; // {id, membresia, nombre}
          await cargarPendientes(current.id);
        }
      } else {
        matchEl.innerHTML = '<div class="notice">Rostro detectado, sin coincidencia confiable...</div>';
      }
    } else {
      matchEl.innerHTML = 'Esperando reconocimiento...';
    }
    requestAnimationFrame(loop);
  }
  loop();

  document.getElementById('guardar').onclick = async () => {
    if (!current) return;
    const inputs = pendientesEl.querySelectorAll('input[type=number]');
    const items = [];
    inputs.forEach(inp => {
      const val = parseInt(inp.value, 10) || 0; const tipo = parseInt(inp.getAttribute('data-tipo'), 10);
      if (val > 0) items.push({ tipo_toalla_id: tipo, cantidad: val });
    });
    if (!items.length) { alert('Ingresa al menos una cantidad a devolver.'); return; }
    const msg = document.getElementById('msg'); msg.innerHTML='';
    try {
      const res = await fetch('../api/movimientos/devolver.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ asociado_id: current.id, items })});
      const json = await res.json();
      if (!json.success) throw new Error(json.message || 'Error');
      msg.innerHTML = `<div class="success">Devolución registrada. Folio #${json.data.movimiento_id}</div>`;
      await cargarPendientes(current.id);
    } catch (e) {
      msg.innerHTML = `<div class="error">${e.message}</div>`;
    }
  };
})();
</script>
</body>
</html>
