-- =====================================================================
--  MotorFix · Sistema de gestión integral para taller mecánico
--  Script de creación de la base de datos (MySQL 8 / MariaDB 10.5+)
--  Semana 1: modelo E-R
-- =====================================================================

DROP DATABASE IF EXISTS motorfix;
CREATE DATABASE motorfix CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE motorfix;

-- ---------------------------------------------------------------------
-- USUARIOS: cuentas para iniciar sesión (login unificado).
-- El rol decide qué puede hacer cada uno.
-- auth_key: token para la opción avanzada de autenticación (bonificación).
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100)  NOT NULL,
    email           VARCHAR(150)  NOT NULL UNIQUE,
    password        VARCHAR(255)  NOT NULL COMMENT 'Hash BCrypt (password_hash)',
    rol             ENUM('admin','oficina','mecanico','cliente') NOT NULL DEFAULT 'cliente',
    auth_key        VARCHAR(64)   NULL UNIQUE,
    auth_key_expira DATETIME      NULL,
    activo          TINYINT(1)    NOT NULL DEFAULT 1,
    fecha_alta      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CLIENTES: datos personales. Un cliente puede tener cuenta de usuario
-- (registro público) o haber sido dado de alta por oficina sin cuenta.
-- Relación usuarios 1:1 clientes (opcional).
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED  NULL UNIQUE,
    dni         VARCHAR(9)    NOT NULL UNIQUE,
    nombre      VARCHAR(100)  NOT NULL,
    apellidos   VARCHAR(150)  NOT NULL,
    telefono    VARCHAR(15)   NOT NULL,
    email       VARCHAR(150)  NULL,
    direccion   VARCHAR(200)  NULL,
    fecha_alta  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_clientes_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- VEHICULOS: relación clientes 1:N vehiculos.
