<?php
/**
 * JVSTORE - Página de Servicios
 */
$pageTitle = 'Servicios';
$pageDescription = 'Servicios profesionales - Consultoría, Logística y Soporte';
require_once 'includes/header.php';

$db = getDB();
$categorias_serv = $db->query("SELECT * FROM categorias WHERE activo=1 AND tipo IN ('servicio','ambos') ORDER BY orden")->fetchAll();
$filtro_cat = $_GET['categoria'] ?? '';

$sql = "SELECT s.*, c.nombre AS cat_nombre, c.slug AS cat_slug FROM servicios s
        LEFT JOIN categorias c ON s.categoria_id=c.id WHERE s.activo=1";
$params = [];
if($filtro_cat){
  $sql .= " AND c.slug=?"; $params[]=$filtro_cat;
}
$sql .= " ORDER BY s.orden, s.id";
$stmt = $db->prepare($sql); $stmt->execute($params);
$servicios = $stmt->fetchAll();
?>

<div style="background:linear-gradient(135deg,var(--navy-dark),var(--navy));padding:50px 0 40px;">
  <div class="container">
    <span class="section-tag" style="background:rgba(212,175,55,.15);color:var(--gold)">Lo que ofrecemos</span>
    <h1 style="font-family:'Montserrat',sans-serif;font-size:2.2rem;font-weight:900;color:#fff;margin:10px 0 12px;">Nuestros Servicios</h1>
    <p style="color:var(--silver);font-size:15px;max-width:500px;">Soluciones profesionales adaptadas a tus necesidades.</p>
  </div>
</div>

<section class="jv-section">
<div class="container">

  <!-- Filtros por categoría -->
  <?php if(!empty($categorias_serv)): ?>
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:32px;">
    <a href="servicios.php" style="padding:8px 20px;border-radius:20px;font-size:13px;font-weight:600;background:<?= !$filtro_cat?'var(--navy)':'var(--white)' ?>;color:<?= !$filtro_cat?'#fff':'var(--navy)' ?>;border:2px solid var(--navy);transition:all .25s">Todos</a>
    <?php foreach($categorias_serv as $c): ?>
    <a href="servicios.php?categoria=<?= $c['slug'] ?>"
       style="padding:8px 20px;border-radius:20px;font-size:13px;font-weight:600;background:<?= $filtro_cat===$c['slug']?'var(--navy)':'var(--white)' ?>;color:<?= $filtro_cat===$c['slug']?'#fff':'var(--navy)' ?>;border:2px solid var(--navy);transition:all .25s">
      <?= sanitize($c['nombre']) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if(empty($servicios)): ?>
  <div style="text-align:center;padding:60px;color:var(--gray)">
    <i class="fas fa-cogs" style="font-size:3rem;margin-bottom:16px;opacity:.3"></i>
    <p>No hay servicios disponibles actualmente.</p>
  </div>
  <?php else: ?>
  <div class="jv-services-grid">
    <?php foreach($servicios as $s): ?>
    <div class="jv-service-card">
      <?php if($s['imagen_url']): ?>
      <img src="<?= getProductImage($s['imagen_url']) ?>" alt="<?= sanitize($s['titulo']) ?>"
           style="width:100%;height:160px;object-fit:cover;border-radius:10px;margin-bottom:18px;">
      <?php endif; ?>
      <div class="jv-service-icon"><i class="<?= sanitize($s['icono']) ?>"></i></div>
      <h3><?= sanitize($s['titulo']) ?></h3>
      <p><?= sanitize($s['descripcion_corta']??$s['descripcion']??'') ?></p>
      <?php if($s['precio_desde']): ?>
      <div class="jv-service-price" style="margin-bottom:14px">Desde <strong><?= formatPrice($s['precio_desde']) ?></strong></div>
      <?php endif; ?>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="servicio.php?id=<?= $s['id'] ?>" class="btn-service">Ver Detalles <i class="fas fa-arrow-right"></i></a>
        <a href="https://wa.me/<?= getSiteConfig('whatsapp',WHATSAPP_NUMBER) ?>?text=Hola,%20me%20interesa%20el%20servicio:%20<?= urlencode($s['titulo']) ?>"
           target="_blank"
           style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#25D366;border:1.5px solid #25D366;padding:8px 18px;border-radius:6px;transition:all .25s"
           onmouseover="this.style.background='#25D366';this.style.color='#fff'"
           onmouseout="this.style.background='transparent';this.style.color='#25D366'">
          <i class="fab fa-whatsapp"></i> Consultar
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
</section>

<?php require_once 'includes/footer.php'; ?>
