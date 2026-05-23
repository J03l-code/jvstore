<?php
/**
 * JVSTORE - Checkout (Finalizar Compra)
 * Usa el esquema correcto: pedidos con cliente_id, items JSON, created_at
 */
$pageTitle = 'Finalizar Compra';
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Requiere login para comprar
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = BASE_URL . 'checkout.php';
    setFlash('info', 'Inicia sesión para completar tu compra.');
    redirect(BASE_URL . 'login.php');
}

$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    setFlash('warning', 'Tu carrito está vacío. Agrega productos antes de continuar.');
    redirect(BASE_URL . 'tienda.php');
}

$user     = getCurrentUser();
$subtotal = getCartTotal();
$iva      = $subtotal * (IVA_PORCENTAJE / 100);
$envio    = 0.00; // El envío se calcula después por WhatsApp
$total    = $subtotal + $iva;

// ── Procesar pedido (POST) ────────────────────────────────────────────────────
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $direccion  = trim($_POST['direccion'] ?? '');
    $telefono   = trim($_POST['telefono']  ?? '');
    $notas      = trim($_POST['notas']     ?? '');
    $metodoPago = 'whatsapp';

    if (empty($direccion) || empty($telefono)) {
        $error = 'Por favor completa la dirección y el teléfono.';
    } else {
        $db = getDB();
        try {
            $db->beginTransaction();

            // Generar código único del pedido
            $codigo = 'JV-' . strtoupper(substr(uniqid(), -6));

            // Serializar carrito como JSON (guardado en campo items)
            $itemsJson = json_encode(array_values($carrito));

            // Insertar pedido con el esquema correcto
            $stmt = $db->prepare("
                INSERT INTO pedidos
                    (cliente_id, codigo, nombre_cliente, email_cliente, telefono,
                     direccion, items, subtotal, iva, costo_envio, total,
                     estado, metodo_pago, notas)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', ?, ?)
            ");
            $stmt->execute([
                $user['id'],
                $codigo,
                $user['nombre'],
                $user['email'],
                $telefono,
                $direccion,
                $itemsJson,
                $subtotal,
                $iva,
                $envio,
                $total,
                $metodoPago,
                $notas,
            ]);
            $pedidoId = $db->lastInsertId();

            // Descontar stock de cada producto
            foreach ($carrito as $item) {
                $db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?")
                   ->execute([$item['cantidad'], $item['id'], $item['cantidad']]);
            }

            $db->commit();

            // Vaciar carrito
            $_SESSION['carrito'] = [];
            syncCartToDB();

            // ── Mensaje de WhatsApp ─────────────────────────────────────────
            $waNumber = WHATSAPP_NUMBER;
            $waText   = "🛒 *Nuevo Pedido #{$codigo}*\n\n";
            $waText  .= "*Cliente:* {$user['nombre']}\n";
            $waText  .= "*Teléfono:* {$telefono}\n";
            $waText  .= "*Dirección:* {$direccion}\n\n";
            $waText  .= "*Artículos:*\n";
            foreach ($carrito as $item) {
                $sub     = formatPrice($item['precio'] * $item['cantidad']);
                $waText .= "  - {$item['nombre']} ×{$item['cantidad']}: {$sub}\n";
            }
            $waText .= "\n";
            $waText .= "Subtotal: " . formatPrice($subtotal) . "\n";
            $waText .= "IVA (" . IVA_PORCENTAJE . "%): " . formatPrice($iva) . "\n";
            $waText .= "*Total (sin envío): " . formatPrice($total) . "*\n";
            $waText .= "*(El costo de envío será coordinado por este medio)*\n\n";
            if ($notas) $waText .= "Notas: {$notas}\n";
            $waText .= "¿Me podrían confirmar mi pedido?";

            $waUrl = "https://api.whatsapp.com/send?phone={$waNumber}&text=" . rawurlencode($waText);

            setFlash('success', "¡Pedido #{$codigo} realizado con éxito! Serás redirigido a WhatsApp.");
            redirect($waUrl);

        } catch (Throwable $e) {
            $db->rollBack();
            error_log('[Checkout] ' . $e->getMessage());
            $error = 'Error de BD: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<style>
.checkout-wrap {
    max-width: 960px;
    margin: 0 auto;
    padding: 2.5rem 1.5rem 4rem;
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 2rem;
    align-items: start;
}
@media(max-width:768px){ .checkout-wrap{grid-template-columns:1fr;} }

/* Formulario */
.checkout-form-card {
    background: #fff;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 2px 16px rgba(0,0,0,0.07);
    border: 1px solid rgba(0,0,0,0.05);
}
.checkout-form-card h2 {
    font-size: 1.1rem; font-weight: 700; color: #1B2A4A;
    margin: 0 0 1.5rem;
    display: flex; align-items: center; gap: 8px;
}
.checkout-form-card h2 i { color: #0ea5e9; }

.cf-group { margin-bottom: 1rem; }
.cf-group label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:5px; }
.cf-group input,
.cf-group textarea,
.cf-group select {
    width: 100%;
    padding: 12px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    color: #1B2A4A;
    background: #fff;
    transition: border-color 0.2s;
    outline: none;
    box-sizing: border-box;
}
.cf-group input:focus,
.cf-group textarea:focus,
.cf-group select:focus { border-color: #0ea5e9; }
.cf-group input[readonly] { background: #f9fafb; color: #6b7280; cursor: not-allowed; }
.cf-group textarea { resize: vertical; min-height: 80px; }

.metodo-pago-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 10px;
    margin-top: 6px;
}
.metodo-option { display: none; }
.metodo-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 10px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    text-align: center;
}
.metodo-label i { font-size: 1.4rem; color: #9ca3af; }
.metodo-option:checked + .metodo-label {
    border-color: #0ea5e9;
    background: rgba(14,165,233,0.06);
    color: #0ea5e9;
}
.metodo-option:checked + .metodo-label i { color: #0ea5e9; }

.btn-confirm {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.25s;
    margin-top: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
.btn-confirm:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(14,165,233,0.35);
}

/* Resumen */
.checkout-summary {
    background: #1B2A4A;
    border-radius: 20px;
    padding: 1.8rem;
    color: #fff;
    position: sticky;
    top: 90px;
}
.checkout-summary h3 { font-size: 1rem; font-weight: 700; margin: 0 0 1.2rem; opacity: 0.9; }
.summary-items { margin-bottom: 1.2rem; }
.summary-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.summary-item:last-child { border-bottom: none; }
.summary-item img {
    width: 44px; height: 44px;
    object-fit: cover; border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.1);
    flex-shrink: 0;
}
.summary-item-info { flex: 1; }
.summary-item-name { font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.9); line-height: 1.3; }
.summary-item-qty  { font-size: 11px; color: rgba(255,255,255,0.5); }
.summary-item-price { font-size: 13px; font-weight: 700; color: #0ea5e9; white-space: nowrap; }

.summary-divider { height: 1px; background: rgba(255,255,255,0.1); margin: 1rem 0; }
.summary-row-num { display:flex; justify-content:space-between; padding:5px 0; font-size:13px; color:rgba(255,255,255,0.7); }
.summary-total-row { display:flex; justify-content:space-between; padding:10px 0 0; font-size:16px; font-weight:800; color:#fff; }
.summary-total-row span:last-child { color: #0ea5e9; }

.envio-gratis-notice {
    display: flex; align-items: center; gap: 6px;
    background: rgba(16,185,129,0.15);
    border: 1px solid rgba(16,185,129,0.3);
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 12px;
    color: #6ee7b7;
    margin-bottom: 1rem;
}
.checkout-error {
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.25);
    border-radius: 10px;
    padding: 12px 14px;
    color: #dc2626;
    font-size: 13px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>

<section class="page-header"
    style="background:linear-gradient(rgba(10,25,47,0.88),rgba(10,25,47,0.92)),url('<?= getHeaderImage() ?>');background-size:cover;background-position:center">
    <div class="container" style="position:relative;z-index:2">
        <h1 style="color:#fff;font-weight:800;font-size:2.2rem">Finalizar Compra</h1>
        <div class="breadcrumb" style="background:rgba(0,0,0,0.4);padding:5px 16px;border-radius:20px;display:inline-flex;gap:6px">
            <a href="<?= BASE_URL ?>" style="color:#fff;text-decoration:none">Inicio</a>
            <span style="color:#fff">/</span>
            <a href="<?= BASE_URL ?>carrito.php" style="color:#fff;text-decoration:none">Carrito</a>
            <span style="color:#fff">/</span>
            <span style="color:#0ea5e9">Checkout</span>
        </div>
    </div>
</section>

<div class="checkout-wrap">

    <!-- ── Formulario de envío ──────────────────────────────── -->
    <div>
        <?php if ($error): ?>
            <div class="checkout-error">
                <i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="checkoutForm">
            <!-- Datos personales -->
            <div class="checkout-form-card" style="margin-bottom:1.5rem">
                <h2><i class="fas fa-user"></i> Datos del Cliente</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div class="cf-group">
                        <label>Nombre</label>
                        <input type="text" value="<?= sanitize($user['nombre']) ?>" readonly>
                    </div>
                    <div class="cf-group">
                        <label>Email</label>
                        <input type="email" value="<?= sanitize($user['email']) ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- Datos de envío -->
            <div class="checkout-form-card" style="margin-bottom:1.5rem">
                <h2><i class="fas fa-map-marker-alt"></i> Datos de Envío</h2>
                <div class="cf-group">
                    <label for="telefono">Teléfono de Contacto <span style="color:#ef4444">*</span></label>
                    <input type="tel" id="telefono" name="telefono" required
                           placeholder="+593 99 000 0000"
                           value="<?= sanitize($_POST['telefono'] ?? '') ?>">
                </div>
                <div class="cf-group">
                    <label for="direccion">Dirección de Envío <span style="color:#ef4444">*</span></label>
                    <textarea id="direccion" name="direccion" required
                              placeholder="Calle principal, número, sector, ciudad, provincia..."><?= sanitize($_POST['direccion'] ?? '') ?></textarea>
                </div>
                <div class="cf-group">
                    <label for="notas">Notas adicionales <span style="color:#9ca3af">(opcional)</span></label>
                    <textarea id="notas" name="notas" rows="2"
                              placeholder="Instrucciones de entrega, referencias, etc."><?= sanitize($_POST['notas'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="checkout-form-card">
                <button type="submit" class="btn-confirm">
                    <i class="fab fa-whatsapp"></i>
                    Confirmar Pedido y Enviar a WhatsApp
                </button>
                <p style="text-align:center;font-size:12px;color:#9ca3af;margin-top:10px">
                    <i class="fas fa-truck" style="color:#10b981"></i>
                    El pago y envío se coordinarán por WhatsApp
                </p>
            </div>
        </form>
    </div>

    <!-- ── Resumen del pedido ───────────────────────────────── -->
    <div class="checkout-summary">
        <h3><i class="fas fa-receipt" style="color:#0ea5e9;margin-right:6px"></i> Tu Pedido</h3>

        <div class="summary-items">
            <?php foreach ($carrito as $item): ?>
                <div class="summary-item">
                    <img src="<?= getProductImage($item['imagen'] ?? '') ?>"
                         alt="<?= sanitize($item['nombre']) ?>">
                    <div class="summary-item-info">
                        <div class="summary-item-name"><?= sanitize($item['nombre']) ?></div>
                        <div class="summary-item-qty">×<?= $item['cantidad'] ?> · <?= formatPrice($item['precio']) ?> c/u</div>
                    </div>
                    <div class="summary-item-price"><?= formatPrice($item['precio'] * $item['cantidad']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="summary-divider"></div>

        <div class="summary-row-num"><span>Subtotal</span><span><?= formatPrice($subtotal) ?></span></div>
        <div class="summary-row-num">
            <span>IVA (<?= IVA_PORCENTAJE ?>%)</span>
            <span><?= formatPrice($iva) ?></span>
        </div>
        <div class="summary-row-num">
            <span>Envío</span>
            <span><span style="color:#9ca3af;font-size:12px">Por coordinar</span></span>
        </div>

        <div class="summary-divider"></div>

        <div class="summary-total-row">
            <span>TOTAL</span>
            <span><?= formatPrice($total) ?></span>
        </div>

        <div style="margin-top:1.5rem;padding-top:1.2rem;border-top:1px solid rgba(255,255,255,0.08)">
            <a href="<?= BASE_URL ?>carrito.php"
               style="color:rgba(255,255,255,0.6);font-size:13px;text-decoration:none;display:flex;align-items:center;gap:6px">
                <i class="fas fa-arrow-left"></i> Editar carrito
            </a>
        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>