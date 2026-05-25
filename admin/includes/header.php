<?php
/**
 * JVSTORE Admin - Header compartido
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Proteger panel admin
requireAdmin();

$currentUser = getCurrentUser();
$siteName    = getSiteConfig('site_name', SITE_NAME);
$currentFile = basename($_SERVER['PHP_SELF']);

// Contadores para sidebar
$db = getDB();
$cntProductos  = $db->query("SELECT COUNT(*) FROM productos WHERE activo=1")->fetchColumn();
$cntServicios  = $db->query("SELECT COUNT(*) FROM servicios WHERE activo=1")->fetchColumn();
$cntPedidos    = $db->query("SELECT COUNT(*) FROM pedidos WHERE estado='pendiente'")->fetchColumn();
$cntMensajes   = $db->query("SELECT COUNT(*) FROM mensajes WHERE leido=0")->fetchColumn();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<link rel="shortcut icon" type="image/png" href="<?= BASE_URL ?>img/logojvm.png">
<title><?= isset($pageTitle) ? sanitize($pageTitle).' | ' : '' ?>Admin - JVM Store</title>
<link rel="stylesheet" href="<?= BASE_URL ?>css/fonts.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>admin/css/admin.css">
</head>
<body>
<div class="adm-wrap">

<!-- SIDEBAR -->
<aside class="adm-sidebar" id="admSidebar">
  <div class="adm-brand">
    <img src="<?= BASE_URL ?>img/logojvm.png" alt="JVM Store" onerror="this.style.display='none'">
    <span>JVM Store</span>
  </div>
  <nav class="adm-nav">
    <div class="adm-nav-label">Principal</div>
    <a href="<?= BASE_URL ?>admin/" class="<?= $currentFile==='index.php'?'active':'' ?>">
      <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a href="<?= BASE_URL ?>admin/pedidos.php" class="<?= $currentFile==='pedidos.php'?'active':'' ?>">
      <i class="fas fa-shopping-bag"></i> Pedidos
      <?php if($cntPedidos>0): ?><span class="badge badge-warning" style="margin-left:auto"><?=$cntPedidos?></span><?php endif; ?>
    </a>
    <a href="<?= BASE_URL ?>admin/mensajes.php" class="<?= $currentFile==='mensajes.php'?'active':'' ?>">
      <i class="fas fa-envelope"></i> Mensajes
      <?php if($cntMensajes>0): ?><span class="badge badge-info" style="margin-left:auto"><?=$cntMensajes?></span><?php endif; ?>
    </a>

    <div class="adm-nav-label">Catálogo</div>
    <a href="<?= BASE_URL ?>admin/productos.php" class="<?= $currentFile==='productos.php'?'active':'' ?>">
      <i class="fas fa-box"></i> Productos
      <span style="margin-left:auto;font-size:11px;opacity:.5"><?=$cntProductos?></span>
    </a>
    <a href="<?= BASE_URL ?>admin/servicios.php" class="<?= $currentFile==='servicios.php'?'active':'' ?>">
      <i class="fas fa-cogs"></i> Servicios
      <span style="margin-left:auto;font-size:11px;opacity:.5"><?=$cntServicios?></span>
    </a>
    <a href="<?= BASE_URL ?>admin/categorias.php" class="<?= $currentFile==='categorias.php'?'active':'' ?>">
      <i class="fas fa-tags"></i> Categorías
    </a>
    <a href="<?= BASE_URL ?>admin/banners.php" class="<?= $currentFile==='banners.php'?'active':'' ?>">
      <i class="fas fa-images"></i> Banners
    </a>
    <a href="<?= BASE_URL ?>admin/marcas.php" class="<?= $currentFile==='marcas.php'?'active':'' ?>">
      <i class="fas fa-copyright"></i> Marcas
    </a>
    <a href="<?= BASE_URL ?>admin/opiniones.php" class="<?= $currentFile==='opiniones.php'?'active':'' ?>">
      <i class="fas fa-comment-dots"></i> Opiniones
    </a>

    <div class="adm-nav-label">Usuarios</div>
    <a href="<?= BASE_URL ?>admin/clientes.php" class="<?= $currentFile==='clientes.php'?'active':'' ?>">
      <i class="fas fa-users"></i> Clientes
    </a>
    <a href="<?= BASE_URL ?>admin/usuarios.php" class="<?= $currentFile==='usuarios.php'?'active':'' ?>">
      <i class="fas fa-user-shield"></i> Administradores
    </a>

    <div class="adm-nav-label">Sistema</div>
    <a href="<?= BASE_URL ?>admin/configuracion.php" class="<?= $currentFile==='configuracion.php'?'active':'' ?>">
      <i class="fas fa-sliders-h"></i> Configuración
    </a>
    <a href="<?= BASE_URL ?>" target="_blank">
      <i class="fas fa-external-link-alt"></i> Ver Sitio
    </a>
  </nav>
  <div class="adm-sidebar-footer">
    <a href="<?= BASE_URL ?>logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
  </div>
</aside>

<!-- MAIN -->
<div class="adm-main">
  <div class="adm-topbar">
    <div style="display:flex;align-items:center;gap:14px;">
      <button onclick="document.getElementById('admSidebar').classList.toggle('open')"
              style="background:none;border:none;font-size:20px;cursor:pointer;color:#64748b;display:none" id="menuToggle">
        <i class="fas fa-bars"></i>
      </button>
      <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
    </div>
    <div class="adm-topbar-right">
      <span style="color:#64748b;font-size:13px"><i class="fas fa-user-circle"></i> <?= sanitize(explode(' ',$currentUser['nombre'])[0]) ?></span>
      <a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Sitio</a>
      <a href="<?= BASE_URL ?>logout.php" style="color:var(--danger)"><i class="fas fa-sign-out-alt"></i> Salir</a>
    </div>
  </div>
  <div class="adm-content">

<?php if($flash): ?>
<div class="adm-alert <?= $flash['type'] ?>" id="admFlash">
  <i class="fas fa-<?= $flash['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
  <?= sanitize($flash['message']) ?>
</div>
<script>setTimeout(()=>document.getElementById('admFlash')?.remove(),4000)</script>
<?php endif; ?>