-- ---------------------------------------------------------------------
CREATE TABLE vehiculos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id  INT UNSIGNED  NOT NULL,
    matricula   VARCHAR(10)   NOT NULL UNIQUE,
    marca       VARCHAR(50)   NOT NULL,
    modelo      VARCHAR(50)   NOT NULL,
    anio        SMALLINT UNSIGNED NULL,
    color       VARCHAR(30)   NULL,
    kilometros  INT UNSIGNED  NULL,
    combustible ENUM('gasolina','diesel','hibrido','electrico','glp') NULL,
    CONSTRAINT fk_vehiculos_cliente FOREIGN KEY (cliente_id)
        REFERENCES clientes(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CITAS: el cliente la solicita, oficina la valida y asigna un mecánico,
-- el mecánico actualiza el estado de la reparación.
-- vehiculos 1:N citas · usuarios(mecánico) 1:N citas
-- ---------------------------------------------------------------------
CREATE TABLE citas (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id       INT UNSIGNED  NOT NULL,
    mecanico_id       INT UNSIGNED  NULL COMMENT 'Usuario con rol mecanico',
    fecha_solicitud   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_cita        DATETIME      NOT NULL,
    motivo            VARCHAR(255)  NOT NULL,
    estado            ENUM('pendiente','confirmada','en_reparacion','finalizada','cancelada')
                      NOT NULL DEFAULT 'pendiente',
    observaciones     TEXT          NULL COMMENT 'Diagnóstico / notas del mecánico',
    CONSTRAINT fk_citas_vehiculo FOREIGN KEY (vehiculo_id)
        REFERENCES vehiculos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_citas_mecanico FOREIGN KEY (mecanico_id)
        REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_citas_estado (estado),
    INDEX idx_citas_fecha (fecha_cita)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- TRABAJOS: horas de mano de obra imputadas por el mecánico en una cita.
-- citas 1:N trabajos
-- ---------------------------------------------------------------------
CREATE TABLE trabajos (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cita_id      INT UNSIGNED   NOT NULL,
    mecanico_id  INT UNSIGNED   NULL,
    descripcion  VARCHAR(255)   NOT NULL,
    horas        DECIMAL(5,2)   NOT NULL CHECK (horas > 0),
    precio_hora  DECIMAL(8,2)   NOT NULL DEFAULT 45.00,
    fecha        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trabajos_cita FOREIGN KEY (cita_id)
        REFERENCES citas(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_trabajos_mecanico FOREIGN KEY (mecanico_id)
        REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- REPUESTOS: catálogo de piezas y materiales del taller.
-- ---------------------------------------------------------------------
CREATE TABLE repuestos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referencia  VARCHAR(30)   NOT NULL UNIQUE,
    nombre      VARCHAR(100)  NOT NULL,
    precio      DECIMAL(8,2)  NOT NULL CHECK (precio >= 0),
    stock       INT UNSIGNED  NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CITA_REPUESTOS: materiales consumidos en cada cita (N:M citas-repuestos).
-- Se guarda el precio en el momento del uso por si cambia el catálogo.
-- ---------------------------------------------------------------------
CREATE TABLE cita_repuestos (
    cita_id          INT UNSIGNED  NOT NULL,
    repuesto_id      INT UNSIGNED  NOT NULL,
    cantidad         INT UNSIGNED  NOT NULL DEFAULT 1 CHECK (cantidad > 0),
    precio_unitario  DECIMAL(8,2)  NOT NULL,
    PRIMARY KEY (cita_id, repuesto_id),
    CONSTRAINT fk_cr_cita FOREIGN KEY (cita_id)
        REFERENCES citas(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_cr_repuesto FOREIGN KEY (repuesto_id)
        REFERENCES repuestos(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- FACTURAS: una por cita finalizada (citas 1:1 facturas).
-- Desglose: mano de obra + repuestos = subtotal; IVA 21 %; total.
-- ---------------------------------------------------------------------
CREATE TABLE facturas (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero        VARCHAR(20)   NOT NULL UNIQUE COMMENT 'Ej.: F2026-0001',
    cita_id       INT UNSIGNED  NOT NULL UNIQUE,
    fecha         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    mano_obra     DECIMAL(10,2) NOT NULL DEFAULT 0,
    repuestos     DECIMAL(10,2) NOT NULL DEFAULT 0,
    subtotal      DECIMAL(10,2) NOT NULL,
    iva_porcentaje DECIMAL(4,2) NOT NULL DEFAULT 21.00,
    iva_importe   DECIMAL(10,2) NOT NULL,
    total         DECIMAL(10,2) NOT NULL,
    estado        ENUM('pendiente','pagada','anulada') NOT NULL DEFAULT 'pendiente',
    CONSTRAINT fk_facturas_cita FOREIGN KEY (cita_id)
        REFERENCES citas(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
--  DATOS DE PRUEBA
--  Contraseña de todos los usuarios de prueba: MotorFix2026
-- =====================================================================
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador',  'admin@motorfix.es',   '$2y$10$5A6RkBbxLu3tqFPL1df3.eIMDtpHeXe/Wm1FU0xYxawI05VdT/UBm', 'admin'),
('Laura Oficina',  'oficina@motorfix.es', '$2y$10$5A6RkBbxLu3tqFPL1df3.eIMDtpHeXe/Wm1FU0xYxawI05VdT/UBm', 'oficina'),
('Pedro Mecánico', 'pedro@motorfix.es',   '$2y$10$5A6RkBbxLu3tqFPL1df3.eIMDtpHeXe/Wm1FU0xYxawI05VdT/UBm', 'mecanico'),
('Ana Mecánica',   'ana@motorfix.es',     '$2y$10$5A6RkBbxLu3tqFPL1df3.eIMDtpHeXe/Wm1FU0xYxawI05VdT/UBm', 'mecanico'),
('Juan García',    'juan@correo.es',      '$2y$10$5A6RkBbxLu3tqFPL1df3.eIMDtpHeXe/Wm1FU0xYxawI05VdT/UBm', 'cliente');

INSERT INTO clientes (usuario_id, dni, nombre, apellidos, telefono, email, direccion) VALUES
(5,    '12345678Z', 'Juan',  'García López',   '600111222', 'juan@correo.es',  'C/ Mayor 1, Madrid'),
(NULL, '87654321X', 'María', 'Pérez Sánchez',  '600333444', 'maria@correo.es', 'Av. Libertad 22, Getafe');

INSERT INTO vehiculos (cliente_id, matricula, marca, modelo, anio, color, kilometros, combustible) VALUES
(1, '1234ABC', 'Seat',    'León',   2018, 'Blanco', 85000,  'diesel'),
(1, '5678DEF', 'Toyota',  'Yaris',  2021, 'Rojo',   30000,  'hibrido'),
(2, '9012GHI', 'Renault', 'Clio',   2015, 'Gris',   140000, 'gasolina');

INSERT INTO repuestos (referencia, nombre, precio, stock) VALUES
('ACE-5W30',  'Aceite motor 5W30 (litro)', 9.50,  60),
('FIL-ACE01', 'Filtro de aceite',          12.00, 25),
('PAS-FRE01', 'Juego pastillas de freno',  45.00, 10),
('ESC-LIMP',  'Escobilla limpiaparabrisas', 8.75, 30);

INSERT INTO citas (vehiculo_id, mecanico_id, fecha_cita, motivo, estado, observaciones) VALUES
(1, 3,    '2026-10-20 09:00:00', 'Revisión y cambio de aceite', 'finalizada', 'Cambio de aceite y filtro realizado.'),
(2, 4,    '2026-10-23 10:30:00', 'Ruido al frenar',             'en_reparacion', NULL),
(3, NULL, '2026-10-27 16:00:00', 'Pre-ITV',                     'pendiente', NULL);

INSERT INTO trabajos (cita_id, mecanico_id, descripcion, horas, precio_hora) VALUES
(1, 3, 'Cambio de aceite y filtro', 1.00, 45.00),
(2, 4, 'Diagnóstico sistema de frenos', 0.50, 45.00);

INSERT INTO cita_repuestos (cita_id, repuesto_id, cantidad, precio_unitario) VALUES
(1, 1, 5, 9.50),
(1, 2, 1, 12.00);

-- Factura de la cita 1: mano de obra 45,00 + repuestos 59,50 = 104,50; IVA 21,95; total 126,45
INSERT INTO facturas (numero, cita_id, mano_obra, repuestos, subtotal, iva_porcentaje, iva_importe, total, estado) VALUES
('F2026-0001', 1, 45.00, 59.50, 104.50, 21.00, 21.95, 126.45, 'pagada');
