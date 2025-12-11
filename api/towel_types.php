
<?php
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');

if ($method==='GET' && $path==='api/towel_types') {
  $stmt = $pdo->query('SELECT id,codigo,nombre,color,tamano,activo FROM towel_types WHERE activo=1 ORDER BY id');
  json_out($stmt->fetchAll());
}

json_out(['error'=>'Ruta no encontrada'],404);
