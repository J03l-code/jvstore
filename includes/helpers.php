<?php
/**
 * IMPORDISPAC - Funciones Helper
 */

/**
 * Formatea precio con símbolo de moneda
 */
function formatPrice($price)
{
    return MONEDA . ' ' . number_format($price, 2, '.', ',');
}

/**
 * Sanitiza entrada de usuario
 */
function sanitize($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirecciona a una URL
 */
function redirect($url)
{
    header("Location: " . $url);
    exit;
}

/**
 * Genera slug a partir de un texto
 */
function generateSlug($text)
{
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Obtiene cantidad de items en el carrito
 */
function getCartCount()
{
    if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
        $count = 0;
        foreach ($_SESSION['carrito'] as $item) {
            $count += $item['cantidad'];
        }
        return $count;
    }
    return 0;
}

/**
 * Obtiene el total del carrito
 */
function getCartTotal()
{
    if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
        return 0;
    }
    $total = 0;
    foreach ($_SESSION['carrito'] as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }
    return $total;
}

/**
 * Muestra mensaje flash (alertas)
 */
function setFlash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Genera badge de estado para pedidos
 */
function getStatusBadge($estado)
{
    $badges = [
        'pendiente' => '<span class="badge badge-warning">Pendiente</span>',
        'pagado' => '<span class="badge badge-info">Pagado</span>',
        'enviado' => '<span class="badge badge-primary">Enviado</span>',
        'entregado' => '<span class="badge badge-success">Entregado</span>',
        'cancelado' => '<span class="badge badge-danger">Cancelado</span>',
    ];
    return $badges[$estado] ?? '<span class="badge">' . ucfirst($estado) . '</span>';
}

/**
 * Trunca texto a X caracteres
 */
function truncateText($text, $length = 100)
{
    if (strlen($text) <= $length)
        return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Obtiene URL de imagen del producto o placeholder
 */
function getProductImage($imagen)
{
    if (!$imagen) {
        return BASE_URL . 'img/no-image.png';
    }
    // Strip 'uploads/' or 'uploads/productos/' prefix if present (old format)
    $cleanName = $imagen;
    if (strpos($cleanName, 'uploads/productos/') === 0) {
        $cleanName = substr($cleanName, strlen('uploads/productos/'));
    } elseif (strpos($cleanName, 'uploads/') === 0) {
        $cleanName = substr($cleanName, strlen('uploads/'));
    }
    // Check in uploads/productos/ directory
    if (file_exists(__DIR__ . '/../uploads/productos/' . $cleanName)) {
        return UPLOAD_URL . 'productos/' . $cleanName;
    }
    // Check in uploads/ directory (legacy flat upload)
    if (file_exists(__DIR__ . '/../uploads/' . $cleanName)) {
        return UPLOAD_URL . $cleanName;
    }
    // Check if full relative path works from root
    if (file_exists(__DIR__ . '/../' . $imagen)) {
        return BASE_URL . $imagen;
    }
    return BASE_URL . 'img/no-image.png';
}

/**
 * Obtiene imagen de fondo para el header según la categoría
 */
function getHeaderImage($categoria_slug = '')
{
    // Mapa de imágenes por categoría (Unsplash)
    // Mapa de imágenes por categoría (Local)
    $images = [
        'motor' => BASE_URL . 'img/backgrounds/motor.jpg',
        'frenos' => BASE_URL . 'img/backgrounds/frenos.jpg',
        'suspension' => BASE_URL . 'img/backgrounds/suspension.jpg',
        'electrico' => BASE_URL . 'img/backgrounds/electrico.jpg',
        'carroceria' => BASE_URL . 'img/backgrounds/carroceria.jpg',
        'accesorios' => BASE_URL . 'img/backgrounds/accesorios.jpg',
    ];

    // Normalizar slug
    $slug = strtolower($categoria_slug ?? '');

    // Retornar imagen específica o default
    return $images[$slug] ?? BASE_URL . 'img/backgrounds/taller.jpg'; // Default: Taller clean
}

/**
 * Función para enviar correos electrónicos nativos
 */
function sendEmail($to, $subject, $message)
{
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SITE_NAME . " <info@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ">\r\n";

    $htmlContent = "
    <html>
    <head><title>$subject</title></head>
    <body style='font-family:Arial,sans-serif;line-height:1.6;color:#333;'>
        <div style='max-width:600px;margin:0 auto;padding:20px;border:1px solid #ddd;border-radius:8px;'>
            <h2 style='color:#004aad;text-align:center;'>$subject</h2>
            $message
            <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>
            <p style='font-size:12px;color:#888;text-align:center;'>Este es un mensaje automático de " . SITE_NAME . ", por favor no respondas a este correo.</p>
        </div>
    </body>
    </html>";

    return mail($to, $subject, $htmlContent, $headers);
}

/**
 * Sincroniza el carrito actual hacia la base de datos (Persistencia)
 */
function syncCartToDB()
{
    if (isset($_SESSION['usuario_id'], $_SESSION['usuario_tabla']) && $_SESSION['usuario_tabla'] === 'clientes') {
        try {
            $db = getDB();
            $carritoJson = json_encode($_SESSION['carrito'] ?? []);
            $stmt = $db->prepare("UPDATE clientes SET carrito = ? WHERE id = ?");
            $stmt->execute([$carritoJson, $_SESSION['usuario_id']]);
        } catch (Exception $e) {
            // Ignorar el error si la columna no existe en bd
        }
    }
}

