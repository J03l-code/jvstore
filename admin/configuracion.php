<?php
/**
 * JVSTORE Admin - Configuración del Sitio
 */
$pageTitle = 'Configuración';
require_once __DIR__ . '/includes/header.php';
$db = getDB();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $configs = [
    'site_name','site_description','whatsapp','email_contacto',
    'facebook','instagram','tiktok',
    'iva_porcentaje','costo_envio','envio_gratis_desde','moneda',
    'hero_badge_texto','mostrar_servicios','mostrar_productos'
  ];
  foreach($configs as $key){
    $val = trim($_POST[$key] ?? '');
    $db->prepare("INSERT INTO configuracion (clave,valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=?")->execute([$key,$val,$val]);
  }
  setFlash('success','Configuración guardada correctamente');
  redirect(BASE_URL.'admin/configuracion.php');
}

// Cargar config actual
$config = $db->query("SELECT clave,valor FROM configuracion")->fetchAll(PDO::FETCH_KEY_PAIR);
function cfg($key, $default='') { global $config; return htmlspecialchars($config[$key] ?? $default, ENT_QUOTES, 'UTF-8'); }
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

<!-- GENERAL -->
<div class="adm-card">
  <div class="adm-card-header"><h2><i class="fas fa-globe" style="color:var(--gold)"></i> Información del Sitio</h2></div>
  <div class="adm-card-body">
  <form method="POST" id="mainForm">
    <div style="display:flex;flex-direction:column;gap:16px">
      <div class="form-group">
        <label class="form-label">Nombre del Sitio</label>
        <input type="text" name="site_name" class="form-control" value="<?= cfg('site_name','JV Ventas Online') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Descripción / Meta SEO</label>
        <textarea name="site_description" class="form-control" rows="2"><?= cfg('site_description') ?></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Texto Promo (Top Bar)</label>
        <input type="text" name="hero_badge_texto" class="form-control" value="<?= cfg('hero_badge_texto','Envío Gratis en compras +$100') ?>">
      </div>
    </div>
  </div>
</div>

<!-- CONTACTO -->
<div class="adm-card">
  <div class="adm-card-header"><h2><i class="fas fa-phone" style="color:var(--gold)"></i> Contacto & Redes Sociales</h2></div>
  <div class="adm-card-body">
    <div style="display:flex;flex-direction:column;gap:16px">
      <div class="form-group">
        <label class="form-label">WhatsApp (solo números con código de país)</label>
        <input type="text" name="whatsapp" class="form-control" value="<?= cfg('whatsapp') ?>" placeholder="5930900000000">
      </div>
      <div class="form-group">
        <label class="form-label">Email de Contacto</label>
        <input type="email" name="email_contacto" class="form-control" value="<?= cfg('email_contacto') ?>">
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fab fa-facebook-f" style="color:#1877F2"></i> Facebook URL</label>
        <input type="text" name="facebook" class="form-control" value="<?= cfg('facebook') ?>">
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fab fa-instagram" style="color:#E4405F"></i> Instagram URL</label>
        <input type="text" name="instagram" class="form-control" value="<?= cfg('instagram') ?>">
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fab fa-tiktok"></i> TikTok URL</label>
        <input type="text" name="tiktok" class="form-control" value="<?= cfg('tiktok') ?>">
      </div>
    </div>
  </div>
</div>

<!-- PAGOS & ENVÍO -->
<div class="adm-card">
  <div class="adm-card-header"><h2><i class="fas fa-dollar-sign" style="color:var(--gold)"></i> Precios & Envío</h2></div>
  <div class="adm-card-body">
    <div style="display:flex;flex-direction:column;gap:16px">
      <div class="form-group">
        <label class="form-label">Moneda (símbolo)</label>
        <input type="text" name="moneda" class="form-control" value="<?= cfg('moneda','$') ?>" style="max-width:80px">
      </div>
      <div class="form-group">
        <label class="form-label">IVA (%)</label>
        <input type="number" name="iva_porcentaje" class="form-control" value="<?= cfg('iva_porcentaje','15') ?>" min="0" max="100" style="max-width:120px">
      </div>
      <div class="form-group">
        <label class="form-label">Costo de Envío ($)</label>
        <input type="number" name="costo_envio" step="0.01" class="form-control" value="<?= cfg('costo_envio','5.00') ?>" style="max-width:150px">
      </div>
      <div class="form-group">
        <label class="form-label">Envío Gratis a partir de ($)</label>
        <input type="number" name="envio_gratis_desde" step="0.01" class="form-control" value="<?= cfg('envio_gratis_desde','100.00') ?>" style="max-width:150px">
      </div>
    </div>
  </div>
</div>

<!-- SECCIONES HOME -->
<div class="adm-card">
  <div class="adm-card-header"><h2><i class="fas fa-home" style="color:var(--gold)"></i> Secciones de Inicio</h2></div>
  <div class="adm-card-body">
    <div style="display:flex;flex-direction:column;gap:18px">
      <label style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:var(--offwhite);border-radius:8px">
        <div><strong>Mostrar Sección Productos</strong><br><span style="font-size:12px;color:#64748b">Muestra productos destacados en el inicio</span></div>
        <label class="toggle">
          <input type="checkbox" name="mostrar_productos" value="1" <?= cfg('mostrar_productos','1')==='1'?'checked':'' ?>>
          <span class="toggle-slider"></span>
        </label>
      </label>
      <label style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:var(--offwhite);border-radius:8px">
        <div><strong>Mostrar Sección Servicios</strong><br><span style="font-size:12px;color:#64748b">Muestra servicios destacados en el inicio</span></div>
        <label class="toggle">
          <input type="checkbox" name="mostrar_servicios" value="1" <?= cfg('mostrar_servicios','1')==='1'?'checked':'' ?>>
          <span class="toggle-slider"></span>
        </label>
      </label>
    </div>
    <div style="margin-top:24px;padding-top:20px;border-top:1px solid #f1f5f9">
      <button type="submit" form="mainForm" class="btn btn-gold" style="width:100%">
        <i class="fas fa-save"></i> Guardar Toda la Configuración
      </button>
    </div>
  </div>
</div>

</div><!-- /grid -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
