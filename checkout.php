<?php
/**
 * IMPORDISPAC - Checkout
 */
$pageTitle = 'Checkout';
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Se requiere login para checkout
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = BASE_URL . 'checkout.php';
    setFlash('info', 'Inicia sesión para completar tu compra.');
    redirect(BASE_URL . 'login.php');
}

$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    setFlash('warning', 'Tu carrito está vacío.');
    redirect(BASE_URL . 'tienda.php');
}

$user = getCurrentUser();
$subtotal = getCartTotal();
$iva = $subtotal * (IVA_PORCENTAJE / 100);
$envio = COSTO_ENVIO;
$total = $subtotal + $iva + $envio;

// Debug Logging
$logFile = __DIR__ . '/checkout_debug.log';
$logEntry = date('Y-m-d H:i:s') . " - Request: " . $_SERVER['REQUEST_METHOD'] . "\n";
$logEntry .= "Session ID: " . session_id() . "\n";
$logEntry .= "User User: " . print_r($user, true) . "\n";
$logEntry .= "Cart Items: " . count($carrito) . "\n";
$logEntry .= "POST Data: " . print_r($_POST, true) . "\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Procesar pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    try {
        $db->beginTransaction();

        // Crear pedido
        $stmt = $db->prepare("INSERT INTO pedidos (usuario_id, subtotal, iva, envio, total, direccion_envio, telefono, notas, estado, fecha) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())");
        $stmt->execute([
            $user['id'],
            $subtotal,
            $iva,
            $envio,
            $total,
            sanitize($_POST['direccion']),
            sanitize($_POST['telefono']),
            sanitize($_POST['notas'] ?? ''),
        ]);
        $pedidoId = $db->lastInsertId();

        file_put_contents($logFile, "Order Created: $pedidoId\n", FILE_APPEND);

        // Insertar detalle y actualizar stock
        foreach ($carrito as $item) {
            $stmt = $db->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $pedidoId,
                $item['id'],
                $item['cantidad'],
                $item['precio'],
                $item['precio'] * $item['cantidad'],
            ]);

            // Reducir stock
            $db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?")->execute([$item['cantidad'], $item['id'], $item['cantidad']]);
        }

        $db->commit();

        // Vaciar carrito
        $_SESSION['carrito'] = [];
        syncCartToDB();

        setFlash('success', '¡Pedido #' . $pedidoId . ' realizado con éxito!');

        // --- Redirección a WhatsApp ---
        $waNumber = WHATSAPP_NUMBER;
        $waText = "Hola JVstore, acabo de realizar el pedido #{$pedidoId}.\n\n";
        $waText .= "*Detalle del pedido:*\n";
        foreach ($carrito as $item) {
            $sub = formatPrice($item['precio'] * $item['cantidad']);
            $waText .= "- {$item['nombre']} (x{$item['cantidad']}): {$sub}\n";
        }
        $waText .= "\n*Subtotal:* " . formatPrice($subtotal) . "\n";
        $waText .= "*Envío:* " . formatPrice($envio) . "\n";
        $waText .= "*Total a Pagar:* " . formatPrice($total) . "\n\n";
        $waText .= "Mis datos:\n";
        $waText .= "Nombre: {$user['nombre']}\n";
        $waText .= "Email: {$user['email']}";

        $waUrl = "https://api.whatsapp.com/send?phone={$waNumber}&text=" . rawurlencode($waText);

        redirect($waUrl);
        // ------------------------------

    } catch (Exception $e) {
        $db->rollBack();
        file_put_contents($logFile, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
        setFlash('danger', 'Error al procesar el pedido. MSG: ' . $e->getMessage());
    }
}

require_once 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Finalizar Compra</h1>
        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>">Inicio</a> <span>/</span>
            <a href="<?= BASE_URL ?>carrito.php">Carrito</a> <span>/</span>
            <span>Checkout</span>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr;gap:2rem;">
            <div style="display:grid;grid-template-columns:1fr;gap:2rem;">
                <!-- Resumen del pedido -->
                <div class="cart-summary">
                    <h3>Resumen del Pedido</h3>
                    <?php foreach ($carrito as $item): ?>
                        <div class="summary-row">
                            <span>
                                <?= sanitize($item['nombre']) ?> ×
                                <?= $item['cantidad'] ?>
                            </span>
                            <span>
                                <?= formatPrice($item['precio'] * $item['cantidad']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-row"><span>Subtotal</span><span>
                            <?= formatPrice($subtotal) ?>
                        </span></div>
                    <div class="summary-row"><span>IVA (
                            <?= IVA_PORCENTAJE ?>%)
                        </span><span>
                            <?= formatPrice($iva) ?>
                        </span></div>
                    <div class="summary-row"><span>Envío</span><span>
                            <?= formatPrice($envio) ?>
                        </span></div>
                    <div class="summary-total"><span>Total a Pagar</span><span>
                            <?= formatPrice($total) ?>
                        </span></div>
                </div>

                <!-- Datos de envío -->
                <div class="form-container" style="max-width:100%;">
                    <h2>Datos de Envío</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" value="<?= sanitize($user['nombre']) ?>" readonly
                                style="background:var(--gris-bg);">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?= sanitize($user['email']) ?>" readonly
                                style="background:var(--gris-bg);">
                        </div>
                        <div class="form-group">
                            <label for="direccion">Dirección de Envío *</label>
                            <textarea id="direccion" name="direccion" required rows="3"
                                placeholder="Calle, número, ciudad, provincia..."><?= sanitize($_POST['direccion'] ?? $user['direccion'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="telefono">Teléfono de Contacto *</label>
                            <input type="tel" id="telefono" name="telefono" required placeholder="+593 99 000 0000"
                                value="<?= sanitize($_POST['telefono'] ?? $user['telefono'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="notas">Notas adicionales (opcional)</label>
                            <textarea id="notas" name="notas" rows="2"
                                placeholder="Instrucciones especiales..."><?= sanitize($_POST['notas'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">
                            <i class="fas fa-check-circle"></i> Confirmar Pedido —
                            <?= formatPrice($total) ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>