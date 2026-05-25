<?php
/**
 * Script temporal — Actualizar nombre del sitio a JVM Store
 * ELIMINAR después de ejecutar.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

try {
    $db = getDB();

    // Actualizar o insertar el nombre del sitio
    $db->prepare(
        "INSERT INTO configuracion (clave, valor) VALUES ('site_name', 'JVM Store')
         ON DUPLICATE KEY UPDATE valor = 'JVM Store'"
    )->execute();

    $valor = $db->query("SELECT valor FROM configuracion WHERE clave='site_name'")->fetchColumn();
    echo "<h2 style='color:green;font-family:sans-serif'>✅ Nombre actualizado correctamente</h2>";
    echo "<p style='font-family:sans-serif'>Nuevo nombre en BD: <strong>" . htmlspecialchars($valor) . "</strong></p>";
    echo "<p style='font-family:sans-serif'><a href='" . BASE_URL . "'>← Ver sitio</a> &nbsp;|&nbsp; <a href='" . BASE_URL . "admin/'>Ir al Admin</a></p>";
    echo "<hr><p style='color:#e00;font-family:sans-serif;font-size:13px'>⚠️ Elimina este archivo una vez ejecutado: <code>update_name_jvm.php</code></p>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ Error</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
