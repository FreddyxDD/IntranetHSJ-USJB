/* =========================================================
    ADMIN UEeI - USUARIOS, ÁREAS, MÓDULOS Y PERMISOS
    Base: hospital_ueei
========================================================= */

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

/* =========================================================
    TABLA: areas
========================================================= */
CREATE TABLE IF NOT EXISTS areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(80) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255) NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* =========================================================
    TABLA: modulos
========================================================= */
CREATE TABLE IF NOT EXISTS modulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(80) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255) NULL,
    ruta VARCHAR(180) NOT NULL,
    icono VARCHAR(255) NULL,
    orden INT NOT NULL DEFAULT 100,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* =========================================================
    TABLA: cuentas_ueei
    Compatible con tu login actual
========================================================= */
CREATE TABLE IF NOT EXISTS cuentas_ueei (
    id INT AUTO_INCREMENT PRIMARY KEY,
    correo VARCHAR(180) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombres VARCHAR(120) NULL,
    apellidos VARCHAR(120) NULL,
    documento VARCHAR(20) NULL,
    telefono VARCHAR(30) NULL,
    rol ENUM('admin','director','supervisor','trabajador') NOT NULL DEFAULT 'trabajador',
    area_id INT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    session_version INT NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cuentas_ueei_area
        FOREIGN KEY (area_id) REFERENCES areas(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* =========================================================
    Si tu tabla cuentas_ueei ya existía, agrega columnas faltantes
========================================================= */
ALTER TABLE cuentas_ueei
    ADD COLUMN IF NOT EXISTS nombres VARCHAR(120) NULL AFTER password,
    ADD COLUMN IF NOT EXISTS apellidos VARCHAR(120) NULL AFTER nombres,
    ADD COLUMN IF NOT EXISTS documento VARCHAR(20) NULL AFTER apellidos,
    ADD COLUMN IF NOT EXISTS telefono VARCHAR(30) NULL AFTER documento,
    ADD COLUMN IF NOT EXISTS area_id INT NULL AFTER rol,
    ADD COLUMN IF NOT EXISTS estado TINYINT(1) NOT NULL DEFAULT 1 AFTER area_id,
    ADD COLUMN IF NOT EXISTS session_version INT NOT NULL DEFAULT 1 AFTER estado,
    ADD COLUMN IF NOT EXISTS creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER session_version,
    ADD COLUMN IF NOT EXISTS actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER creado_en;

/* =========================================================
    TABLA: area_modulos
    Permisos generales por área
========================================================= */
CREATE TABLE IF NOT EXISTS area_modulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_id INT NOT NULL,
    modulo_id INT NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_area_modulo (area_id, modulo_id),
    CONSTRAINT fk_area_modulos_area
        FOREIGN KEY (area_id) REFERENCES areas(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_area_modulos_modulo
        FOREIGN KEY (modulo_id) REFERENCES modulos(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* =========================================================
    TABLA: cuenta_modulos
    Permisos específicos por usuario
    Esta es la tabla importante para tu lógica nueva.
========================================================= */
CREATE TABLE IF NOT EXISTS cuenta_modulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cuenta_id INT NOT NULL,
    modulo_id INT NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_cuenta_modulo (cuenta_id, modulo_id),
    CONSTRAINT fk_cuenta_modulos_cuenta
        FOREIGN KEY (cuenta_id) REFERENCES cuentas_ueei(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_cuenta_modulos_modulo
        FOREIGN KEY (modulo_id) REFERENCES modulos(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* =========================================================
    ÁREAS BASE
========================================================= */
INSERT INTO areas (codigo, nombre, descripcion, estado)
VALUES
    ('administracion', 'Administración', 'Gestión de usuarios, roles y permisos del intranet.', 1),
    ('ueei', 'Unidad de Estadística e Información', 'Área de estadística e información hospitalaria.', 1),
    ('citas', 'Citas', 'Gestión administrativa de citas hospitalarias.', 1),
    ('cirugias', 'Cirugías', 'Gestión y seguimiento de cirugías.', 1),
    ('uvi', 'UVI', 'Gestión interna del módulo UVI.', 1),
    ('direccion', 'Dirección', 'Área directiva con acceso a indicadores institucionales.', 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    estado = VALUES(estado);

/* =========================================================
    MÓDULOS BASE
    Los códigos deben coincidir con los que usa public/index.php
========================================================= */
INSERT INTO modulos (codigo, nombre, descripcion, ruta, icono, orden, estado)
VALUES
    ('informacion', 'Información', 'Información institucional del Hospital San José.', '/informacion', '/assets/icon/InforHSJ.png', 10, 1),
    ('citas_admin', 'Citas', 'Administración de citas y registros de sala de espera.', '/citas-admin', '/assets/icon/CitasLog.png', 20, 1),
    ('cirugias', 'Cirugías', 'Registro, control y análisis de cirugías.', '/cirugias-login', '/assets/icon/CirugiasLog.png', 30, 1),
    ('uvi', 'UVI', 'Administración del módulo UVI.', '/uvi-login', '/assets/icon/UVIlo.png', 40, 1),
    ('produccion', 'Producción', 'Indicadores de producción y rendimiento.', '/produccion', '/assets/icon/Total_cirugias.png', 50, 1),
    ('eficiencia', 'Eficiencia', 'Indicadores de eficiencia hospitalaria.', '/eficiencia', '/assets/icon/Tasa_Urgencia.png', 60, 1),
    ('calidad', 'Calidad', 'Indicadores de calidad institucional.', '/calidad', '/assets/icon/Segura.png', 70, 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    ruta = VALUES(ruta),
    icono = VALUES(icono),
    orden = VALUES(orden),
    estado = VALUES(estado);

/* =========================================================
    PERMISOS BASE POR ÁREA
    Esto sirve como respaldo.
    Luego el admin podrá asignar permisos específicos por usuario.
========================================================= */

/* Administración: todos los módulos */
INSERT IGNORE INTO area_modulos (area_id, modulo_id)
SELECT a.id, m.id
FROM areas a
CROSS JOIN modulos m
WHERE a.codigo = 'administracion';

/* Citas: solo citas */
INSERT IGNORE INTO area_modulos (area_id, modulo_id)
SELECT a.id, m.id
FROM areas a
INNER JOIN modulos m ON m.codigo = 'citas_admin'
WHERE a.codigo = 'citas';

/* Cirugías: solo cirugías */
INSERT IGNORE INTO area_modulos (area_id, modulo_id)
SELECT a.id, m.id
FROM areas a
INNER JOIN modulos m ON m.codigo = 'cirugias'
WHERE a.codigo = 'cirugias';

/* UVI: solo UVI */
INSERT IGNORE INTO area_modulos (area_id, modulo_id)
SELECT a.id, m.id
FROM areas a
INNER JOIN modulos m ON m.codigo = 'uvi'
WHERE a.codigo = 'uvi';

/* UEeI: producción, eficiencia, calidad e información */
INSERT IGNORE INTO area_modulos (area_id, modulo_id)
SELECT a.id, m.id
FROM areas a
INNER JOIN modulos m ON m.codigo IN ('informacion', 'produccion', 'eficiencia', 'calidad')
WHERE a.codigo = 'ueei';

/* Dirección: indicadores e información */
INSERT IGNORE INTO area_modulos (area_id, modulo_id)
SELECT a.id, m.id
FROM areas a
INNER JOIN modulos m ON m.codigo IN ('informacion', 'produccion', 'eficiencia', 'calidad')
WHERE a.codigo = 'direccion';

/* =========================================================
    CORREGIR ÁREA DEL ADMIN EXISTENTE
    No cambia contraseña.
========================================================= */
UPDATE cuentas_ueei
SET
    rol = 'admin',
    area_id = (SELECT id FROM areas WHERE codigo = 'administracion' LIMIT 1),
    estado = 1
WHERE correo = 'admin@hospital.gob.pe';

/* Dar todos los módulos al admin existente */
INSERT IGNORE INTO cuenta_modulos (cuenta_id, modulo_id)
SELECT c.id, m.id
FROM cuentas_ueei c
CROSS JOIN modulos m
WHERE c.correo = 'admin@hospital.gob.pe';

SET FOREIGN_KEY_CHECKS = 1;