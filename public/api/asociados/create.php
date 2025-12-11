<?php require_once __DIR__.'/../../../config/db.php'; require_once __DIR__.'/../../../config/helpers.php';
ensure_dirs();
$data = req_json();
$nombre = trim($data['nombre'] ?? '');
$membresia = trim($data['membresia'] ?? '');
if (!$nombre || !$membresia) json_response(['success'=>false,'message'=>'Datos incompletos'],400);
try{
  $pdo = db();
  $stmt = $pdo->prepare('INSERT INTO asociado (membresia, nombre) VALUES (:m, :n)');
  $stmt->execute([':m'=>$membresia, ':n'=>$nombre]);
  $id = $pdo->lastInsertId();
  json_response(['success'=>true,'data'=>['id'=>$id]]);
}catch(PDOException $e){
  if ($e->getCode() == '23000') {
    json_response(['success'=>false,'message'=>'La membresía ya existe'],409);
  }
  json_response(['success'=>false,'message'=>'Error BD: '.$e->getMessage()],500);
}
