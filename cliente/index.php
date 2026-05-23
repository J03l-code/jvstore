<?php
/**
 * JVSTORE - Área de Cliente: Dashboard Principal
 */
$pageTitle = 'Mi Cuenta';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
$user = getCurrentUser();
$db   = getDB();

// ── Estadísticas del cliente ──────────────────────────────────────────────────
$totalPedidos   = 0;
$totalGastado   = 0;
$ultimosPedidos = [];

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE cliente_id = ?");
    $stmt->execute([$user['id']]);
    $totalPedidos = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE cliente_id = ? AND estado != 'cancelado'");
    $stmt->execute([$user['id']]);
    $totalGastado = (float)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM pedidos WHERE cliente_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user['id']]);
    $ultimosPedidos = $stmt->fetchAll();
} catch (Throwable $e) {
    // BD puede no tener todos los datos aún
}

$cartCount = getCartCount();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ── Dashboard Premium ─────────────────────────────── */
.dash-wrap {
    max-width: 1100px;
    margin: 0 auto;
    padding: 2.5rem 1.5rem 4rem;
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 2rem;
    align-items: start;
}
@media(max-width:768px){
    .dash-wrap { grid-template-columns:1fr; padding:1.5rem 1rem; }
}

/* Sidebar */
.dash-sidebar {
    background: var(--azul-profundo, #1B2A4A);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    position: sticky;
    top: 90px;
}
.dash-user-banner {
    background: linear-gradient(135deg, #0ea5e9 0%, #1B2A4A 100%);
    padding: 2rem 1.5rem 1.5rem;
    text-align: center;
}
.dash-avatar {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: rgba(255,255,255,0.15);
    border: 3px solid rgba(255,255,255,0.4);
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; font-weight: 700; color: #fff;
    margin: 0 auto 12px;
    overflow: hidden;
    backdrop-filter: blur(4px);
}
.dash-avatar img { width:100%; height:100%; object-fit:cover; border-radius:50%; }
.dash-user-name { color:#fff; font-size:1.05rem; font-weight:700; margin:0 0 4px; }
.dash-user-email { color:rgba(255,255,255,0.65); font-size:12px; margin:0; word-break:break-all; }
.dash-google-badge {
    display:inline-flex; align-items:center; gap:5px;
    background:rgba(255,255,255,0.12); border-radius:20px;
    padding:3px 10px; margin-top:8px; font-size:11px; color:rgba(255,255,255,0.8);
}
.dash-nav { padding: 1rem 0; }
.dash-nav a {
    display: flex; align-items: center; gap: 12px;
    padding: 13px 24px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    border-left: 3px solid transparent;
}
.dash-nav a:hover { background:rgba(255,255,255,0.07); color:#fff; border-left-color:rgba(14,165,233,0.5); }
.dash-nav a.active { background:rgba(14,165,233,0.15); color:#0ea5e9; border-left-color:#0ea5e9; font-weight:600; }
.dash-nav a i { width:18px; text-align:center; }
.dash-nav .nav-divider { height:1px; background:rgba(255,255,255,0.08); margin:8px 20px; }
.dash-nav a.logout { color:rgba(239,68,68,0.7); }
.dash-nav a.logout:hover { color:#ef4444; background:rgba(239,68,68,0.08); }

/* Main content */
.dash-main {}

/* Header de bienvenida */
.dash-welcome {
    background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%);
    border-radius: 20px;
    padding: 2rem 2.5rem;
    color: #fff;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    box-shadow: 0 8px 32px rgba(14,165,233,0.3);
}
.dash-welcome-text h2 { margin:0 0 6px; font-size:1.5rem; font-weight:700; }
.dash-welcome-text p  { margin:0; opacity:0.85; font-size:14px; }
.dash-welcome-icon { font-size:3.5rem; opacity:0.3; flex-shrink:0; }

/* Stats grid */
.dash-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.dash-stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem 1.2rem;
    display: flex; align-items: center; gap: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid rgba(0,0,0,0.05);
}
.dash-stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,0.12); }
.stat-icon-circle {
    width: 48px; height: 48px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink:0;
}
.stat-icon-circle.blue  { background:rgba(14,165,233,0.12); color:#0ea5e9; }
.stat-icon-circle.green { background:rgba(16,185,129,0.12); color:#10b981; }
.stat-icon-circle.purple{ background:rgba(139,92,246,0.12); color:#8b5cf6; }
.stat-info h4 { font-size:12px; color:#6b7280; margin:0 0 4px; font-weight:500; text-transform:uppercase; letter-spacing:0.5px; }
.stat-number  { font-size:1.5rem; font-weight:800; color:#1B2A4A; line-height:1; }

/* Sección de pedidos */
.dash-section {
    background: #fff;
    border-radius: 20px;
    padding: 1.8rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    border: 1px solid rgba(0,0,0,0.05);
    margin-bottom: 1.5rem;
}
.dash-section-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.5rem;
}
.dash-section-title {
    font-size: 1rem; font-weight: 700; color: #1B2A4A;
    display: flex; align-items: center; gap: 8px; margin: 0;
}
.dash-section-title i { color: #0ea5e9; }
.dash-section-link { font-size:13px; color:#0ea5e9; text-decoration:none; font-weight:500; }
.dash-section-link:hover { text-decoration:underline; }

/* Tabla de pedidos */
.pedidos-table { width:100%; border-collapse:collapse; font-size:14px; }
.pedidos-table th {
    text-align:left; padding:10px 12px;
    font-size:11px; font-weight:600; color:#9ca3af;
    text-transform:uppercase; letter-spacing:0.5px;
    border-bottom: 2px solid #f3f4f6;
}
.pedidos-table td { padding:14px 12px; border-bottom:1px solid #f9fafb; color:#374151; vertical-align:middle; }
.pedidos-table tr:last-child td { border-bottom:none; }
.pedidos-table tr:hover td { background:#f9fafb; }

/* Badges de estado */
.badge-estado {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 10px; border-radius:20px;
    font-size:11px; font-weight:600; letter-spacing:0.3px;
}
.badge-pendiente  { background:rgba(245,158,11,0.12); color:#d97706; }
.badge-pagado     { background:rgba(14,165,233,0.12); color:#0ea5e9; }
.badge-procesando { background:rgba(139,92,246,0.12); color:#8b5cf6; }
.badge-enviado    { background:rgba(59,130,246,0.12); color:#3b82f6; }
.badge-entregado  { background:rgba(16,185,129,0.12); color:#10b981; }
.badge-cancelado  { background:rgba(239,68,68,0.12); color:#ef4444; }

/* Empty state */
.dash-empty {
    text-align:center; padding:3rem 2rem;
}
.dash-empty i { font-size:3rem; color:#e5e7eb; margin-bottom:1rem; display:block; }
.dash-empty h4 { color:#6b7280; margin:0 0 8px; font-size:1rem; }
.dash-empty p  { color:#9ca3af; font-size:13px; margin:0 0 1.5rem; }

/* Carrito rápido */
.cart-preview {
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 0; border-bottom:1px solid #f3f4f6;
}
.cart-preview:last-child { border-bottom:none; }
</style>

<div class="dash-wrap">

  <!-- ── SIDEBAR ─────────────────────────────────────────── -->
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
      <?php if (!empty($_SESSION['google_id']) || isset($_SESSION['usuario_avatar'])): ?>
        <span class="dash-google-badge">
          <svg width="12" height="12" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
          Cuenta Google
        </span>
      <?php endif; ?>
    </div>

    <nav class="dash-nav">
      <a href="<?= BASE_URL ?>cliente/" class="active"><i class="fas fa-home"></i> Mi Panel</a>
      <a href="<?= BASE_URL ?>cliente/pedidos.php"><i class="fas fa-box"></i> Mis Pedidos</a>
      <a href="<?= BASE_URL ?>carrito.php"><i class="fas fa-shopping-cart"></i> Mi Carrito <?php if($cartCount > 0): ?><span style="background:#0ea5e9;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:auto"><?= $cartCount ?></span><?php endif; ?></a>
      <a href="<?= BASE_URL ?>cliente/perfil.php"><i class="fas fa-user-edit"></i> Mi Perfil</a>
      <a href="<?= BASE_URL ?>tienda.php"><i class="fas fa-store"></i> Ir a la Tienda</a>
      <div class="nav-divider"></div>
      <a href="<?= BASE_URL ?>logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    </nav>
  </aside>

  <!-- ── CONTENIDO PRINCIPAL ─────────────────────────────── -->
  <div class="dash-main">

    <!-- Bienvenida -->
    <div class="dash-welcome">
      <div class="dash-welcome-text">
        <h2>¡Hola, <?= sanitize(explode(' ', $user['nombre'])[0]) ?>! 👋</h2>
        <p>Bienvenido a tu panel de cliente. Aquí puedes ver tus pedidos y gestionar tu cuenta.</p>
      </div>
      <div class="dash-welcome-icon"><i class="fas fa-user-circle"></i></div>
    </div>

    <!-- Stats -->
    <div class="dash-stats">
      <div class="dash-stat-card">
        <div class="stat-icon-circle blue"><i class="fas fa-box"></i></div>
        <div class="stat-info">
          <h4>Pedidos</h4>
          <div class="stat-number"><?= $totalPedidos ?></div>
        </div>
      </div>
      <div class="dash-stat-card">
        <div class="stat-icon-circle green"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-info">
          <h4>Total Gastado</h4>
          <div class="stat-number" style="font-size:1.1rem"><?= formatPrice($totalGastado) ?></div>
        </div>
      </div>
      <div class="dash-stat-card">
        <div class="stat-icon-circle purple"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-info">
          <h4>En Carrito</h4>
          <div class="stat-number"><?= $cartCount ?></div>
        </div>
      </div>
    </div>

    <!-- Últimos pedidos -->
    <div class="dash-section">
      <div class="dash-section-header">
        <h3 class="dash-section-title"><i class="fas fa-history"></i> Últimos Pedidos</h3>
        <a href="<?= BASE_URL ?>cliente/pedidos.php" class="dash-section-link">Ver todos →</a>
      </div>

      <?php if (empty($ultimosPedidos)): ?>
        <div class="dash-empty">
          <i class="fas fa-box-open"></i>
          <h4>Sin pedidos aún</h4>
          <p>¡Explora nuestra tienda y haz tu primera compra!</p>
          <a href="<?= BASE_URL ?>tienda.php" class="btn btn-primary">
            <i class="fas fa-store"></i> Ir a la Tienda
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
                <th>Estado</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ultimosPedidos as $p):
                $estadoClass = 'badge-' . ($p['estado'] ?? 'pendiente');
                $estadoLabel = ucfirst($p['estado'] ?? 'pendiente');
                $estadoIconos = [
                  'pendiente'  => 'clock',
                  'pagado'     => 'check-circle',
                  'procesando' => 'cog',
                  'enviado'    => 'shipping-fast',
                  'entregado'  => 'box-open',
                  'cancelado'  => 'times-circle',
                ];
                $icono = $estadoIconos[$p['estado']] ?? 'circle';
              ?>
              <tr>
                <td><strong style="color:#1B2A4A">#<?= sanitize($p['codigo'] ?? $p['id']) ?></strong></td>
                <td style="color:#6b7280"><?= date('d/m/Y', strtotime($p['created_at'] ?? $p['updated_at'] ?? 'now')) ?></td>
                <td><strong><?= formatPrice($p['total']) ?></strong></td>
                <td>
                  <span class="badge-estado <?= $estadoClass ?>">
                    <i class="fas fa-<?= $icono ?>"></i> <?= $estadoLabel ?>
                  </span>
                </td>
                <td>
                  <a href="<?= BASE_URL ?>cliente/pedidos.php?ver=<?= $p['id'] ?>"
                     style="font-size:12px;color:#0ea5e9;text-decoration:none;font-weight:600">
                    Ver <i class="fas fa-arrow-right"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Carrito rápido -->
    <?php if ($cartCount > 0): ?>
    <div class="dash-section">
      <div class="dash-section-header">
        <h3 class="dash-section-title"><i class="fas fa-shopping-cart"></i> Tu Carrito Actual</h3>
        <a href="<?= BASE_URL ?>carrito.php" class="dash-section-link">Ver carrito →</a>
      </div>
      <?php foreach (($_SESSION['carrito'] ?? []) as $item): ?>
        <div class="cart-preview">
          <div style="display:flex;align-items:center;gap:12px">
            <img src="<?= getProductImage($item['imagen'] ?? '') ?>"
                 style="width:44px;height:44px;object-fit:cover;border-radius:8px;border:1px solid #f3f4f6"
                 alt="<?= sanitize($item['nombre']) ?>">
            <div>
              <div style="font-size:14px;font-weight:600;color:#1B2A4A"><?= sanitize($item['nombre']) ?></div>
              <div style="font-size:12px;color:#9ca3af">Cant: <?= $item['cantidad'] ?></div>
            </div>
          </div>
          <strong style="color:#0ea5e9"><?= formatPrice($item['precio'] * $item['cantidad']) ?></strong>
        </div>
      <?php endforeach; ?>
      <div style="margin-top:1rem;display:flex;gap:10px">
        <a href="<?= BASE_URL ?>carrito.php" class="btn btn-primary btn-sm">
          <i class="fas fa-shopping-cart"></i> Ver Carrito
        </a>
        <a href="<?= BASE_URL ?>checkout.php" class="btn btn-outline btn-sm">
          <i class="fas fa-lock"></i> Pagar Ahora
        </a>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /.dash-main -->
</div><!-- /.dash-wrap -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>