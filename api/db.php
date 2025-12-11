
<?php
// api/db.php
// Configurar credenciales de MySQL
$dsn = getenv('TOALLAS_DSN') ?: 'mysql:host=127.0.0.1;dbname=toallas;charset=utf8mb4';
$user = getenv('TOALLAS_DB_USER') ?: 'root';
$pass = getenv('TOALLAS_DB_PASS') ?: '';

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error'=>'Error de conexión BD', 'details'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
  exit;
}

function json_out($data, int $code=200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

// Helper para obtener cuerpo JSON
function get_json_body() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}
?>
