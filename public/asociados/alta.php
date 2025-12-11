<?php require_once __DIR__.'/../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Registro de Asociado</title>
  <link rel="stylesheet" href="../assets/css/styles.css" />
</head>
<body>
<header>
  <div class="title">Registro de Asociado</div>
  <nav class="nav">
    <a href="../index.php">Inicio</a>
    <a class="active" href="./alta.php">Registro</a>
  </nav>
</header>
<main class="container">
  <div class="row">
    <div class="col">
      <h3>Datos del asociado</h3>
      <label>Nombre completo</label>
      <input type="text" id="nombre" placeholder="Juan Pérez" />
      <label>Membresía (única)</label>
      <input type="text" id="membresia" placeholder="A12345" />
      <div class="small">La membresía no debe repetirse.</div>
      <hr />
      <button id="btnGuardar">Guardar asociado + rostro</button>
      <div id="msg" style="margin-top:12px"></div>
    </div>
    <div class="col">
      <h3>Captura facial (3 tomas)</h3>
      <video id="video" autoplay muted playsinline style="width:100%"></video>
      <div class="toolbar">
        <button id="btnTomar">Tomar foto</button>
        <span class="small">Tomas: <span id="count">0</span>/3</span>
      </div>
      <div class="row" id="prevs"></div>
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

  const btnTomar = document.getElementById('btnTomar');
  const btnGuardar = document.getElementById('btnGuardar');
  const prevs = document.getElementById('prevs');
  const countEl = document.getElementById('count');
  const msg = document.getElementById('msg');
  const tomas = [];
  const descriptors = [];

  btnTomar.onclick = async () => {
    const img = await captureImageFromVideo(video);
    const desc = await computeDescriptorFromVideo(video);
    if (!desc) { alert('No se detectó rostro, intenta de nuevo.'); return; }
    tomas.push(img);
    descriptors.push(desc);
    const imgEl = new Image(); imgEl.src = img; imgEl.className = 'preview';
    const wrapper = document.createElement('div'); wrapper.className = 'col'; wrapper.appendChild(imgEl); prevs.appendChild(wrapper);
    countEl.textContent = String(tomas.length);
    if (tomas.length >= 3) btnTomar.disabled = true;
  };

  btnGuardar.onclick = async () => {
    const nombre = document.getElementById('nombre').value.trim();
    const membresia = document.getElementById('membresia').value.trim();
    msg.innerHTML = '';
    if (!nombre || !membresia) { msg.innerHTML = '<div class="error">Completa nombre y membresía.</div>'; return; }
    if (tomas.length < 3) { msg.innerHTML = '<div class="error">Captura las 3 tomas faciales.</div>'; return; }

    try {
      // 1) Crear asociado
      let res = await fetch('../api/asociados/create.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ nombre, membresia })});
      let json = await res.json();
      if (!json.success) throw new Error(json.message || 'Error creando asociado');
      const asociado_id = json.data.id;

      const descriptor = averageDescriptors(descriptors);
      // 2) Subir rostros y descriptor
      res = await fetch('../api/rostros/upload.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ asociado_id, membresia, tomas, descriptor })});
      json = await res.json();
      if (!json.success) throw new Error(json.message || 'Error subiendo rostros');

      msg.innerHTML = '<div class="success">Asociado y rostro registrados correctamente.</div>';
      btnGuardar.disabled = true;
    } catch (e) {
      msg.innerHTML = '<div class="error">' + e.message + '</div>';
    }
  };
})();
</script>
</body>
</html>
