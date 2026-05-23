<?php
// Credenciales de Base de Datos
// Para desarrollo local (XAMPP/WAMP por defecto):
$host = "localhost";
$db = "impordispac_db";
$user = "root";
$pass = ""; // Generalmente vacío en XAMPP

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