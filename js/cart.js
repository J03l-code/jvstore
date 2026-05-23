/**
 * JVSTORE - Cart JavaScript v3.0
 * Sistema de carrito 100% funcional con feedback premium
 */

// BASE_URL es inyectada por PHP en el header
const CART_API = (typeof BASE_URL !== 'undefined' ? BASE_URL : '/') + 'api/cart.php';

// ── Estado del carrito ────────────────────────────────────────────────────────
let _cartLoading = false;

// ── Agregar al carrito ────────────────────────────────────────────────────────
function addToCart(productId, cantidad = 1) {
    if (_cartLoading) return;
    _cartLoading = true;

    // Feedback visual en el botón
    const btn = document.querySelector(`[onclick*="addToCart(${productId}"]`);
    if (btn) {
        btn.disabled = true;
        btn.dataset.originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }

    const formData = new FormData();
    formData.append('action',     'add');
    formData.append('product_id', productId);
    formData.append('cantidad',   cantidad);

    fetch(CART_API, { method: 'POST', body: formData })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(data => {
            if (data.success) {
                updateCartBadge(data.cartCount);
                showToast(data.message || '¡Producto añadido!', 'success');
                animateCartIcon();
            } else {
                showToast(data.message || 'No se pudo agregar el producto.', 'danger');
            }
        })
        .catch(err => {
            console.error('[Cart]', err);
            showToast('Error de conexión. Verifica tu internet e intenta de nuevo.', 'danger');
        })
        .finally(() => {
            _cartLoading = false;
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = btn.dataset.originalHtml || '<i class="fas fa-shopping-cart"></i>';
            }
        });
}

// ── Actualizar cantidad ───────────────────────────────────────────────────────
function updateCartItem(productId, cantidad) {
    const formData = new FormData();
    formData.append('action',     'update');
    formData.append('product_id', productId);
    formData.append('cantidad',   cantidad);

    fetch(CART_API, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateCartBadge(data.cartCount);
                if (typeof refreshCartPage === 'function') refreshCartPage();
                else location.reload();
            } else {
                showToast(data.message || 'No se pudo actualizar.', 'warning');
            }
        })
        .catch(() => showToast('Error de conexión.', 'danger'));
}

// ── Eliminar del carrito ──────────────────────────────────────────────────────
function removeFromCart(productId) {
    const formData = new FormData();
    formData.append('action',     'remove');
    formData.append('product_id', productId);

    fetch(CART_API, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateCartBadge(data.cartCount);
                showToast('Producto eliminado del carrito.', 'warning');
                if (typeof refreshCartPage === 'function') refreshCartPage();
                else location.reload();
            }
        })
        .catch(() => showToast('Error al eliminar.', 'danger'));
}

// ── Actualizar badge del carrito en el header ─────────────────────────────────
function updateCartBadge(count) {
    // Actualizar todos los badges de carrito en la página
    document.querySelectorAll('.jv-cart-count, #cartBadge, .cart-badge').forEach(el => {
        el.textContent = count;
        el.style.display = count > 0 ? '' : 'none';
    });

    // Si no existe el badge y hay items, crearlo
    if (count > 0) {
        const cartBtn = document.querySelector('.jv-cart-btn');
        if (cartBtn && !cartBtn.querySelector('.jv-cart-count')) {
            const badge = document.createElement('span');
            badge.className = 'jv-cart-count';
            badge.textContent = count;
            cartBtn.appendChild(badge);
        }
    }
}

// ── Animación del ícono de carrito ────────────────────────────────────────────
function animateCartIcon() {
    const cartBtn = document.querySelector('.jv-cart-btn');
    if (!cartBtn) return;
    cartBtn.style.transform = 'scale(1.2)';
    setTimeout(() => cartBtn.style.transform = '', 300);
}

// ── Toast de notificación ─────────────────────────────────────────────────────
function showToast(message, type = 'info') {
    // Remover toast anterior
    const existing = document.getElementById('jv-toast');
    if (existing) existing.remove();

    const icons = {
        success: 'check-circle',
        danger:  'exclamation-circle',
        warning: 'exclamation-triangle',
        info:    'info-circle'
    };

    const colors = {
        success: 'linear-gradient(135deg, #0ea5e9, #0284c7)',
        danger:  'linear-gradient(135deg, #ef4444, #dc2626)',
        warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
        info:    'linear-gradient(135deg, #6366f1, #4f46e5)'
    };

    const toast = document.createElement('div');
    toast.id = 'jv-toast';
    toast.style.cssText = `
        position: fixed;
        top: 90px;
        right: 1.5rem;
        z-index: 99999;
        background: ${colors[type] || colors.info};
        color: #fff;
        padding: 14px 20px;
        border-radius: 12px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 500;
        max-width: 360px;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.25);
        animation: toastIn 0.35s cubic-bezier(0.34,1.56,0.64,1);
        cursor: pointer;
    `;
    toast.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'}" style="font-size:18px;flex-shrink:0"></i><span>${message}</span>`;

    // Agregar animación CSS si no existe
    if (!document.getElementById('toast-style')) {
        const style = document.createElement('style');
        style.id = 'toast-style';
        style.textContent = `
            @keyframes toastIn {
                from { opacity:0; transform: translateX(100px) scale(0.8); }
                to   { opacity:1; transform: translateX(0) scale(1); }
            }
            @keyframes toastOut {
                from { opacity:1; transform: translateX(0); }
                to   { opacity:0; transform: translateX(100px); }
            }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(toast);

    // Click para cerrar
    toast.addEventListener('click', () => removeToast(toast));

    // Auto-remover después de 3.5s
    setTimeout(() => removeToast(toast), 3500);
}

function removeToast(toast) {
    if (!toast || !toast.parentNode) return;
    toast.style.animation = 'toastOut 0.3s ease forwards';
    setTimeout(() => toast.remove(), 300);
}
