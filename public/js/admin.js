
let selectedMemberId = '';

function fmt(dt){ return dt ? dt.replace(' ', 'T').slice(0,16) : ''; }

async function buscarSugerencias(q){
  if (!q || q.length<2) { document.getElementById('sugs').innerHTML=''; return; }
  const res = await fetch('/api/members?query='+encodeURIComponent(q));
  const arr = await res.json();
  const box = document.getElementById('sugs');
  box.innerHTML = arr.map(m=>`<div data-id="${m.id}" class="opt">${m.nombre} <span class="badge">${m.membresia}</span></div>`).join('');
  box.querySelectorAll('.opt').forEach(el=>{
    el.onclick = ()=>{ selectedMemberId = el.dataset.id; document.getElementById('q').value = el.textContent.trim(); box.innerHTML=''; };
  });
}

function renderRows(rows){
  const tb = document.querySelector('#tbl tbody');
  tb.innerHTML = rows.map(r=>`<tr><td>${r.created_at}</td><td>${r.nombre||''}</td><td>${r.membresia||''}</td><td>${r.event_type}</td><td>${r.codigo}</td><td>${r.towel_nombre}</td><td>${r.cantidad}</td></tr>`).join('');
}

async function buscar(){
  const from = document.getElementById('from').value ? document.getElementById('from').value.replace('T',' ') : '';
  const to   = document.getElementById('to').value ? document.getElementById('to').value.replace('T',' ')   : '';
  const tipo = document.getElementById('tipo').value;
  const params = new URLSearchParams();
  if (from) params.append('from', from);
  if (to) params.append('to', to);
  if (selectedMemberId) params.append('member_id', selectedMemberId);
  if (tipo) params.append('event_type', tipo);
  const res = await fetch('/api/history?'+params.toString());
  const rows = await res.json();
  renderRows(rows);

  // Link CSV servidor
  const csvParams = new URLSearchParams(params);
  csvParams.set('format','csv');
  document.getElementById('btnCSV').href = '/api/reports/history?'+csvParams.toString();

  // Guardar para exportar
  window.__rows = rows;
}

function exportXLSX(){
  const rows = window.__rows || [];
  const data = [['Fecha/Hora','Asociado','Membresía','Tipo','Código','Toalla','Cantidad']].concat(
    rows.map(r=>[r.created_at,r.nombre||'',r.membresia||'',r.event_type,r.codigo,r.towel_nombre,r.cantidad])
  );
  const ws = XLSX.utils.aoa_to_sheet(data);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Historial');
  XLSX.writeFile(wb, 'historial_toallas.xlsx');
}

async function exportPDF(){
  const rows = window.__rows || [];
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF('l', 'pt', 'a4');
  doc.text('Historial de toallas', 40, 40);
  const head = [['Fecha/Hora','Asociado','Membresía','Tipo','Código','Toalla','Cantidad']];
  const body = rows.map(r=>[r.created_at, r.nombre||'', r.membresia||'', r.event_type, r.codigo, r.towel_nombre, String(r.cantidad)]);
  doc.autoTable({ head, body, startY: 60, styles:{ fontSize: 8 } });
  doc.save('historial_toallas.pdf');
}

function init(){
  const now = new Date();
  const start = new Date(now.getTime()-7*24*3600*1000);
  document.getElementById('from').value = start.toISOString().slice(0,16);
  document.getElementById('to').value = now.toISOString().slice(0,16);

  document.getElementById('q').addEventListener('input', e=>buscarSugerencias(e.target.value));
  document.getElementById('btnBuscar').onclick = buscar;
  document.getElementById('btnXLSX').onclick = exportXLSX;
  document.getElementById('btnPDF').onclick = exportPDF;

  buscar();
}

document.addEventListener('DOMContentLoaded', init);
