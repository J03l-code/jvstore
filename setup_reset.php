<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$db = getDB();

echo "<h2>Actualizando tablas para recuperación de contraseña...</h2>";

try {
    // Agregar columnas a clientes
    $sqlClientes = "ALTER TABLE clientes 
                    ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL AFTER direccion,
                    ADD COLUMN reset_expires DATETIME DEFAULT NULL AFTER reset_token";
    $db->exec($sqlClientes);
    echo "<p>✅ Tabla 'clientes' actualizada.</p>";
} catch (Exception $e) {
    echo "<p>ℹ️ Nota sobre clientes: " . $e->getMessage() . "</p>";
}

try {
    // Agregar columnas a usuarios (staff)
    $sqlUsuarios = "ALTER TABLE usuarios 
                    ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL,
                    ADD COLUMN reset_expires DATETIME DEFAULT NULL";
    $db->exec($sqlUsuarios);
    echo "<p>✅ Tabla 'usuarios' actualizada.</p>";
} catch (Exception $e) {
    echo "<p>ℹ️ Nota sobre usuarios: " . $e->getMessage() . "</p>";
}

echo "<h3>Listo. columnas agregadas.</h3>";
?>