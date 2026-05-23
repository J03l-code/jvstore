<?php
/**
 * IMPORDISPAC - API del Carrito (AJAX)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

define('IS_API', true);
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $productId = (int) ($_POST['product_id'] ?? 0);
        $cantidad = max(1, (int) ($_POST['cantidad'] ?? 1));

        $db = getDB();
        $stmt = $db->prepare("SELECT id, nombre, precio, precio_oferta, stock, imagen_url FROM productos WHERE id = ? AND activo = 1");
        $stmt->execute([$productId]);
        $prod = $stmt->fetch();

        if (!$prod) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
            exit;
        }
        if ($prod['stock'] < $cantidad) {
            echo json_encode(['success' => false, 'message' => 'Stock insuficiente.']);
            exit;
        }

        if (!isset($_SESSION['carrito']))
            $_SESSION['carrito'] = [];

        $precio = $prod['precio_oferta'] ?? $prod['precio'];

        if (isset($_SESSION['carrito'][$productId])) {
            $newQty = $_SESSION['carrito'][$productId]['cantidad'] + $cantidad;
            if ($newQty > $prod['stock']) {
                echo json_encode(['success' => false, 'message' => 'No hay suficiente stock.']);
                exit;
            }
            $_SESSION['carrito'][$productId]['cantidad'] = $newQty;
        } else {
            $_SESSION['carrito'][$productId] = [
                'id' => $prod['id'],
                'nombre' => $prod['nombre'],
                'precio' => (float) $precio,
                'imagen' => $prod['imagen_url'],
                'cantidad' => $cantidad,
            ];
        }

        syncCartToDB();

        echo json_encode([
            'success' => true,
            'message' => 'Producto añadido al carrito.',
            'cartCount' => getCartCount(),
            'cartTotal' => getCartTotal(),
        ]);
        break;

    case 'update':
        $productId = (int) ($_POST['product_id'] ?? 0);
        $cantidad = (int) ($_POST['cantidad'] ?? 0);

        if ($cantidad <= 0) {
            unset($_SESSION['carrito'][$productId]);
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT stock FROM productos WHERE id = ?");
            $stmt->execute([$productId]);
            $prod = $stmt->fetch();
            if ($prod && $cantidad <= $prod['stock']) {
                $_SESSION['carrito'][$productId]['cantidad'] = $cantidad;
            }
        }

        syncCartToDB();

        echo json_encode([
            'success' => true,
            'cartCount' => getCartCount(),
            'cartTotal' => getCartTotal(),
        ]);
        break;

    case 'remove':
        $productId = (int) ($_POST['product_id'] ?? 0);
        unset($_SESSION['carrito'][$productId]);
        syncCartToDB();

        echo json_encode([
            'success' => true,
            'message' => 'Producto eliminado.',
            'cartCount' => getCartCount(),
            'cartTotal' => getCartTotal(),
        ]);
        break;

    case 'get':
        echo json_encode([
            'success' => true,
            'items' => $_SESSION['carrito'] ?? [],
            'cartCount' => getCartCount(),
            'cartTotal' => getCartTotal(),
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
}
