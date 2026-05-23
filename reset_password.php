<?php
require_once 'includes/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $email = 'admin@impordispac.com';
    $password = 'admin123'; // Nueva contraseña
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Verificar si el usuario existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        // Actualizar contraseña
        $update = $pdo->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
        $update->execute([$hash, $email]);
        echo "<h1>Contraseña actualizada correctamente</h1>";
        echo "<p>Usuario: <strong>$email</strong></p>";
        echo "<p>Nueva contraseña: <strong>$password</strong></p>";
    } else {
        // Crear usuario si no existe
        $insert = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, 'admin')");
        $insert->execute(['Administrador', $email, $hash]);
        echo "<h1>Usuario Administrador creado</h1>";
        echo "<p>Usuario: <strong>$email</strong></p>";
        echo "<p>Contraseña: <strong>$password</strong></p>";
    }

    echo "<br><a href='login.php'>Ir al Login</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>