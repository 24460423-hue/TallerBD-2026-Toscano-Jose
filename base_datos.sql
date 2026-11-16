CREATE DATABASE IF NOT EXISTS prestamos_equipo
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE prestamos_equipo;

CREATE TABLE IF NOT EXISTS categorias (
    id_categoria INT UNSIGNED AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_categoria),
    UNIQUE (nombre)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS departamentos (
    id_departamento INT UNSIGNED AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_departamento),
    UNIQUE (nombre)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS solicitantes (
    id_solicitante INT UNSIGNED AUTO_INCREMENT,
    numero_institucional VARCHAR(30) NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    id_departamento INT UNSIGNED NOT NULL,

    PRIMARY KEY (id_solicitante),
    UNIQUE (numero_institucional),

    CONSTRAINT fk_solicitantes_departamento
        FOREIGN KEY (id_departamento)
        REFERENCES departamentos (id_departamento)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS equipos (
    id_equipo INT UNSIGNED AUTO_INCREMENT,
    numero_inventario VARCHAR(50) NOT NULL,
    descripcion VARCHAR(200) NOT NULL,
    marca VARCHAR(60),
    modelo VARCHAR(80),
    condicion VARCHAR(200) NOT NULL,
    estado ENUM('disponible', 'prestado', 'mantenimiento')
        NOT NULL DEFAULT 'disponible',
    activo INT UNSIGNED NOT NULL DEFAULT 1,
    id_categoria INT UNSIGNED NOT NULL,

    PRIMARY KEY (id_equipo),
    UNIQUE (numero_inventario),

    CONSTRAINT chk_equipos_activo
        CHECK (activo IN (0, 1)),

    CONSTRAINT fk_equipos_categoria
        FOREIGN KEY (id_categoria)
        REFERENCES categorias (id_categoria)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT UNSIGNED AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    usuario VARCHAR(50) NOT NULL,
    contrasena_hash VARCHAR(255) NOT NULL,
    perfil VARCHAR(20) NOT NULL,
    activo INT UNSIGNED NOT NULL DEFAULT 1,

    PRIMARY KEY (id_usuario),
    UNIQUE (usuario),

    CONSTRAINT chk_usuarios_perfil
        CHECK (perfil IN ('administrador', 'consulta')),

    CONSTRAINT chk_usuarios_activo
        CHECK (activo IN (0, 1))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS prestamos (
    id_prestamo INT UNSIGNED AUTO_INCREMENT,
    id_equipo INT UNSIGNED NOT NULL,
    id_solicitante INT UNSIGNED NOT NULL,
    id_usuario_entrega INT UNSIGNED NOT NULL,
    fecha_prestamo DATETIME NOT NULL,
    fecha_prevista DATETIME NOT NULL,
    fecha_devolucion DATETIME NULL,
    condicion_devolucion VARCHAR(200) NULL,
    id_usuario_recibe INT UNSIGNED NULL,

    PRIMARY KEY (id_prestamo),

    CONSTRAINT chk_prestamos_fecha_prevista
        CHECK (fecha_prevista >= fecha_prestamo),

    CONSTRAINT chk_prestamos_devolucion
        CHECK (
            (
                fecha_devolucion IS NULL
                AND condicion_devolucion IS NULL
                AND id_usuario_recibe IS NULL
            )
            OR
            (
                fecha_devolucion IS NOT NULL
                AND condicion_devolucion IS NOT NULL
                AND id_usuario_recibe IS NOT NULL
                AND fecha_devolucion >= fecha_prestamo
            )
        ),

    CONSTRAINT fk_prestamos_equipo
        FOREIGN KEY (id_equipo)
        REFERENCES equipos (id_equipo)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_prestamos_solicitante
        FOREIGN KEY (id_solicitante)
        REFERENCES solicitantes (id_solicitante)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_prestamos_entrega
        FOREIGN KEY (id_usuario_entrega)
        REFERENCES usuarios (id_usuario)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_prestamos_recibe
        FOREIGN KEY (id_usuario_recibe)
        REFERENCES usuarios (id_usuario)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB;

