<?php
$pageTitle = 'Carrito de Compras';
require_once 'includes/header.php';
$carrito = $_SESSION['carrito'] ?? [];
$subtotal = getCartTotal();
$iva = $subtotal * (IVA_PORCENTAJE / 100);
$envio = $subtotal > 0 ? COSTO_ENVIO : 0;
$total = $subtotal + $iva + $envio;
?>

<section class="page-header"
    style="background-image: linear-gradient(rgba(10, 25, 47, 0.85), rgba(10, 25, 47, 0.9)), url('<?= getHeaderImage() ?>');">
    <div class="container" style="position:relative; z-index:2;">
        <h1
            style="color: #ffffff !important; text-shadow: 0 4px 8px rgba(0,0,0,0.9); font-weight: 800; font-size: 3rem;">
            Carrito de Compras</h1>
        <div class="breadcrumb"
            style="background: rgba(0,0,0,0.5); padding: 5px 15px; border-radius: 20px; display: inline-flex;">
            <a href="<?= BASE_URL ?>" style="color: #ffffff !important; text-decoration: none;">Inicio</a> <span
                style="color: #ffffff !important; margin: 0 5px;">/</span> <span
                style="color: #ffffff !important;">Carrito</span>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (empty($carrito)): ?>
            <div class="text-center" style="padding:4rem 0;">
                <i class="fas fa-shopping-cart"
                    style="font-size:4rem;color:var(--gris-claro);margin-bottom:1.5rem;display:block;"></i>
                <h3 style="color:var(--gris-medio);margin-bottom:.5rem;">Tu carrito está vacío</h3>
                <p class="text-muted mb-3">Agrega productos desde nuestra tienda para comenzar.</p>
                <a href="<?= BASE_URL ?>tienda.php" class="btn btn-primary btn-lg"><i class="fas fa-store"></i> Ir a la
                    Tienda</a>
            </div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:1fr;gap:2rem;">
                <div style="overflow-x:auto;">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Cantidad</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($carrito as $item): ?>
                                <tr>
                                    <td>
                                        <div class="cart-product">
                                            <img src="<?= getProductImage($item['imagen']) ?>"
                                                alt="<?= sanitize($item['nombre']) ?>">
                                            <div class="cart-product-info">
                                                <h4>
                                                    <?= sanitize($item['nombre']) ?>
                                                </h4>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= formatPrice($item['precio']) ?>
                                    </td>
                                    <td>
                                        <div class="quantity-selector" style="margin-bottom:0;">
                                            <button
                                                onclick="updateCartItem(<?= $item['id'] ?>, <?= $item['cantidad'] - 1 ?>)">−</button>
                                            <input type="number" value="<?= $item['cantidad'] ?>" min="1" readonly>
                                            <button
                                                onclick="updateCartItem(<?= $item['id'] ?>, <?= $item['cantidad'] + 1 ?>)">+</button>
                                        </div>
                                    </td>
                                    <td><strong>
                                            <?= formatPrice($item['precio'] * $item['cantidad']) ?>
                                        </strong></td>
                                    <td>
                                        <button class="cart-remove" onclick="removeFromCart(<?= $item['id'] ?>)"><i
                                                class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="max-width:400px;margin-left:auto;">
                    <div class="cart-summary">
                        <h3>Resumen del Pedido</h3>
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
                        <div class="summary-total"><span>Total</span><span>
                                <?= formatPrice($total) ?>
                            </span></div>
                        <a href="<?= BASE_URL ?>checkout.php" class="btn btn-primary btn-block btn-lg mt-3"><i
                                class="fas fa-lock"></i> Proceder al Pago</a>
                        <a href="<?= BASE_URL ?>tienda.php" class="btn btn-outline btn-block btn-sm mt-2">Seguir
                            Comprando</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
$extraScripts = ['cart.js'];
require_once 'includes/footer.php';
?>