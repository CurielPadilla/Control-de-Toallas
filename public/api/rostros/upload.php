<?php require_once __DIR__.'/../../../config/db.php'; require_once __DIR__.'/../../../config/helpers.php';
ensure_dirs();
$data = req_json();
$asociado_id = (int)($data['asociado_id'] ?? 0);
$membresia = trim($data['membresia'] ?? '');
$tomas = $data['tomas'] ?? [];
$descriptor = $data['descriptor'] ?? null;
if (!$asociado_id || !$membresia || count($tomas) < 3 || !$descriptor) json_response(['success'=>false,'message'=>'Datos incompletos'],400);
$baseDir = FACES_PATH . sanitize_filename($membresia) . '/' . date('Y/m/d') . '/';
if (!is_dir($baseDir)) @mkdir($baseDir, 0777, true);
$paths = [];
for ($i=0; $i<3; $i++) {
  $img = $tomas[$i];
  if (strpos($img, 'data:image') !== 0) continue;
  $bin = explode(',', $img, 2)[1];
  $dataBin = base64_decode($bin);
  $fname = $baseDir . $membresia . '_' . date('Ymd_His') . '_' . ($i+1) . '.jpg';
  file_put_contents($fname, $dataBin);
  $paths[] = str_replace(BASE_PATH, '', $fname);
}
$descPath = $baseDir . 'descriptor.json';
file_put_contents($descPath, json_encode(['descriptor'=>$descriptor]));
try{
  $pdo = db();
  $stmt = $pdo->prepare('INSERT INTO rostro_asociado (asociado_id, ruta_imagen1, ruta_imagen2, ruta_imagen3, descriptor_json_path) VALUES (:id,:p1,:p2,:p3,:dp)');
  $stmt->execute([':id'=>$asociado_id, ':p1'=>$paths[0]??null, ':p2'=>$paths[1]??null, ':p3'=>$paths[2]??null, ':dp'=>str_replace(BASE_PATH,'',$descPath)]);
  json_response(['success'=>true]);
}catch(Exception $e){
  json_response(['success'=>false,'message'=>$e->getMessage()],500);
}
