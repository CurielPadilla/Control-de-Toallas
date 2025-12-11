<?php require_once __DIR__.'/../../../config/db.php';
$pdo = db();
$m = $_GET['membresia'] ?? '';
$d = $_GET['desde'] ?? '';
$h = $_GET['hasta'] ?? '';
$sql = "SELECT mv.creado_en, a.membresia, a.nombre, mv.tipo, tt.descripcion, dm.cantidad, u.nombre AS operador
        FROM movimiento mv
        JOIN asociado a ON a.id = mv.asociado_id
        JOIN detalle_movimiento dm ON dm.movimiento_id = mv.id
        JOIN tipo_toalla tt ON tt.id = dm.tipo_toalla_id
        JOIN usuario u ON u.id = mv.usuario_id
        WHERE 1=1";
$params = [];
if ($m) { $sql .= " AND a.membresia = :m"; $params[':m'] = $m; }
if ($d) { $sql .= " AND DATE(mv.creado_en) >= :d"; $params[':d'] = $d; }
if ($h) { $sql .= " AND DATE(mv.creado_en) <= :h"; $params[':h'] = $h; }
$sql .= " ORDER BY mv.creado_en DESC LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
