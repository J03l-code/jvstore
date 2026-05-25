<?php
/**
 * JVSTORE - Login / Registro con Google OAuth
 */
$pageTitle = 'Iniciar Sesión';
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Si ya está logueado
if (isLoggedIn()) {
    redirect(BASE_URL . 'cliente/');
}

// Google OAuth callback
if (isset($_GET['action']) && $_GET['action'] === 'google_callback' && isset($_GET['code'])) {
    if (handleGoogleCallback($_GET['code'])) {
        $redirectTo = $_SESSION['redirect_after_login'] ?? BASE_URL . 'cliente/';
        unset($_SESSION['redirect_after_login']);
        setFlash('success', '¡Bienvenido! Has iniciado sesión con Google correctamente.');
        redirect($redirectTo);
    } else {
        setFlash('danger', 'No se pudo iniciar sesión con Google. Intenta de nuevo.');
        redirect(BASE_URL . 'login.php');
    }
}

// Login tradicional (POST)
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    if (empty($email) || empty($password)) {
        $error = 'Por favor completa todos los campos.';
    } elseif (login($email, $password)) {
        $redirectTo = $_SESSION['redirect_after_login'] ?? BASE_URL . 'cliente/';
        unset($_SESSION['redirect_after_login']);
        setFlash('success', '¡Bienvenido de vuelta!');
        redirect($redirectTo);
    } else {
        $error = 'Email o contraseña incorrectos.';
    }
}

$googleUrl    = getGoogleLoginUrl();
$googleActive = !empty(GOOGLE_CLIENT_ID) && GOOGLE_CLIENT_ID !== '';

require_once 'includes/header.php';
?>

<style>
/* ── Login Premium ─────────────────────────────── */
.login-page {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f8fafc 100%);
}
.login-box {
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.12), 0 4px 20px rgba(14,165,233,0.08);
    overflow: hidden;
    display: grid;
    grid-template-columns: 1fr 1fr;
    max-width: 900px;
    width: 100%;
}
@media(max-width:640px){ .login-box{grid-template-columns:1fr;} .login-visual{display:none;} }

