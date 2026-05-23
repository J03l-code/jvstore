<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h2>Actualización de Base de Datos - Carrito Persistente</h2>";

try {
    $db = getDB();

    try {
        $db->exec("ALTER TABLE clientes ADD COLUMN carrito TEXT NULL AFTER direccion");
        echo "<p style='color:green;'>✅ Columna <b>carrito</b> agregada a la tabla clientes.</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color:blue;'>ℹ️ La columna <b>carrito</b> ya existe en clientes.</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ Error: " . $e->getMessage() . "</p>";
        }
    }

    echo "<h3>¡Actualización completada!</h3>";
    echo "<p>Cierra esta pestaña y continúa probando en tu tienda.</p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error de conexión DB: " . $e->getMessage() . "</p>";
}
?>