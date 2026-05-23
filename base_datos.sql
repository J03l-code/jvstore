-- Base de datos para IMPORDISPAC
CREATE DATABASE IF NOT EXISTS `impordispac_db`;
USE `impordispac_db`;
-- Tabla de Usuarios (Admin)
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `rol` enum('admin', 'staff') DEFAULT 'admin',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Tabla de Mensajes de Contacto
CREATE TABLE IF NOT EXISTS `mensajes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `mensaje` text NOT NULL,
    `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Tabla de Servicios/Productos
CREATE TABLE IF NOT EXISTS `servicios` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `titulo` varchar(100) NOT NULL,
    `descripcion` text,
    `icono` varchar(50) DEFAULT 'fas fa-box',
    `activo` boolean DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Datos de Prueba
INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol`)
VALUES (
        'Admin Import',
        'admin@impordispac.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin'
    );
-- password: password
INSERT INTO `servicios` (`titulo`, `descripcion`, `icono`)
VALUES (
        'Importación de Tecnología',
        'Traemos lo último en gadgets y equipos electrónicos.',
        'fas fa-microchip'
    ),
    (
        'Logística Nacional',
        'Cobertura en todo el territorio para entregas rápidas.',
        'fas fa-truck'
    ),
    (
        'Asesoría en Comex',
        'Consultoría especializada en comercio exterior.',
        'fas fa-globe-americas'
    );