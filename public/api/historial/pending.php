<?php require_once __DIR__.'/../../../config/db.php';
$pdo = db();
$asociado_id = (int)($_GET['asociado_id'] ?? 0);
if (!$asociado_id) { echo json_encode(['success'=>false,'message'=>'Falta asociado']); exit; }
$sql = "SELECT tt.id AS tipo_toalla_id, tt.descripcion,
  (SELECT IFNULL(SUM(dm.cantidad),0) FROM movimiento mv JOIN detalle_movimiento dm ON dm.movimiento_id=mv.id WHERE mv.asociado_id=:a AND mv.tipo='PRESTAMO' AND dm.tipo_toalla_id=tt.id) AS prestadas,
  (SELECT IFNULL(SUM(dm.cantidad),0) FROM movimiento mv JOIN detalle_movimiento dm ON dm.movimiento_id=mv.id WHERE mv.asociado_id=:a AND mv.tipo='DEVOLUCION' AND dm.tipo_toalla_id=tt.id) AS devueltas
  FROM tipo_toalla tt WHERE tt.activo=1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':a'=>$asociado_id]);
$out = [];
foreach ($stmt->fetchAll() as $r) {
  $pend = (int)$r['prestadas'] - (int)$r['devueltas'];
  if ($pend > 0) $out[] = ['tipo_toalla_id'=>(int)$r['tipo_toalla_id'],'descripcion'=>$r['descripcion'],'pendiente'=>$pend];
}
echo json_encode(['success'=>true,'data'=>$out], JSON_UNESCAPED_UNICODE);
