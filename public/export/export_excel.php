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
$filename = 'historial_' . ($m ?: 'todos') . '_' . date('Ymd_His') . '.xls';
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=' . $filename);
echo "<table border='1'>";
echo "<tr><th>Fecha/Hora</th><th>Membresía</th><th>Nombre</th><th>Evento</th><th>Tipo Toalla</th><th>Cantidad</th><th>Operador</th></tr>";
foreach ($rows as $r) {
  echo '<tr>';
  echo '<td>'.htmlspecialchars($r['creado_en']).'</td>';
  echo '<td>'.htmlspecialchars($r['membresia']).'</td>';
  echo '<td>'.htmlspecialchars($r['nombre']).'</td>';
  echo '<td>'.htmlspecialchars($r['tipo']).'</td>';
  echo '<td>'.htmlspecialchars($r['descripcion']).'</td>';
  echo '<td>'.(int)$r['cantidad'].'</td>';
  echo '<td>'.htmlspecialchars($r['operador']).'</td>';
  echo '</tr>';
}
echo '</table>';
exit;
