<?php require_once __DIR__.'/../../config/config.php'; require_once __DIR__.'/../../config/db.php';
$pdo = db();
$m = $_GET['membresia'] ?? '';
$d = $_GET['desde'] ?? '';
$h = $_GET['hasta'] ?? '';
$sql = "SELECT mv.creado_en, a.membresia, a.nombre, mv.tipo, tt.descripcion, dm.cantidad, u.nombre AS operador
        FROM movimiento mv
        JOIN asociado a ON a.id = mv.asociado_id
        JOIN detalle_movimiento dm ON dm.movimiento_id = mv.id
        JOIN tipo_toalla tt ON tt.id = dm.tipo_toalla_id
        JOIN usuario u ON u.id = mv.usuario_id
        WHERE 1=1";
$params = [];
if ($m) { $sql .= " AND a.membresia = :m"; $params[':m'] = $m; }
if ($d) { $sql .= " AND DATE(mv.creado_en) >= :d"; $params[':d'] = $d; }
if ($h) { $sql .= " AND DATE(mv.creado_en) <= :h"; $params[':h'] = $h; }
$sql .= " ORDER BY mv.creado_en DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Historial (Imprimir/PDF)</title>
  <style>
    body{font-family: Arial, sans-serif; padding:20px}
    h2{margin:0 0 10px}
    .small{color:#555}
    table{width:100%; border-collapse:collapse}
    th,td{border:1px solid #ddd; padding:6px; font-size:13px}
    th{background:#f3f4f6}
    @media print{button{display:none}}
  </style>
</head>
<body>
  <button onclick="window.print()">Imprimir / Guardar como PDF</button>
  <h2>Historial de movimientos</h2>
  <div class="small">Membresía: <strong><?= htmlspecialchars($m ?: 'Todas') ?></strong> — Desde: <strong><?= htmlspecialchars($d ?: '-') ?></strong> — Hasta: <strong><?= htmlspecialchars($h ?: '-') ?></strong></div>
  <table>
    <thead><tr><th>Fecha/Hora</th><th>Membresía</th><th>Nombre</th><th>Evento</th><th>Tipo Toalla</th><th>Cantidad</th><th>Operador</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['creado_en']) ?></td>
        <td><?= htmlspecialchars($r['membresia']) ?></td>
        <td><?= htmlspecialchars($r['nombre']) ?></td>
        <td><?= htmlspecialchars($r['tipo']) ?></td>
        <td><?= htmlspecialchars($r['descripcion']) ?></td>
        <td><?= (int)$r['cantidad'] ?></td>
        <td><?= htmlspecialchars($r['operador']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
