<?php
$pageTitle = 'Tienda';
$pageDescription = 'Catálogo completo de productos de alta calidad.';

require_once 'includes/header.php';

$db = getDB();

// Obtener categorías para filtros
$categorias = $db->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll();

// Cargar categoría activa para filtros dinámicos
$categoriaActiva = null;
$atributosFiltrables = []; // array de {nombre, icono}
$valoresFiltros = [];

if (!empty($_GET['categoria'])) {
    $stmtCat = $db->prepare("SELECT * FROM categorias WHERE slug = ? AND activo = 1");
    $stmtCat->execute([$_GET['categoria']]);
    $categoriaActiva = $stmtCat->fetch();
    
    if ($categoriaActiva && !empty($categoriaActiva['atributos'])) {
        $decoded = json_decode($categoriaActiva['atributos'], true) ?: [];
        // Normalizar: viejo formato (strings) → nuevo ({nombre, icono})
        foreach ($decoded as $item) {
            if (is_string($item)) {
                $atributosFiltrables[] = ['nombre' => $item, 'icono' => 'fas fa-filter'];
            } else {
                $atributosFiltrables[] = $item;
            }
        }
        
        // Para cada atributo, extraer TODOS los valores únicos (incluyendo multi-valor)
        foreach ($atributosFiltrables as $attr) {
            $attrName = $attr['nombre'];
            $sqlAttr = "SELECT JSON_UNQUOTE(JSON_EXTRACT(atributos, '$." . addslashes($attrName) . "')) AS val 
                        FROM productos 
                        WHERE categoria_id = ? AND activo = 1 AND atributos IS NOT NULL";
            $stmtAttr = $db->prepare($sqlAttr);
            $stmtAttr->execute([$categoriaActiva['id']]);
            $rawVals = $stmtAttr->fetchAll(PDO::FETCH_COLUMN);
            // Expandir valores separados por coma
            $uniqueVals = [];
            foreach ($rawVals as $rv) {
                if ($rv !== null && $rv !== '') {
                    foreach (array_map('trim', explode(',', $rv)) as $v) {
                        if ($v !== '') $uniqueVals[$v] = true;
                    }
                }
            }
            ksort($uniqueVals);
            $valoresFiltros[$attrName] = array_keys($uniqueVals);
        }
    }
}

// Obtener marcas y modelos únicos
$marcas = $db->query("SELECT DISTINCT marca FROM productos WHERE activo = 1 AND marca IS NOT NULL ORDER BY marca")->fetchAll(PDO::FETCH_COLUMN);
$modelos = $db->query("SELECT DISTINCT modelo FROM productos WHERE activo = 1 AND modelo IS NOT NULL ORDER BY modelo")->fetchAll(PDO::FETCH_COLUMN);

// Construir consulta con filtros
$where = ["p.activo = 1"];
$params = [];

if (!empty($_GET['buscar'])) {
    $where[] = "(p.nombre LIKE ? OR p.sku LIKE ? OR p.descripcion LIKE ? OR p.marca LIKE ? OR p.modelo LIKE ?)";
    $searchTerm = '%' . $_GET['buscar'] . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}
if (!empty($_GET['categoria'])) {
    $where[] = "c.slug = ?";
    $params[] = $_GET['categoria'];
}
if (!empty($_GET['marca'])) {
    $where[] = "p.marca = ?";
    $params[] = $_GET['marca'];
}
if (!empty($_GET['modelo'])) {
    $where[] = "p.modelo = ?";
    $params[] = $_GET['modelo'];
}
if (!empty($_GET['precio_min'])) {
    $where[] = "COALESCE(p.precio_oferta, p.precio) >= ?";
    $params[] = (float) $_GET['precio_min'];
}
if (!empty($_GET['precio_max'])) {
    $where[] = "COALESCE(p.precio_oferta, p.precio) <= ?";
    $params[] = (float) $_GET['precio_max'];
}

// Aplicar filtros dinámicos si corresponden a la categoría seleccionada
if ($categoriaActiva && !empty($_GET['filtro']) && is_array($_GET['filtro'])) {
    foreach ($_GET['filtro'] as $key => $value) {
        if ($value !== '' && in_array($key, $atributosFiltrables)) {
            $where[] = "JSON_UNQUOTE(JSON_EXTRACT(p.atributos, '$." . str_replace('"', '\\"', $key) . "')) = ?";
            $params[] = $value;
        }
    }
}

$orderBy = "p.created_at DESC";
if (!empty($_GET['orden'])) {
    switch ($_GET['orden']) {
        case 'precio_asc':
            $orderBy = "COALESCE(p.precio_oferta, p.precio) ASC";
            break;
        case 'precio_desc':
            $orderBy = "COALESCE(p.precio_oferta, p.precio) DESC";
            break;
        case 'nombre':
            $orderBy = "p.nombre ASC";
            break;
        case 'nuevo':
            $orderBy = "p.created_at DESC";
            break;
    }
}

