<?php
/**
 * JVSTORE - Mis Pedidos (Área Cliente)
 */
$pageTitle = 'Mis Pedidos';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
$user = getCurrentUser();
$db   = getDB();

// ── Obtener pedidos del cliente ───────────────────────────────────────────────
$pedidos = [];
try {
    $stmt = $db->prepare("SELECT * FROM pedidos WHERE cliente_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $pedidos = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('[Pedidos] ' . $e->getMessage());
}

// ── Detalle de un pedido específico ──────────────────────────────────────────
$pedidoDetalle = null;
$itemsPedido   = [];
if (isset($_GET['ver'])) {
    $pedidoId = (int)$_GET['ver'];
    try {
        $stmt = $db->prepare("SELECT * FROM pedidos WHERE id = ? AND cliente_id = ?");
        $stmt->execute([$pedidoId, $user['id']]);
        $pedidoDetalle = $stmt->fetch();

        if ($pedidoDetalle) {
            // Items guardados como JSON en el campo 'items'
            $itemsPedido = json_decode($pedidoDetalle['items'] ?? '[]', true) ?: [];
        }
    } catch (Throwable $e) {
        error_log('[PedidoDetalle] ' . $e->getMessage());
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ── Reutilizar estilos del dashboard ─── */
.dash-wrap {
    max-width:1100px; margin:0 auto; padding:2.5rem 1.5rem 4rem;
    display:grid; grid-template-columns:260px 1fr; gap:2rem; align-items:start;
}
@media(max-width:768px){ .dash-wrap{grid-template-columns:1fr;padding:1.5rem 1rem;} }
.dash-sidebar{background:var(--azul-profundo,#1B2A4A);border-radius:20px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);position:sticky;top:90px;}
.dash-user-banner{background:linear-gradient(135deg,#0ea5e9 0%,#1B2A4A 100%);padding:2rem 1.5rem 1.5rem;text-align:center;}
.dash-avatar{width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,0.15);border:3px solid rgba(255,255,255,0.4);display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:700;color:#fff;margin:0 auto 10px;overflow:hidden;}
.dash-avatar img{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.dash-user-name{color:#fff;font-size:1rem;font-weight:700;margin:0 0 4px;}
.dash-user-email{color:rgba(255,255,255,0.6);font-size:11px;margin:0;word-break:break-all;}
.dash-nav{padding:1rem 0;}
.dash-nav a{display:flex;align-items:center;gap:12px;padding:13px 24px;color:rgba(255,255,255,0.7);text-decoration:none;font-size:14px;font-weight:500;transition:all 0.2s;border-left:3px solid transparent;}
.dash-nav a:hover{background:rgba(255,255,255,0.07);color:#fff;border-left-color:rgba(14,165,233,0.5);}
.dash-nav a.active{background:rgba(14,165,233,0.15);color:#0ea5e9;border-left-color:#0ea5e9;font-weight:600;}
.dash-nav a i{width:18px;text-align:center;}
.dash-nav .nav-divider{height:1px;background:rgba(255,255,255,0.08);margin:8px 20px;}
.dash-nav a.logout{color:rgba(239,68,68,0.7);}
.dash-nav a.logout:hover{color:#ef4444;background:rgba(239,68,68,0.08);}
.dash-section{background:#fff;border-radius:20px;padding:1.8rem;box-shadow:0 2px 12px rgba(0,0,0,0.07);border:1px solid rgba(0,0,0,0.05);margin-bottom:1.5rem;}
.dash-section-title{font-size:1rem;font-weight:700;color:#1B2A4A;display:flex;align-items:center;gap:8px;margin:0 0 1.5rem;}
.dash-section-title i{color:#0ea5e9;}
/* Tabla pedidos */
.pedidos-table{width:100%;border-collapse:collapse;font-size:14px;}
.pedidos-table th{text-align:left;padding:10px 12px;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #f3f4f6;}
.pedidos-table td{padding:14px 12px;border-bottom:1px solid #f9fafb;color:#374151;vertical-align:middle;}
.pedidos-table tr:last-child td{border-bottom:none;}
.pedidos-table tr:hover td{background:#f9fafb;}
.badge-estado{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-pendiente{background:rgba(245,158,11,0.12);color:#d97706;}
.badge-pagado{background:rgba(14,165,233,0.12);color:#0ea5e9;}
.badge-procesando{background:rgba(139,92,246,0.12);color:#8b5cf6;}
.badge-enviado{background:rgba(59,130,246,0.12);color:#3b82f6;}
.badge-entregado{background:rgba(16,185,129,0.12);color:#10b981;}
.badge-cancelado{background:rgba(239,68,68,0.12);color:#ef4444;}
/* Tracker */
.order-tracker{display:flex;align-items:center;justify-content:center;gap:0;margin:2rem 0;flex-wrap:wrap;}
.tracker-step{display:flex;flex-direction:column;align-items:center;flex:1;min-width:80px;position:relative;}
.tracker-step::before{content:'';position:absolute;top:20px;left:50%;width:100%;height:2px;background:#e5e7eb;z-index:0;}
.tracker-step:last-child::before{display:none;}
.tracker-icon{width:40px;height:40px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;font-size:14px;color:#9ca3af;position:relative;z-index:1;transition:all 0.3s;}
.tracker-label{font-size:11px;color:#9ca3af;margin-top:6px;text-align:center;font-weight:500;}
.tracker-step.done .tracker-icon{background:#0ea5e9;color:#fff;box-shadow:0 4px 12px rgba(14,165,233,0.35);}
.tracker-step.done .tracker-step::before{background:#0ea5e9;}
.tracker-step.done .tracker-label{color:#0ea5e9;font-weight:600;}
.tracker-step.active .tracker-icon{background:#1B2A4A;color:#fff;box-shadow:0 4px 16px rgba(27,42,74,0.4);}
.tracker-step.active .tracker-label{color:#1B2A4A;font-weight:700;}
.tracker-step.cancelled .tracker-icon{background:#ef4444;color:#fff;}
/* Detalle */
.detail-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;margin-bottom:2rem;}
.detail-card{background:#f9fafb;border-radius:14px;padding:1.2rem 1.4rem;}
.detail-card h4{font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;margin:0 0 10px;}
.detail-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #f3f4f6;font-size:13px;}
.detail-row:last-child{border-bottom:none;}
.detail-row span:first-child{color:#6b7280;}
.detail-row span:last-child{font-weight:500;color:#1B2A4A;}
/* Items */
.item-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #f3f4f6;}
.item-row:last-child{border-bottom:none;}
.item-img{width:52px;height:52px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb;flex-shrink:0;}
/* Empty */
.dash-empty{text-align:center;padding:3rem 2rem;}
.dash-empty i{font-size:3rem;color:#e5e7eb;margin-bottom:1rem;display:block;}
.dash-empty h4{color:#6b7280;margin:0 0 8px;font-size:1rem;}
.dash-empty p{color:#9ca3af;font-size:13px;margin:0 0 1.5rem;}
</style>

<div class="dash-wrap">

  <!-- Sidebar -->
  <aside class="dash-sidebar">
    <div class="dash-user-banner">
      <div class="dash-avatar">
        <?php if (!empty($user['avatar'])): ?>
          <img src="<?= sanitize($user['avatar']) ?>" alt="Avatar">
        <?php else: ?>
          <?= strtoupper(mb_substr($user['nombre'], 0, 1)) ?>
        <?php endif; ?>
      </div>
      <p class="dash-user-name"><?= sanitize($user['nombre']) ?></p>
      <p class="dash-user-email"><?= sanitize($user['email']) ?></p>
    </div>
    <nav class="dash-nav">
      <a href="<?= BASE_URL ?>cliente/"><i class="fas fa-home"></i> Mi Panel</a>
      <a href="<?= BASE_URL ?>cliente/pedidos.php" class="active"><i class="fas fa-box"></i> Mis Pedidos</a>
      <a href="<?= BASE_URL ?>carrito.php"><i class="fas fa-shopping-cart"></i> Mi Carrito</a>
      <a href="<?= BASE_URL ?>cliente/perfil.php"><i class="fas fa-user-edit"></i> Mi Perfil</a>
      <a href="<?= BASE_URL ?>tienda.php"><i class="fas fa-store"></i> Ir a la Tienda</a>
      <div class="nav-divider"></div>
      <a href="<?= BASE_URL ?>logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    </nav>
  </aside>

  <!-- Contenido -->
  <div>
    <?php if ($pedidoDetalle): ?>
      <!-- ── DETALLE DE UN PEDIDO ──────────────────────────── -->
      <a href="<?= BASE_URL ?>cliente/pedidos.php"
         style="display:inline-flex;align-items:center;gap:6px;color:#0ea5e9;text-decoration:none;font-size:14px;font-weight:600;margin-bottom:1.2rem">
        <i class="fas fa-arrow-left"></i> Volver a Pedidos
      </a>

      <div class="dash-section">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem">
          <h2 style="margin:0;font-size:1.3rem;color:#1B2A4A">
            Pedido #<?= sanitize($pedidoDetalle['codigo'] ?? $pedidoDetalle['id']) ?>
          </h2>
          <?php
            $estado = $pedidoDetalle['estado'] ?? 'pendiente';
            $estadoIconos = ['pendiente'=>'clock','pagado'=>'check-circle','procesando'=>'cog','enviado'=>'shipping-fast','entregado'=>'box-open','cancelado'=>'times-circle'];
          ?>
          <span class="badge-estado badge-<?= $estado ?>">
            <i class="fas fa-<?= $estadoIconos[$estado] ?? 'circle' ?>"></i>
            <?= ucfirst($estado) ?>
          </span>
        </div>

        <?php if ($estado === 'cancelado'): ?>
          <div style="text-align:center;padding:2rem;background:rgba(239,68,68,0.05);border-radius:14px;margin-bottom:1.5rem;">
            <i class="fas fa-times-circle" style="font-size:2.5rem;color:#ef4444;margin-bottom:.75rem;display:block"></i>
            <strong style="color:#ef4444">Pedido Cancelado</strong>
            <p style="color:#6b7280;font-size:13px;margin:.5rem 0 0">Este pedido fue cancelado y no será procesado.</p>
          </div>
        <?php else: ?>
          <?php
            $pasos = ['pendiente'=>0,'pagado'=>1,'procesando'=>1,'enviado'=>2,'entregado'=>3];
            $pasoActual = $pasos[$estado] ?? 0;
            $etapas = [
              ['pendiente','file-invoice','Pendiente'],
              ['pagado','money-check-alt','Pagado'],
              ['enviado','shipping-fast','Enviado'],
              ['entregado','box-open','Entregado'],
            ];
          ?>
          <div class="order-tracker">
            <?php foreach ($etapas as $i => [$slug, $icon, $label]): ?>
              <?php
                $cls = '';
                if ($i < $pasoActual) $cls = 'done';
                elseif ($i === $pasoActual) $cls = 'active';
              ?>
              <div class="tracker-step <?= $cls ?>">
                <div class="tracker-icon"><i class="fas fa-<?= $icon ?>"></i></div>
                <span class="tracker-label"><?= $label ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Resumen financiero y envío -->
        <div class="detail-grid">
          <div class="detail-card">
            <h4>Resumen Financiero</h4>
            <div class="detail-row"><span>Subtotal</span><span><?= formatPrice($pedidoDetalle['subtotal'] ?? 0) ?></span></div>
            <div class="detail-row"><span>IVA</span><span><?= formatPrice($pedidoDetalle['iva'] ?? 0) ?></span></div>
            <div class="detail-row"><span>Envío</span><span><?= formatPrice($pedidoDetalle['costo_envio'] ?? 0) ?></span></div>
            <div class="detail-row" style="font-weight:700;font-size:15px"><span style="color:#1B2A4A">Total</span><span style="color:#0ea5e9"><?= formatPrice($pedidoDetalle['total'] ?? 0) ?></span></div>
          </div>
          <div class="detail-card">
            <h4>Datos del Pedido</h4>
            <div class="detail-row"><span>Cliente</span><span><?= sanitize($pedidoDetalle['nombre_cliente'] ?? $user['nombre']) ?></span></div>
            <div class="detail-row"><span>Teléfono</span><span><?= sanitize($pedidoDetalle['telefono'] ?? 'N/A') ?></span></div>
            <div class="detail-row"><span>Dirección</span><span style="text-align:right;max-width:60%"><?= sanitize($pedidoDetalle['direccion'] ?? 'N/A') ?></span></div>
            <div class="detail-row"><span>Método Pago</span><span><?= sanitize($pedidoDetalle['metodo_pago'] ?? 'N/A') ?></span></div>
            <div class="detail-row"><span>Fecha</span><span><?= date('d/m/Y H:i', strtotime($pedidoDetalle['created_at'] ?? 'now')) ?></span></div>
          </div>
        </div>

        <!-- Items del pedido -->
        <h3 class="dash-section-title"><i class="fas fa-list"></i> Artículos del Pedido</h3>
        <?php if (empty($itemsPedido)): ?>
          <p style="color:#9ca3af;font-size:13px">No se encontraron artículos para este pedido.</p>
        <?php else: ?>
          <?php foreach ($itemsPedido as $item): ?>
            <div class="item-row">
              <img class="item-img"
                   src="<?= getProductImage($item['imagen'] ?? '') ?>"
                   alt="<?= sanitize($item['nombre'] ?? '') ?>">
              <div style="flex:1">
                <div style="font-size:14px;font-weight:600;color:#1B2A4A"><?= sanitize($item['nombre'] ?? '') ?></div>
                <div style="font-size:12px;color:#9ca3af">Cantidad: <?= (int)($item['cantidad'] ?? 1) ?></div>
              </div>
              <div style="text-align:right">
                <div style="font-size:13px;color:#6b7280"><?= formatPrice($item['precio'] ?? 0) ?> c/u</div>
                <div style="font-size:15px;font-weight:700;color:#0ea5e9"><?= formatPrice(($item['precio'] ?? 0) * ($item['cantidad'] ?? 1)) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <!-- ── LISTA DE PEDIDOS ──────────────────────────────── -->
      <div class="dash-section">
        <h3 class="dash-section-title"><i class="fas fa-history"></i> Historial de Pedidos</h3>

        <?php if (empty($pedidos)): ?>
          <div class="dash-empty">
            <i class="fas fa-box-open"></i>
            <h4>Sin pedidos aún</h4>
            <p>Cuando realices una compra aparecerá aquí con el seguimiento completo.</p>
            <a href="<?= BASE_URL ?>tienda.php" class="btn btn-primary">
              <i class="fas fa-store"></i> Explorar Tienda
            </a>
          </div>
        <?php else: ?>
          <div style="overflow-x:auto">
            <table class="pedidos-table">
              <thead>
                <tr>
                  <th>Pedido</th>
                  <th>Fecha</th>
                  <th>Total</th>
                  <th>Método Pago</th>
                  <th>Estado</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pedidos as $p):
                  $estado = $p['estado'] ?? 'pendiente';
                  $iconos = ['pendiente'=>'clock','pagado'=>'check-circle','procesando'=>'cog','enviado'=>'shipping-fast','entregado'=>'box-open','cancelado'=>'times-circle'];
                ?>
                <tr>
                  <td><strong style="color:#1B2A4A">#<?= sanitize($p['codigo'] ?? $p['id']) ?></strong></td>
                  <td style="color:#6b7280;font-size:13px"><?= date('d/m/Y H:i', strtotime($p['created_at'] ?? 'now')) ?></td>
                  <td><strong style="color:#1B2A4A"><?= formatPrice($p['total']) ?></strong></td>
                  <td style="font-size:13px;color:#6b7280;text-transform:capitalize"><?= sanitize($p['metodo_pago'] ?? 'N/A') ?></td>
                  <td>
                    <span class="badge-estado badge-<?= $estado ?>">
                      <i class="fas fa-<?= $iconos[$estado] ?? 'circle' ?>"></i>
                      <?= ucfirst($estado) ?>
                    </span>
                  </td>
                  <td>
                    <a href="<?= BASE_URL ?>cliente/pedidos.php?ver=<?= $p['id'] ?>"
                       style="font-size:12px;color:#0ea5e9;text-decoration:none;font-weight:600;white-space:nowrap">
                      Ver detalle <i class="fas fa-arrow-right"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>