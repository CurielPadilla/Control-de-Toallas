
-- Crear base de datos (opcional)
-- CREATE DATABASE toallas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE toallas;

CREATE TABLE IF NOT EXISTS members (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(120) NOT NULL,
  membresia     VARCHAR(60)  NOT NULL UNIQUE,
  activo        TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_faces (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  member_id       INT NOT NULL,
  image_path      VARCHAR(255) NOT NULL,
  descriptor_json JSON         NOT NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS towel_types (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  codigo    VARCHAR(30) UNIQUE NOT NULL,
  nombre    VARCHAR(80) NOT NULL,
  color     VARCHAR(30) NULL,
  tamano    VARCHAR(30) NULL,
  activo    TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO towel_types (codigo, nombre, color, tamano) VALUES
('BC','Blanca chica','Blanco','Chica')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);
INSERT INTO towel_types (codigo, nombre, color, tamano) VALUES
('BG','Blanca grande','Blanco','Grande')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);
INSERT INTO towel_types (codigo, nombre, color, tamano) VALUES
('AA','Azul alberca','Azul','Estándar')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);
INSERT INTO towel_types (codigo, nombre, color, tamano) VALUES
('TF','Facial','Blanco','Facial')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

CREATE TABLE IF NOT EXISTS events (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  event_type   ENUM('prestamo','devolucion') NOT NULL,
  member_id    INT NOT NULL,
  staff_user   VARCHAR(80) NULL,
  notes        VARCHAR(255) NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  event_id      INT NOT NULL,
  towel_type_id INT NOT NULL,
  cantidad      INT NOT NULL,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  FOREIGN KEY (towel_type_id) REFERENCES towel_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP VIEW IF EXISTS v_member_pending;
CREATE VIEW v_member_pending AS
SELECT
  e.member_id,
  ei.towel_type_id,
  SUM(CASE WHEN e.event_type='prestamo'  THEN ei.cantidad ELSE 0 END)
  -
  SUM(CASE WHEN e.event_type='devolucion' THEN ei.cantidad ELSE 0 END) AS pendientes
FROM event_items ei
JOIN events e ON e.id = ei.event_id
GROUP BY e.member_id, ei.towel_type_id;
