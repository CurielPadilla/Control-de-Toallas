<?php
// config/config.php
// Ajusta estos valores a tu entorno local

define('DB_HOST', 'db');     // Nombre del contenedor MySQL
define('DB_NAME', 'control_toallas');
define('DB_USER', 'root');

define('DB_PASS', 'root');   // Misma contraseña usada en docker-compose.yml

define('BASE_PATH', __DIR__ . '/../');
define('PUBLIC_PATH', __DIR__ . '/../public/');
define('STORAGE_PATH', __DIR__ . '/../storage/');

define('FACES_PATH', STORAGE_PATH . 'faces/');
define('EXPORTS_PATH', STORAGE_PATH . 'exports/');

define('TIMEZONE', 'America/Mexico_City');
date_default_timezone_set(TIMEZONE);
?>
