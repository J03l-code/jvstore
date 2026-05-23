<?php
/**
 * JVSTORE - index.php (Página Principal)
 */
$pageTitle = 'Inicio';
$pageDescription = 'Tu tienda online de confianza - Productos y Servicios';
require_once 'includes/header.php';

$db = getDB();

// Banners para el hero
$banners = $db->query("SELECT * FROM banners WHERE activo=1 AND posicion='principal' ORDER BY orden LIMIT 5")->fetchAll();

// Productos destacados
$destacados = $db->query("
  SELECT p.*, c.nombre AS cat_nombre, c.slug AS cat_slug
  FROM productos p
  LEFT JOIN categorias c ON p.categoria_id = c.id
  WHERE p.activo=1 AND p.destacado=1
  ORDER BY p.created_at DESC LIMIT 8
")->fetchAll();

// Productos nuevos
$nuevos = $db->query("
  SELECT p.*, c.nombre AS cat_nombre
  FROM productos p
  LEFT JOIN categorias c ON p.categoria_id = c.id
  WHERE p.activo=1 AND p.nuevo=1
  ORDER BY p.created_at DESC LIMIT 4
")->fetchAll();

// Productos en oferta (para banners laterales)
$ofertas = $db->query("
  SELECT p.*, c.nombre AS cat_nombre
  FROM productos p
  LEFT JOIN categorias c ON p.categoria_id = c.id
  WHERE p.activo=1 AND p.precio_oferta IS NOT NULL
  ORDER BY RAND() LIMIT 2
")->fetchAll();

// Categorías de productos
$cats = $db->query("SELECT * FROM categorias WHERE activo=1 AND tipo IN ('producto','ambos') ORDER BY orden,id LIMIT 12")->fetchAll();

// Servicios destacados
$servicios = $db->query("
  SELECT s.*, c.nombre AS cat_nombre
  FROM servicios s
  LEFT JOIN categorias c ON s.categoria_id = c.id
  WHERE s.activo=1 AND s.destacado=1
  ORDER BY s.orden LIMIT 4
")->fetchAll();

$totalProductos = $db->query("SELECT COUNT(*) FROM productos WHERE activo=1")->fetchColumn();
$whatsapp = getSiteConfig('whatsapp', WHATSAPP_NUMBER);
$siteName = getSiteConfig('site_name', SITE_NAME);
?>

<!-- HERO -->
<section style="background:var(--offwhite);padding:10px 0 0;">
<div class="container">
<div class="jv-hero">

  <!-- Slider principal -->
  <div class="jv-hero-main">
    <?php if(empty($banners)): ?>
    <div class="jv-hero-slide active" style="background-image:url('https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1200&q=80')"></div>
    <?php else: foreach($banners as $i=>$b): ?>
    <div class="jv-hero-slide <?= $i===0?'active':'' ?>" style="background-image:url('<?= sanitize($b['imagen_url']) ?>')"></div>
    <?php endforeach; endif; ?>
    <div class="jv-hero-overlay"></div>
    <div class="jv-hero-content">
      <div class="jv-hero-badge"><i class="fas fa-star"></i> <?= getSiteConfig('hero_badge_texto','Envío Gratis en compras +$100') ?></div>
      <?php if(!empty($banners)): $b=$banners[0]; ?>
      <h1><?= sanitize($b['titulo'] ?? 'Tu Tienda Online de Confianza') ?></h1>
      <p><?= sanitize($b['subtitulo'] ?? 'Productos y servicios de calidad con entrega a todo el país') ?></p>
      <div class="jv-hero-btns">
        <a href="<?= BASE_URL ?>tienda.php" class="btn-primary"><i class="fas fa-store"></i> Ver Productos</a>
        <a href="<?= BASE_URL ?>servicios.php" class="btn-outline"><i class="fas fa-cogs"></i> Ver Servicios</a>
      </div>
      <?php else: ?>
      <h1>Tu Tienda Online de <em>Confianza</em></h1>
      <p>Productos de calidad y servicios profesionales con entrega a todo el país.</p>
      <div class="jv-hero-btns">
        <a href="<?= BASE_URL ?>tienda.php" class="btn-primary"><i class="fas fa-store"></i> Ver Productos</a>
        <a href="<?= BASE_URL ?>servicios.php" class="btn-outline"><i class="fas fa-cogs"></i> Ver Servicios</a>
      </div>
      <?php endif; ?>
      <div class="jv-hero-dots" id="heroDots">
        <?php $count=max(1,count($banners)); for($i=0;$i<$count;$i++): ?>
        <button class="jv-dot <?= $i===0?'active':'' ?>" onclick="jvGoSlide(<?=$i?>)"></button>
        <?php endfor; ?>
      </div>
    </div>
  </div>

  <!-- Banners laterales (ofertas) -->
  <div class="jv-hero-side">
    <?php if(!empty($ofertas)): foreach($ofertas as $o): ?>
    <a href="<?= BASE_URL ?>producto.php?id=<?= $o['id'] ?>" class="jv-hero-card">
      <img src="<?= getProductImage($o['imagen_url']) ?>" alt="<?= sanitize($o['nombre']) ?>">
      <div class="jv-hero-card-body">
        <div class="tag">OFERTA</div>
        <h3><?= sanitize($o['nombre']) ?></h3>
        <div class="price"><?= formatPrice($o['precio_oferta']) ?> <s style="font-size:11px;opacity:.7"><?= formatPrice($o['precio']) ?></s></div>
      </div>
    </a>
    <?php endforeach; else: ?>
    <a href="<?= BASE_URL ?>tienda.php" class="jv-hero-card"
       style="background:linear-gradient(135deg,var(--navy),var(--navy-mid));">
      <div class="jv-hero-card-body" style="justify-content:center;text-align:center;">
        <div class="tag">TIENDA</div>
        <h3>Ver todos los productos</h3>
      </div>
    </a>
    <a href="<?= BASE_URL ?>servicios.php" class="jv-hero-card"
       style="background:linear-gradient(135deg,var(--navy-mid),#3a5f9e);">
      <div class="jv-hero-card-body" style="justify-content:center;text-align:center;">
        <div class="tag">SERVICIOS</div>
        <h3>Servicios profesionales</h3>
      </div>
    </a>
    <?php endif; ?>
  </div>

</div>
</div>
</section>

<!-- BRANDS -->
<div class="jv-brands">
  <div class="jv-brands-track" id="brandsTrack">
    <?php for($r=0;$r<2;$r++): ?>
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/Mastercard-logo.svg/200px-Mastercard-logo.svg.png" alt="Mastercard">
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/200px-Visa_Inc._logo.svg.png" alt="Visa">
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b5/PayPal.svg/200px-PayPal.svg.png" alt="PayPal">
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/Apple_logo_black.svg/100px-Apple_logo_black.svg.png" alt="Apple">
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Samsung_Logo.svg/200px-Samsung_Logo.svg.png" alt="Samsung">
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2f/Bosch-logo.svg/200px-Bosch-logo.svg.png" alt="Bosch">
    <?php endfor; ?>
  </div>
</div>

<!-- STATS -->
<div style="background:var(--navy);padding:20px 0;">
  <div class="container" style="display:grid;grid-template-columns:repeat(4,1fr);gap:0;text-align:center;">
    <div style="padding:10px;border-right:1px solid rgba(255,255,255,.1)">
      <div style="font-family:'Montserrat',sans-serif;font-size:1.6rem;font-weight:900;color:var(--gold)"><?= number_format($totalProductos) ?>+</div>
      <div style="font-size:12px;color:var(--silver);text-transform:uppercase;letter-spacing:1px">Productos</div>
    </div>
    <div style="padding:10px;border-right:1px solid rgba(255,255,255,.1)">
      <div style="font-family:'Montserrat',sans-serif;font-size:1.6rem;font-weight:900;color:var(--gold)"><?= count($cats) ?>+</div>
      <div style="font-size:12px;color:var(--silver);text-transform:uppercase;letter-spacing:1px">Categorías</div>
    </div>
    <div style="padding:10px;border-right:1px solid rgba(255,255,255,.1)">
      <div style="font-family:'Montserrat',sans-serif;font-size:1.6rem;font-weight:900;color:var(--gold)">100%</div>
      <div style="font-size:12px;color:var(--silver);text-transform:uppercase;letter-spacing:1px">Garantizado</div>
    </div>
    <div style="padding:10px;">
      <div style="font-family:'Montserrat',sans-serif;font-size:1.6rem;font-weight:900;color:var(--gold)">24/7</div>
      <div style="font-size:12px;color:var(--silver);text-transform:uppercase;letter-spacing:1px">Soporte</div>
    </div>
  </div>
</div>

<!-- CATEGORÍAS -->
<?php if(!empty($cats)): ?>
<section class="jv-section bg-white">
<div class="container">
  <div class="jv-section-header">
    <h2>Categorías</h2>
    <a href="<?= BASE_URL ?>tienda.php">Ver todo <i class="fas fa-arrow-right"></i></a>
  </div>
  <div class="jv-cats-grid">
    <?php foreach($cats as $c): ?>
    <a href="<?= BASE_URL ?>tienda.php?categoria=<?= $c['slug'] ?>" class="jv-cat-card">
      <div class="jv-cat-icon"><i class="<?= sanitize($c['icono']) ?>"></i></div>
      <h3><?= sanitize($c['nombre']) ?></h3>
    </a>
    <?php endforeach; ?>
  </div>
</div>
</section>
<?php endif; ?>

<!-- PRODUCTOS DESTACADOS -->
<?php if(!empty($destacados)): ?>
<section class="jv-section">
<div class="container">
  <div class="jv-section-header">
    <h2>Productos Destacados</h2>
    <a href="<?= BASE_URL ?>tienda.php?destacado=1">Ver todos <i class="fas fa-arrow-right"></i></a>
  </div>
  <div class="jv-products-grid">
    <?php foreach($destacados as $p): ?>
    <div class="jv-card">
      <a href="<?= BASE_URL ?>producto.php?id=<?= $p['id'] ?>">
        <div class="jv-card-img">
          <img src="<?= getProductImage($p['imagen_url']) ?>" alt="<?= sanitize($p['nombre']) ?>" loading="lazy">
          <?php if($p['precio_oferta']): $pct=round(100-($p['precio_oferta']/$p['precio']*100)); ?>
          <span class="jv-badge sale">-<?=$pct?>%</span><?php endif; ?>
          <?php if($p['nuevo']): ?><span class="jv-badge new" style="top:<?= $p['precio_oferta']?'34':'10' ?>px">NUEVO</span><?php endif; ?>
          <?php if($p['stock']<=0): ?><span class="jv-badge out">Agotado</span><?php endif; ?>
          <span class="jv-card-stock"><?= $p['stock']>5?'En stock':($p['stock']>0?'Últimas '.$p['stock']:'Sin stock') ?></span>
        </div>
      </a>
      <div class="jv-card-body">
        <div class="jv-card-cat"><?= sanitize($p['cat_nombre']??'') ?></div>
        <a href="<?= BASE_URL ?>producto.php?id=<?= $p['id'] ?>">
          <h3 class="jv-card-title"><?= sanitize($p['nombre']) ?></h3>
        </a>
        <div class="jv-card-footer">
          <div class="jv-price-wrap">
            <span class="jv-price"><?= formatPrice($p['precio_oferta']??$p['precio']) ?></span>
            <?php if($p['precio_oferta']): ?><span class="jv-price-old"><?= formatPrice($p['precio']) ?></span><?php endif; ?>
          </div>
          <?php if($p['stock']>0): ?>
          <button class="btn-cart" onclick="addToCart(<?= $p['id'] ?>)" title="Agregar al carrito">
            <i class="fas fa-cart-plus"></i>
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</section>
<?php endif; ?>

<!-- BANNER PROMO -->
<section class="jv-section bg-offwhite" style="padding:30px 0;">
<div class="container">
  <div class="jv-promo-banner">
    <div class="jv-promo-text">
      <h2>¿Necesitas <em>asesoría</em> personalizada?</h2>
      <p>Contáctanos por WhatsApp y te ayudamos a encontrar lo que necesitas.</p>
    </div>
    <div class="jv-promo-cta">
      <a href="https://wa.me/<?= $whatsapp ?>?text=Hola,%20necesito%20información" target="_blank" class="btn-wa">
        <i class="fab fa-whatsapp"></i> Chatear por WhatsApp
      </a>
      <a href="<?= BASE_URL ?>contacto.php" class="btn-outline" style="text-align:center;padding:13px 24px;border-color:rgba(255,255,255,.3);color:#fff">
        <i class="fas fa-envelope"></i> Enviar Mensaje
      </a>
    </div>
  </div>
</div>
</section>

<!-- SERVICIOS -->
<?php if(!empty($servicios)): ?>
<section class="jv-section bg-white">
<div class="container">
  <div class="jv-section-header">
    <div>
      <span class="section-tag">Lo que ofrecemos</span>
      <h2>Nuestros Servicios</h2>
    </div>
    <a href="<?= BASE_URL ?>servicios.php">Ver todos <i class="fas fa-arrow-right"></i></a>
  </div>
  <div class="jv-services-grid">
    <?php foreach($servicios as $s): ?>
    <div class="jv-service-card">
      <div class="jv-service-icon"><i class="<?= sanitize($s['icono']) ?>"></i></div>
      <h3><?= sanitize($s['titulo']) ?></h3>
      <p><?= sanitize($s['descripcion_corta']??'') ?></p>
      <?php if($s['precio_desde']): ?>
      <div class="jv-service-price">Desde <strong><?= formatPrice($s['precio_desde']) ?></strong></div>
      <?php endif; ?>
      <br>
      <a href="<?= BASE_URL ?>servicio.php?id=<?= $s['id'] ?>" class="btn-service">
        Ver Detalles <i class="fas fa-arrow-right"></i>
      </a>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</section>
<?php endif; ?>

<!-- NUEVOS PRODUCTOS -->
<?php if(!empty($nuevos)): ?>
<section class="jv-section">
<div class="container">
  <div class="jv-section-header">
    <div>
      <span class="section-tag">Recién llegados</span>
      <h2>Nuevos en Tienda</h2>
    </div>
    <a href="<?= BASE_URL ?>tienda.php?nuevo=1">Ver todos <i class="fas fa-arrow-right"></i></a>
  </div>
  <div class="jv-products-grid">
    <?php foreach($nuevos as $p): ?>
    <div class="jv-card">
      <a href="<?= BASE_URL ?>producto.php?id=<?= $p['id'] ?>">
        <div class="jv-card-img">
          <img src="<?= getProductImage($p['imagen_url']) ?>" alt="<?= sanitize($p['nombre']) ?>" loading="lazy">
          <span class="jv-badge new">NUEVO</span>
        </div>
      </a>
      <div class="jv-card-body">
        <div class="jv-card-cat"><?= sanitize($p['cat_nombre']??'') ?></div>
        <a href="<?= BASE_URL ?>producto.php?id=<?= $p['id'] ?>"><h3 class="jv-card-title"><?= sanitize($p['nombre']) ?></h3></a>
        <div class="jv-card-footer">
          <span class="jv-price"><?= formatPrice($p['precio_oferta']??$p['precio']) ?></span>
          <?php if($p['stock']>0): ?>
          <button class="btn-cart" onclick="addToCart(<?= $p['id'] ?>)"><i class="fas fa-cart-plus"></i></button>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</section>
<?php endif; ?>

<!-- TESTIMONIOS -->
<section class="jv-section bg-white">
<div class="container">
  <div class="jv-section-header">
    <div><span class="section-tag">Opiniones</span><h2>Lo que dicen nuestros clientes</h2></div>
  </div>
  <div class="jv-testimonials-grid">
    <div class="jv-testi">
      <div class="jv-testi-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
      <p>"Excelente servicio, recibí mi pedido en tiempo récord y la calidad superó mis expectativas. 100% recomendado."</p>
      <div class="jv-testi-author">Carlos M.</div><div class="jv-testi-role">Cliente verificado</div>
    </div>
    <div class="jv-testi">
      <div class="jv-testi-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
      <p>"Contraté el servicio de consultoría y fue muy profesional. Me ayudaron a mejorar mi negocio significativamente."</p>
      <div class="jv-testi-author">María F.</div><div class="jv-testi-role">Empresaria</div>
    </div>
    <div class="jv-testi">
      <div class="jv-testi-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
      <p>"Los productos son de primera calidad y el soporte al cliente es excelente. Ya hice mi tercera compra."</p>
      <div class="jv-testi-author">Roberto L.</div><div class="jv-testi-role">Cliente frecuente</div>
    </div>
  </div>
</div>
</section>

<?php
$extraScripts = ['cart.js'];
require_once 'includes/footer.php';
?>