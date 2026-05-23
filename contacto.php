<?php
/**
 * JVSTORE - Contacto
 */
$pageTitle = 'Contacto';
$pageDescription = 'Contáctanos para cualquier consulta, cotización o soporte.';
require_once 'includes/header.php';

$db = getDB();
$whatsapp = getSiteConfig('whatsapp', WHATSAPP_NUMBER);
$email_contacto = getSiteConfig('email_contacto', 'contacto@jvstore.com');
$siteName = getSiteConfig('site_name', SITE_NAME);

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim(sanitize($_POST['nombre'] ?? ''));
    $email    = trim(sanitize($_POST['email'] ?? ''));
    $telefono = trim(sanitize($_POST['telefono'] ?? ''));
    $asunto   = trim(sanitize($_POST['asunto'] ?? ''));
    $mensaje  = trim(sanitize($_POST['mensaje'] ?? ''));

    if (!$nombre)  $errors[] = 'El nombre es obligatorio.';
    if (!$email || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Ingresa un email válido.';
    if (!$mensaje) $errors[] = 'El mensaje no puede estar vacío.';

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO mensajes (nombre, email, telefono, asunto, mensaje) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $_POST['email'], $telefono, $asunto, $mensaje]);
            $success = true;
        } catch (Exception $e) {
            $errors[] = 'Error al enviar el mensaje. Por favor inténtalo de nuevo.';
        }
    }
}
?>

<section class="page-header" style="background:linear-gradient(135deg,var(--navy) 0%,var(--navy-mid) 100%);padding:60px 0;text-align:center;color:#fff;">
  <div class="container">
    <h1 style="font-size:2.2rem;font-weight:800;margin:0 0 8px">Contáctanos</h1>
    <p style="opacity:.8;font-size:1.05rem;margin:0">Estamos aquí para ayudarte. ¡Escríbenos!</p>
  </div>
</section>

