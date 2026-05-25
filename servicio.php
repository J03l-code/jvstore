<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    redirect(BASE_URL . 'servicios.php');
}

$stmt = $db->prepare("SELECT s.*, c.nombre as categoria_nombre, c.slug as categoria_slug FROM servicios s LEFT JOIN categorias c ON s.categoria_id = c.id WHERE s.id = ? AND s.activo = 1");
$stmt->execute([$id]);
$serv = $stmt->fetch();
if (!$serv) {
    redirect(BASE_URL . 'servicios.php');
}

$pageTitle = $serv['titulo'];
$pageDescription = truncateText($serv['descripcion_corta'] ?: $serv['descripcion'], 160);

// Servicios relacionados (misma categoría)
$stmt = $db->prepare("SELECT s.*, c.nombre as categoria_nombre FROM servicios s LEFT JOIN categorias c ON s.categoria_id = c.id WHERE s.categoria_id = ? AND s.id != ? AND s.activo = 1 LIMIT 4");
$stmt->execute([$serv['categoria_id'], $serv['id']]);
$relacionados = $stmt->fetchAll();

// Obtener sub-servicios independientes
$stmt = $db->prepare("SELECT * FROM servicios WHERE parent_id = ? AND activo = 1 ORDER BY orden");
$stmt->execute([$serv['id']]);
$subservicios_independientes = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>
            <?= sanitize($serv['titulo']) ?>
        </h1>
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>">Inicio</a> <span>/</span>
            <a href="<?= BASE_URL ?>servicios.php">Servicios</a> <span>/</span>
            <?php if ($serv['categoria_nombre']): ?>
                <a href="<?= BASE_URL ?>servicios.php?categoria=<?= $serv['categoria_slug'] ?>">
                    <?= sanitize($serv['categoria_nombre']) ?>
                </a> <span>/</span>
            <?php endif; ?>
            <span>
                <?= truncateText($serv['titulo'], 30) ?>
            </span>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (!empty($subservicios_independientes)): ?>
            <!-- Si este servicio contiene sub-servicios independientes -->
            <div style="margin-bottom: 45px; text-align: center;">
                <p style="font-size: 1.15rem; color: var(--navy-mid); max-width: 750px; margin: 0 auto 35px; line-height: 1.6; font-weight: 500;">
                    <?= sanitize($serv['descripcion']) ?>
                </p>
                <div style="width: 60px; height: 3px; background: var(--gold); margin: 0 auto;"></div>
            </div>
            
            <div class="jv-services-grid" style="margin-top: 30px;">
                <?php foreach ($subservicios_independientes as $sub): ?>
                    <div class="jv-service-card" style="display: flex; flex-direction: column; justify-content: space-between; min-height: 380px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; transition: all 0.3s ease; background: #fff;" onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 10px 25px rgba(0,0,0,0.08)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 20px rgba(0,0,0,0.04)'">
                      <div>
                        <?php if($sub['imagen_url']): ?>
                        <img src="<?= getProductImage($sub['imagen_url']) ?>" alt="<?= sanitize($sub['titulo']) ?>"
                             style="width:100%;height:180px;object-fit:cover;border-radius:10px;margin-bottom:18px;">
                        <?php else: ?>
                        <div style="width:100%; height:180px; background:#f8fafc; border-radius:10px; display:flex; align-items:center; justify-content:center; margin-bottom:18px; border: 1px solid #e2e8f0;">
                            <i class="<?= sanitize($sub['icono'] ?: 'fas fa-cog') ?>" style="font-size: 4rem; color: var(--gold); opacity:0.8;"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                            <div class="jv-service-icon" style="margin: 0; width: 36px; height: 36px; font-size: 14px;"><i class="<?= sanitize($sub['icono'] ?: 'fas fa-cog') ?>"></i></div>
                            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--navy-dark);"><?= sanitize($sub['titulo']) ?></h3>
                        </div>
                        <p style="margin-bottom:16px; font-size: 0.9rem; color: var(--navy-mid); line-height: 1.5;"><?= sanitize($sub['descripcion_corta'] ?: truncateText($sub['descripcion'], 100)) ?></p>
                      </div>
                      <div>
                        <?php if($sub['precio_desde']): ?>
                        <div class="jv-service-price" style="margin-bottom:16px; font-size: 0.95rem; color: var(--navy-mid);">Desde <strong style="font-size: 1.25rem; color: var(--navy-dark);"><?= formatPrice($sub['precio_desde']) ?></strong></div>
                        <?php else: ?>
                        <div class="jv-service-price" style="margin-bottom:16px; color: var(--gold); font-weight: 700; font-size: 0.95rem;">Precio bajo cotización</div>
                        <?php endif; ?>
                        <a href="servicio.php?id=<?= $sub['id'] ?>" class="btn-service" style="width:100%; text-align:center; display: flex; align-items: center; justify-content: center; gap: 6px;">Ver Detalles <i class="fas fa-arrow-right"></i></a>
                      </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Si es un servicio final sin sub-servicios -->
            <div class="product-detail">
                <div class="product-gallery">
                    <div class="main-image-container" style="position:relative; width:100%; display:flex; align-items:center; justify-content:center; background:#f8fafc; overflow:hidden; border-radius:12px; border:1px solid #e2e8f0; min-height: 400px;">
                        <?php if ($serv['imagen_url']): ?>
                            <img src="<?= getProductImage($serv['imagen_url']) ?>" alt="<?= sanitize($serv['titulo']) ?>" id="mainImage" class="img-fluid" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <i class="<?= sanitize($serv['icono']) ?>" style="font-size: 8rem; color: var(--gold); opacity: 0.8;"></i>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="product-info">
                    <h1 class="product-title" style="display:flex; align-items:center; gap: 12px; margin-bottom: 20px;">
                        <i class="<?= sanitize($serv['icono']) ?>" style="color:var(--gold);"></i>
                        <?= sanitize($serv['titulo']) ?>
                    </h1>

                    <?php if ($serv['precio_desde']): ?>
                        <div class="price-block" style="margin-bottom: 20px;">
                            <span class="price-main" style="font-size: 1.2rem; color: #64748b;">Desde</span>
                            <span class="price-main" style="margin-left: 8px;">
                                <?= formatPrice($serv['precio_desde']) ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="price-block" style="margin-bottom: 20px;">
                            <span class="price-main" style="font-size: 1.3rem; color: var(--gold);">Precio por cotización</span>
                        </div>
                    <?php endif; ?>

                    <?php if ($serv['descripcion_corta']): ?>
                    <p class="product-description" style="font-size: 1.1rem; font-weight: 500; color: var(--navy-mid);">
                        <?= sanitize($serv['descripcion_corta']) ?>
                    </p>
                    <?php endif; ?>

                    <div class="product-description" style="margin-top: 20px;">
                        <?= nl2br(sanitize($serv['descripcion'])) ?>
                    </div>

                    <?php
                    $subservicios = json_decode($serv['caracteristicas'] ?? '[]', true) ?: [];
                    if (!empty($subservicios)):
                    ?>
                    <div class="service-features" style="margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 25px;">
                        <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--navy-dark); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-list-ul" style="color: var(--gold);"></i> ¿Qué incluye este servicio?
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px;">
                            <?php foreach ($subservicios as $item): ?>
                                <div style="display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                    <i class="fas fa-check-circle" style="color: #10b981; font-size: 1rem; flex-shrink: 0;"></i>
                                    <span style="font-size: 0.95rem; font-weight: 600; color: var(--navy-mid);"><?= sanitize($item) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="purchase-actions" style="margin-top: 30px;">
                        <a href="https://wa.me/<?= getSiteConfig('whatsapp',WHATSAPP_NUMBER) ?>?text=Hola,%20me%20interesa%20el%20servicio:%20<?= urlencode($serv['titulo']) ?>"
                           target="_blank"
                           class="btn btn-primary btn-lg btn-block"
                           style="background:#25D366; border-color:#25D366; display:flex; align-items:center; justify-content:center; gap: 10px;">
                            <i class="fab fa-whatsapp" style="font-size: 1.2rem;"></i> Solicitar Cotización
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (count($relacionados) > 0): ?>
            <div class="mt-4 related-section">
                <div class="section-header">
                    <h2>Servicios Relacionados</h2>
                    <div class="line"></div>
                </div>
                <div class="jv-services-grid" style="margin-top: 20px;">
                    <?php foreach ($relacionados as $rel): ?>
                        <div class="jv-service-card">
                          <?php if($rel['imagen_url']): ?>
                          <img src="<?= getProductImage($rel['imagen_url']) ?>" alt="<?= sanitize($rel['titulo']) ?>"
                               style="width:100%;height:160px;object-fit:cover;border-radius:10px;margin-bottom:18px;">
                          <?php endif; ?>
                          <div class="jv-service-icon"><i class="<?= sanitize($rel['icono']) ?>"></i></div>
                          <h3><?= sanitize($rel['titulo']) ?></h3>
                          <p><?= sanitize($rel['descripcion_corta']??$rel['descripcion']??'') ?></p>
                          <?php if($rel['precio_desde']): ?>
                          <div class="jv-service-price" style="margin-bottom:14px">Desde <strong><?= formatPrice($rel['precio_desde']) ?></strong></div>
                          <?php endif; ?>
                          <div style="display:flex;gap:10px;flex-wrap:wrap">
                            <a href="servicio.php?id=<?= $rel['id'] ?>" class="btn-service">Ver Detalles <i class="fas fa-arrow-right"></i></a>
                          </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
