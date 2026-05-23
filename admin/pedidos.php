<?php
/**
 * JVSTORE - Admin: Gestión de Pedidos
 */
$pageTitle = 'Pedidos';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireStaff();
$db = getDB();

// Actualizar estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pedido_id'], $_POST['nuevo_estado'])) {
    $estados = ['pendiente', 'pagado', 'enviado', 'entregado', 'cancelado'];
    if (in_array($_POST['nuevo_estado'], $estados)) {
        $pedidoId = (int) $_POST['pedido_id'];
        $nuevoEstado = $_POST['nuevo_estado'];

        $stmt = $db->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->execute([$nuevoEstado, $pedidoId]);

        // --- ENVIAR CORREO DE NOTIFICACIÓN ---
        // 1. Obtener datos del cliente del pedido
        $stmt = $db->prepare("SELECT p.*, c.nombre, c.email FROM pedidos p LEFT JOIN clientes c ON p.usuario_id = c.id WHERE p.id = ?");
        $stmt->execute([$pedidoId]);
        $dataPedido = $stmt->fetch();

        if ($dataPedido && $dataPedido['email']) {
            $asunto = "Actualización de Pedido #$pedidoId - " . SITE_NAME;
            $mensaje = "<p>Hola <strong>" . sanitize($dataPedido['nombre']) . "</strong>,</p>";
            $mensaje .= "<p>Tu pedido <strong>#$pedidoId</strong> ha cambiado de estado a: <strong style='color:#004aad; text-transform:uppercase;'>$nuevoEstado</strong>.</p>";
            $mensaje .= "<p>Puedes ver los detalles en tu cuenta.</p>";
            $mensaje .= "<a href='" . BASE_URL . "cliente/pedidos.php' style='display:inline-block;padding:10px 20px;background:#004aad;color:white;text-decoration:none;border-radius:4px;'>Ver Pedido</a>";

            sendEmail($dataPedido['email'], $asunto, $mensaje);
        }
        // -------------------------------------

        setFlash('success', 'Estado del pedido actualizado y notificación enviada.');
    }
    redirect(BASE_URL . 'admin/pedidos.php?ver=' . $_POST['pedido_id']);
}

// Eliminar pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $pedidoId = (int) $_POST['pedido_id'];
    try {
        $db->beginTransaction();

        // Restaurar stock
        $stmt = $db->prepare("SELECT producto_id, cantidad FROM detalle_pedidos WHERE pedido_id = ?");
        $stmt->execute([$pedidoId]);
        foreach ($stmt->fetchAll() as $det) {
            $db->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?")->execute([$det['cantidad'], $det['producto_id']]);
        }

        // Eliminar detalles y pedido
        $db->prepare("DELETE FROM detalle_pedidos WHERE pedido_id = ?")->execute([$pedidoId]);
        $db->prepare("DELETE FROM pedidos WHERE id = ?")->execute([$pedidoId]);

        $db->commit();
        setFlash('success', 'Pedido eliminado y stock restaurado.');
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('danger', 'Error al eliminar el pedido.');
    }
    redirect(BASE_URL . 'admin/pedidos.php');
}

// Filtrar por estado
// Filtrar por estado y búsqueda
$filtroEstado = $_GET['estado'] ?? '';
$filtroQuery = trim($_GET['q'] ?? '');

$whereClauses = [];
$params = [];

if ($filtroEstado) {
    $whereClauses[] = "p.estado = ?";
    $params[] = $filtroEstado;
}

