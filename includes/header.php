<?php
/**
 * JVSTORE - Header Global
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$currentUser  = getCurrentUser();
$cartCount    = getCartCount();
$flash        = getFlash();

// Categorías para nav
$db = getDB();
$navCats = $db->query("SELECT * FROM categorias WHERE activo=1 ORDER BY orden,id")->fetchAll();
$siteName = getSiteConfig('site_name', SITE_NAME);
$whatsapp = getSiteConfig('whatsapp', WHATSAPP_NUMBER);
$heroBadge = getSiteConfig('hero_badge_texto', 'Envío Gratis en compras +$100');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= isset($pageTitle) ? sanitize($pageTitle).' | ' : '' ?><?= sanitize($siteName) ?></title>
<meta name="description" content="<?= isset($pageDescription) ? sanitize($pageDescription) : getSiteConfig('site_description', SITE_DESCRIPTION) ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>css/fonts.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>css/components.css">
<link rel="stylesheet" href="<?= BASE_URL ?>css/layout.css">
<link rel="stylesheet" href="<?= BASE_URL ?>css/jvstore.css?v=4.0">
<?php if(isset($extraCSS)) foreach($extraCSS as $css): ?>
<link rel="stylesheet" href="<?= BASE_URL ?>css/<?= $css ?>">
<?php endforeach; ?>
</head>
<body>



<!-- HEADER -->
<header class="jv-header">
  <div class="container inner">
    <a href="<?= BASE_URL ?>" class="jv-logo">
      <img src="<?= BASE_URL ?>img/logo jv.png" alt="<?= sanitize($siteName) ?>"
           onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
      <div class="jv-logo-text" style="display:none">
        JV<span>Ventas Online</span>
      </div>
    </a>

    <form class="jv-search" action="<?= BASE_URL ?>tienda.php" method="GET">
      <input type="text" name="buscar" placeholder="Buscar productos o servicios..."
             value="<?= sanitize($_GET['buscar'] ?? '') ?>" autocomplete="off">
      <button type="submit"><i class="fas fa-search"></i></button>
    </form>

    <div class="jv-header-actions">
      <a href="<?= BASE_URL ?>tienda.php" class="jv-header-btn">
        <i class="fas fa-store"></i><span>Tienda</span>
      </a>
      <a href="<?= BASE_URL ?>servicios.php" class="jv-header-btn">
        <i class="fas fa-cogs"></i><span>Servicios</span>
      </a>
      <?php if($currentUser): ?>
      <a href="<?= BASE_URL ?>cliente/" class="jv-header-btn">
        <i class="fas fa-user-circle"></i><span><?= explode(' ',sanitize($currentUser['nombre']))[0] ?></span>
      </a>
      <?php else: ?>
      <a href="<?= BASE_URL ?>login.php" class="jv-header-btn">
        <i class="fas fa-sign-in-alt"></i><span>Ingresar</span>
      </a>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>carrito.php" class="jv-header-btn jv-cart-btn">
        <i class="fas fa-shopping-cart"></i><span>Carrito</span>
        <?php if($cartCount>0): ?><span class="jv-cart-count"><?= $cartCount ?></span><?php endif; ?>
      </a>
      <button class="jv-mobile-toggle" onclick="jvToggleNav()"><i class="fas fa-bars"></i></button>
    </div>
  </div>
</header>

<!-- CATEGORY NAV -->
<nav class="jv-catnav">
  <div class="container inner">
    <a href="<?= BASE_URL ?>tienda.php" class="all-cats"><i class="fas fa-th-large"></i> Productos</a>
    <a href="<?= BASE_URL ?>servicios.php" <?= basename($_SERVER['PHP_SELF'])=='servicios.php'?'class="active"':'' ?>>
      <i class="fas fa-cogs"></i> Servicios
    </a>
    <?php foreach($navCats as $nc): ?>
    <a href="<?= BASE_URL ?>tienda.php?categoria=<?= $nc['slug'] ?>"
       class="<?= ($_GET['categoria']??'')===$nc['slug']?'active':'' ?>">
      <?= strtoupper(sanitize($nc['nombre'])) ?>
    </a>
    <?php endforeach; ?>
    <a href="<?= BASE_URL ?>nosotros.php" <?= basename($_SERVER['PHP_SELF'])=='nosotros.php'?'class="active"':'' ?>>Nosotros</a>
    <a href="<?= BASE_URL ?>contacto.php" <?= basename($_SERVER['PHP_SELF'])=='contacto.php'?'class="active"':'' ?>>Contacto</a>
  </div>
</nav>

<!-- MOBILE NAV -->
<div class="jv-mobile-nav" id="jvMobileNav">
  <div class="jv-mobile-nav-header">
    <img src="<?= BASE_URL ?>img/logo jv.png" alt="JV" style="height:40px">
    <button class="jv-mobile-close" onclick="jvToggleNav()"><i class="fas fa-times"></i></button>
  </div>
  <form action="<?= BASE_URL ?>tienda.php" method="GET" style="margin-bottom:20px;display:flex;gap:8px;">
    <input type="text" name="buscar" placeholder="Buscar..." style="flex:1;padding:10px 14px;border-radius:8px;border:none;background:rgba(255,255,255,.1);color:#fff;">
    <button type="submit" style="padding:10px 16px;background:var(--gold);border:none;border-radius:8px;cursor:pointer;"><i class="fas fa-search"></i></button>
  </form>
  <a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
  <a href="<?= BASE_URL ?>tienda.php"><i class="fas fa-store"></i> Tienda</a>
  <a href="<?= BASE_URL ?>servicios.php"><i class="fas fa-cogs"></i> Servicios</a>
  <?php foreach($navCats as $nc): ?>
  <a href="<?= BASE_URL ?>tienda.php?categoria=<?= $nc['slug'] ?>"><i class="<?= sanitize($nc['icono']) ?>"></i> <?= sanitize($nc['nombre']) ?></a>
  <?php endforeach; ?>
  <a href="<?= BASE_URL ?>nosotros.php"><i class="fas fa-building"></i> Nosotros</a>
  <a href="<?= BASE_URL ?>contacto.php"><i class="fas fa-envelope"></i> Contacto</a>
  <a href="<?= BASE_URL ?>carrito.php"><i class="fas fa-shopping-cart"></i> Carrito (<?= $cartCount ?>)</a>
  <?php if($currentUser): ?>
  <a href="<?= BASE_URL ?>cliente/"><i class="fas fa-user"></i> Mi Cuenta</a>
  <a href="<?= BASE_URL ?>logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
  <?php else: ?>
  <a href="<?= BASE_URL ?>login.php"><i class="fas fa-sign-in-alt"></i> Ingresar</a>
  <?php endif; ?>
  <?php if($currentUser && in_array($currentUser['rol'],['admin','staff'])): ?>
  <a href="<?= BASE_URL ?>admin/" style="color:var(--gold)"><i class="fas fa-tachometer-alt"></i> Panel Admin</a>
  <?php endif; ?>
</div>

<?php if($flash): ?>
<div class="jv-flash <?= $flash['type'] ?>" id="jvFlash">
  <i class="fas fa-<?= $flash['type']==='success'?'check-circle':($flash['type']==='danger'?'exclamation-circle':'info-circle') ?>"></i>
  <?= $flash['message'] ?>
</div>
<script>setTimeout(()=>document.getElementById('jvFlash')?.remove(),4000)</script>
<?php endif; ?>

<main>