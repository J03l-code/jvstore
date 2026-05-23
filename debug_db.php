<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$db = getDB();
echo "<h1>Contenido de tabla USUARIOS</h1>";
echo "<pre>";
$usuarios = $db->query("SELECT * FROM usuarios")->fetchAll();
print_r($usuarios);
echo "</pre>";

echo "<h1>Contenido de tabla CLIENTES</h1>";
echo "<pre>";
$clientes = $db->query("SELECT * FROM clientes")->fetchAll();
print_r($clientes);
echo "</pre>";
