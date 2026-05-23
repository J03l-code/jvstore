<?php
/**
 * JVSTORE Admin - Dashboard Principal
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Stats generales
$stats = [
  'productos'  => $db->query("SELECT COUNT(*) FROM productos WHERE activo=1")->fetchColumn(),
  'servicios'  => $db->query("SELECT COUNT(*) FROM servicios WHERE activo=1")->fetchColumn(),
  'pedidos'    => $db->query("SELECT COUNT(*) FROM pedidos")->fetchColumn(),
  'clientes'   => $db->query("SELECT COUNT(*) FROM clientes WHERE activo=1")->fetchColumn(),
  'ingresos'   => $db->query("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE estado NOT IN ('cancelado')")->fetchColumn(),
  'pendientes' => $db->query("SELECT COUNT(*) FROM pedidos WHERE estado='pendiente'")->fetchColumn(),
  'mensajes'   => $db->query("SELECT COUNT(*) FROM mensajes WHERE leido=0")->fetchColumn(),
  'stock_bajo' => $db->query("SELECT COUNT(*) FROM productos WHERE stock<=5 AND activo=1")->fetchColumn(),
];

// Últimos pedidos
$ultimosPedidos = $db->query("SELECT p.*, c.nombre AS cliente_nombre FROM pedidos p LEFT JOIN clientes c ON p.cliente_id=c.id ORDER BY p.created_at DESC LIMIT 6")->fetchAll();

// Productos con stock bajo
$stockBajo = $db->query("SELECT p.*, c.nombre AS cat FROM productos p LEFT JOIN categorias c ON p.categoria_id=c.id WHERE p.stock<=5 AND p.activo=1 ORDER BY p.stock ASC LIMIT 6")->fetchAll();

// Mensajes recientes
$mensajes = $db->query("SELECT * FROM mensajes ORDER BY fecha DESC LIMIT 5")->fetchAll();

$estadoColors = ['pendiente'=>'warning','pagado'=>'info','procesando'=>'info','enviado'=>'success','entregado'=>'success','cancelado'=>'danger'];
?>

<!-- STATS -->
<div class="adm-stats">
  <div class="adm-stat gold">
    <div class="adm-stat-icon"><i class="fas fa-dollar-sign"></i></div>
    <div class="adm-stat-info"><h3><?= formatPrice($stats['ingresos']) ?></h3><p>Ingresos Totales</p></div>
  </div>
  <div class="adm-stat">
    <div class="adm-stat-icon"><i class="fas fa-shopping-bag"></i></div>
    <div class="adm-stat-info"><h3><?= $stats['pedidos'] ?></h3><p>Pedidos Totales</p></div>
  </div>
  <div class="adm-stat green">
    <div class="adm-stat-icon"><i class="fas fa-box"></i></div>
    <div class="adm-stat-info"><h3><?= $stats['productos'] ?></h3><p>Productos Activos</p></div>
  </div>
  <div class="adm-stat">
    <div class="adm-stat-icon"><i class="fas fa-users"></i></div>
    <div class="adm-stat-info"><h3><?= $stats['clientes'] ?></h3><p>Clientes</p></div>
  </div>
</div>

<!-- ACCESOS RÁPIDOS -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:28px;">
  <?php
  $accesos = [
    ['productos.php?action=new','fas fa-plus','Nuevo Producto','navy'],
    ['servicios.php?action=new','fas fa-cogs','Nuevo Servicio','navy'],
    ['categorias.php','fas fa-tags','Categorías','gold'],
    ['banners.php','fas fa-images','Banners','navy'],
    ['pedidos.php','fas fa-shopping-bag','Pedidos','navy'],
    ['configuracion.php','fas fa-sliders-h','Configuración','gold'],
  ];
  foreach($accesos as [$url,$ico,$label,$color]):
  ?>
  <a href="<?= BASE_URL ?>admin/<?= $url ?>"
     style="background:#fff;border-radius:12px;padding:18px 14px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,.06);transition:all .2s;border-top:3px solid <?= $color==='gold'?'var(--gold)':'var(--navy)' ?>;display:block;"
     onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
    <i class="<?=$ico?>" style="font-size:22px;color:<?= $color==='gold'?'var(--gold)':'var(--navy)' ?>;margin-bottom:8px;display:block"></i>
    <span style="font-size:12px;font-weight:600;color:#374151"><?=$label?></span>
  </a>
  <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">

<!-- ÚLTIMOS PEDIDOS -->
<div class="adm-card">
  <div class="adm-card-header">
    <h2><i class="fas fa-shopping-bag" style="color:var(--gold)"></i> Últimos Pedidos</h2>
    <a href="<?= BASE_URL ?>admin/pedidos.php" class="btn btn-sm btn-outline">Ver todos</a>
  </div>
  <div style="overflow-x:auto">
  <table class="adm-table">
    <thead><tr><th>Código</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
    <tbody>
    <?php if(empty($ultimosPedidos)): ?>
    <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:30px">Sin pedidos aún</td></tr>
    <?php else: foreach($ultimosPedidos as $p): ?>
    <tr>
      <td><a href="<?= BASE_URL ?>admin/pedidos.php?id=<?=$p['id']?>" style="color:var(--navy);font-weight:600"><?= sanitize($p['codigo']) ?></a></td>
      <td><?= sanitize($p['nombre_cliente']) ?></td>
      <td><strong><?= formatPrice($p['total']) ?></strong></td>
      <td><span class="badge badge-<?= $estadoColors[$p['estado']]??'gray' ?>"><?= ucfirst($p['estado']) ?></span></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- STOCK BAJO -->
<div class="adm-card">
  <div class="adm-card-header">
    <h2><i class="fas fa-exclamation-triangle" style="color:var(--warning)"></i> Stock Bajo</h2>
    <a href="<?= BASE_URL ?>admin/productos.php" class="btn btn-sm btn-outline">Ver todos</a>
  </div>
  <div style="overflow-x:auto">
  <table class="adm-table">
    <thead><tr><th>Producto</th><th>Categoría</th><th>Stock</th></tr></thead>
    <tbody>
    <?php if(empty($stockBajo)): ?>
    <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:30px"><i class="fas fa-check-circle" style="color:var(--success)"></i> Todo el stock OK</td></tr>
    <?php else: foreach($stockBajo as $p): ?>
    <tr>
      <td><a href="<?= BASE_URL ?>admin/productos.php?edit=<?=$p['id']?>" style="color:var(--navy);font-weight:600"><?= sanitize(truncateText($p['nombre'],30)) ?></a></td>
      <td><?= sanitize($p['cat']??'—') ?></td>
      <td><span class="badge badge-<?= $p['stock']===0?'danger':'warning' ?>"><?= $p['stock']===0?'Agotado':$p['stock'].' uds' ?></span></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>

</div><!-- /grid -->

<!-- MENSAJES + SERVICIOS RESUMEN -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

<!-- MENSAJES -->
<div class="adm-card">
  <div class="adm-card-header">
    <h2><i class="fas fa-envelope" style="color:var(--info)"></i> Mensajes Recientes</h2>
    <a href="<?= BASE_URL ?>admin/mensajes.php" class="btn btn-sm btn-outline">Ver todos</a>
  </div>
  <div style="overflow-x:auto">
  <table class="adm-table">
    <thead><tr><th>Nombre</th><th>Asunto</th><th>Fecha</th></tr></thead>
    <tbody>
    <?php if(empty($mensajes)): ?>
    <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:30px">Sin mensajes</td></tr>
    <?php else: foreach($mensajes as $m): ?>
    <tr>
      <td style="font-weight:<?= $m['leido']?'400':'700' ?>"><?= sanitize($m['nombre']) ?></td>
      <td style="color:#64748b"><?= sanitize(truncateText($m['asunto']??'Sin asunto',28)) ?></td>
      <td style="color:#94a3b8;font-size:12px"><?= date('d/m',strtotime($m['fecha'])) ?></td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- RESUMEN CATÁLOGO -->
<div class="adm-card">
  <div class="adm-card-header"><h2><i class="fas fa-chart-pie" style="color:var(--gold)"></i> Resumen del Catálogo</h2></div>
  <div class="adm-card-body">
    <?php
    $cats = $db->query("SELECT c.nombre, COUNT(p.id) AS total FROM categorias c LEFT JOIN productos p ON p.categoria_id=c.id AND p.activo=1 WHERE c.activo=1 GROUP BY c.id ORDER BY total DESC LIMIT 6")->fetchAll();
    $maxTotal = max(array_column($cats,'total') ?: [1]);
    foreach($cats as $c):
      $pct = $maxTotal > 0 ? round($c['total']/$maxTotal*100) : 0;
    ?>
    <div style="margin-bottom:14px">
      <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
        <span><?= sanitize($c['nombre']) ?></span>
        <span style="font-weight:600"><?= $c['total'] ?> prods</span>
      </div>
      <div style="background:#f1f5f9;border-radius:4px;height:6px">
        <div style="background:var(--navy);height:6px;border-radius:4px;width:<?=$pct?>%;transition:width .5s"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>