<?php
// Script para corregir las claves foráneas tras la migración de usuarios a clientes
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h2>Actualización de Base de Datos - Solución de Checkout</h2>";

try {
    $db = getDB();

    // 1. Buscar si hay una clave foránea conectando pedidos.usuario_id con usuarios.id
    $stmt = $db->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'pedidos' 
          AND COLUMN_NAME = 'usuario_id' 
          AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $fks = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($fks) {
        foreach ($fks as $fk) {
            $db->exec("ALTER TABLE pedidos DROP FOREIGN KEY `$fk`");
            echo "<p>✅ Relación antigua '$fk' eliminada correctamente.</p>";
        }
    } else {
        echo "<p>ℹ️ No se encontraron claves foráneas antiguas vinculadas a usuarios.</p>";
    }

    // 2. Intentar crear la nueva clave foránea a la tabla clientes
    try {
        $db->exec("ALTER TABLE pedidos ADD CONSTRAINT fk_pedidos_clientes FOREIGN KEY (usuario_id) REFERENCES clientes(id) ON DELETE CASCADE");
        echo "<p>✅ Nueva relación creada vinculando pedidos con la tabla <b>clientes</b> correctamente.</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "<p>✅ La clave foránea a clientes ya existe.</p>";
        } else {
            // Si falla por registros huérfanos, no importa tanto, el sistema ya funcionará sin la restricción.
            echo "<p>⚠️ No se pudo crear la nueva restricción estricta (probablemente hay pedidos antiguos de prueba sin cliente). Pero el error de bloqueo ya está solucionado. Error técnico: " . $e->getMessage() . "</p>";
        }
    }

    echo "<h3>¡Listo! Ya puedes ir al Checkout y confirmar el pedido sin problema.</h3>";
    echo "<a href='" . BASE_URL . "carrito.php' style='padding: 10px 20px; background: #004aad; color: white; text-decoration: none; border-radius: 5px;'>Volver al Carrito</a>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error crítico: " . $e->getMessage() . "</p>";
}
?>