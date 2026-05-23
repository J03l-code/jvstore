<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$token = $_GET['token'] ?? '';
$type = $_GET['type'] ?? '';
$error = '';
$success = '';

if (!$token || !$type) {
    die("Enlace inválido.");
}

$db = getDB();
$table = ($type === 'cliente') ? 'clientes' : 'usuarios';

// Verificar token
$stmt = $db->prepare("SELECT id FROM $table WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = "El enlace es inválido o ha expirado.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];

    if ($pass1 !== $pass2) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($pass1) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        // Actualizar password
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE $table SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $update->execute([$hash, $user['id']]);

        $success = "Contraseña actualizada correctamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nueva Contraseña |
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
        <h2 style="margin-bottom:1rem;">Nueva Contraseña</h2>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= $success ?>
            </div>
            <p><a href="<?= BASE_URL ?>login.php" class="btn btn-primary btn-block">Iniciar Sesión</a></p>
        <?php elseif ($error && !$user): ?>
            <div class="alert alert-danger">
                <?= $error ?>
            </div>
            <p><a href="<?= BASE_URL ?>recuperar.php" class="btn btn-outline btn-block">Solicitar nuevo enlace</a></p>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group" style="text-align:left;">
                    <label>Nueva Contraseña</label>
                    <input type="password" name="pass1" required class="form-control" placeholder="••••••••">
                </div>
                <div class="form-group" style="text-align:left;">
                    <label>Confirmar Contraseña</label>
                    <input type="password" name="pass2" required class="form-control" placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Guardar Contraseña</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>