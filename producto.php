<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    redirect(BASE_URL . 'tienda.php');
}

$stmt = $db->prepare("SELECT p.*, c.nombre as categoria_nombre, c.slug as categoria_slug FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.id = ? AND p.activo = 1");
$stmt->execute([$id]);
$prod = $stmt->fetch();
if (!$prod) {
    redirect(BASE_URL . 'tienda.php');
}

$pageTitle = $prod['nombre'];
$pageDescription = truncateText($prod['descripcion'], 160);

// Productos relacionados (misma categoría)
$stmt = $db->prepare("SELECT p.*, c.nombre as categoria_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.categoria_id = ? AND p.id != ? AND p.activo = 1 LIMIT 4");
$stmt->execute([$prod['categoria_id'], $prod['id']]);
$relacionados = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>
            <?= sanitize($prod['nombre']) ?>
        </h1>
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>">Inicio</a> <span>/</span>
            <a href="<?= BASE_URL ?>tienda.php">Tienda</a> <span>/</span>
            <?php if ($prod['categoria_nombre']): ?>
                <a href="<?= BASE_URL ?>tienda.php?categoria=<?= $prod['categoria_slug'] ?>">
                    <?= sanitize($prod['categoria_nombre']) ?>
                </a> <span>/</span>
            <?php endif; ?>
            <span>
                <?= truncateText($prod['nombre'], 30) ?>
            </span>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="product-detail">
            <div class="product-gallery">
                <div class="main-image-container">
                    <img src="<?= getProductImage($prod['imagen_url']) ?>" alt="<?= sanitize($prod['nombre']) ?>"
                        id="mainImage" class="img-fluid">
                </div>
                <?php if ($prod['imagen_2'] || $prod['imagen_3']): ?>
                    <div class="thumbnails">
                        <div class="thumb active"
                            onclick="changeImage(this, '<?= getProductImage($prod['imagen_url']) ?>')">
                            <img src="<?= getProductImage($prod['imagen_url']) ?>" alt="Vista 1">
                        </div>
                        <?php if ($prod['imagen_2']): ?>
                            <div class="thumb" onclick="changeImage(this, '<?= getProductImage($prod['imagen_2']) ?>')">
                                <img src="<?= getProductImage($prod['imagen_2']) ?>" alt="Vista 2">
                            </div>
                        <?php endif; ?>
                        <?php if ($prod['imagen_3']): ?>
                            <div class="thumb" onclick="changeImage(this, '<?= getProductImage($prod['imagen_3']) ?>')">
                                <img src="<?= getProductImage($prod['imagen_3']) ?>" alt="Vista 3">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="product-info">
                <div class="product-meta-top">
                    <div class="oem-code" title="Código SKU">
                        <i class="fas fa-barcode"></i> SKU: <?= sanitize($prod['sku'] ?? $prod['oem_code']) ?>
                    </div>
                    <?php if ($prod['marca']): ?>
                        <div class="brand-badge"><?= sanitize($prod['marca']) ?></div>
                    <?php endif; ?>
                </div>

                <h1 class="product-title">
                    <?= sanitize($prod['nombre']) ?>
                </h1>

                <div class="price-block">
                    <span class="price-main">
                        <?= formatPrice($prod['precio_oferta'] ?? $prod['precio']) ?>
                    </span>
                    <?php if ($prod['precio_oferta']): ?>
                        <span class="price-original">
                            <?= formatPrice($prod['precio']) ?>
                        </span>
                        <span class="discount-tag">
                            AHORRAS <?= round(100 - ($prod['precio_oferta'] / $prod['precio'] * 100)) ?>%
                        </span>
                    <?php endif; ?>
                </div>

                <p class="product-description">
                    <?= nl2br(sanitize($prod['descripcion'])) ?>
                </p>

                <div
                    class="stock-indicator <?= $prod['stock'] > 5 ? 'in-stock' : ($prod['stock'] > 0 ? 'low-stock' : 'no-stock') ?>">
                    <i class="fas fa-<?= $prod['stock'] > 0 ? 'check-circle' : 'times-circle' ?>"></i>
                    <?= $prod['stock'] > 5 ? 'En Stock (' . $prod['stock'] . ' disponibles)' : ($prod['stock'] > 0 ? 'Últimas ' . $prod['stock'] . ' unidades' : 'Agotado') ?>
                </div>

                <?php
                $dynamicSpecs = [];
                if (!empty($prod['atributos'])) {
                    $dynamicSpecs = json_decode($prod['atributos'], true) ?: [];
                }
                ?>

                <?php if ($prod['descripcion_tecnica'] || !empty($dynamicSpecs) || $prod['modelo'] || $prod['marca']): ?>
                    <div class="product-specs" style="margin-top:25px; background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <h3 style="font-size:1.05rem; color:#1B2A4A; font-weight:700; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-list-ul" style="color:#ffd700;"></i> Especificaciones Detalladas
                        </h3>
                        <table class="specs-table" style="width:100%; border-collapse:collapse;">
                            <?php if ($prod['marca']): ?>
                                <tr>
                                    <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0; color:#64748b; font-size:13px;">Marca</td>
                                    <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-size:13px;"><strong><?= sanitize($prod['marca']) ?></strong></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($prod['modelo']): ?>
                                <tr>
                                    <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0; color:#64748b; font-size:13px;">Modelo</td>
                                    <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-size:13px;"><strong><?= sanitize($prod['modelo']) ?></strong></td>
                                </tr>
                            <?php endif; ?>
                            
                            <!-- Atributos Dinámicos -->
                            <?php foreach ($dynamicSpecs as $key => $val): ?>
                                <tr>
                                    <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0; color:#64748b; font-size:13px;"><?= sanitize($key) ?></td>
                                    <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-size:13px;"><strong><?= sanitize($val) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Atributos Legacy -->
                            <?php
                            if ($prod['descripcion_tecnica']) {
                                $specs = explode('|', $prod['descripcion_tecnica']);
                                foreach ($specs as $spec):
                                    $parts = explode(':', $spec, 2);
                                    if (count($parts) === 2):
                                        ?>
                                        <tr>
                                            <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0; color:#64748b; font-size:13px;"><?= sanitize(trim($parts[0])) ?></td>
                                            <td style="padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-size:13px;"><strong><?= sanitize(trim($parts[1])) ?></strong></td>
                                        </tr>
                                        <?php
                                    endif;
                                endforeach;
                            }
                            ?>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if ($prod['stock'] > 0): ?>
                    <div class="purchase-actions">
                        <div class="quantity-wrapper">
                            <label>Cantidad:</label>
                            <div class="quantity-selector">
                                <button type="button" onclick="changeQty(-1)">−</button>
                                <input type="number" id="quantity" value="1" min="1" max="<?= $prod['stock'] ?>" readonly>
                                <button type="button" onclick="changeQty(1)">+</button>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-lg btn-block"
                            onclick="addToCart(<?= $prod['id'] ?>, document.getElementById('quantity').value)"
                            id="addToCartBtn">
                            <i class="fas fa-cart-plus"></i> Añadir al Carrito
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($prod['compatibilidad']): ?>
                    <div class="compatibility-list">
                        <h4><i class="fas fa-check-double"></i> Compatibilidad Verificada</h4>
                        <p><?= sanitize($prod['compatibilidad']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($relacionados) > 0): ?>
            <div class="mt-4 related-section">
                <div class="section-header">
                    <h2>Productos Relacionados</h2>
                    <div class="line"></div>
                </div>
                <div class="products-grid">
                    <?php foreach ($relacionados as $rel): ?>
                        <div class="card product-card">
                            <a href="<?= BASE_URL ?>producto.php?id=<?= $rel['id'] ?>">
                                <div class="card-img">
                                    <img src="<?= getProductImage($rel['imagen_url']) ?>" alt="<?= sanitize($rel['nombre']) ?>"
                                        loading="lazy">
                                </div>
                            </a>
                            <div class="card-body">
                                <div class="card-category">
                                    <?= sanitize($rel['categoria_nombre'] ?? '') ?>
                                </div>
                                <a href="<?= BASE_URL ?>producto.php?id=<?= $rel['id'] ?>">
                                    <h3 class="card-title">
                                        <?= sanitize($rel['nombre']) ?>
                                    </h3>
                                </a>
                                <div class="card-footer">
                                    <span class="price">
                                        <?= formatPrice($rel['precio_oferta'] ?? $rel['precio']) ?>
                                    </span>
                                    <?php if ($rel['stock'] > 0): ?>
                                        <button class="btn-add-cart" onclick="addToCart(<?= $rel['id'] ?>)"><i
                                                class="fas fa-cart-plus"></i></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
    function changeImage(el, src) {
        const main = document.getElementById('mainImage');
        main.style.opacity = '0.5';
        setTimeout(() => {
            main.src = src;
            main.onload = () => main.style.opacity = '1';
        }, 150);

        document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
        el.classList.add('active');
    }

    function changeQty(delta) {
        const input = document.getElementById('quantity');
        const max = parseInt(input.max);
        let val = parseInt(input.value) + delta;
        if (val >= 1 && val <= max) {
            input.value = val;
        }
    }

    // Zoom Script
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.querySelector('.main-image-container');
        const img = document.getElementById('mainImage');

        if (container && img) {
            container.addEventListener('mousemove', function (e) {
                const { left, top, width, height } = container.getBoundingClientRect();
                const x = ((e.clientX - left) / width) * 100;
                const y = ((e.clientY - top) / height) * 100;

                img.style.transformOrigin = `${x}% ${y}%`;
            });

            // Resetear al salir (opcional, para suavidad)
            container.addEventListener('mouseleave', function () {
                setTimeout(() => {
                    img.style.transformOrigin = 'center center';
                }, 200);
            });
        }
    });
</script>

<?php
$extraScripts = ['cart.js'];
require_once 'includes/footer.php';
?>