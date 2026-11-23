
-- =============================================
-- Alpha System - MySQL 8 Schema (DDL + minimal seeds)
-- =============================================

-- Create database and user (optional for local/dev)
CREATE DATABASE IF NOT EXISTS alpha_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Uncomment if you want to create the user here
-- CREATE USER IF NOT EXISTS 'laravel'@'%' IDENTIFIED BY 'secret';
-- GRANT ALL PRIVILEGES ON alpha_system.* TO 'laravel'@'%';
-- FLUSH PRIVILEGES;

USE alpha_system;

-- -----------------------
-- Core reference tables
-- -----------------------

CREATE TABLE IF NOT EXISTS pais (
  idPais INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombrePais VARCHAR(100) NOT NULL,
  UNIQUE KEY uq_pais_nombre (nombrePais)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estado (
  idEstado INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombreEstado VARCHAR(100) NOT NULL,
  idPais INT UNSIGNED NOT NULL,
  INDEX idx_estado_pais (idPais),
  CONSTRAINT fk_estado_pais FOREIGN KEY (idPais)
    REFERENCES pais(idPais)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  UNIQUE KEY uq_estado_pais_nombre (idPais, nombreEstado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS municipio (
  idMunicipio INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombreMunicipio VARCHAR(100) NOT NULL,
  idEstado INT UNSIGNED NOT NULL,
  INDEX idx_municipio_estado (idEstado),
  CONSTRAINT fk_municipio_estado FOREIGN KEY (idEstado)
    REFERENCES estado(idEstado)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  UNIQUE KEY uq_municipio_estado_nombre (idEstado, nombreMunicipio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ciudad (
  idCiudad INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombreCiudad VARCHAR(100) NOT NULL,
  idMunicipio INT UNSIGNED NOT NULL,
  INDEX idx_ciudad_municipio (idMunicipio),
  CONSTRAINT fk_ciudad_municipio FOREIGN KEY (idMunicipio)
    REFERENCES municipio(idMunicipio)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  UNIQUE KEY uq_ciudad_municipio_nombre (idMunicipio, nombreCiudad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------
-- User/Book status tables
-- -----------------------

CREATE TABLE IF NOT EXISTS tipo_usuario (
  idTipoUsr INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombreTipo VARCHAR(50) NOT NULL,
  UNIQUE KEY uq_tipo_usuario_nombre (nombreTipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estado_usuario (
  idEstadoUsr INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  estadoUsr VARCHAR(50) NOT NULL,
  UNIQUE KEY uq_estado_usuario_nombre (estadoUsr)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS estado_libro (
  idEstadoLibro INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  estadoLibro VARCHAR(50) NOT NULL,
  UNIQUE KEY uq_estado_libro_nombre (estadoLibro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------
-- Domain tables
-- -----------------------

CREATE TABLE IF NOT EXISTS genero (
  idGenero INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombreGenero VARCHAR(100) NOT NULL,
  UNIQUE KEY uq_genero_nombre (nombreGenero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuario (
  idUsr INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombreUsr VARCHAR(100) NOT NULL,
  apellidoP VARCHAR(100) NULL,
  apellidoM VARCHAR(100) NULL,
  fotoUsr VARCHAR(255) NULL,
  calle VARCHAR(150) NULL,
  colonia VARCHAR(150) NULL,
  idCiudad INT UNSIGNED NULL,
  telefonoUsr VARCHAR(30) NULL,
  correoUsr VARCHAR(150) NOT NULL,
  contrasena VARCHAR(255) NOT NULL,
  idTipoUsr INT UNSIGNED NOT NULL,
  idEstadoUsr INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uq_usuario_correo (correoUsr),
  INDEX idx_usuario_ciudad (idCiudad),
  INDEX idx_usuario_tipo (idTipoUsr),
  INDEX idx_usuario_estado (idEstadoUsr),
  CONSTRAINT fk_usuario_ciudad FOREIGN KEY (idCiudad)
    REFERENCES ciudad(idCiudad)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_usuario_tipo FOREIGN KEY (idTipoUsr)
    REFERENCES tipo_usuario(idTipoUsr)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_usuario_estado FOREIGN KEY (idEstadoUsr)
    REFERENCES estado_usuario(idEstadoUsr)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS libro (
  isbn VARCHAR(20) PRIMARY KEY,
  titulo VARCHAR(200) NOT NULL,
  fotoUsr VARCHAR(255) NULL,
  cantidad INT UNSIGNED NOT NULL DEFAULT 0,
  anioPubli INT NULL,
  edicion VARCHAR(50) NULL,
  autor VARCHAR(150) NULL,
  editorial VARCHAR(150) NULL,
  idGenero INT UNSIGNED NOT NULL,
  idEstadoLibro INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_libro_genero (idGenero),
  INDEX idx_libro_estado (idEstadoLibro),
  CONSTRAINT fk_libro_genero FOREIGN KEY (idGenero)
    REFERENCES genero(idGenero)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_libro_estado FOREIGN KEY (idEstadoLibro)
    REFERENCES estado_libro(idEstadoLibro)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prestamo (
  idPrestamo INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idUsuario INT UNSIGNED NOT NULL,
  idLibro VARCHAR(20) NOT NULL,
  fechaPrestamo DATE NOT NULL,
  fechaEntrega DATE NOT NULL,
  multa DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  devuelto TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_prestamo_usuario (idUsuario),
  INDEX idx_prestamo_libro (idLibro),
  CONSTRAINT fk_prestamo_usuario FOREIGN KEY (idUsuario)
    REFERENCES usuario(idUsr)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_prestamo_libro FOREIGN KEY (idLibro)
    REFERENCES libro(isbn)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;