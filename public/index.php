<?php require_once __DIR__.'/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Control de Toallas</title>
  <link rel="stylesheet" href="assets/css/styles.css" />
</head>
<body>
<header>
  <div class="title">Sistema de Control de Toallas</div>
  <nav class="nav">
    <a href="./index.php" class="active">Inicio</a>
    <a href="./asociados/alta.php">Registro de Asociado</a>
    <a href="./movimientos/prestamo.php">Préstamo</a>
    <a href="./movimientos/devolucion.php">Devolución</a>
    <a href="./reportes/historial.php">Historial/Reportes</a>
  </nav>
</header>
<main class="container">
  <h2>Bienvenido</h2>
  <p>Utiliza el menú para registrar asociados, prestar y devolver toallas con verificación facial, y consultar historiales.</p>
  <div class="notice">
    <strong>Nota:</strong> En atlas colomos lo primero.
  </div>
  <hr />
  <div class="row">
    <div class="col card">
      <h3>1) Registrar asociado</h3>
      <p>Captura nombre, membresía y su rostro (3 tomas).</p>
      <a href="./asociados/alta.php"><button>Ir a Registro</button></a>
    </div>
    <div class="col card">
      <h3>2) Préstamo</h3>
      <p>Reconoce al asociado por webcam, selecciona tipo y cantidad.</p>
      <a href="./movimientos/prestamo.php"><button>Ir a Préstamo</button></a>
    </div>
    <div class="col card">
      <h3>3) Devolución</h3>
      <p>Reconoce al asociado y registra devolución parcial o total.</p>
      <a href="./movimientos/devolucion.php"><button>Ir a Devolución</button></a>
    </div>
  </div>
</main>
</body>
</html>
