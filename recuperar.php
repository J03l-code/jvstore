<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php'; // Para funciones de usuario

// Si ya está logueado, redirigir
if (isLoggedIn())
    redirect(BASE_URL);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $db = getDB();

    // Buscar en clientes primero
    $stmt = $db->prepare("SELECT id, nombre, email, 'cliente' as tipo FROM clientes WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Si no, buscar en usuarios
    if (!$user) {
        $stmt = $db->prepare("SELECT id, nombre, email, 'usuario' as tipo FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    }

    if ($user) {
        // Generar token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Guardar token
        $table = ($user['tipo'] === 'cliente') ? 'clientes' : 'usuarios';
        $update = $db->prepare("UPDATE $table SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $update->execute([$token, $expires, $user['id']]);

        // Enviar correo
        $link = BASE_URL . "reset.php?token=" . $token . "&type=" . $user['tipo'];
        $subject = "Recuperar Contraseña - " . SITE_NAME;
        $msg = "<p>Hola <strong>{$user['nombre']}</strong>,</p>";
        $msg .= "<p>Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace:</p>";
        $msg .= "<p><a href='$link'>Restablecer Contraseña</a></p>";
        $msg .= "<p>Este enlace expira en 1 hora.</p>";

        if (sendEmail($user['email'], $subject, $msg)) {
            $message = "Te hemos enviado un correo con las instrucciones.";
        } else {
            $error = "Error al enviar el correo.";
        }
    } else {
        // Por seguridad, mostrar mismo mensaje
        $message = "Si el correo existe, recibirás instrucciones.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña |
        <?= SITE_NAME ?>
    </title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/fonts.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/components.css">
    <style>
        body {
            background: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .card {
            max-width: 400px;
            width: 100%;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2 style="margin-bottom:1rem;">Recuperar Contraseña</h2>
        <p style="color:#666; margin-bottom:2rem;">Ingresa tu correo para recibir un enlace de recuperación.</p>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <?= $message ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group" style="text-align:left;">
                <label>Correo Electrónico</label>
                <input type="email" name="email" required class="form-control" placeholder="nombre@ejemplo.com">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Enviar Enlace</button>
        </form>
        <div style="margin-top:1.5rem;">
            <a href="<?= BASE_URL ?>login.php" style="color:#666; text-decoration:none;">Volver al Login</a>
        </div>
    </div>
</body>

</html>