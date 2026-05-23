<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$db = getDB();

echo "<h2>Estructura de tabla USUARIOS</h2>";
$stmt = $db->query("DESCRIBE usuarios");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";

echo "<h2>Contenido de columna ROL</h2>";
$stmt = $db->query("SELECT id, nombre, email, rol FROM usuarios");
echo "<pre>";
print_r($stmt->fetchAll());
echo "</pre>";
