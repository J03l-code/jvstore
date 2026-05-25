<?php
/**
 * IMPORDISPAC - Conexión a Base de Datos (PDO Singleton)
 */
require_once __DIR__ . '/config.php';

function getDB()
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Auto-reparación silenciosa del esquema
            try {
                // 1. Columnas tipo y atributos en categorias
                $q1 = $pdo->query("SHOW COLUMNS FROM `categorias` LIKE 'tipo'");
                if (!$q1->fetch()) {
                    $pdo->exec("ALTER TABLE `categorias` ADD COLUMN `tipo` ENUM('producto','servicio','ambos') DEFAULT 'producto'");
                }
                $q2 = $pdo->query("SHOW COLUMNS FROM `categorias` LIKE 'atributos'");
                if (!$q2->fetch()) {
                    $pdo->exec("ALTER TABLE `categorias` ADD COLUMN `atributos` TEXT DEFAULT NULL");
                }
                
                // 2. Tabla servicios
                $pdo->exec("CREATE TABLE IF NOT EXISTS `servicios` (
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
                    KEY `fk_serv_cat` (`categoria_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                
                $q3 = $pdo->query("SHOW COLUMNS FROM `servicios` LIKE 'caracteristicas'");
                if (!$q3->fetch()) {
                    $pdo->exec("ALTER TABLE `servicios` ADD COLUMN `caracteristicas` JSON DEFAULT NULL");
                }
                
                $q4 = $pdo->query("SHOW COLUMNS FROM `servicios` LIKE 'parent_id'");
                if (!$q4->fetch()) {
                    $pdo->exec("ALTER TABLE `servicios` ADD COLUMN `parent_id` INT DEFAULT NULL");
                }
                
                $q_serv_gal = $pdo->query("SHOW COLUMNS FROM `servicios` LIKE 'galeria'");
                if (!$q_serv_gal->fetch()) {
                    $pdo->exec("ALTER TABLE `servicios` ADD COLUMN `galeria` TEXT DEFAULT NULL");
                }
                
                $q_prod_dc = $pdo->query("SHOW COLUMNS FROM `productos` LIKE 'descripcion_corta'");
                if (!$q_prod_dc->fetch()) {
                    $pdo->exec("ALTER TABLE `productos` ADD COLUMN `descripcion_corta` VARCHAR(255) DEFAULT NULL");
                }
                
                // 3. Tabla marcas
                $pdo->exec("CREATE TABLE IF NOT EXISTS `marcas` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `nombre` VARCHAR(100) NOT NULL,
                    `imagen_url` VARCHAR(255) NOT NULL,
                    `enlace` VARCHAR(255) DEFAULT NULL,
                    `orden` INT DEFAULT 0,
                    `activo` TINYINT(1) DEFAULT 1,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                // 4. Categoría y servicios por defecto si están vacíos
                $cntCats = $pdo->query("SELECT COUNT(*) FROM `categorias` WHERE `tipo` IN ('servicio','ambos')")->fetchColumn();
                if ($cntCats == 0) {
                    $pdo->exec("INSERT INTO `categorias` (`nombre`, `slug`, `descripcion`, `icono`, `color`, `tipo`, `orden`, `activo`) VALUES
                    ('Servicios Generales', 'servicios-generales', 'Servicios profesionales y técnicos', 'fas fa-cogs', '#1B2A4A', 'servicio', 10, 1)");
                }
                
                $cntServ = $pdo->query("SELECT COUNT(*) FROM `servicios`")->fetchColumn();
                if ($cntServ == 0) {
                    // Obtener el ID de la primera categoría de servicios
                    $catId = $pdo->query("SELECT id FROM `categorias` WHERE `tipo` IN ('servicio','ambos') LIMIT 1")->fetchColumn();
                    if ($catId) {
                        $pdo->exec("INSERT INTO `servicios` (`categoria_id`, `titulo`, `slug`, `descripcion`, `descripcion_corta`, `precio_desde`, `icono`, `destacado`, `orden`, `activo`) VALUES
                        ($catId, 'Soporte Técnico Especializado', 'soporte-tecnico-especializado', 'Ofrecemos soporte técnico presencial y remoto para todo tipo de equipos y sistemas. Diagnóstico preciso, repuestos de calidad y garantía.', 'Soporte técnico profesional y rápido para tus equipos.', 35.00, 'fas fa-headset', 1, 1, 1),
                        ($catId, 'Instalación y Configuración', 'instalacion-configuracion', 'Servicio profesional de instalación, montaje y configuración de sistemas, hardware y software a domicilio o para empresas.', 'Instalación garantizada por expertos.', 50.00, 'fas fa-tools', 1, 2, 1)");
                    }
                }
            } catch (Throwable $t) {
                error_log("Auto-migración DB fallida: " . $t->getMessage());
            }
        } catch (PDOException $e) {
            // En producción, loguear el error en vez de mostrarlo
            error_log("Error de conexión DB: " . $e->getMessage());

            if (defined('IS_API') && IS_API) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
                exit;
            }

            die("Error de conexión a la base de datos. Intente más tarde.");
        }
    }

    return $pdo;
}
