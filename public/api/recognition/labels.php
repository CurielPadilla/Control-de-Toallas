<?php require_once __DIR__.'/../../../config/db.php'; require_once __DIR__.'/../../../config/config.php';
$pdo = db();
$sql = "SELECT a.membresia, a.nombre, r.descriptor_json_path FROM asociado a JOIN rostro_asociado r ON r.asociado_id = a.id WHERE a.activo=1 ORDER BY r.id DESC";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();
$data = [];
foreach ($rows as $row) {
  $path = BASE_PATH . $row['descriptor_json_path'];
  if (is_file($path)) {
    $json = json_decode(file_get_contents($path), true);
    if (isset($json['descriptor']) && is_array($json['descriptor'])) {
      $data[] = [
        'membresia' => $row['membresia'],
        'nombre' => $row['nombre'],
        'descriptor' => $json['descriptor']
      ];
    }
  }
}
json_response(['success'=>true,'data'=>$data]);
