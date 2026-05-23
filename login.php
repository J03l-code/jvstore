<?php
$pageTitle = 'Iniciar Sesión';
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    redirect(BASE_URL);
}

// Google OAuth callback
if (isset($_GET['action']) && $_GET['action'] === 'google_callback' && isset($_GET['code'])) {
    if (handleGoogleCallback($_GET['code'])) {
        $redirectTo = $_SESSION['redirect_after_login'] ?? BASE_URL;
        unset($_SESSION['redirect_after_login']);
        setFlash('success', '¡Bienvenido! Has iniciado sesión con Google.');
        redirect($redirectTo);
    } else {
        setFlash('danger', 'Error al iniciar sesión con Google.');
    }
}

// Login tradicional
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (login($email, $password)) {
        $redirectTo = $_SESSION['redirect_after_login'] ?? BASE_URL;
        unset($_SESSION['redirect_after_login']);
        setFlash('success', '¡Bienvenido de vuelta!');
        redirect($redirectTo);
    } else {
        $error = 'Email o contraseña incorrectos.';
    }
}

require_once 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Iniciar Sesión</h1>
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>">Inicio</a> <span>/</span> <span>Login</span>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="form-container">
            <div class="login-logo">
                <img src="<?= BASE_URL ?>img/pacifico_sin_fondo.png" alt="<?= SITE_NAME ?>">
            </div>
            <h2>Bienvenido</h2>
            <p class="subtitle">Ingresa a tu cuenta para continuar</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

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
                <div class="form-divider">o usa tu email</div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" required placeholder="tu@email.com"
                            value="<?= sanitize($_POST['email'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required placeholder="••••••••">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Iniciar Sesión</button>
            </form>
            <div class="form-footer">
                ¿No tienes cuenta? <a href="<?= BASE_URL ?>registro.php">Regístrate aquí</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>