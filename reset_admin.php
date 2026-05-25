<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $db = getDB();
    $hash = password_hash('Admin2026!', PASSWORD_DEFAULT);
    
    // Check if user exists first
    $stmt = $db->query("SELECT id FROM usuarios WHERE email = 'admin@jvnstore.com'");
    if ($stmt->fetch()) {
        $db->prepare("UPDATE usuarios SET password = ? WHERE email = 'admin@jvnstore.com'")->execute([$hash]);
        echo "<h1>✅ Contraseña actualizada correctamente</h1>";
    } else {
        $db->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)")
           ->execute(['Administrador JVN', 'admin@jvnstore.com', $hash, 'admin']);
        echo "<h1>✅ Usuario creado y contraseña configurada correctamente</h1>";
    }
    
    echo "<p>Email: admin@jvnstore.com<br>Password: Admin2026!</p>";
    echo "<p><a href='".BASE_URL."login.php'>Ir al Login</a></p>";
    echo "<p style='color:red;'><strong>IMPORTANTE:</strong> Elimina este archivo (reset_admin.php) de tu servidor por seguridad.</p>";
} catch (Exception $e) {
    echo "<h1>❌ Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
