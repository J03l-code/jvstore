<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/config.php';

echo "<h3>Test de Conexión a Base de Datos</h3>";
echo "Host: " . DB_HOST . "<br>";
echo "Base de Datos: " . DB_NAME . "<br>";
echo "Usuario: " . DB_USER . "<br>";
echo "Contraseña: " . (empty(DB_PASS) ? "Vacía" : "******** (Longitud: " . strlen(DB_PASS) . ")") . "<br><br>";

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<strong style='color:green;'>✅ ¡Conexión exitosa a la base de datos!</strong><br>";
    
    // Probar si las tablas existen
    $stmt = $pdo->query("SHOW TABLES");
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<br><strong>Tablas encontradas (" . count($tablas) . "):</strong><br>";
    foreach($tablas as $t) echo "- $t <br>";
    
} catch (PDOException $e) {
    echo "<strong style='color:red;'>❌ Error de Conexión:</strong><br>";
    echo "<code>" . htmlspecialchars($e->getMessage()) . "</code><br><br>";
    
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<p><strong>Posible causa:</strong> La contraseña es incorrecta, o el usuario no tiene permisos asignados a esta base de datos en Hostinger.</p>";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<p><strong>Posible causa:</strong> El nombre de la base de datos está mal escrito. En Hostinger recuerda incluir el prefijo u434851126_.</p>";
    }
}
