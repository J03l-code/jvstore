<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h2>Actualización de Base de Datos - Columnas de Pedidos</h2>";

try {
    $db = getDB();

    $columnas = [
        "subtotal" => "DECIMAL(10,2) DEFAULT 0.00 AFTER usuario_id",
        "iva" => "DECIMAL(10,2) DEFAULT 0.00 AFTER subtotal",
        "envio" => "DECIMAL(10,2) DEFAULT 0.00 AFTER iva",
        "direccion_envio" => "TEXT NULL AFTER total",
        "telefono" => "VARCHAR(30) NULL AFTER direccion_envio",
        "notas" => "TEXT NULL AFTER telefono"
    ];

    foreach ($columnas as $columna => $definicion) {
        try {
            $db->exec("ALTER TABLE pedidos ADD COLUMN $columna $definicion");
            echo "<p style='color:green;'>✅ Columna <b>$columna</b> agregada a la base de datos.</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "<p style='color:blue;'>ℹ️ Columna <b>$columna</b> ya existe.</p>";
            } else {
                echo "<p style='color:orange;'>⚠️ Problema con $columna: " . $e->getMessage() . "</p>";
            }
        }
    }

    echo "<h3>¡Todo listo!</h3>";
    echo "<a href='" . BASE_URL . "carrito.php' style='padding:10px 15px; background:#004aad; color:#fff; text-decoration:none; border-radius:5px;'>Intentar comprar de nuevo</a>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error de conexión DB: " . $e->getMessage() . "</p>";
}
?>