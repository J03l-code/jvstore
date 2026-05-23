<?php
/**
 * JVSTORE - Configuración Global
 * Versión 2.0
 */

// ============================================================
// ENTORNO: Local vs Producción
// ============================================================
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_local = in_array($host, ['127.0.0.1', '::1', 'localhost']) 
            || strpos($host, 'localhost:') === 0;

if ($is_local) {
    // LOCAL
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'jvstore_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    define('BASE_URL', $protocol . '://' . $host . '/JVstore/');

    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // PRODUCCIÓN (Hostinger)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'u434851126_jvstore_db'); // Nombre de BD en Hostinger
    define('DB_USER', 'u434851126_jvstore_usr'); // Usuario de BD en Hostinger
    define('DB_PASS', 'Jvstore2026!');

    define('BASE_URL', 'https://' . $host . '/');

    error_reporting(0);
    ini_set('display_errors', 0);
}

define('DB_CHARSET', 'utf8mb4');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');

// ============================================================
// CONSTANTES DEL SITIO (cargadas desde BD en runtime)
// ============================================================
define('SITE_NAME',        'JV Ventas Online');
define('SITE_DESCRIPTION', 'Tu tienda online de confianza');
define('WHATSAPP_NUMBER',  '5930900000000');
define('IVA_PORCENTAJE',   15);
define('COSTO_ENVIO',      5.00);
define('ENVIO_GRATIS_DESDE', 100.00);
define('MONEDA',           '$');

// Google OAuth (opcional)
define('GOOGLE_CLIENT_ID',     '');
define('GOOGLE_CLIENT_SECRET', '');
define('GOOGLE_REDIRECT_URI',  BASE_URL . 'login.php?action=google_callback');

// ============================================================
// SESIÓN
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Guayaquil');

/**
 * Obtiene configuración dinámica desde la BD
 */
function getSiteConfig($clave, $default = '')
{
    static $config = null;
    if ($config === null) {
        try {
            $db = getDB();
            $rows = $db->query("SELECT clave, valor FROM configuracion")->fetchAll(PDO::FETCH_KEY_PAIR);
            $config = $rows ?: [];
        } catch (Exception $e) {
            $config = [];
        }
    }
    return $config[$clave] ?? $default;
}