$whereClause = implode(" AND ", $where);
$sql = "SELECT p.*, c.nombre as categoria_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE $whereClause ORDER BY $orderBy";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();
$totalProductos = count($productos);
?>

<section class="page-header"
    style="background-image: linear-gradient(rgba(10, 25, 47, 0.85), rgba(10, 25, 47, 0.9)), url('<?= getHeaderImage($_GET['categoria'] ?? '') ?>');">
    <div class="container" style="position:relative; z-index:2;">
        <h1
            style="color: #ffffff !important; text-shadow: 0 4px 8px rgba(0,0,0,0.9); font-weight: 800; font-size: 3rem;">
            Tienda Online</h1>
        <div class="breadcrumb"
            style="background: rgba(0,0,0,0.5); padding: 5px 15px; border-radius: 20px; display: inline-flex;">
            <a href="<?= BASE_URL ?>" style="color: #ffffff !important; text-decoration: none;">Inicio</a> <span
                style="color: #ffffff !important; margin: 0 5px;">/</span>
            <span style="color: #ffffff !important;">Tienda</span>
            <?php if (!empty($_GET['categoria'])): ?>
                <span style="color: #ffffff !important; margin: 0 5px;">/</span> <span
                    style="color: #ffd700 !important; font-weight: bold;">
                    <?= sanitize(ucfirst($_GET['categoria'])) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="shop-layout">
            <!-- Sidebar Filters -->
            <aside class="shop-sidebar" id="shopSidebar">
                <form method="GET" action="<?= BASE_URL ?>tienda.php" id="filterForm">
                    <div class="filter-section">
                        <h4><i class="fas fa-search"></i> Buscar</h4>
                        <input type="text" name="buscar" placeholder="Nombre, marca o SKU..."
                            value="<?= sanitize($_GET['buscar'] ?? '') ?>">
                    </div>
                    <div class="filter-section">
                        <h4><i class="fas fa-tags"></i> Categoría</h4>
                        <select name="categoria" onchange="this.form.submit()">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['slug'] ?>" <?= ($_GET['categoria'] ?? '') === $cat['slug'] ? 'selected' : '' ?>>
                                    <?= sanitize($cat['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filtros Dinámicos de Categoría -->
                    <?php if (!empty($atributosFiltrables)): ?>
                        <?php foreach ($atributosFiltrables as $attr): ?>
                            <?php 
                            $attrName = is_array($attr) ? $attr['nombre'] : $attr;
                            $attrIcon = is_array($attr) ? ($attr['icono'] ?? 'fas fa-filter') : 'fas fa-filter';
                            ?>
                            <?php if (!empty($valoresFiltros[$attrName])): ?>
                                <div class="filter-section" style="background: rgba(27, 42, 74, 0.04); padding: 12px; border-radius: 8px; border-left: 3px solid #ffd700;">
                                    <h4 style="color: #1B2A4A; font-weight: 700; font-size: 0.88rem; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                                        <i class="<?= sanitize($attrIcon) ?>" style="color: #ffd700; width:16px; text-align:center;"></i>
                                        <?= sanitize($attrName) ?>
                                    </h4>
                                    <select name="filtro[<?= sanitize($attrName) ?>]" onchange="this.form.submit()" style="border: 1px solid rgba(27, 42, 74, 0.15);">
                                        <option value="">Todos/as</option>
                                        <?php foreach ($valoresFiltros[$attrName] as $val): ?>
                                            <option value="<?= sanitize($val) ?>" <?= ($_GET['filtro'][$attrName] ?? '') === $val ? 'selected' : '' ?>>
                                                <?= sanitize($val) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="filter-section">
                        <h4><i class="fas fa-tag"></i> Marca</h4>
                        <select name="marca" onchange="this.form.submit()">
                            <option value="">Todas las marcas</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?= $marca ?>" <?= ($_GET['marca'] ?? '') === $marca ? 'selected' : '' ?>>
                                    <?= sanitize($marca) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-section">
                        <h4><i class="fas fa-folder"></i> Modelo</h4>
                        <select name="modelo" onchange="this.form.submit()">
                            <option value="">Todos los modelos</option>
                            <?php foreach ($modelos as $modelo): ?>
                                <option value="<?= $modelo ?>" <?= ($_GET['modelo'] ?? '') === $modelo ? 'selected' : '' ?>>
                                    <?= sanitize($modelo) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-section">
                        <h4><i class="fas fa-dollar-sign"></i> Rango de Precio</h4>
                        <div class="price-range">
                            <input type="number" name="precio_min" placeholder="Mín"
                                value="<?= $_GET['precio_min'] ?? '' ?>" min="0" step="0.01">
                            <span>—</span>
                            <input type="number" name="precio_max" placeholder="Máx"
                                value="<?= $_GET['precio_max'] ?? '' ?>" min="0" step="0.01">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-dark btn-block"><i class="fas fa-filter"></i> Filtrar</button>
                    <a href="<?= BASE_URL ?>tienda.php" class="btn btn-outline btn-block btn-sm mt-2">Limpiar
                        Filtros</a>
                </form>
            </aside>

            <!-- Products Grid -->
            <div class="shop-content">
                <div class="shop-toolbar">
                    <div class="results-count">
                        <strong><?= $totalProductos ?></strong> resultados encontrados
                    </div>
                    <div class="sort-wrapper">
                        <label for="orden" style="font-size:0.85rem;color:var(--gris-medio);">Ordenar por:</label>
                        <select id="orden" class="sort-select" onchange="window.location.href=this.value">
                            <option
                                value="<?= BASE_URL ?>tienda.php?<?= http_build_query(array_merge($_GET, ['orden' => 'nuevo'])) ?>"
                                <?= ($_GET['orden'] ?? '') === 'nuevo' ? 'selected' : '' ?>>Más recientes</option>
                            <option
                                value="<?= BASE_URL ?>tienda.php?<?= http_build_query(array_merge($_GET, ['orden' => 'precio_asc'])) ?>"
                                <?= ($_GET['orden'] ?? '') === 'precio_asc' ? 'selected' : '' ?>>Precio: Menor a Mayor
                            </option>
                            <option
                                value="<?= BASE_URL ?>tienda.php?<?= http_build_query(array_merge($_GET, ['orden' => 'precio_desc'])) ?>"
                                <?= ($_GET['orden'] ?? '') === 'precio_desc' ? 'selected' : '' ?>>Precio: Mayor a Menor
                            </option>
                            <option
                                value="<?= BASE_URL ?>tienda.php?<?= http_build_query(array_merge($_GET, ['orden' => 'nombre'])) ?>"
                                <?= ($_GET['orden'] ?? '') === 'nombre' ? 'selected' : '' ?>>Nombre A-Z</option>
                        </select>
                    </div>
                </div>

                <?php if ($totalProductos > 0): ?>
                    <div class="products-grid">
                        <?php foreach ($productos as $prod): ?>
                            <div class="card product-card">
                                <a href="<?= BASE_URL ?>producto.php?id=<?= $prod['id'] ?>">
                                    <div class="card-img">
                                        <img src="<?= getProductImage($prod['imagen_url']) ?>"
                                            alt="<?= sanitize($prod['nombre']) ?>" loading="lazy">
                                        <?php if ($prod['precio_oferta']): ?>
                                            <span class="badge-offer">-
                                                <?= round(100 - ($prod['precio_oferta'] / $prod['precio'] * 100)) ?>%
                                            </span>
                                        <?php endif; ?>
                                        <span
                                            class="badge-stock <?= $prod['stock'] > 5 ? 'in-stock' : ($prod['stock'] > 0 ? 'low-stock' : 'no-stock') ?>">
                                            <?= $prod['stock'] > 5 ? 'En Stock' : ($prod['stock'] > 0 ? 'Últimas ' . $prod['stock'] : 'Agotado') ?>
                                        </span>
                                    </div>
                                </a>
                                <div class="card-body">
                                    <div class="card-category">
                                        <?= sanitize($prod['categoria_nombre'] ?? '') ?>
                                    </div>
                                    <a href="<?= BASE_URL ?>producto.php?id=<?= $prod['id'] ?>">
                                        <h3 class="card-title">
                                            <?= sanitize($prod['nombre']) ?>
                                        </h3>
                                    </a>
                                    <div class="card-oem">SKU:
                                        <?= sanitize($prod['sku'] ?? $prod['oem_code']) ?>
                                    </div>
                                    <div class="card-footer">
                                        <div>
                                            <span class="price">
                                                <?= formatPrice($prod['precio_oferta'] ?? $prod['precio']) ?>
                                            </span>
                                            <?php if ($prod['precio_oferta']): ?>
                                                <span class="price-old">
                                                    <?= formatPrice($prod['precio']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($prod['stock'] > 0): ?>
                                            <button class="btn-add-cart" onclick="addToCart(<?= $prod['id'] ?>)"
                                                title="Añadir al carrito">
                                                <i class="fas fa-cart-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state text-center" style="padding: 4rem 1rem;">
                        <div
                            style="background:var(--gris-bg);width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                            <i class="fas fa-search" style="font-size:2rem;color:var(--gris-medio);"></i>
                        </div>
                        <h3 style="color:var(--azul-profundo);margin-bottom:0.5rem;">No encontramos lo que buscas</h3>
                        <p class="text-muted" style="max-width:400px;margin:0 auto 1.5rem;">Intenta usar términos más
                            generales o verifica que el código OEM sea correcto.</p>
                        <a href="<?= BASE_URL ?>tienda.php" class="btn btn-outline">Limpiar Filtros</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
$extraScripts = ['cart.js'];
require_once 'includes/footer.php';
?>