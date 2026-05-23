<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $db = getDB();
    
    // Disable foreign key checks temporarily
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    // Drop bad tables if they exist
    $db->exec("DROP TABLE IF EXISTS `detalle_pedidos`;");
    $db->exec("DROP TABLE IF EXISTS `pedidos`;");
    
    // Create the correct pedidos table
    $db->exec("CREATE TABLE `pedidos` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `usuario_id` int(11) NOT NULL,
        `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
        `iva` decimal(10,2) NOT NULL DEFAULT '0.00',
        `envio` decimal(10,2) NOT NULL DEFAULT '0.00',
        `total` decimal(10,2) NOT NULL DEFAULT '0.00',
        `estado` varchar(50) DEFAULT 'pendiente',
        `direccion_envio` text,
        `ciudad_envio` varchar(100) DEFAULT NULL,
        `telefono` varchar(20) DEFAULT NULL,
        `notas` text,
        `fecha` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_usuario` (`usuario_id`),
        CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Create the correct detalle_pedidos table
    $db->exec("CREATE TABLE `detalle_pedidos` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `pedido_id` int(11) NOT NULL,
        `producto_id` int(11) NOT NULL,
        `cantidad` int(11) NOT NULL DEFAULT '1',
        `precio_unitario` decimal(10,2) NOT NULL,
        `subtotal` decimal(10,2) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_pedido` (`pedido_id`),
        KEY `idx_producto` (`producto_id`),
        CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Ensure admin user exists with correct password
    $hash = password_hash('Admin2026!', PASSWORD_DEFAULT);
    $stmt = $db->query("SELECT id FROM usuarios WHERE email = 'admin@jvstore.com'");
    if (!$stmt->fetch()) {
        $db->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)")
           ->execute(['Administrador JV', 'admin@jvstore.com', $hash, 'admin']);
    } else {
        $db->prepare("UPDATE usuarios SET password = ? WHERE email = 'admin@jvstore.com'")->execute([$hash]);
    }
    
    // Check if 'telefono' column exists in pedidos, if not, add it (in case it was created with another name)
    try {
        $db->exec("ALTER TABLE pedidos ADD COLUMN telefono VARCHAR(20) DEFAULT NULL AFTER direccion_envio");
    } catch (Exception $e) {}
    
    // Check if 'iva' column exists in pedidos
    try {
        $db->exec("ALTER TABLE pedidos ADD COLUMN iva DECIMAL(10,2) NOT NULL DEFAULT '0.00' AFTER subtotal");
    } catch (Exception $e) {}

    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "<div style='font-family:sans-serif; text-align:center; padding: 50px;'>";
    echo "<h1 style='color:green;'>✅ Base de datos reparada con éxito</h1>";
    echo "<p>Las tablas <b>pedidos</b> y <b>detalle_pedidos</b> han sido restauradas correctamente.</p>";
    echo "<p>Las credenciales del dashboard son:</p>";
    echo "<h3>admin@jvstore.com<br>Admin2026!</h3>";
    echo "<br><a href='" . BASE_URL . "login.php' style='display:inline-block; padding: 15px 30px; background: #004aad; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Ir al Dashboard</a>";
    echo "<p style='color:red; margin-top:20px;'>⚠️ Por favor ELIMINA este archivo (fix_db.php) después de usarlo.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h1 style='color:red;'>❌ Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
