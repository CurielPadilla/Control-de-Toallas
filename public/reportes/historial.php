<?php require_once __DIR__.'/../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Historial y Reportes</title>
  <link rel="stylesheet" href="../assets/css/styles.css" />
</head>
<body>
<header>
  <div class="title">Historial y Reportes</div>
  <nav class="nav">
    <a href="../index.php">Inicio</a>
    <a class="active" href="./historial.php">Historial</a>
  </nav>
</header>
<main class="container">
  <div class="toolbar">
    <label>Membresía</label>
    <input type="text" id="membresia" placeholder="A12345" style="max-width:140px" />
    <label>Desde</label><input type="date" id="desde" />
    <label>Hasta</label><input type="date" id="hasta" />
    <button id="filtrar">Buscar</button>
    <button id="exportXls" class="secondary">Exportar Excel</button>
    <button id="print" class="secondary">Imprimir / PDF</button>
  </div>
  <table class="table" id="tabla">
    <thead><tr><th>Fecha/Hora</th><th>Membresía</th><th>Nombre</th><th>Evento</th><th>Tipo Toalla</th><th>Cantidad</th><th>Operador</th></tr></thead>
    <tbody></tbody>
  </table>
</main>
<script>
async function cargar() {
  const params = new URLSearchParams();
  const m = document.getElementById('membresia').value.trim();
  const d = document.getElementById('desde').value; const h = document.getElementById('hasta').value;
  if (m) params.set('membresia', m);
  if (d) params.set('desde', d);
  if (h) params.set('hasta', h);
  const res = await fetch('../api/historial/list.php?' + params.toString());
  const json = await res.json();
  const tbody = document.querySelector('#tabla tbody');
  tbody.innerHTML = '';
  if (json.success) {
    json.data.forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${r.creado_en}</td><td>${r.membresia}</td><td>${r.nombre}</td><td>${r.tipo}</td><td>${r.descripcion}</td><td>${r.cantidad}</td><td>${r.operador}</td>`;
      tbody.appendChild(tr);
    });
  }
}

document.getElementById('filtrar').onclick = cargar;
document.getElementById('exportXls').onclick = () => {
  const params = new URLSearchParams();
  const m = document.getElementById('membresia').value.trim();
  const d = document.getElementById('desde').value; const h = document.getElementById('hasta').value;
  if (m) params.set('membresia', m);
  if (d) params.set('desde', d);
  if (h) params.set('hasta', h);
  window.location.href = '../export/export_excel.php?' + params.toString();
};

document.getElementById('print').onclick = () => {
  const params = new URLSearchParams();
  const m = document.getElementById('membresia').value.trim();
  const d = document.getElementById('desde').value; const h = document.getElementById('hasta').value;
  if (m) params.set('membresia', m);
  if (d) params.set('desde', d);
  if (h) params.set('hasta', h);
  window.open('./print_historial.php?' + params.toString(), '_blank');
};

cargar();
</script>
</body>
</html>
