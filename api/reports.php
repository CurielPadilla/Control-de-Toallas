
<?php
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');

if ($method==='GET' && $path==='api/reports/history') {
  $member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : null;
  $from = $_GET['from'] ?? '1970-01-01';
  $to   = $_GET['to']   ?? date('Y-m-d 23:59:59');
  $format = $_GET['format'] ?? 'csv';

  $sql = 'SELECT m.nombre, m.membresia, e.event_type, e.created_at, tt.codigo, tt.nombre AS towel_nombre, ei.cantidad
    FROM events e
    JOIN event_items ei ON ei.event_id=e.id
    JOIN towel_types tt ON tt.id=ei.towel_type_id
    JOIN members m ON m.id=e.member_id
    WHERE e.created_at BETWEEN ? AND ?';
  $params = [$from, $to];
  if ($member_id) { $sql .= ' AND e.member_id=?'; $params[]=$member_id; }
  $sql .= ' ORDER BY e.created_at DESC, e.id DESC';

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  if ($format==='csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="historial.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Nombre','Membresía','Tipo Evento','Fecha/Hora','Código','Toalla','Cantidad']);
    foreach($rows as $r) {
      fputcsv($out, [$r['nombre'],$r['membresia'],$r['event_type'],$r['created_at'],$r['codigo'],$r['towel_nombre'],$r['cantidad']]);
    }
    fclose($out);
    exit;
  }

  json_out(['error'=>'Formato no implementado en servidor. Use CSV o exporte desde la interfaz (Excel/PDF).'],400);
}

json_out(['error'=>'Ruta no encontrada'],404);
