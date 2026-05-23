-- ============================================================
-- IMPORDISPAC - Base de Datos E-Commerce Repuestos Automotrices
-- ============================================================
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
-- --------------------------------------------------------
-- Tabla: categorias
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categorias` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `slug` varchar(100) NOT NULL,
    `icono` varchar(50) DEFAULT 'fas fa-cog',
    `imagen` varchar(255) DEFAULT NULL,
    `descripcion` text DEFAULT NULL,
    `activo` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- --------------------------------------------------------
-- Tabla: usuarios
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    `email` varchar(150) NOT NULL,
    `password` varchar(255) DEFAULT NULL,
    `google_id` varchar(255) DEFAULT NULL,
    `avatar` varchar(255) DEFAULT NULL,
    `telefono` varchar(20) DEFAULT NULL,
    `direccion` text DEFAULT NULL,
    `ciudad` varchar(100) DEFAULT NULL,
    `rol` enum('cliente', 'admin') DEFAULT 'cliente',
    `activo` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    KEY `idx_google_id` (`google_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- --------------------------------------------------------
-- Tabla: productos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `productos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `oem_code` varchar(50) NOT NULL,
    `nombre` varchar(200) NOT NULL,
    `slug` varchar(200) NOT NULL,
    `descripcion` text DEFAULT NULL,
    `descripcion_tecnica` text DEFAULT NULL,
    `marca` varchar(100) DEFAULT NULL,
    `modelo` varchar(100) DEFAULT NULL,
    `anio` varchar(20) DEFAULT NULL,
    `categoria_id` int(11) DEFAULT NULL,
    `precio` decimal(10, 2) NOT NULL DEFAULT 0.00,
    `precio_oferta` decimal(10, 2) DEFAULT NULL,
    `stock` int(11) NOT NULL DEFAULT 0,
    `imagen_url` varchar(255) DEFAULT NULL,
    `imagen_2` varchar(255) DEFAULT NULL,
    `imagen_3` varchar(255) DEFAULT NULL,
    `compatibilidad` text DEFAULT NULL,
    `destacado` tinyint(1) DEFAULT 0,
    `activo` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `oem_code` (`oem_code`),
    UNIQUE KEY `slug` (`slug`),
    KEY `idx_marca` (`marca`),
    KEY `idx_modelo` (`modelo`),
    KEY `idx_anio` (`anio`),
    KEY `idx_categoria` (`categoria_id`),
    KEY `idx_precio` (`precio`),
    CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE
    SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- --------------------------------------------------------
-- Tabla: pedidos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedidos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `usuario_id` int(11) NOT NULL,
    `subtotal` decimal(10, 2) NOT NULL DEFAULT 0.00,
    `impuestos` decimal(10, 2) NOT NULL DEFAULT 0.00,
    `envio` decimal(10, 2) NOT NULL DEFAULT 0.00,
    `total` decimal(10, 2) NOT NULL DEFAULT 0.00,
    `estado` enum(
        'pendiente',
        'pagado',
        'enviado',
        'entregado',
        'cancelado'
    ) DEFAULT 'pendiente',
    `direccion_envio` text DEFAULT NULL,
    `ciudad_envio` varchar(100) DEFAULT NULL,
    `telefono_envio` varchar(20) DEFAULT NULL,
    `notas` text DEFAULT NULL,
    `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_usuario` (`usuario_id`),
    KEY `idx_estado` (`estado`),
    CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- --------------------------------------------------------
-- Tabla: detalle_pedidos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `detalle_pedidos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `pedido_id` int(11) NOT NULL,
    `producto_id` int(11) NOT NULL,
    `cantidad` int(11) NOT NULL DEFAULT 1,
    `precio_unitario` decimal(10, 2) NOT NULL,
    `subtotal` decimal(10, 2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pedido` (`pedido_id`),
    KEY `idx_producto` (`producto_id`),
    CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- ============================================================
-- DATOS INICIALES
-- ============================================================
-- Categorías
INSERT INTO `categorias` (`nombre`, `slug`, `icono`, `descripcion`)
VALUES (
        'Motor',
        'motor',
        'fas fa-cogs',
        'Pistones, empaques, cadenas de distribución y componentes internos del motor.'
    ),
    (
        'Frenos',
        'frenos',
        'fas fa-compact-disc',
        'Pastillas, discos, calipers y sistemas de frenado completos.'
    ),
    (
        'Suspensión',
        'suspension',
        'fas fa-car-side',
        'Amortiguadores, rótulas, terminales y brazos de suspensión.'
    ),
    (
        'Eléctrico',
        'electrico',
        'fas fa-bolt',
        'Alternadores, motores de arranque, sensores y módulos electrónicos.'
    ),
    (
        'Carrocería',
        'carroceria',
        'fas fa-car',
        'Faros, espejos, parachoques y partes exteriores del vehículo.'
    ),
    (
        'Accesorios',
        'accesorios',
        'fas fa-toolbox',
        'Filtros, aceites, bujías y accesorios de mantenimiento general.'
    );
-- Usuario admin (password: Admin2024!)
INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol`)
VALUES (
        'Administrador',
        'admin@impordispac.com',
        '$2y$10$8KzQ1vN6rG3mH4pJ5wX7AOdYbCfE9L0hI2kM3nP4qR5sT6uV7wXy',
        'admin'
    );
