<?php
try {
    $host = "localhost";
    $db = "u434851126_impordispacec";
    $user = "u434851126_admin";
    $pass = "Impordispac2026";
    $charset = "utf8mb4";
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo "Fallo primera conexión: " . $e->getMessage() . "\n";
    try {
        $db = "jvstore_db";
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $conn = new PDO($dsn, "root", "", $options);
    } catch (PDOException $ex) {
        die("Error de conexión: " . $ex->getMessage());
    }
}

try {
    // Verificar si existe la columna 'atributos' en 'categorias'
    $q1 = $conn->query("SHOW COLUMNS FROM categorias LIKE 'atributos'");
    if (!$q1->fetch()) {
        $conn->exec("ALTER TABLE categorias ADD COLUMN atributos TEXT DEFAULT NULL");
        echo "Columna 'atributos' agregada a tabla 'categorias'.\n";
    } else {
        echo "Columna 'atributos' ya existe en 'categorias'.\n";
    }

    // Verificar si existe la columna 'atributos' en 'productos'
    $q2 = $conn->query("SHOW COLUMNS FROM productos LIKE 'atributos'");
    if (!$q2->fetch()) {
        $conn->exec("ALTER TABLE productos ADD COLUMN atributos TEXT DEFAULT NULL");
        echo "Columna 'atributos' agregada a tabla 'productos'.\n";
    } else {
        echo "Columna 'atributos' ya existe en 'productos'.\n";
    }

    // Verificar si existe la columna 'atributos' en 'servicios'
    $q3 = $conn->query("SHOW COLUMNS FROM servicios LIKE 'atributos'");
    if (!$q3->fetch()) {
        $conn->exec("ALTER TABLE servicios ADD COLUMN atributos TEXT DEFAULT NULL");
        echo "Columna 'atributos' agregada a tabla 'servicios'.\n";
    } else {
        echo "Columna 'atributos' ya existe en 'servicios'.\n";
    }
} catch (Exception $e) {
    echo "Error en migración: " . $e->getMessage() . "\n";
}
