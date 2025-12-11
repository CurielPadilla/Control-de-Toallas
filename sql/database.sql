
-- Base de datos y tablas
CREATE DATABASE IF NOT EXISTS control_toallas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE control_toallas;

-- Asociados
CREATE TABLE IF NOT EXISTS asociado (
  id INT AUTO_INCREMENT PRIMARY KEY,
  membresia VARCHAR(50) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Rostros (rutas a archivos de imagen y descriptor promedio JSON)
CREATE TABLE IF NOT EXISTS rostro_asociado (
  id INT AUTO_INCREMENT PRIMARY KEY,
  asociado_id INT NOT NULL,
  ruta_imagen1 VARCHAR(255) NULL,
  ruta_imagen2 VARCHAR(255) NULL,
  ruta_imagen3 VARCHAR(255) NULL,
  descriptor_json_path VARCHAR(255) NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (asociado_id) REFERENCES asociado(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tipos de toalla
CREATE TABLE IF NOT EXISTS tipo_toalla (
  id INT AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(50) NOT NULL UNIQUE,
  descripcion VARCHAR(100) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

INSERT IGNORE INTO tipo_toalla (id, clave, descripcion, activo) VALUES
(1, 'BLANCA_CHICA', 'Blanca chica', 1),
(2, 'BLANCA_GRANDE', 'Blanca grande', 1),
(3, 'AZUL_ALBERCA', 'Azul alberca', 1),
(4, 'FACIAL', 'Facial', 1);

-- Usuarios del sistema (simple, sin autenticación en MVP)
CREATE TABLE IF NOT EXISTS usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  rol ENUM('OPERADOR','ADMIN') NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO usuario (id, nombre, rol, activo) VALUES
(1, 'Operador', 'OPERADOR', 1),
(2, 'Administrador', 'ADMIN', 1);

-- Movimientos (evento = préstamo o devolución)
CREATE TABLE IF NOT EXISTS movimiento (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  asociado_id INT NOT NULL,
  tipo ENUM('PRESTAMO','DEVOLUCION') NOT NULL,
  usuario_id INT NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  observaciones VARCHAR(255) NULL,
  FOREIGN KEY (asociado_id) REFERENCES asociado(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE RESTRICT,
  INDEX (asociado_id, creado_en)
) ENGINE=InnoDB;

-- Detalle de cada movimiento
CREATE TABLE IF NOT EXISTS detalle_movimiento (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  movimiento_id BIGINT NOT NULL,
  tipo_toalla_id INT NOT NULL,
  cantidad INT NOT NULL,
  FOREIGN KEY (movimiento_id) REFERENCES movimiento(id) ON DELETE CASCADE,
  FOREIGN KEY (tipo_toalla_id) REFERENCES tipo_toalla(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Auditoría (mínima)
CREATE TABLE IF NOT EXISTS auditoria (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  accion VARCHAR(50) NOT NULL,
  entidad VARCHAR(50) NOT NULL,
  entidad_id BIGINT NULL,
  detalles TEXT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuario(id)
) ENGINE=InnoDB;
