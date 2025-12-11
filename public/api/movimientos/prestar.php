<?php require_once __DIR__.'/../../../config/db.php'; require_once __DIR__.'/../../../config/helpers.php';
$data = req_json();
$membresia = trim($data['membresia'] ?? '');
$items = $data['items'] ?? [];
if (!$membresia || !is_array($items) || !count($items)) json_response(['success'=>false,'message'=>'Datos incompletos'],400);
$pdo = db();
$pdo->beginTransaction();
try{
  $stmt = $pdo->prepare('SELECT id FROM asociado WHERE membresia = :m AND activo=1');
  $stmt->execute([':m'=>$membresia]);
  $a = $stmt->fetch();
  if (!$a) throw new Exception('Asociado no encontrado');
  $asociado_id = (int)$a['id'];
  $pdo->prepare('INSERT INTO movimiento (asociado_id, tipo, usuario_id, creado_en) VALUES (:a,"PRESTAMO",1,:f)')
     ->execute([':a'=>$asociado_id, ':f'=>now_db()]);
  $movId = $pdo->lastInsertId();
  $ins = $pdo->prepare('INSERT INTO detalle_movimiento (movimiento_id, tipo_toalla_id, cantidad) VALUES (:m,:t,:c)');
  foreach ($items as $it) {
    $t = (int)($it['tipo_toalla_id'] ?? 0); $c = (int)($it['cantidad'] ?? 0);
    if ($t && $c>0) $ins->execute([':m'=>$movId, ':t'=>$t, ':c'=>$c]);
  }
  $pdo->commit();
  json_response(['success'=>true,'data'=>['movimiento_id'=>$movId]]);
}catch(Exception $e){
  $pdo->rollBack();
  json_response(['success'=>false,'message'=>$e->getMessage()],500);
}
