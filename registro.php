<?php
$pageTitle = 'Registro';
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

if (isLoggedIn()) {
    redirect(BASE_URL);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (empty($nombre) || empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif ($password !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        $result = register($nombre, $email, $password);
        if ($result['success']) {
            setFlash('success', '¡Cuenta creada exitosamente! Bienvenido.');
            $redirectTo = $_SESSION['redirect_after_login'] ?? BASE_URL;
            unset($_SESSION['redirect_after_login']);
            redirect($redirectTo);
        } else {
            $error = $result['message'];
        }
    }
}

require_once 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Crear Cuenta</h1>
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>">Inicio</a> <span>/</span> <span>Registro</span>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="form-container">
            <h2>Crear tu Cuenta</h2>
            <p class="subtitle">Regístrate para comprar y rastrear tus pedidos</p>


            <!-- Google Login -->
            <?php $googleUrl = getGoogleLoginUrl();
            if ($googleUrl !== '#'): ?>
                <a href="<?= $googleUrl ?>" class="btn btn-google btn-block btn-lg">
                    <svg width="20" height="20" viewBox="0 0 48 48">
                        <path fill="#EA4335"
                            d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z" />
                        <path fill="#4285F4"
                            d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z" />
                        <path fill="#FBBC05"
                            d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z" />
                        <path fill="#34A853"
                            d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z" />
                    </svg>
                    Continuar con Google
                </a>
                <div class="form-divider">o con tu email</div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="nombre">Nombre Completo</label>
                    <div class="input-icon"><i class="fas fa-user"></i>
                        <input type="text" id="nombre" name="nombre" required placeholder="Tu nombre"
                            value="<?= sanitize($_POST['nombre'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <div class="input-icon"><i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" required placeholder="tu@email.com"
                            value="<?= sanitize($_POST['email'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-icon"><i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required placeholder="Mínimo 6 caracteres"
                            minlength="6">
                    </div>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirmar Contraseña</label>
                    <div class="input-icon"><i class="fas fa-lock"></i>
                        <input type="password" id="password_confirm" name="password_confirm" required
                            placeholder="Repite tu contraseña">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Crear Cuenta</button>
            </form>
            <div class="form-footer">
                ¿Ya tienes cuenta? <a href="<?= BASE_URL ?>login.php">Inicia sesión</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>