-- Productos de ejemplo
INSERT INTO `productos` (
        `oem_code`,
        `nombre`,
        `slug`,
        `descripcion`,
        `descripcion_tecnica`,
        `marca`,
        `modelo`,
        `anio`,
        `categoria_id`,
        `precio`,
        `precio_oferta`,
        `stock`,
        `compatibilidad`,
        `destacado`
    )
VALUES (
        'TOY-1NZ-PST-01',
        'Kit de Pistones 1NZ-FE',
        'kit-pistones-1nz-fe',
        'Kit completo de 4 pistones forjados para motor 1NZ-FE. Incluye anillos y pasadores.',
        'Material: Aluminio forjado T6 | Diámetro: 75mm | Compresión: 10.5:1',
        'Toyota',
        'Yaris',
        '2006-2015',
        1,
        185.00,
        159.99,
        12,
        'Toyota Yaris 2006-2015, Toyota Echo 2003-2005',
        1
    ),
    (
        'BRK-CRM-PAD-02',
        'Pastillas de Freno Cerámicas Delanteras',
        'pastillas-freno-ceramicas',
        'Pastillas de freno cerámicas de alto rendimiento. Frenado suave y sin ruido.',
        'Material: Cerámica avanzada | Temperatura max: 600°C | Desgaste: Bajo',
        'Chevrolet',
        'Aveo',
        '2008-2017',
        2,
        45.00,
        NULL,
        30,
        'Chevrolet Aveo 2008-2017, Chevrolet Sail 2010-2016',
        1
    ),
    (
        'SUS-KYB-AMT-03',
        'Amortiguadores KYB Gas-a-Just',
        'amortiguadores-kyb-gas',
        'Par de amortiguadores traseros de gas. Mejora la estabilidad y confort de manejo.',
        'Tipo: Gas presurizado | Longitud: 345mm | Recorrido: 200mm',
        'Hyundai',
        'Accent',
        '2012-2020',
        3,
        120.00,
        99.90,
        8,
        'Hyundai Accent 2012-2020, Kia Rio 2012-2017',
        1
    ),
    (
        'ELC-ALT-DEN-04',
        'Alternador Denso Remanufacturado',
        'alternador-denso-reman',
        'Alternador remanufacturado de fábrica con garantía de 12 meses. Rendimiento OEM.',
        'Amperaje: 90A | Voltaje: 12V | Polea: 6 canales',
        'Nissan',
        'Sentra',
        '2013-2019',
        4,
        210.00,
        NULL,
        5,
        'Nissan Sentra 2013-2019, Nissan Versa 2014-2019',
        1
    ),
    (
        'CAR-FAR-LED-05',
        'Faro LED Proyector Derecho',
        'faro-led-proyector-der',
        'Faro delantero derecho tipo proyector con tecnología LED integrada. Plug & Play.',
        'Tipo: Proyector LED | Lumens: 6000lm | Color: 6000K | Certificación: DOT/SAE',
        'Kia',
        'Sportage',
        '2016-2021',
        5,
        320.00,
        289.00,
        4,
        'Kia Sportage 2016-2021',
        1
    ),
    (
        'ACC-FLT-ACE-06',
        'Filtro de Aceite Premium',
        'filtro-aceite-premium',
        'Filtro de aceite de alta eficiencia. Retiene partículas desde 10 micrones.',
        'Eficiencia: 99.6% | Rosca: M20x1.5 | Presión bypass: 1.0 bar',
        'Universal',
        'Varios',
        '2000-2025',
        6,
        12.50,
        NULL,
        100,
        'Compatible con la mayoría de motores de 4 cilindros',
        0
    );
COMMIT;