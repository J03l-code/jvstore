<?php
/**
 * JVSTORE - API del Carrito (AJAX)
 * Versión 3.0 - Carrito basado en sesión (no requiere login)
 *              Sincronización opcional a BD si el usuario está autenticado
 */

// Iniciar sesión ANTES de cualquier output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('IS_API', true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Cargar config y dependencias
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Función auxiliar: responder JSON ──────────────────────────────────────────
function jsonResponse(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Función auxiliar: sincronizar carrito a BD (silenciosamente) ───────────────
function syncCart(): void {
    if (!isset($_SESSION['usuario_id'], $_SESSION['usuario_tabla'])) return;
    if ($_SESSION['usuario_tabla'] !== 'clientes') return;
    try {
        $db  = getDB();
        // Verificar que la columna carrito existe
        $cols = $db->query("SHOW COLUMNS FROM clientes LIKE 'carrito'")->fetch();
        if (!$cols) return;
        $json = json_encode($_SESSION['carrito'] ?? []);
        $stmt = $db->prepare("UPDATE clientes SET carrito = ? WHERE id = ?");
        $stmt->execute([$json, $_SESSION['usuario_id']]);
    } catch (Throwable $e) {
        // Silenciar errores de sincronización — el carrito de sesión es suficiente
        error_log('[JVStore Cart Sync] ' . $e->getMessage());
    }
}

switch ($action) {

    // ── AGREGAR AL CARRITO ─────────────────────────────────────────────────────
    case 'add':
        $productId = (int)($_POST['product_id'] ?? 0);
        $cantidad  = max(1, (int)($_POST['cantidad'] ?? 1));

        if ($productId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Producto inválido.']);
        }

        try {
            $db   = getDB();
            $stmt = $db->prepare("SELECT id, nombre, precio, precio_oferta, stock, imagen_url FROM productos WHERE id = ? AND activo = 1");
            $stmt->execute([$productId]);
            $prod = $stmt->fetch();
        } catch (Throwable $e) {
            error_log('[JVStore Cart] DB error: ' . $e->getMessage());
            jsonResponse(['success' => false, 'message' => 'Error al consultar el producto. Intenta de nuevo.']);
        }

        if (!$prod) {
            jsonResponse(['success' => false, 'message' => 'Producto no encontrado o no disponible.']);
        }

        if ((int)$prod['stock'] < $cantidad) {
            jsonResponse(['success' => false, 'message' => 'Stock insuficiente. Solo quedan ' . $prod['stock'] . ' unidades.']);
        }

        // Inicializar carrito en sesión si no existe
        if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        // Precio efectivo (oferta si existe)
        $precio = ($prod['precio_oferta'] && (float)$prod['precio_oferta'] > 0)
                  ? (float)$prod['precio_oferta']
                  : (float)$prod['precio'];

        if (isset($_SESSION['carrito'][$productId])) {
            $newQty = $_SESSION['carrito'][$productId]['cantidad'] + $cantidad;
            if ($newQty > (int)$prod['stock']) {
                jsonResponse(['success' => false, 'message' => 'No hay suficiente stock disponible.']);
            }
            $_SESSION['carrito'][$productId]['cantidad'] = $newQty;
        } else {
            $_SESSION['carrito'][$productId] = [
                'id'      => (int)$prod['id'],
                'nombre'  => $prod['nombre'],
                'precio'  => $precio,
                'imagen'  => $prod['imagen_url'] ?? '',
                'cantidad'=> $cantidad,
            ];
        }

        syncCart();

        jsonResponse([
            'success'   => true,
            'message'   => '¡Producto añadido al carrito!',
            'cartCount' => getCartCount(),
            'cartTotal' => getCartTotal(),
        ]);

    // ── ACTUALIZAR CANTIDAD ────────────────────────────────────────────────────
    case 'update':
        $productId = (int)($_POST['product_id'] ?? 0);
        $cantidad  = (int)($_POST['cantidad'] ?? 0);

        if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

        if ($cantidad <= 0) {
            unset($_SESSION['carrito'][$productId]);
        } else {
            try {
                $db   = getDB();
                $stmt = $db->prepare("SELECT stock FROM productos WHERE id = ? AND activo = 1");
                $stmt->execute([$productId]);
                $prod = $stmt->fetch();
                if ($prod && $cantidad <= (int)$prod['stock']) {
                    if (isset($_SESSION['carrito'][$productId])) {
                        $_SESSION['carrito'][$productId]['cantidad'] = $cantidad;
                    }
                }
            } catch (Throwable $e) {
                error_log('[JVStore Cart] ' . $e->getMessage());
            }
        }

        syncCart();

        jsonResponse([
            'success'   => true,
            'cartCount' => getCartCount(),
            'cartTotal' => getCartTotal(),
        ]);

    // ── ELIMINAR ITEM ──────────────────────────────────────────────────────────
    case 'remove':
        $productId = (int)($_POST['product_id'] ?? 0);
        if (isset($_SESSION['carrito'][$productId])) {
            unset($_SESSION['carrito'][$productId]);
        }
        syncCart();

        jsonResponse([
            'success'   => true,
            'message'   => 'Producto eliminado del carrito.',
            'cartCount' => getCartCount(),
            'cartTotal' => getCartTotal(),
        ]);

    // ── OBTENER CARRITO ────────────────────────────────────────────────────────
    case 'get':
        jsonResponse([
            'success'   => true,
            'items'     => array_values($_SESSION['carrito'] ?? []),
            'cartCount' => getCartCount(),
            'cartTotal' => getCartTotal(),
        ]);

    // ── VACIAR CARRITO ─────────────────────────────────────────────────────────
    case 'clear':
        $_SESSION['carrito'] = [];
        syncCart();
        jsonResponse(['success' => true, 'cartCount' => 0, 'cartTotal' => 0]);

    default:
        jsonResponse(['success' => false, 'message' => 'Acción no válida.']);
}
