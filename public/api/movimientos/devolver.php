<?php require_once __DIR__.'/../../../config/db.php'; require_once __DIR__.'/../../../config/helpers.php';
$data = req_json();
$asociado_id = (int)($data['asociado_id'] ?? 0);
$items = $data['items'] ?? [];
if (!$asociado_id || !is_array($items) || !count($items)) json_response(['success'=>false,'message'=>'Datos incompletos'],400);
$pdo = db();
$pdo->beginTransaction();
try{
  // Validar pendientes
  $pend = [];
  $stmt = $pdo->prepare("SELECT tt.id AS tipo_toalla_id,
    (SELECT IFNULL(SUM(dm.cantidad),0) FROM movimiento mv JOIN detalle_movimiento dm ON dm.movimiento_id=mv.id WHERE mv.asociado_id=:a AND mv.tipo='PRESTAMO' AND dm.tipo_toalla_id=tt.id) AS prestadas,
    (SELECT IFNULL(SUM(dm.cantidad),0) FROM movimiento mv JOIN detalle_movimiento dm ON dm.movimiento_id=mv.id WHERE mv.asociado_id=:a AND mv.tipo='DEVOLUCION' AND dm.tipo_toalla_id=tt.id) AS devueltas
    FROM tipo_toalla tt");
  $stmt->execute([':a'=>$asociado_id]);
  foreach ($stmt->fetchAll() as $r) { $pend[$r['tipo_toalla_id']] = (int)$r['prestadas'] - (int)$r['devueltas']; }

  foreach ($items as $it) {
    $t = (int)($it['tipo_toalla_id'] ?? 0); $c = (int)($it['cantidad'] ?? 0);
    if ($t && $c>0) {
      if (!isset($pend[$t]) || $c > $pend[$t]) throw new Exception('Cantidad a devolver excede pendientes');
    }
  }

  $pdo->prepare('INSERT INTO movimiento (asociado_id, tipo, usuario_id, creado_en) VALUES (:a,"DEVOLUCION",1,:f)')
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
