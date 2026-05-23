<?php
$pageTitle = 'Mis Pedidos';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
$user = getCurrentUser();
$db = getDB();

$stmt = $db->prepare("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY fecha DESC");
$stmt->execute([$user['id']]);
$pedidos = $stmt->fetchAll();

// Detalle de un pedido específico
$detalle = null;
if (isset($_GET['ver'])) {
    $stmt = $db->prepare("SELECT p.*, dp.cantidad, dp.precio_unitario, dp.subtotal, pr.nombre as producto_nombre, pr.imagen_url FROM pedidos p JOIN detalle_pedidos dp ON p.id = dp.pedido_id JOIN productos pr ON dp.producto_id = pr.id WHERE p.id = ? AND p.usuario_id = ?");
    $stmt->execute([(int) $_GET['ver'], $user['id']]);
    $detalle = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Mis Pedidos</h1>
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>">Inicio</a> <span>/</span>
            <a href="<?= BASE_URL ?>cliente/">Mi Cuenta</a> <span>/</span>
            <span>Pedidos</span>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="client-layout">
            <aside class="client-sidebar">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['nombre'], 0, 1)) ?>
                    </div>
                    <h3>
                        <?= sanitize($user['nombre']) ?>
                    </h3>
                    <p class="text-muted text-sm">
                        <?= sanitize($user['email']) ?>
                    </p>
                </div>
                <nav class="client-nav">
                    <a href="<?= BASE_URL ?>cliente/"><i class="fas fa-tachometer-alt"></i> Panel</a>
                    <a href="<?= BASE_URL ?>cliente/pedidos.php" class="active"><i class="fas fa-box"></i> Mis
                        Pedidos</a>
                    <a href="<?= BASE_URL ?>cliente/perfil.php"><i class="fas fa-user-edit"></i> Mi Perfil</a>
                    <a href="<?= BASE_URL ?>logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                </nav>
            </aside>

            <div>
                <?php if ($detalle): ?>
                    <a href="<?= BASE_URL ?>cliente/pedidos.php" class="btn btn-outline btn-sm mb-3"><i
                            class="fas fa-arrow-left"></i> Volver</a>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                        <h2 style="color:var(--azul-profundo);margin:0;">Pedido #<?= (int) $_GET['ver'] ?></h2>
                        <span style="font-size:1.1rem;"><?= getStatusBadge($detalle[0]['estado'] ?? 'pendiente') ?></span>
                    </div>

                    <?php
                    $estadoActual = strtolower($detalle[0]['estado'] ?? 'pendiente');
                    $mapaPasos = ['pendiente' => 1, 'pagado' => 2, 'enviado' => 3, 'entregado' => 4];
                    $pasoNum = $mapaPasos[$estadoActual] ?? 1;
                    ?>

                    <?php if ($estadoActual === 'cancelado'): ?>
                        <div class="alert alert-danger" style="margin: 2rem 0; text-align:center;">
                            <i class="fas fa-times-circle" style="font-size:3rem;margin-bottom:1rem;"></i><br>
                            <strong>Pedido Cancelado</strong>
                            <p class="text-sm mt-1">Este pedido fue cancelado y no será procesado.</p>
                        </div>
                    <?php else: ?>
                        <div class="tracking-stepper" data-step="<?= $pasoNum ?>">
                            <div class="tracking-step <?= $pasoNum >= 1 ? 'done' : '' ?>">
                                <div class="tracking-icon"><i class="fas fa-file-invoice"></i></div>
                                <div class="tracking-text">Pendiente</div>
                            </div>
                            <div class="tracking-step <?= $pasoNum >= 2 ? ($pasoNum == 2 ? 'active' : 'done') : '' ?>">
                                <div class="tracking-icon"><i class="fas fa-money-check-alt"></i></div>
                                <div class="tracking-text">Pagado</div>
                            </div>
                            <div class="tracking-step <?= $pasoNum >= 3 ? ($pasoNum == 3 ? 'active' : 'done') : '' ?>">
                                <div class="tracking-icon"><i class="fas fa-shipping-fast"></i></div>
                                <div class="tracking-text">Enviado</div>
                            </div>
                            <div class="tracking-step <?= $pasoNum >= 4 ? 'done' : '' ?>">
                                <div class="tracking-icon"><i class="fas fa-box-open"></i></div>
                                <div class="tracking-text">Entregado</div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div
                        style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:2rem;margin-bottom:2.5rem;">
                        <div class="cart-summary" style="margin:0;">
                            <h3>Resumen Financiero</h3>
                            <div class="summary-row">
                                <span>Subtotal</span><span><?= formatPrice($detalle[0]['subtotal'] ?? 0) ?></span></div>
                            <div class="summary-row">
                                <span>IVA</span><span><?= formatPrice($detalle[0]['iva'] ?? 0) ?></span></div>
                            <div class="summary-row">
                                <span>Envío</span><span><?= formatPrice($detalle[0]['envio'] ?? 0) ?></span></div>
                            <div class="summary-total"><span>Total
                                    Pagado</span><span><?= formatPrice($detalle[0]['total'] ?? 0) ?></span></div>
                        </div>
                        <div class="cart-summary" style="margin:0;">
                            <h3>Datos de Envío</h3>
                            <div class="summary-row">
                                <span>Dirección</span><span><?= sanitize($detalle[0]['direccion_envio'] ?? 'N/A') ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Teléfono</span><span><?= sanitize($detalle[0]['telefono'] ?? 'N/A') ?></span></div>
                            <?php if (!empty($detalle[0]['notas'])): ?>
                                <div class="summary-row"><span>Notas</span><span><?= sanitize($detalle[0]['notas']) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="summary-row">
                                <span>Fecha</span><span><?= date('d/m/Y H:i', strtotime($detalle[0]['fecha'] ?? '')) ?></span>
                            </div>
                        </div>
                    </div>

                    <h3 style="color:var(--azul-profundo);margin-bottom:1rem;">Artículos del Pedido</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Cantidad</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalle as $item): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:.75rem;">
                                            <img src="<?= getProductImage($item['imagen_url']) ?>" alt=""
                                                style="width:45px;height:45px;object-fit:cover;border-radius:4px;">
                                            <?= sanitize($item['producto_nombre']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?= formatPrice($item['precio_unitario']) ?>
                                    </td>
                                    <td>
                                        <?= $item['cantidad'] ?>
                                    </td>
                                    <td><strong>
                                            <?= formatPrice($item['subtotal']) ?>
                                        </strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <h2 style="color:var(--azul-profundo);margin-bottom:1.5rem;">Historial de Pedidos</h2>
                    <?php if (empty($pedidos)): ?>
                        <p class="text-muted">No tienes pedidos realizados aún.</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidos as $p): ?>
                                    <tr>
                                        <td><strong>#
                                                <?= $p['id'] ?>
                                            </strong></td>
                                        <td>
                                            <?= date('d/m/Y H:i', strtotime($p['fecha'])) ?>
                                        </td>
                                        <td>
                                            <?= formatPrice($p['total']) ?>
                                        </td>
                                        <td>
                                            <?= getStatusBadge($p['estado']) ?>
                                        </td>
                                        <td><a href="<?= BASE_URL ?>cliente/pedidos.php?ver=<?= $p['id'] ?>"
                                                class="btn btn-outline btn-sm">Ver</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>