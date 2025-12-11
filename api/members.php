
<?php
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');

if ($method==='POST' && $path==='api/members') {
  $input = get_json_body();
  if (!isset($input['nombre'],$input['membresia'])) json_out(['error'=>'Datos incompletos'],400);
  $stmt = $pdo->prepare('INSERT INTO members(nombre,membresia) VALUES(?,?)');
  try {
    $stmt->execute([$input['nombre'], $input['membresia']]);
    json_out(['id'=>$pdo->lastInsertId()]);
  } catch (Exception $e) {
    json_out(['error'=>$e->getMessage()], 400);
  }
}

if ($method==='GET' && $path==='api/members') {
  $q = $_GET['query'] ?? '';
  if ($q==='') {
    $stmt = $pdo->query('SELECT id,nombre,membresia,activo,created_at FROM members ORDER BY created_at DESC LIMIT 200');
    json_out($stmt->fetchAll());
  } else {
    $stmt = $pdo->prepare('SELECT id,nombre,membresia,activo,created_at FROM members WHERE nombre LIKE ? OR membresia LIKE ? ORDER BY created_at DESC LIMIT 200');
    $like = '%'.$q.'%';
    $stmt->execute([$like,$like]);
    json_out($stmt->fetchAll());
  }
}

if ($method==='GET' && preg_match('#^api/members/(\d+)$#',$path,$m)) {
  $id = (int)$m[1];
  $stmt = $pdo->prepare('SELECT id,nombre,membresia,activo FROM members WHERE id=?');
  $stmt->execute([$id]);
  $member = $stmt->fetch(); if(!$member) json_out(['error'=>'No encontrado'],404);

  $p = $pdo->prepare('SELECT tt.id,tt.codigo,tt.nombre, GREATEST(p.pendientes,0) pendientes
    FROM v_member_pending p
    JOIN towel_types tt ON tt.id=p.towel_type_id
    WHERE p.member_id=? AND p.pendientes>0
    ORDER BY tt.nombre');
  $p->execute([$id]);
  $member['pendientes'] = $p->fetchAll();
  json_out($member);
}

json_out(['error'=>'Ruta no encontrada'],404);
