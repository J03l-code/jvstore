-- ============================================================
-- JVSTORE - Base de Datos Completa
-- Versión 2.0 - Productos + Servicios + Dashboard Control
-- ============================================================
SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

CREATE DATABASE IF NOT EXISTS `jvstore_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `jvstore_db`;

-- ============================================================
-- TABLA: usuarios (Admin/Staff)
-- ============================================================
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`         INT(11) NOT NULL AUTO_INCREMENT,
    `nombre`     VARCHAR(100) NOT NULL,
    `email`      VARCHAR(100) NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL,
    `rol`        ENUM('admin','staff') DEFAULT 'admin',
    `avatar`     VARCHAR(255) DEFAULT NULL,
    `activo`     TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: clientes
-- ============================================================
CREATE TABLE IF NOT EXISTS `clientes` (
    `id`         INT(11) NOT NULL AUTO_INCREMENT,
    `nombre`     VARCHAR(100) NOT NULL,
    `email`      VARCHAR(100) NOT NULL UNIQUE,
    `password`   VARCHAR(255) DEFAULT NULL,
    `telefono`   VARCHAR(20) DEFAULT NULL,
    `direccion`  TEXT DEFAULT NULL,
    `avatar`     VARCHAR(255) DEFAULT NULL,
    `google_id`  VARCHAR(100) DEFAULT NULL,
    `carrito`    JSON DEFAULT NULL,
    `rol`        VARCHAR(20) DEFAULT 'cliente',
    `activo`     TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: categorias (Productos Y Servicios)
-- ============================================================
CREATE TABLE IF NOT EXISTS `categorias` (
    `id`          INT(11) NOT NULL AUTO_INCREMENT,
    `nombre`      VARCHAR(100) NOT NULL,
    `slug`        VARCHAR(100) NOT NULL UNIQUE,
    `descripcion` TEXT DEFAULT NULL,
    `icono`       VARCHAR(100) DEFAULT 'fas fa-box',
    `imagen`      VARCHAR(255) DEFAULT NULL,
    `color`       VARCHAR(20) DEFAULT '#1B2A4A',
    `tipo`        ENUM('producto','servicio','ambos') DEFAULT 'producto',
    `orden`       INT(11) DEFAULT 0,
    `activo`      TINYINT(1) DEFAULT 1,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: productos
-- ============================================================
CREATE TABLE IF NOT EXISTS `productos` (
    `id`            INT(11) NOT NULL AUTO_INCREMENT,
    `categoria_id`  INT(11) DEFAULT NULL,
    `nombre`        VARCHAR(200) NOT NULL,
    `slug`          VARCHAR(200) DEFAULT NULL,
    `descripcion`   TEXT DEFAULT NULL,
    `descripcion_corta` VARCHAR(300) DEFAULT NULL,
    `precio`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `precio_oferta` DECIMAL(10,2) DEFAULT NULL,
    `stock`         INT(11) DEFAULT 0,
    `sku`           VARCHAR(100) DEFAULT NULL,
    `marca`         VARCHAR(100) DEFAULT NULL,
    `modelo`        VARCHAR(100) DEFAULT NULL,
    `imagen_url`    VARCHAR(255) DEFAULT NULL,
    `galeria`       JSON DEFAULT NULL,
    `destacado`     TINYINT(1) DEFAULT 0,
    `nuevo`         TINYINT(1) DEFAULT 0,
    `activo`        TINYINT(1) DEFAULT 1,
    `vistas`        INT(11) DEFAULT 0,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_prod_cat` (`categoria_id`),
    CONSTRAINT `fk_prod_cat` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: servicios
-- ============================================================
CREATE TABLE IF NOT EXISTS `servicios` (
    `id`                INT(11) NOT NULL AUTO_INCREMENT,
    `categoria_id`      INT(11) DEFAULT NULL,
    `titulo`            VARCHAR(200) NOT NULL,
    `slug`              VARCHAR(200) DEFAULT NULL,
    `descripcion`       TEXT DEFAULT NULL,
    `descripcion_corta` VARCHAR(300) DEFAULT NULL,
    `precio_desde`      DECIMAL(10,2) DEFAULT NULL,
    `icono`             VARCHAR(100) DEFAULT 'fas fa-cog',
    `imagen_url`        VARCHAR(255) DEFAULT NULL,
    `caracteristicas`   JSON DEFAULT NULL,
    `destacado`         TINYINT(1) DEFAULT 0,
    `orden`             INT(11) DEFAULT 0,
    `activo`            TINYINT(1) DEFAULT 1,
    `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_serv_cat` (`categoria_id`),
    CONSTRAINT `fk_serv_cat` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: banners (Hero sliders)
-- ============================================================
CREATE TABLE IF NOT EXISTS `banners` (
    `id`          INT(11) NOT NULL AUTO_INCREMENT,
    `titulo`      VARCHAR(200) DEFAULT NULL,
    `subtitulo`   VARCHAR(300) DEFAULT NULL,
    `imagen_url`  VARCHAR(255) NOT NULL,
    `enlace`      VARCHAR(255) DEFAULT NULL,
    `boton_texto` VARCHAR(100) DEFAULT 'Ver más',
    `posicion`    ENUM('principal','secundario','mini') DEFAULT 'principal',
    `orden`       INT(11) DEFAULT 0,
    `activo`      TINYINT(1) DEFAULT 1,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: pedidos
-- ============================================================
CREATE TABLE IF NOT EXISTS `pedidos` (
    `id`              INT(11) NOT NULL AUTO_INCREMENT,
    `cliente_id`      INT(11) DEFAULT NULL,
    `codigo`          VARCHAR(20) NOT NULL UNIQUE,
    `nombre_cliente`  VARCHAR(100) NOT NULL,
    `email_cliente`   VARCHAR(100) NOT NULL,
    `telefono`        VARCHAR(20) DEFAULT NULL,
    `direccion`       TEXT DEFAULT NULL,
    `items`           JSON NOT NULL,
    `subtotal`        DECIMAL(10,2) NOT NULL,
    `iva`             DECIMAL(10,2) DEFAULT 0.00,
    `costo_envio`     DECIMAL(10,2) DEFAULT 0.00,
    `total`           DECIMAL(10,2) NOT NULL,
    `estado`          ENUM('pendiente','pagado','procesando','enviado','entregado','cancelado') DEFAULT 'pendiente',
    `metodo_pago`     VARCHAR(50) DEFAULT 'transferencia',
    `notas`           TEXT DEFAULT NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_ped_cli` (`cliente_id`),
    CONSTRAINT `fk_ped_cli` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: mensajes (Formulario de contacto)
-- ============================================================
CREATE TABLE IF NOT EXISTS `mensajes` (
    `id`       INT(11) NOT NULL AUTO_INCREMENT,
    `nombre`   VARCHAR(100) NOT NULL,
    `email`    VARCHAR(100) NOT NULL,
    `telefono` VARCHAR(20) DEFAULT NULL,
    `asunto`   VARCHAR(200) DEFAULT NULL,
    `mensaje`  TEXT NOT NULL,
    `leido`    TINYINT(1) DEFAULT 0,
    `fecha`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLA: configuracion (Settings del sitio desde dashboard)
-- ============================================================
CREATE TABLE IF NOT EXISTS `configuracion` (
    `clave`  VARCHAR(100) NOT NULL,
    `valor`  TEXT DEFAULT NULL,
    `grupo`  VARCHAR(50) DEFAULT 'general',
    PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

-- Admin por defecto (password: Admin2026!)
INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol`) VALUES
('Administrador JV', 'admin@jvstore.com', '$2y$10$TKh8H1.PfMaHGLqRajyB8.MwSUE.XEQoFm7JX67JhixkCr2MuCuva', 'admin');

-- Categorías de Productos
INSERT INTO `categorias` (`nombre`, `slug`, `descripcion`, `icono`, `color`, `tipo`, `orden`, `activo`) VALUES
('Electrónica',    'electronica',    'Dispositivos y gadgets electrónicos', 'fas fa-laptop',        '#1B2A4A', 'producto', 1, 1),
('Hogar',          'hogar',          'Artículos para el hogar',             'fas fa-home',          '#1B2A4A', 'producto', 2, 1),
('Moda',           'moda',           'Ropa y accesorios de moda',           'fas fa-tshirt',        '#1B2A4A', 'producto', 3, 1),
('Deportes',       'deportes',       'Equipamiento deportivo',              'fas fa-dumbbell',      '#1B2A4A', 'producto', 4, 1),
('Mascotas',       'mascotas',       'Productos para mascotas',             'fas fa-paw',           '#1B2A4A', 'producto', 5, 1),
('Herramientas',   'herramientas',   'Herramientas y construcción',         'fas fa-tools',         '#1B2A4A', 'producto', 6, 1);

-- Categorías de Servicios
INSERT INTO `categorias` (`nombre`, `slug`, `descripcion`, `icono`, `color`, `tipo`, `orden`, `activo`) VALUES
('Consultoría',    'consultoria',    'Servicios de consultoría empresarial','fas fa-briefcase',     '#2C4A7C', 'servicio', 7, 1),
('Logística',      'logistica',      'Envíos y gestión logística',          'fas fa-shipping-fast', '#2C4A7C', 'servicio', 8, 1),
('Soporte',        'soporte',        'Servicio de soporte técnico',         'fas fa-headset',       '#2C4A7C', 'servicio', 9, 1);

-- Productos de muestra
INSERT INTO `productos` (`categoria_id`, `nombre`, `slug`, `descripcion_corta`, `precio`, `precio_oferta`, `stock`, `sku`, `marca`, `imagen_url`, `destacado`, `nuevo`, `activo`) VALUES
(1, 'Audífonos Bluetooth Premium',  'audifonos-bluetooth-premium',  'Sonido envolvente 360°, batería 40h', 89.99, 69.99, 25, 'ELEC-001', 'SoundMax', NULL, 1, 1, 1),
(1, 'Smartwatch Serie X',           'smartwatch-serie-x',           'Monitor cardíaco, GPS, resistente al agua', 159.00, NULL, 12, 'ELEC-002', 'TechWear', NULL, 1, 0, 1),
(1, 'Cargador Inalámbrico 15W',     'cargador-inalambrico-15w',     'Carga rápida compatible Qi universal', 24.99, 19.99, 50, 'ELEC-003', 'ChargePro', NULL, 0, 1, 1),
(2, 'Set de Sábanas Premium',       'set-sabanas-premium',          'Algodón 100%, 500 hilos, queen size', 45.00, NULL, 30, 'HOG-001', 'DreamHome', NULL, 1, 0, 1),
(2, 'Cafetera Automática',          'cafetera-automatica',          'Espresso y americano, 12 tazas',  75.00, 59.00, 8, 'HOG-002', 'BrewMaster', NULL, 1, 1, 1),
(3, 'Zapatillas Running Pro',       'zapatillas-running-pro',       'Suela antideslizante, ultra ligeras', 65.00, NULL, 20, 'MOD-001', 'SpeedRun', NULL, 1, 0, 1),
(4, 'Mancuernas Ajustables 20kg',   'mancuernas-ajustables-20kg',   'Set completo 2kg a 20kg por mancuerna', 120.00, 95.00, 15, 'DEP-001', 'FitPro', NULL, 0, 1, 1),
(5, 'Comedero Automático Mascotas', 'comedero-automatico-mascotas', 'Dispensador con timer, 4L capacidad', 38.00, NULL, 22, 'MAS-001', 'PetCare', NULL, 1, 0, 1);

-- Servicios de muestra
INSERT INTO `servicios` (`categoria_id`, `titulo`, `slug`, `descripcion_corta`, `precio_desde`, `icono`, `imagen_url`, `destacado`, `orden`, `activo`) VALUES
(7, 'Consultoría Empresarial',    'consultoria-empresarial',    'Asesoría personalizada para tu negocio', 150.00, 'fas fa-briefcase', NULL, 1, 1, 1),
(8, 'Logística y Distribución',   'logistica-distribucion',     'Gestión completa de envíos nacionales',  NULL,   'fas fa-truck',      NULL, 1, 2, 1),
(9, 'Soporte Técnico 24/7',       'soporte-tecnico',            'Atención especializada para tus equipos', 50.00, 'fas fa-headset',    NULL, 1, 3, 1),
(7, 'Importación Directa',        'importacion-directa',        'Traemos productos desde el exterior',    NULL,   'fas fa-globe',      NULL, 0, 4, 1);

-- Banners iniciales
INSERT INTO `banners` (`titulo`, `subtitulo`, `imagen_url`, `enlace`, `boton_texto`, `posicion`, `orden`, `activo`) VALUES
('Tu Tienda Online de Confianza', 'Productos y servicios de calidad con entrega a todo el país', 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1920&q=80', 'tienda.php', 'Ver Productos', 'principal', 1, 1),
('Servicios Profesionales',       'Asesoría y soporte para tu negocio',                          'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1920&q=80', 'servicios.php', 'Ver Servicios', 'principal', 2, 1),
('Nuevas Llegadas',               'Descubre los productos más recientes de nuestra tienda',       'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1920&q=80', 'tienda.php?nuevo=1', 'Ver Novedades', 'principal', 3, 1);

-- Configuración inicial del sitio
INSERT INTO `configuracion` (`clave`, `valor`, `grupo`) VALUES
('site_name',       'JV Ventas Online',              'general'),
('site_description','Tu tienda online de confianza - Productos y Servicios', 'general'),
('whatsapp',        '5930900000000',                 'contacto'),
('email_contacto',  'contacto@jvstore.com',          'contacto'),
('facebook',        'https://facebook.com/jvstore',  'redes'),
('instagram',       'https://instagram.com/jvstore', 'redes'),
('tiktok',          '',                              'redes'),
('iva_porcentaje',  '15',                            'pagos'),
('costo_envio',     '5.00',                          'pagos'),
('envio_gratis_desde', '100.00',                     'pagos'),
('moneda',          '$',                             'pagos'),
('hero_badge_texto','Envío Gratis en compras +$100', 'home'),
('mostrar_servicios','1',                            'home'),
('mostrar_productos','1',                            'home');

SET foreign_key_checks = 1;
