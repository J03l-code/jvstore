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

                // Obtener mapa de iconos de la categoría
                $catFiltroIconos = [];
                if (!empty($prod['categoria_id'])) {
                    $stmtCatFiltros = $db->prepare("SELECT atributos FROM categorias WHERE id = ?");
                    $stmtCatFiltros->execute([$prod['categoria_id']]);
                    $catFiltrosRaw = $stmtCatFiltros->fetchColumn();
                    if ($catFiltrosRaw) {
                        $catFiltrosArr = json_decode($catFiltrosRaw, true) ?: [];
                        foreach ($catFiltrosArr as $cf) {
                            if (is_array($cf)) {
                                $catFiltroIconos[$cf['nombre']] = $cf['icono'] ?? 'fas fa-filter';
                            }
                        }
                    }
                }
                ?>

                <?php if ($prod['descripcion_tecnica'] || !empty($dynamicSpecs) || $prod['modelo'] || $prod['marca']): ?>
                    <div class="product-specs" style="margin-top:25px; background: #f8fafc; padding: 18px; border-radius: 14px; border: 1px solid #e2e8f0;">
                        <h3 style="font-size:1rem; color:#1B2A4A; font-weight:700; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-list-ul" style="color:#ffd700;"></i> Especificaciones Detalladas
                        </h3>

                        <!-- Marca y Modelo primero -->
                        <?php if ($prod['marca'] || $prod['modelo']): ?>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #e2e8f0">
                            <?php if ($prod['marca']): ?>
                            <span style="background:#1B2A4A;color:#fff;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600">
                                <i class="fas fa-trademark"></i> <?= sanitize($prod['marca']) ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($prod['modelo']): ?>
                            <span style="background:#2C4A7C;color:#fff;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600">
                                <i class="fas fa-tag"></i> <?= sanitize($prod['modelo']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Atributos Dinámicos con pills multi-valor -->
                        <?php if (!empty($dynamicSpecs)): ?>
                        <div style="display:flex;flex-direction:column;gap:14px;margin-bottom:<?= !empty($prod['descripcion_tecnica']) ? '14px;padding-bottom:14px;border-bottom:1px solid #e2e8f0' : '0' ?>">
                            <?php foreach ($dynamicSpecs as $key => $val): ?>
                                <?php
                                $filtroIcon = $catFiltroIconos[$key] ?? 'fas fa-filter';
                                $valores = array_map('trim', explode(',', $val));
                                ?>
                                <div style="display:flex;align-items:flex-start;gap:10px">
                                    <span style="min-width:110px;font-size:12px;color:#64748b;font-weight:600;display:flex;align-items:center;gap:5px;padding-top:4px">
                                        <i class="<?= sanitize($filtroIcon) ?>" style="color:#1B2A4A;width:14px;text-align:center"></i>
                                        <?= sanitize($key) ?>
                                    </span>
                                    <div style="display:flex;flex-wrap:wrap;gap:6px">
                                        <?php foreach ($valores as $v): ?>
                                            <?php if ($v !== ''): ?>
                                            <span style="background:#e8edf5;color:#1B2A4A;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;border:1px solid #d1dae8">
                                                <?= sanitize($v) ?>
                                            </span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Atributos Legacy (descripcion_tecnica) -->
                        <?php if ($prod['descripcion_tecnica']): ?>
                        <table style="width:100%;border-collapse:collapse;font-size:13px">
                            <?php
                            $specs = explode('|', $prod['descripcion_tecnica']);
                            foreach ($specs as $spec):
                                $parts = explode(':', $spec, 2);
                                if (count($parts) === 2):
                            ?>
                            <tr>
                                <td style="padding:7px 10px;border-bottom:1px solid #e2e8f0;color:#64748b;width:40%"><?= sanitize(trim($parts[0])) ?></td>
                                <td style="padding:7px 10px;border-bottom:1px solid #e2e8f0"><strong><?= sanitize(trim($parts[1])) ?></strong></td>
                            </tr>
                            <?php endif; endforeach; ?>
                        </table>
                        <?php endif; ?>
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