/* Panel visual */
.login-visual {
    background: linear-gradient(160deg, #1B2A4A 0%, #0ea5e9 100%);
    padding: 3rem 2.5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    overflow: hidden;
}
.login-visual::before {
    content: '';
    position: absolute;
    width: 300px; height: 300px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
    top: -80px; right: -80px;
}
.login-visual::after {
    content: '';
    position: absolute;
    width: 200px; height: 200px;
    background: rgba(255,255,255,0.06);
    border-radius: 50%;
    bottom: -60px; left: -60px;
}
.login-visual-logo { height: 160px; margin-top: -30px; margin-bottom: 1.5rem; background: #fff; padding: 15px 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); object-fit: contain; }
.login-visual h2 {
    color: #fff;
    font-size: 1.6rem;
    font-weight: 800;
    margin: 0 0 1rem;
    line-height: 1.3;
    position: relative; z-index: 1;
}
.login-visual p {
    color: rgba(255,255,255,0.75);
    font-size: 14px;
    line-height: 1.7;
    margin: 0 0 2rem;
    position: relative; z-index: 1;
}
.login-features {
    display: flex;
    flex-direction: column;
    gap: 14px;
    position: relative; z-index: 1;
}
.login-feature {
    display: flex;
    align-items: center;
    gap: 12px;
    color: rgba(255,255,255,0.85);
    font-size: 13px;
}
.login-feature i {
    width: 32px; height: 32px;
    background: rgba(255,255,255,0.12);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}

/* Panel del form */
.login-form-panel {
    padding: 3rem 2.5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.login-form-panel h1 {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1B2A4A;
    margin: 0 0 6px;
}
.login-form-panel .subtitle {
    color: #6b7280;
    font-size: 14px;
    margin: 0 0 2rem;
}

/* Botón Google */
.btn-google-premium {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    width: 100%;
    padding: 14px 20px;
    background: #fff;
    border: 2px solid #e5e7eb;
    border-radius: 14px;
    font-family: 'Inter', sans-serif;
    font-size: 15px;
    font-weight: 600;
    color: #374151;
    text-decoration: none;
    transition: all 0.25s;
    cursor: pointer;
    margin-bottom: 1.2rem;
}
.btn-google-premium:hover {
    border-color: #0ea5e9;
    background: #f0f9ff;
    color: #0ea5e9;
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(14,165,233,0.15);
}
.btn-google-premium svg { flex-shrink: 0; }

/* Divisor */
.login-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 1.2rem 0;
    color: #9ca3af;
    font-size: 13px;
}
.login-divider::before, .login-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e5e7eb;
}

/* Inputs */
.form-field {
    margin-bottom: 1rem;
}
.form-field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
.form-field .field-wrap {
    position: relative;
}
.form-field .field-wrap i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 15px;
}
.form-field input {
    width: 100%;
    padding: 13px 14px 13px 42px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    color: #1B2A4A;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
    box-sizing: border-box;
}
.form-field input:focus {
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14,165,233,0.1);
}
.btn-login {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.25s;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-login:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(14,165,233,0.35);
}
.login-footer {
    text-align: center;
    margin-top: 1.5rem;
    font-size: 13px;
    color: #6b7280;
}
.login-footer a { color: #0ea5e9; font-weight: 600; text-decoration: none; }
.login-footer a:hover { text-decoration: underline; }

/* Alert de error */
.login-error {
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.25);
    border-radius: 10px;
    padding: 12px 14px;
    color: #dc2626;
    font-size: 13px;
    margin-bottom: 1.2rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Google no configurado */
.google-setup-notice {
    background: rgba(245,158,11,0.08);
    border: 1px solid rgba(245,158,11,0.25);
    border-radius: 12px;
    padding: 14px;
    font-size: 12px;
    color: #92400e;
    margin-bottom: 1rem;
    text-align: center;
}
.google-setup-notice strong { display: block; margin-bottom: 4px; color: #d97706; font-size: 13px; }
.google-setup-notice a { color: #0ea5e9; }
</style>

<div class="login-page">
  <div class="login-box">

    <!-- Panel visual izquierdo -->
    <div class="login-visual">
      <img src="<?= BASE_URL ?>img/logojvm.png" alt="JVM Store" class="login-visual-logo"
           onerror="this.style.display='none'">
      <h2>Tu tienda online de confianza</h2>
      <p>Inicia sesión para ver tus pedidos, gestionar tu carrito y acceder a tu cuenta personal.</p>
      <div class="login-features">
        <div class="login-feature">
          <i class="fas fa-box"></i>
          <span>Seguimiento de pedidos en tiempo real</span>
        </div>
        <div class="login-feature">
          <i class="fas fa-shopping-cart"></i>
          <span>Carrito persistente entre sesiones</span>
        </div>
        <div class="login-feature">
          <i class="fas fa-shield-alt"></i>
          <span>Compras 100% seguras y protegidas</span>
        </div>
        <div class="login-feature">
          <i class="fab fa-google"></i>
          <span>Acceso rápido con tu cuenta Google</span>
        </div>
      </div>
    </div>

    <!-- Panel del formulario derecho -->
    <div class="login-form-panel">
      <h1>¡Bienvenido!</h1>
      <p class="subtitle">Ingresa a tu cuenta para continuar</p>

      <?php if ($error): ?>
        <div class="login-error">
          <i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?>
        </div>
      <?php endif; ?>

      <!-- Botón Google -->
      <?php if ($googleActive): ?>
        <a href="<?= $googleUrl ?>" class="btn-google-premium" id="btn-google-login">
          <svg width="22" height="22" viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
          </svg>
          Continuar con Google
        </a>
      <?php else: ?>
        <div class="google-setup-notice">
          <strong><i class="fab fa-google"></i> Login con Google</strong>
          Para activar el inicio de sesión con Google, configura tu
          <code>GOOGLE_CLIENT_ID</code> en <code>includes/config.php</code>.
          <br><a href="https://console.cloud.google.com/" target="_blank">Obtener credenciales →</a>
        </div>
      <?php endif; ?>

      <?php if ($googleActive): ?>
        <div class="login-divider">o usa tu email</div>
      <?php endif; ?>

      <!-- Formulario email/password -->
      <form method="POST" autocomplete="on">
        <div class="form-field">
          <label for="email">Correo Electrónico</label>
          <div class="field-wrap">
            <i class="fas fa-envelope"></i>
            <input type="email" id="email" name="email" required
                   placeholder="tu@email.com"
                   value="<?= sanitize($_POST['email'] ?? '') ?>">
          </div>
        </div>
        <div class="form-field">
          <label for="password">Contraseña</label>
          <div class="field-wrap">
            <i class="fas fa-lock"></i>
            <input type="password" id="password" name="password" required
                   placeholder="••••••••" autocomplete="current-password">
          </div>
        </div>
        <button type="submit" class="btn-login">
          <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
        </button>
      </form>

      <div class="login-footer">
        ¿No tienes cuenta?
        <a href="<?= BASE_URL ?>registro.php">Regístrate gratis</a>
        &nbsp;·&nbsp;
        <a href="<?= BASE_URL ?>recuperar.php">¿Olvidaste tu contraseña?</a>
      </div>
    </div>

  </div>
</div>

<?php require_once 'includes/footer.php'; ?>