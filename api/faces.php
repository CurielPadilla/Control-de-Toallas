
<?php
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');

if ($method==='POST' && $path==='api/faces') {
  if (!isset($_POST['member_id'], $_POST['descriptor_json']) || !isset($_FILES['image'])) {
    json_out(['error'=>'Campos faltantes'],400);
  }
  $member_id = (int)$_POST['member_id'];
  $descriptor = json_decode($_POST['descriptor_json'], true);
  if (!is_array($descriptor)) json_out(['error'=>'Descriptor inválido'],400);

  // Guardar imagen
  $dir = __DIR__ . '/../faces/' . $member_id;
  if (!is_dir($dir)) mkdir($dir, 0750, true);

  $orig = $_FILES['image']['name'];
  $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png'])) $ext = 'jpg';
  $filename = uniqid('face_', true) . '.' . $ext;
  $dest = $dir . '/' . $filename;
  if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
    json_out(['error'=>'No se pudo guardar la imagen'],500);
  }
  $publicPath = 'faces/' . $member_id . '/' . $filename;

  $stmt = $pdo->prepare('INSERT INTO member_faces(member_id,image_path,descriptor_json) VALUES(?,?,JSON_ARRAY())');
  $stmt->execute([$member_id, $publicPath]);
  $faceId = $pdo->lastInsertId();

  $stmt2 = $pdo->prepare('UPDATE member_faces SET descriptor_json=? WHERE id=?');
  $stmt2->execute([json_encode($descriptor, JSON_UNESCAPED_UNICODE), $faceId]);

  json_out(['id'=>$faceId,'image_path'=>$publicPath],201);
}

if ($method==='GET' && $path==='api/faces/descriptors') {
  $stmt = $pdo->query('SELECT m.id as member_id, m.nombre, m.membresia, mf.descriptor_json
    FROM members m JOIN member_faces mf ON mf.member_id=m.id
    WHERE m.activo=1 ORDER BY m.id');
  $rows = $stmt->fetchAll();
  $by = [];
  foreach ($rows as $r) {
    $mid = (int)$r['member_id'];
    if (!isset($by[$mid])) $by[$mid] = ['member_id'=>$mid,'nombre'=>$r['nombre'],'membresia'=>$r['membresia'],'descriptors'=>[]];
    $by[$mid]['descriptors'][] = json_decode($r['descriptor_json'], true);
  }
  json_out(array_values($by));
}

json_out(['error'=>'Ruta no encontrada'],404);
