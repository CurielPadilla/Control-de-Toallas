
<?php
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');

if ($method==='POST' && $path==='api/events') {
  $input = get_json_body();
  if (!isset($input['event_type'],$input['member_id'],$input['items']) || !in_array($input['event_type'],['prestamo','devolucion'])) {
    json_out(['error'=>'Datos inválidos'],400);
  }
  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare('INSERT INTO events(event_type,member_id,staff_user,notes) VALUES(?,?,?,?)');
    $stmt->execute([$input['event_type'], $input['member_id'], $input['staff_user'] ?? null, $input['notes'] ?? null]);
    $eventId = $pdo->lastInsertId();

    $it = $pdo->prepare('INSERT INTO event_items(event_id,towel_type_id,cantidad) VALUES(?,?,?)');
    foreach ($input['items'] as $row) {
      if (!isset($row['towel_type_id'],$row['cantidad'])) continue;
      $it->execute([$eventId, (int)$row['towel_type_id'], max(1,(int)$row['cantidad'])]);
    }
    $pdo->commit();
    json_out(['event_id'=>$eventId],201);
  } catch(Exception $e) {
    $pdo->rollBack();
    json_out(['error'=>$e->getMessage()],500);
  }
}

// Historial por miembro
if ($method==='GET' && preg_match('#^api/members/(\d+)/history$#',$path,$m)) {
  $id = (int)$m[1];
  $from = $_GET['from'] ?? '1970-01-01';
  $to   = $_GET['to']   ?? date('Y-m-d 23:59:59');
  $stmt = $pdo->prepare('SELECT e.id as event_id, e.event_type, e.created_at, e.staff_user, e.notes,
           tt.id AS towel_type_id, tt.codigo, tt.nombre as towel_nombre, ei.cantidad
    FROM events e
    JOIN event_items ei ON ei.event_id=e.id
    JOIN towel_types tt ON tt.id=ei.towel_type_id
    WHERE e.member_id=? AND e.created_at BETWEEN ? AND ?
    ORDER BY e.created_at DESC, e.id DESC');
  $stmt->execute([$id, $from, $to]);
  json_out($stmt->fetchAll());
}

// Historial general con filtros opcionales
if ($method==='GET' && $path==='api/history') {
  $from = $_GET['from'] ?? '1970-01-01';
  $to   = $_GET['to']   ?? date('Y-m-d 23:59:59');
  $member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : null;
  $tipo = $_GET['event_type'] ?? null; // prestamo|devolucion

  $sql = 'SELECT m.id as member_id, m.nombre, m.membresia, e.id as event_id, e.event_type, e.created_at, tt.codigo, tt.nombre AS towel_nombre, ei.cantidad
          FROM events e
          JOIN event_items ei ON ei.event_id=e.id
          JOIN towel_types tt ON tt.id=ei.towel_type_id
          JOIN members m ON m.id=e.member_id
          WHERE e.created_at BETWEEN ? AND ?';
  $params = [$from, $to];
  if ($member_id) { $sql .= ' AND e.member_id=?'; $params[]=$member_id; }
  if ($tipo && in_array($tipo, ['prestamo','devolucion'])) { $sql .= ' AND e.event_type=?'; $params[]=$tipo; }
  $sql .= ' ORDER BY e.created_at DESC, e.id DESC';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  json_out($stmt->fetchAll());
}

json_out(['error'=>'Ruta no encontrada'],404);
