<?php
/**
 * IMPORDISPAC - Área de Cliente: Panel Principal
 */
$pageTitle = 'Mi Cuenta';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
$user = getCurrentUser();
$db = getDB();

// Contar pedidos del usuario
$stmt = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE usuario_id = ?");
$stmt->execute([$user['id']]);
$totalPedidos = $stmt->fetchColumn();

// Últimos pedidos
$stmt = $db->prepare("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 5");
$stmt->execute([$user['id']]);
$ultimosPedidos = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Mi Cuenta</h1>
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>">Inicio</a> <span>/</span> <span>Mi Cuenta</span>
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
                    <a href="<?= BASE_URL ?>cliente/" class="active"><i class="fas fa-tachometer-alt"></i> Panel</a>
                    <a href="<?= BASE_URL ?>cliente/pedidos.php"><i class="fas fa-box"></i> Mis Pedidos</a>
                    <a href="<?= BASE_URL ?>cliente/perfil.php"><i class="fas fa-user-edit"></i> Mi Perfil</a>
                    <a href="<?= BASE_URL ?>logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                </nav>
            </aside>

            <div>
                <h2 style="margin-bottom:1.5rem;color:var(--azul-profundo);">Bienvenido,
                    <?= sanitize($user['nombre']) ?>
                </h2>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue"><i class="fas fa-box"></i></div>
                        <div class="stat-info">
                            <h4>Pedidos</h4>
                            <div class="stat-number">
                                <?= $totalPedidos ?>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-green"><i class="fas fa-shopping-cart"></i></div>
                        <div class="stat-info">
                            <h4>En Carrito</h4>
                            <div class="stat-number">
                                <?= getCartCount() ?>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 style="margin:2rem 0 1rem;color:var(--azul-profundo);">Últimos Pedidos</h3>
                <?php if (empty($ultimosPedidos)): ?>
                    <p class="text-muted">Aún no tienes pedidos. <a href="<?= BASE_URL ?>tienda.php"
                            style="color:var(--acento);">¡Comienza a comprar!</a></p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimosPedidos as $pedido): ?>
                                <tr>
                                    <td><strong>#
                                            <?= $pedido['id'] ?>
                                        </strong></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($pedido['fecha'])) ?>
                                    </td>
                                    <td>
                                        <?= formatPrice($pedido['total']) ?>
                                    </td>
                                    <td>
                                        <?= getStatusBadge($pedido['estado']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>