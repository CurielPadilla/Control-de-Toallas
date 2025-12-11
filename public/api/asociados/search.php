<?php require_once __DIR__.'/../../../config/db.php';
$m = $_GET['membresia'] ?? '';
if (!$m) { echo json_encode(['success'=>false,'message'=>'Falta membresía']); exit; }
$pdo = db();
$stmt = $pdo->prepare('SELECT id, membresia, nombre FROM asociado WHERE membresia = :m LIMIT 1');
$stmt->execute([':m'=>$m]);
$row = $stmt->fetch();
if ($row) echo json_encode(['success'=>true,'data'=>$row], JSON_UNESCAPED_UNICODE);
else echo json_encode(['success'=>false,'message'=>'No encontrado']);