if ($filtroQuery) {
    // Buscar por ID de pedido o Nombre/Email de cliente
    $whereClauses[] = "(p.id LIKE ? OR c.nombre LIKE ? OR c.email LIKE ?)";
    $term = "%$filtroQuery%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$whereSQL = "";
if (!empty($whereClauses)) {
    $whereSQL = "WHERE " . implode(" AND ", $whereClauses);
}

$pedidos = $db->prepare("SELECT p.*, c.nombre as cliente_nombre, c.email as cliente_email FROM pedidos p LEFT JOIN clientes c ON p.usuario_id = c.id $whereSQL ORDER BY p.fecha DESC");
$pedidos->execute($params);
$pedidos = $pedidos->fetchAll();

// Detalle
$detalle = null;
if (isset($_GET['ver'])) {
    $stmt = $db->prepare("SELECT p.*, c.nombre as cliente_nombre, c.email as cliente_email, c.telefono as cliente_telefono FROM pedidos p LEFT JOIN clientes c ON p.usuario_id = c.id WHERE p.id = ?");
    $stmt->execute([(int) $_GET['ver']]);
    $pedidoInfo = $stmt->fetch();
    if ($pedidoInfo) {
        $stmt = $db->prepare("SELECT dp.*, pr.nombre as producto_nombre, pr.imagen_url, pr.sku, pr.oem_code FROM detalle_pedidos dp JOIN productos pr ON dp.producto_id = pr.id WHERE dp.pedido_id = ?");
        $stmt->execute([$pedidoInfo['id']]);
        $detalle = $stmt->fetchAll();
    }
}

$flash = getFlash();
?>
require_once __DIR__ . '/includes/header.php';
?>

<div class="adm-card">
  <div class="adm-card-header">
    <h2><i class="fas fa-shopping-bag"></i> <?= isset($pedidoInfo) ? 'Pedido #' . $pedidoInfo['id'] : 'Pedidos' ?></h2>
    <?php if (isset($pedidoInfo)): ?>
      <a href="<?= BASE_URL ?>admin/pedidos.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
    <?php endif; ?>
  </div>
  <div class="adm-card-body">
    <?php if ($flash): ?>
      <div class="adm-alert <?= $flash['type'] ?>"><i class="fas fa-info-circle"></i>
        <?= $flash['message'] ?>
      </div>
    <?php endif; ?>

                <?php if (isset($pedidoInfo) && $detalle): ?>
                    <!-- DETALLE DEL PEDIDO -->

                    <!-- Print Only Header -->
                    <div class="print-only" style="text-align:center;margin-bottom:2rem;display:none;">
                        <img src="<?= BASE_URL ?>img/logo.png" style="max-width:150px;">
                        <h1>Orden de Compra #<?= $pedidoInfo['id'] ?></h1>
                    </div>

                    <div class="admin-actions-bar no-print"
                        style="margin-bottom:1rem;display:flex;justify-content:flex-end;">
                        <button onclick="window.print()" class="btn btn-secondary"><i class="fas fa-print"></i>
                            Imprimir</button>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr;gap:2rem;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;" class="print-grid">
                            <div class="cart-summary">
                                <h3>Info del Pedido</h3>
                                <div class="summary-row"><span>Estado</span><span>
                                        <?= getStatusBadge($pedidoInfo['estado']) ?>
                                    </span></div>
                                <div class="summary-row"><span>Fecha</span><span>
                                        <?= date('d/m/Y H:i', strtotime($pedidoInfo['fecha'])) ?>
                                    </span></div>
                                <div class="summary-row"><span>Subtotal</span><span>
                                        <?= formatPrice($pedidoInfo['subtotal']) ?>
                                    </span></div>
                                <div class="summary-row"><span>IVA</span><span>
                                        <?= formatPrice($pedidoInfo['iva']) ?>
                                    </span></div>
                                <div class="summary-row"><span>Envío</span><span>
                                        <?= formatPrice($pedidoInfo['envio']) ?>
                                    </span></div>
                                <div class="summary-total"><span>Total</span><span>
                                        <?= formatPrice($pedidoInfo['total']) ?>
                                    </span></div>
                            </div>
                            <div class="cart-summary">
                                <h3>Datos del Cliente</h3>
                                <div class="summary-row"><span>Nombre</span><span>
                                        <?= sanitize($pedidoInfo['cliente_nombre']) ?>
                                    </span></div>
                                <div class="summary-row"><span>Email</span><span>
                                        <?= sanitize($pedidoInfo['cliente_email']) ?>
                                    </span></div>
                                <div class="summary-row"><span>Teléfono</span><span>
                                        <?= sanitize($pedidoInfo['telefono'] ?? $pedidoInfo['cliente_telefono'] ?? 'N/A') ?>
                                    </span></div>
                                <div class="summary-row"><span>Dirección</span><span>
                                        <?= sanitize($pedidoInfo['direccion_envio'] ?? 'N/A') ?>
                                    </span></div>
                                <?php if ($pedidoInfo['notas']): ?>
                                    <div class="summary-row"><span>Notas</span><span>
                                            <?= sanitize($pedidoInfo['notas']) ?>
                                        </span></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Actualizar estado (No imprimir) -->
                        <div class="no-print" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                            <span class="fw-600">Cambiar Estado:</span>
                            <form method="POST" style="display:flex;gap:.5rem;flex-wrap:wrap;">
                                <input type="hidden" name="pedido_id" value="<?= $pedidoInfo['id'] ?>">
                                <?php
                                $estados = ['pendiente' => 'Pendiente', 'pagado' => 'Pagado', 'enviado' => 'Enviado', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
                                foreach ($estados as $key => $label): ?>
                                    <button type="submit" name="nuevo_estado" value="<?= $key ?>"
                                        class="btn btn-sm <?= $pedidoInfo['estado'] === $key ? 'btn-primary' : 'btn-outline' ?>"
                                        <?= $pedidoInfo['estado'] === $key ? 'disabled' : '' ?>>
                                        <?= $label ?>
                                    </button>
                                <?php endforeach; ?>
                            </form>
                        </div>

                        <!-- Items -->
                        <table class="adm-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>SKU</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalle as $item): ?>
                                    <tr>
                                        <td style="display:flex;align-items:center;gap:.75rem;">
                                            <img src="<?= getProductImage($item['imagen_url']) ?>" alt=""
                                                style="width:40px;height:40px;object-fit:cover;border-radius:4px;"
                                                class="no-print">
                                            <?= sanitize($item['producto_nombre']) ?>
                                        </td>
                                        <td><code><?= sanitize($item['sku'] ?? $item['oem_code'] ?? '') ?></code></td>
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
                    </div>

                <?php else: ?>
                    <!-- LISTADO -->

                    <!-- Filtros y Buscador -->
                    <div class="admin-filters"
                        style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem; background:white; padding:1rem; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.05);">
                        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                            <a href="<?= BASE_URL ?>admin/pedidos.php"
                                class="btn btn-sm <?= !$filtroEstado ? 'btn-primary' : 'btn-outline' ?>">Todos</a>
                            <a href="?estado=pendiente&q=<?= htmlspecialchars($filtroQuery) ?>"
                                class="btn btn-sm <?= $filtroEstado === 'pendiente' ? 'btn-primary' : 'btn-outline' ?>">Pendientes</a>
                            <a href="?estado=pagado&q=<?= htmlspecialchars($filtroQuery) ?>"
                                class="btn btn-sm <?= $filtroEstado === 'pagado' ? 'btn-primary' : 'btn-outline' ?>">Pagados</a>
                            <a href="?estado=enviado&q=<?= htmlspecialchars($filtroQuery) ?>"
                                class="btn btn-sm <?= $filtroEstado === 'enviado' ? 'btn-primary' : 'btn-outline' ?>">Enviados</a>
                        </div>

                        <form action="" method="GET" style="display:flex; gap:0.5rem;">
                            <?php if ($filtroEstado): ?><input type="hidden" name="estado"
                                    value="<?= $filtroEstado ?>"><?php endif; ?>
                            <input type="text" name="q" value="<?= htmlspecialchars($filtroQuery) ?>"
                                placeholder="Buscar ID, Cliente..."
                                style="padding:0.4rem; border:1px solid #ddd; border-radius:4px;">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                            <?php if ($filtroQuery): ?>
                                <a href="?estado=<?= $filtroEstado ?>" class="btn btn-outline btn-sm" title="Limpiar"><i
                                        class="fas fa-times"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div style="overflow-x:auto;">
                        <table class="adm-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
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
                                            <?= sanitize($p['cliente_nombre'] ?? 'N/A') ?>
                                            <br><small class="text-muted">
                                                <?= sanitize($p['cliente_email'] ?? '') ?>
                                            </small>
                                        </td>
                                        <td><strong>
                                                <?= formatPrice($p['total']) ?>
                                            </strong></td>
                                        <td>
                                            <?= getStatusBadge($p['estado']) ?>
                                        </td>
                                        <td class="text-sm text-muted">
                                            <?= date('d/m/Y H:i', strtotime($p['fecha'])) ?>
                                        </td>
                                        <td style="display:flex; gap:0.5rem; align-items:center;">
                                            <a href="?ver=<?= $p['id'] ?>" class="btn btn-outline btn-sm"><i
                                                    class="fas fa-eye"></i> Ver</a>
                                            <form method="POST" style="margin:0;"
                                                onsubmit="return confirm('¿Eliminar pedido definitivamente? El stock será devuelto y el cliente ya no lo verá.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-sm"
                                                    style="background:#dc3545; color:white; border:none; cursor:pointer;"
                                                    title="Eliminar"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($pedidos)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted" style="padding:3rem;">No hay pedidos
                                            encontrados.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>