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
                <div class="main-image-container" style="position:relative; width:100%; aspect-ratio:1; display:flex; align-items:center; justify-content:center; background:#fff; overflow:hidden; border-radius:12px; border:1px solid #e2e8f0;">
                    <img src="<?= getProductImage($prod['imagen_url']) ?>" alt="<?= sanitize($prod['nombre']) ?>" id="mainImage" class="img-fluid" style="max-height:100%; object-fit:contain; transition: opacity 0.15s ease;">
                    <div id="mainVideoContainer" style="display:none; width:100%; height:100%; background:#000; align-items:center; justify-content:center;"></div>
                </div>
                
                <?php
                $galItems = [];
                if (!empty($prod['galeria'])) {
                    $galItems = json_decode($prod['galeria'], true) ?: [];
                }
                ?>
                <?php if (!empty($galItems) || $prod['imagen_2'] || $prod['imagen_3']): ?>
                    <div class="thumbnails" style="display:flex; gap:10px; margin-top:15px; overflow-x:auto; padding-bottom:5px;">
                        <!-- Miniatura Principal -->
                        <div class="thumb active" onclick="showMedia('image', '<?= getProductImage($prod['imagen_url']) ?>', this)" style="width:70px; height:70px; border-radius:8px; border:2px solid #1B2A4A; overflow:hidden; cursor:pointer; flex-shrink:0;">
                            <img src="<?= getProductImage($prod['imagen_url']) ?>" style="width:100%; height:100%; object-fit:cover;">
                        </div>
                        
                        <!-- Miniaturas Galería JSON -->
                        <?php foreach ($galItems as $index => $item):
                            $isVid = ($item['tipo'] ?? 'imagen') === 'video';
                            $itemUrl = $item['url'];
                            $fullUrl = (strpos($itemUrl, 'http') === 0) ? $itemUrl : BASE_URL . $itemUrl;
                        ?>
                            <div class="thumb" onclick="showMedia('<?= $isVid ? 'video' : 'image' ?>', '<?= sanitize($fullUrl) ?>', this)" style="width:70px; height:70px; border-radius:8px; border:2px solid transparent; overflow:hidden; cursor:pointer; flex-shrink:0; position:relative; background:#f1f5f9;">
                                <?php if ($isVid): ?>
                                    <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#0f172a; color:#fff;">
                                        <i class="fas fa-play-circle" style="font-size:24px; color:#0ea5e9;"></i>
                                    </div>
                                <?php else: ?>
                                    <img src="<?= sanitize($fullUrl) ?>" style="width:100%; height:100%; object-fit:cover;">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Legacy Fallback -->
                        <?php if (empty($galItems)): ?>
                            <?php if ($prod['imagen_2']): ?>
                                <div class="thumb" onclick="showMedia('image', '<?= getProductImage($prod['imagen_2']) ?>', this)" style="width:70px; height:70px; border-radius:8px; border:2px solid transparent; overflow:hidden; cursor:pointer; flex-shrink:0;">
                                    <img src="<?= getProductImage($prod['imagen_2']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                </div>
                            <?php endif; ?>
                            <?php if ($prod['imagen_3']): ?>
                                <div class="thumb" onclick="showMedia('image', '<?= getProductImage($prod['imagen_3']) ?>', this)" style="width:70px; height:70px; border-radius:8px; border:2px solid transparent; overflow:hidden; cursor:pointer; flex-shrink:0;">
                                    <img src="<?= getProductImage($prod['imagen_3']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                </div>
                            <?php endif; ?>
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

                <?php if ($prod['descripcion_tecnica'] || !empty($dynamicSpecs) || $prod['marca']): ?>
                    <div class="product-specs" style="margin-top:25px; background: #f8fafc; padding: 18px; border-radius: 14px; border: 1px solid #e2e8f0;">
                        <h3 style="font-size:1rem; color:#1B2A4A; font-weight:700; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-list-ul" style="color:#0ea5e9;"></i> Especificaciones Detalladas
                        </h3>

                        <!-- Marca primero -->
                        <?php if ($prod['marca']): ?>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #e2e8f0">
                            <span style="background:#1B2A4A;color:#fff;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600">
                                <i class="fas fa-trademark"></i> <?= sanitize($prod['marca']) ?>
                            </span>
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
    function showMedia(type, url, el) {
        // Quitar clase activa de todas las miniaturas
        document.querySelectorAll('.thumbnails .thumb').forEach(t => {
            t.classList.remove('active');
            t.style.borderColor = 'transparent';
        });
        el.classList.add('active');
        el.style.borderColor = '#1B2A4A';
        
        const mainImg = document.getElementById('mainImage');
        const mainVidCont = document.getElementById('mainVideoContainer');
        
        if (type === 'video') {
            mainImg.style.display = 'none';
            mainVidCont.style.display = 'flex';
            
            // Determinar si es un link de YouTube
            if (url.includes('youtube.com') || url.includes('youtu.be')) {
                let videoId = '';
                if (url.includes('youtube.com/watch')) {
                    const urlParams = new URLSearchParams(new URL(url).search);
                    videoId = urlParams.get('v');
                } else if (url.includes('youtu.be/')) {
                    videoId = url.split('/').pop();
                }
                mainVidCont.innerHTML = `<iframe src="https://www.youtube.com/embed/${videoId}?autoplay=1" style="width:100%; height:100%; border:none;" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
            } else {
                // Video local
                mainVidCont.innerHTML = `<video src="${url}" controls autoplay style="max-width:100%; max-height:100%;"></video>`;
            }
        } else {
            mainVidCont.style.display = 'none';
            mainVidCont.innerHTML = '';
            mainImg.style.display = 'block';
            mainImg.style.opacity = '0.5';
            setTimeout(() => {
                mainImg.src = url;
                mainImg.onload = () => mainImg.style.opacity = '1';
            }, 150);
        }
    }

    function changeImage(el, src) {
        showMedia('image', src, el);
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
                if (img.style.display === 'none') return; // No zoom if video is shown
                const { left, top, width, height } = container.getBoundingClientRect();
                const x = ((e.clientX - left) / width) * 100;
                const y = ((e.clientY - top) / height) * 100;

                img.style.transformOrigin = `${x}% ${y}%`;
            });

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