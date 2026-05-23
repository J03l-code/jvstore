<?php
/**
 * IMPORDISPAC - Sistema de Autenticación
 * Login tradicional + Google OAuth 2.0
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Verifica si el usuario ha iniciado sesión
 */
function isLoggedIn()
{
    return isset($_SESSION['usuario_id']);
}

/**
 * Verifica si el usuario es administrador (Super Admin)
 */
function isAdmin()
{
    return isLoggedIn() && isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}

/**
 * Verifica si el usuario es Staff (Admin o Staff)
 * Tiene acceso a operaciones del día a día pero no a gestión de usuarios
 */
function isStaff()
{
    return isLoggedIn() && isset($_SESSION['usuario_rol']) && ($_SESSION['usuario_rol'] === 'admin' || $_SESSION['usuario_rol'] === 'staff');
}

/**
 * Requiere autenticación para acceder
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        setFlash('warning', 'Debes iniciar sesión para continuar.');
        redirect(BASE_URL . 'login.php');
    }
}

/**
 * Requiere rol de admin (Super Admin)
 */
function requireAdmin()
{
    if (!isAdmin()) {
        setFlash('danger', 'Acceso denegado. Se requieren permisos de Administrador.');
        redirect(BASE_URL . 'admin/');
    }
}

/**
 * Requiere rol de Staff o Admin
 */
function requireStaff()
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        setFlash('warning', 'Inicia sesión con tu cuenta de personal.');
        redirect(BASE_URL . 'login.php');
    }

    if (!isStaff()) {
        setFlash('danger', 'Acceso denegado. No tienes permisos de personal.');
        redirect(BASE_URL);
    }
}

/**
 * Inicia sesión con email y contraseña
 */
/**
 * Inicia sesión con email y contraseña
 * Busca primero en Clientes, luego en Usuarios (Staff)
 */
function login($email, $password)
{
    $db = getDB();

    // 1. Intentar como Cliente
    $stmt = $db->prepare("SELECT * FROM clientes WHERE email = ?");
    $stmt->execute([$email]);
    $cliente = $stmt->fetch();

    if ($cliente) {
        // Verificar password (si tiene password seteadas)
        if ($cliente['password'] && password_verify($password, $cliente['password'])) {
            setSessionData($cliente, 'cliente');
            return true;
        }
    }

    // 2. Intentar como Usuario (Admin/Staff)
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?"); // Removed activo=1check as schema didn't show it explicitly initialized, usually default 1 or not present? SQL showed schemas without activo in usuarios creation, but let's assume standard behavior. Re-reading database.sql... table users doesn't have active column in creation script viewed earlier.
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        setSessionData($user, 'usuario');
        return true;
    }

    return false;
}

/**
 * Registra un nuevo Cliente
 */
function register($nombre, $email, $password)
{
    $db = getDB();

    // Verificar si el email ya existe en Clientes
    $stmt = $db->prepare("SELECT id FROM clientes WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Este email ya está registrado.'];
    }

    // Verificar si existe en Usuarios (opcional, pero buena práctica evitar duplicados globales)
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Este email ya está registrado como personal.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    // Insertar en tabla clientes
    $stmt = $db->prepare("INSERT INTO clientes (nombre, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $email, $hash]);

    $userId = $db->lastInsertId();
    $stmt = $db->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    setSessionData($user, 'cliente');
    return ['success' => true];
}

/**
 * Genera la URL de login con Google
 */
function getGoogleLoginUrl()
{
    if (empty(GOOGLE_CLIENT_ID))
        return '#';

    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'offline',
        'prompt' => 'consent',
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

/**
 * Procesa callback de Google OAuth
 * Siempre registra/loguea como CLIENTE
 */
function handleGoogleCallback($code)
{
    // Intercambiar código por token
    $tokenData = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code',
    ];

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $token = json_decode($response, true);
    if (!isset($token['access_token']))
        return false;

    // Obtener info del usuario
    $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token['access_token']]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $googleUser = json_decode($response, true);
    if (!isset($googleUser['email']))
        return false;

    $db = getDB();

    // Buscar en clientes
    $stmt = $db->prepare("SELECT * FROM clientes WHERE email = ? OR google_id = ?");
    $stmt->execute([$googleUser['email'], $googleUser['id']]);
    $user = $stmt->fetch();

    if ($user) {
        // Actualizar google_id si no lo tenía
        if (!$user['google_id']) {
            $stmt = $db->prepare("UPDATE clientes SET google_id = ?, avatar = ? WHERE id = ?");
            $stmt->execute([$googleUser['id'], $googleUser['picture'] ?? null, $user['id']]);
        }
    } else {
        // Crear nuevo cliente
        $stmt = $db->prepare("INSERT INTO clientes (nombre, email, google_id, avatar) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $googleUser['name'],
            $googleUser['email'],
            $googleUser['id'],
            $googleUser['picture'] ?? null,
        ]);
        $stmt = $db->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([$db->lastInsertId()]);
        $user = $stmt->fetch();
    }

    setSessionData($user, 'cliente');
    return true;
}

/**
 * Establece los datos de sesión del usuario
 * @param array $user Datos del usuario
 * @param string $tipo 'cliente' o 'usuario'
 */
function setSessionData($user, $tipo)
{
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['usuario_nombre'] = $user['nombre'];
    $_SESSION['usuario_email'] = $user['email'];
    // Si es cliente, el rol es 'cliente'. Si viene de usuarios, usa su campo rol ('admin', 'staff')
    $_SESSION['usuario_rol'] = ($tipo === 'cliente') ? 'cliente' : $user['rol'];
    $_SESSION['usuario_avatar'] = $user['avatar'] ?? null;
    $_SESSION['usuario_tabla'] = ($tipo === 'cliente') ? 'clientes' : 'usuarios';

    if ($tipo === 'cliente') {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT carrito FROM clientes WHERE id = ?");
            $stmt->execute([$user['id']]);
            $dbCartJson = $stmt->fetchColumn();
            $dbCart = $dbCartJson ? json_decode($dbCartJson, true) : [];

            if (!empty($_SESSION['carrito'])) {
                // El usuario navegaba como invitado y armó un carrito.
                // Sobreescribimos el de la BD con el actual de sesión.
                syncCartToDB();
            } else if (!empty($dbCart)) {
                // Rescata el carrito existente en la base de datos
                $_SESSION['carrito'] = $dbCart;
            }
        } catch (Exception $e) {
            // Ignorar silenciosamente si la columna 'carrito' no existe
        }
    }
}

/**
 * Obtiene datos del usuario actual
 */
function getCurrentUser()
{
    if (!isLoggedIn())
        return null;
    return [
        'id' => $_SESSION['usuario_id'],
        'nombre' => $_SESSION['usuario_nombre'],
        'email' => $_SESSION['usuario_email'],
        'rol' => $_SESSION['usuario_rol'],
        'avatar' => $_SESSION['usuario_avatar'] ?? null,
        'tabla' => $_SESSION['usuario_tabla'] ?? 'usuarios' // Fallback
    ];
}