<section class="jv-section bg-white">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:3rem;align-items:start;">

      <!-- Info de contacto -->
      <div>
        <h2 style="font-size:1.5rem;font-weight:700;margin:0 0 24px">Información de Contacto</h2>

        <div style="display:flex;flex-direction:column;gap:20px;">
          <a href="https://wa.me/<?= $whatsapp ?>" target="_blank"
             style="display:flex;align-items:center;gap:16px;padding:20px;background:#f8fafc;border-radius:14px;text-decoration:none;color:inherit;border:1px solid #e2e8f0;transition:.2s"
             onmouseover="this.style.borderColor='#25D366';this.style.background='#f0fdf4'"
             onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'">
            <div style="width:48px;height:48px;background:#25D366;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="fab fa-whatsapp" style="color:#fff;font-size:22px"></i>
            </div>
            <div>
              <div style="font-weight:700;font-size:.95rem">WhatsApp</div>
              <div style="color:#64748b;font-size:.875rem">+<?= sanitize($whatsapp) ?></div>
            </div>
          </a>

          <a href="mailto:<?= sanitize($email_contacto) ?>"
             style="display:flex;align-items:center;gap:16px;padding:20px;background:#f8fafc;border-radius:14px;text-decoration:none;color:inherit;border:1px solid #e2e8f0;transition:.2s"
             onmouseover="this.style.borderColor='var(--gold)';this.style.background='#fffbeb'"
             onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'">
            <div style="width:48px;height:48px;background:var(--navy);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="fas fa-envelope" style="color:#fff;font-size:20px"></i>
            </div>
            <div>
              <div style="font-weight:700;font-size:.95rem">Email</div>
              <div style="color:#64748b;font-size:.875rem"><?= sanitize($email_contacto) ?></div>
            </div>
          </a>

          <div style="display:flex;align-items:center;gap:16px;padding:20px;background:#f8fafc;border-radius:14px;border:1px solid #e2e8f0;">
            <div style="width:48px;height:48px;background:var(--navy-mid);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i class="fas fa-clock" style="color:#fff;font-size:20px"></i>
            </div>
            <div>
              <div style="font-weight:700;font-size:.95rem">Horario de Atención</div>
              <div style="color:#64748b;font-size:.875rem">Lun – Vie: 8:00 AM – 6:00 PM</div>
              <div style="color:#64748b;font-size:.875rem">Sáb: 9:00 AM – 2:00 PM</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Formulario -->
      <div style="background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:36px;box-shadow:0 4px 24px rgba(0,0,0,.06)">
        <h2 style="font-size:1.4rem;font-weight:700;margin:0 0 24px">Envíanos un Mensaje</h2>

        <?php if ($success): ?>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:20px;text-align:center;margin-bottom:20px;">
          <i class="fas fa-check-circle" style="color:#22c55e;font-size:2rem;margin-bottom:8px;display:block"></i>
          <h3 style="margin:0 0 6px;color:#15803d">¡Mensaje enviado!</h3>
          <p style="margin:0;color:#166534;font-size:.9rem">Gracias por contactarnos. Te responderemos pronto.</p>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px;margin-bottom:20px;">
          <?php foreach($errors as $e): ?>
          <p style="margin:0 0 4px;color:#dc2626;font-size:.875rem"><i class="fas fa-exclamation-circle"></i> <?= $e ?></p>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" style="display:flex;flex-direction:column;gap:18px;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
              <label style="display:block;font-size:.875rem;font-weight:600;margin-bottom:6px;color:#374151">Nombre *</label>
              <input type="text" name="nombre" required value="<?= sanitize($_POST['nombre'] ?? '') ?>"
                     placeholder="Tu nombre completo"
                     style="width:100%;padding:12px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.95rem;font-family:inherit;outline:none;box-sizing:border-box;transition:.2s"
                     onfocus="this.style.borderColor='var(--navy)'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <div>
              <label style="display:block;font-size:.875rem;font-weight:600;margin-bottom:6px;color:#374151">Email *</label>
              <input type="email" name="email" required value="<?= sanitize($_POST['email'] ?? '') ?>"
                     placeholder="tu@email.com"
                     style="width:100%;padding:12px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.95rem;font-family:inherit;outline:none;box-sizing:border-box;transition:.2s"
                     onfocus="this.style.borderColor='var(--navy)'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div>
              <label style="display:block;font-size:.875rem;font-weight:600;margin-bottom:6px;color:#374151">Teléfono</label>
              <input type="tel" name="telefono" value="<?= sanitize($_POST['telefono'] ?? '') ?>"
                     placeholder="+593 99 000 0000"
                     style="width:100%;padding:12px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.95rem;font-family:inherit;outline:none;box-sizing:border-box;transition:.2s"
                     onfocus="this.style.borderColor='var(--navy)'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <div>
              <label style="display:block;font-size:.875rem;font-weight:600;margin-bottom:6px;color:#374151">Asunto</label>
              <input type="text" name="asunto" value="<?= sanitize($_POST['asunto'] ?? '') ?>"
                     placeholder="¿En qué podemos ayudarte?"
                     style="width:100%;padding:12px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.95rem;font-family:inherit;outline:none;box-sizing:border-box;transition:.2s"
                     onfocus="this.style.borderColor='var(--navy)'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
          </div>
          <div>
            <label style="display:block;font-size:.875rem;font-weight:600;margin-bottom:6px;color:#374151">Mensaje *</label>
            <textarea name="mensaje" required rows="5" placeholder="Escribe tu mensaje aquí..."
                      style="width:100%;padding:12px 16px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.95rem;font-family:inherit;outline:none;resize:vertical;box-sizing:border-box;transition:.2s"
                      onfocus="this.style.borderColor='var(--navy)'" onblur="this.style.borderColor='#e2e8f0'"><?= sanitize($_POST['mensaje'] ?? '') ?></textarea>
          </div>
          <button type="submit"
                  style="padding:14px 32px;background:var(--navy);color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:10px"
                  onmouseover="this.style.background='var(--gold)';this.style.color='var(--navy)'"
                  onmouseout="this.style.background='var(--navy)';this.style.color='#fff'">
            <i class="fas fa-paper-plane"></i> Enviar Mensaje
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
