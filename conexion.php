<?php
// Credenciales de Base de Datos
// Detectar entono
$whitelist_local = array('127.0.0.1', '::1', 'localhost');
$is_local = in_array($_SERVER['HTTP_HOST'] ?? 'localhost', $whitelist_local);

if ($is_local) {
    // --- CREDENCIALES LOCALES ---
    $host = "localhost";
    $db = "u434851126_impordispacec";
    $user = "u434851126_admin";
    $pass = "Impordispac2026";
} else {
    // --- CREDENCIALES HOSTINGER (PRODUCCIÓN) ---
    // Asegúrate de que estos datos sean los CORRECTOS de tu hPanel
    $host = "localhost";
    $db = "u434851126_impordispac";
    $user = "u434851126_impordispac_us";
    $pass = "Impordispac2026";
}

// En Hostinger, deberás cambiar esto manualmente o subir una versión diferente.
// Ver GUIA_DESPLIEGUE_HOSTINGER.md para más detalles.

$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Si falla la conexión, mostramos un mensaje amigable (o nada en producción)
    // echo "Error de conexión: " . $e->getMessage();
    $pdo = null;
}
?>