<?php
$pageTitle = 'Mi Perfil';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
$user = getCurrentUser();
$db = getDB();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';

    try {
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                $error = 'La contraseña debe tener al menos 6 caracteres.';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, telefono = ?, direccion = ?, password = ? WHERE id = ?");
                $stmt->execute([$nombre, $telefono, $direccion, $hash, $user['id']]);
            }
        } else {
            $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, telefono = ?, direccion = ? WHERE id = ?");
            $stmt->execute([$nombre, $telefono, $direccion, $user['id']]);
        }
        if (!$error) {
            $_SESSION['user_name'] = $nombre;
            $success = 'Perfil actualizado correctamente.';
            // Refrescar datos
            $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$user['id']]);
            $user = $stmt->fetch();
        }
    } catch (Exception $e) {
        $error = 'Error al actualizar el perfil.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Mi Perfil</h1>
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>">Inicio</a> <span>/</span>
            <a href="<?= BASE_URL ?>cliente/">Mi Cuenta</a> <span>/</span>
            <span>Perfil</span>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="client-layout">
            <aside class="client-sidebar">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['nombre'], 0, 1)) ?>
                    </div>
                    <h3>
                        <?= sanitize($user['nombre']) ?>
                    </h3>
                    <p class="text-muted text-sm">
                        <?= sanitize($user['email']) ?>
                    </p>
                </div>
                <nav class="client-nav">
                    <a href="<?= BASE_URL ?>cliente/"><i class="fas fa-tachometer-alt"></i> Panel</a>
                    <a href="<?= BASE_URL ?>cliente/pedidos.php"><i class="fas fa-box"></i> Mis Pedidos</a>
                    <a href="<?= BASE_URL ?>cliente/perfil.php" class="active"><i class="fas fa-user-edit"></i> Mi
                        Perfil</a>
                    <a href="<?= BASE_URL ?>logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                </nav>
            </aside>

            <div>
                <div class="form-container" style="max-width:600px;margin:0;">
                    <h2>Editar Perfil</h2>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle"></i>
                            <?= $success ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i>
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label>Nombre Completo</label>
                            <input type="text" name="nombre" value="<?= sanitize($user['nombre']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?= sanitize($user['email']) ?>" readonly
                                style="background:var(--gris-bg);">
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="tel" name="telefono" value="<?= sanitize($user['telefono'] ?? '') ?>"
                                placeholder="+593 99 000 0000">
                        </div>
                        <div class="form-group">
                            <label>Dirección</label>
                            <textarea name="direccion" rows="3"
                                placeholder="Tu dirección de envío"><?= sanitize($user['direccion'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Nueva Contraseña (dejar en blanco para no cambiar)</label>
                            <input type="password" name="new_password" placeholder="Mínimo 6 caracteres" minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Guardar
                            Cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>