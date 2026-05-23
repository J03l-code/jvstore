/**
 * JVSTORE - Cart JavaScript
 */
const API_URL = (typeof BASE_URL !== 'undefined' ? BASE_URL : '/JVstore/') + 'api/cart.php';

function addToCart(productId, cantidad = 1) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('cantidad', cantidad);

    fetch(API_URL, {
        method: 'POST',
        body: formData,
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateCartBadge(data.cartCount);
                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'danger');
            }
        })
        .catch(() => showToast('Error de conexión.', 'danger'));
}

function updateCartItem(productId, cantidad) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('product_id', productId);
    formData.append('cantidad', cantidad);

    fetch(API_URL, {
        method: 'POST',
        body: formData,
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateCartBadge(data.cartCount);
                if (typeof refreshCartPage === 'function') refreshCartPage();
                else location.reload();
            }
        });
}

function removeFromCart(productId) {
    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('product_id', productId);

    fetch(API_URL, {
        method: 'POST',
        body: formData,
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateCartBadge(data.cartCount);
                showToast('Producto eliminado del carrito.', 'warning');
                if (typeof refreshCartPage === 'function') refreshCartPage();
                else location.reload();
            }
        });
}

function updateCartBadge(count) {
    let badge = document.getElementById('cartBadge');
    const cartIcon = document.querySelector('.header-cart');

    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'cart-badge';
            badge.id = 'cartBadge';
            cartIcon.appendChild(badge);
        }
        badge.textContent = count;
    } else if (badge) {
        badge.remove();
    }
}

function showToast(message, type = 'info') {
    const existing = document.getElementById('toast-notification');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = 'toast-notification';
    toast.className = `alert alert-${type}`;
    toast.style.cssText = 'position:fixed;top:80px;right:1rem;z-index:9999;max-width:350px;animation:fadeInUp 0.3s ease;';

    const icons = { success: 'check-circle', danger: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };
    toast.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'}"></i> ${message}`;

    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
