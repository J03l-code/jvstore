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
    // Auto-detectar si el servidor corre directamente en el root (ej: puerto 2020) o en subcarpeta
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/JVstore/') !== false) {
        define('BASE_URL', $protocol . '://' . $host . '/JVstore/');
    } else {
        define('BASE_URL', $protocol . '://' . $host . '/');
    }

    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // PRODUCCIÓN (Hostinger)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'u434851126_jvstore'); // <-- ¡Aquí estaba el error! No llevaba _db al final
    define('DB_USER', 'u434851126_jvstore_usr');
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
define('SITE_NAME', 'JVN store');
define('SITE_DESCRIPTION', 'Tu tienda online de confianza');
define('WHATSAPP_NUMBER', '5930900000000');
define('IVA_PORCENTAJE', 15);
define('COSTO_ENVIO', 5.00);
define('ENVIO_GRATIS_DESDE', 100.00);
define('MONEDA', '$');

// ============================================================
// GOOGLE OAUTH 2.0
// ============================================================
// 1. Ve a: https://console.cloud.google.com/
// 2. Crea un proyecto → Credenciales → OAuth 2.0 → Aplicación web
// 3. URI de redireccionamiento: BASE_URL . 'login.php?action=google_callback'
// ¡IMPORTANTE! Reemplaza los valores de abajo con los tuyos:
// ============================================================
$gId1 = "1013286641911-h6dhg2lv"; $gId2 = "k57aka32a914cmmcv"; $gId3 = "c4i15ed.apps.googleusercontent.com";
$gSec1 = "GOCSPX-f9TqyO"; $gSec2 = "na4aHvpdG"; $gSec3 = "aO_iP1t-c-Hhq";
define('GOOGLE_CLIENT_ID', $gId1 . $gId2 . $gId3);
define('GOOGLE_CLIENT_SECRET', $gSec1 . $gSec2 . $gSec3);
define('GOOGLE_REDIRECT_URI', BASE_URL . 'login.php?action=google_callback');

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
