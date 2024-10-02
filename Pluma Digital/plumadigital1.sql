CREATE DATABASE IF NOT EXISTS plumadigital1;

USE plumadigital1;

-- Tabla de roles
CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

-- Insertar roles predefinidos
INSERT INTO roles (nombre) VALUES
    ('owner'),
    ('administrador'),
    ('registrado'),
    ('invitado');

CREATE TABLE IF NOT EXISTS usuarios (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    nombre_usuario VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    genero VARCHAR(20) NOT NULL,
    foto_perfil VARCHAR(255) DEFAULT 'default_profile_picture.jpg',
    descripcion TEXT NOT NULL DEFAULT '...',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rol_id INT UNSIGNED NOT NULL DEFAULT 3, -- Asignar por defecto el rol de 'registrado'
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);


-- Tabla de publicaciones
CREATE TABLE IF NOT EXISTS publicaciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contenido TEXT NOT NULL,
    fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT(11) UNSIGNED NOT NULL,
    imagen VARCHAR(255),  
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);


CREATE TABLE IF NOT EXISTS seguir (
    id_seguidor INT UNSIGNED NOT NULL,
    id_seguido INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_seguidor, id_seguido),
    FOREIGN KEY (id_seguidor) REFERENCES usuarios(id),
    FOREIGN KEY (id_seguido) REFERENCES usuarios(id)
);

CREATE TABLE `mensajes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contenido` text NOT NULL,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `receptor_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_usuario_id` (`usuario_id`),
  KEY `fk_receptor_id` (`receptor_id`),
  CONSTRAINT `fk_receptor_id` FOREIGN KEY (`receptor_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_usuario_id` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

