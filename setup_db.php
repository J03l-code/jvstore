<?php
/**
 * JVSTORE - Script de Migración / Reparación de BD
 * Ejecutar UNA VEZ: https://tudominio.com/setup_db.php
 * ⚠️ ELIMINAR ESTE ARCHIVO después de ejecutar
 */

// Seguridad básica
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'jvstore2026') {
    die('<h2>Agrega ?confirm=jvstore2026 a la URL para ejecutar</h2>');
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');
$db = getDB();
$ok = [];
$err = [];

function runSQL($db, $sql, $label) {
    global $ok, $err;
    try {
        $db->exec($sql);
        $ok[] = "✅ $label";
    } catch (Throwable $e) {
        $err[] = "⚠️ $label: " . $e->getMessage();
    }
}

// ── 1. Tabla clientes — columnas extra ───────────────────────────────────────
runSQL($db,
    "ALTER TABLE `clientes` ADD COLUMN IF NOT EXISTS `carrito` JSON DEFAULT NULL",
    "clientes.carrito"
);
runSQL($db,
    "ALTER TABLE `clientes` ADD COLUMN IF NOT EXISTS `google_id` VARCHAR(100) DEFAULT NULL",
    "clientes.google_id"
);
runSQL($db,
    "ALTER TABLE `clientes` ADD COLUMN IF NOT EXISTS `avatar` VARCHAR(255) DEFAULT NULL",
    "clientes.avatar"
);
runSQL($db,
    "ALTER TABLE `clientes` ADD COLUMN IF NOT EXISTS `rol` VARCHAR(20) DEFAULT 'cliente'",
    "clientes.rol"
);
runSQL($db,
    "ALTER TABLE `clientes` ADD COLUMN IF NOT EXISTS `activo` TINYINT(1) DEFAULT 1",
    "clientes.activo"
);

// ── 2. Tabla pedidos — verificar columnas clave ───────────────────────────────
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `cliente_id` INT(11) DEFAULT NULL",
    "pedidos.cliente_id"
);
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `codigo` VARCHAR(20) DEFAULT NULL",
    "pedidos.codigo"
);
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `nombre_cliente` VARCHAR(100) DEFAULT ''",
    "pedidos.nombre_cliente"
);
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `email_cliente` VARCHAR(100) DEFAULT ''",
    "pedidos.email_cliente"
);
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `telefono` VARCHAR(20) DEFAULT NULL",
    "pedidos.telefono"
);
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `direccion` TEXT DEFAULT NULL",
    "pedidos.direccion"
);
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `items` JSON DEFAULT NULL",
    "pedidos.items (JSON)"
);
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `subtotal` DECIMAL(10,2) DEFAULT 0.00",
    "pedidos.subtotal"
);
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `iva` DECIMAL(10,2) DEFAULT 0.00",
    "pedidos.iva"
);
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `costo_envio` DECIMAL(10,2) DEFAULT 0.00",
    "pedidos.costo_envio"
);
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `total` DECIMAL(10,2) DEFAULT 0.00",
    "pedidos.total"
);
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `estado` VARCHAR(30) DEFAULT 'pendiente'",
    "pedidos.estado"
);
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `metodo_pago` VARCHAR(50) DEFAULT 'transferencia'",
    "pedidos.metodo_pago"
);
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `notas` TEXT DEFAULT NULL",
    "pedidos.notas"
);
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    "pedidos.created_at"
);
runSQL($db,
    "ALTER TABLE `pedidos` ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    "pedidos.updated_at"
);

// ── 3. Generar códigos a pedidos sin código ───────────────────────────────────
try {
    $sin_codigo = $db->query("SELECT id FROM pedidos WHERE codigo IS NULL OR codigo = ''")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($sin_codigo as $pid) {
        $codigo = 'JV-' . strtoupper(substr(uniqid(), -6));
        $db->prepare("UPDATE pedidos SET codigo = ? WHERE id = ?")->execute([$codigo, $pid]);
    }
    if ($sin_codigo) $ok[] = '✅ Códigos generados para ' . count($sin_codigo) . ' pedidos';
} catch (Throwable $e) {
    $err[] = '⚠️ Códigos: ' . $e->getMessage();
}

// ── 4. Índice en clientes.google_id ──────────────────────────────────────────
runSQL($db,
    "CREATE INDEX IF NOT EXISTS idx_clientes_google ON clientes(google_id)",
    "Índice google_id"
);

// ── 4.5 Crear Admin por defecto si no existe ──────────────────────────────────
try {
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = 'joeljiyane@gmail.com'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $hash = password_hash('jvstore2026', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES ('Joel', 'joeljiyane@gmail.com', ?, 'admin')")->execute([$hash]);
        $ok[] = "✅ Administrador joeljiyane@gmail.com creado";
    }
} catch (Throwable $e) {
    $err[] = "⚠️ Error creando Admin: " . $e->getMessage();
}

// ── 5. Verificar tabla productos ──────────────────────────────────────────────
try {
    $count = $db->query("SELECT COUNT(*) FROM productos WHERE activo=1")->fetchColumn();
    $ok[]  = "✅ Productos activos en BD: $count";
} catch (Throwable $e) {
    $err[] = '⚠️ Tabla productos: ' . $e->getMessage();
}

echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<title>Setup JVStore</title>
<style>body{font-family:sans-serif;max-width:700px;margin:40px auto;padding:20px}
h1{color:#1B2A4A}
.ok{background:#f0fdf4;border-left:4px solid #22c55e;padding:8px 14px;margin:4px 0;border-radius:4px;font-size:14px}
.err{background:#fef2f2;border-left:4px solid #ef4444;padding:8px 14px;margin:4px 0;border-radius:4px;font-size:14px}
.done{background:#0ea5e9;color:#fff;padding:16px;border-radius:8px;margin-top:20px;text-align:center}
</style></head><body>';
echo '<h1>🛠 JVStore — Migración de BD</h1>';
foreach ($ok  as $m) echo "<div class='ok'>$m</div>";
foreach ($err as $m) echo "<div class='err'>$m</div>";
echo '<div class="done"><strong>✅ Migración completada</strong><br><small>Elimina este archivo (setup_db.php) por seguridad.</small></div>';
echo '<p style="margin-top:20px"><a href="' . BASE_URL . '">← Volver al sitio</a></p>';
echo '</body></html>';
