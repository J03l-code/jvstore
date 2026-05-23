<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Contraseña nueva
$new_password = 'admin123';
$email = 'admin@impordispac.com';

try {
    $db = getDB();
    $hash = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE email = ? AND rol = 'admin'");
    $stmt->execute([$hash, $email]);

    if ($stmt->rowCount() > 0) {
        echo "<h1>¡Éxito!</h1>";
        echo "<p>La contraseña para <b>$email</b> se ha restablecido a: <b>$new_password</b></p>";
        echo "<p><a href='login.php'>Ir al Login</a></p>";
    } else {
        echo "<h1>Error</h1>";
        echo "<p>No se encontró el usuario admin ($email) o la contraseña ya era esa.</p>";
        // Intentar crear si no existe
        echo "<p>Intentando crear usuario admin...</p>";

        $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES ('Administrador', ?, ?, 'admin')");
        try {
            $stmt->execute([$email, $hash]);
            echo "<p style='color:green'>Usuario admin creado correctamente.</p>";
            echo "<p>Usuario: <b>$email</b></p>";
            echo "<p>Contraseña: <b>$new_password</b></p>";
            echo "<p><a href='login.php'>Ir al Login</a></p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>Error al crear: " . $e->getMessage() . "</p>";
        }
    }

} catch (PDOException $e) {
    echo "Error de BD: " . $e->getMessage();
}
