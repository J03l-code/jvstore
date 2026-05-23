<?php
/**
 * IMPORDISPAC - Admin: Gestión de Clientes
 */
$pageTitle = 'Clientes';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireStaff(); // Acceso para Admin y Staff
$db = getDB();

// Acciones CRUD
$action = $_GET['action'] ?? 'list';

// Eliminar Cliente
if ($action === 'delete' && isset($_GET['id'])) {
    if (isAdmin()) { // Solo admin puede borrar
        $idToDelete = (int) $_GET['id'];
        $db->prepare("DELETE FROM clientes WHERE id = ?")->execute([$idToDelete]);
        setFlash('success', 'Cliente eliminado correctamente.');
    } else {
        setFlash('danger', 'No tienes permiso para realizar esta acción.');
    }
    redirect(BASE_URL . 'admin/clientes.php');
}

// Crear Cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_cliente'])) {
    if (isAdmin()) {
        $nombre = sanitize($_POST['nombre']);
        $email = sanitize($_POST['email']);
        $telefono = sanitize($_POST['telefono']);
        $direccion = sanitize($_POST['direccion']);
        $password = $_POST['password'];

        // Verificar duplicados
        $stmt = $db->prepare("SELECT id FROM clientes WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            setFlash('danger', 'El email ya está registrado.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO clientes (nombre, email, password, telefono, direccion) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $email, $hash, $telefono, $direccion]);
            setFlash('success', 'Cliente creado exitosamente.');
        }
    } else {
        setFlash('danger', 'No tienes permiso para crear clientes.');
    }
    redirect(BASE_URL . 'admin/clientes.php');
}

// Ver Detalle de Cliente
$cliente = null;
$pedidosCliente = [];
if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([(int) $_GET['id']]);
    $cliente = $stmt->fetch();

    if ($cliente) {
        // Obtener historial de pedidos
        $stmt = $db->prepare("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY fecha DESC");
        $stmt->execute([$cliente['id']]);
        $pedidosCliente = $stmt->fetchAll();
    } else {
        redirect(BASE_URL . 'admin/clientes.php');
    }
}

// Listar Clientes
if (!$cliente) {
    // Consulta con total gastado
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM pedidos p WHERE p.usuario_id = c.id) as total_pedidos,
            (SELECT COALESCE(SUM(total), 0) FROM pedidos p WHERE p.usuario_id = c.id AND p.estado != 'cancelado') as total_gastado
            FROM clientes c 
            ORDER BY c.created_at DESC";
    $clientes = $db->query($sql)->fetchAll();
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes | Admin
        <?= SITE_NAME ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/components.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/layout.css">
</head>

<body>
    <div class="admin-layout">
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

        <div class="admin-main">
            <div class="admin-topbar">
                <h2>
                    <?= $cliente ? 'Detalle de Cliente' : 'Gestión de Clientes' ?>
                </h2>
                <?php if ($cliente): ?>
                    <a href="<?= BASE_URL ?>admin/clientes.php" class="btn btn-outline btn-sm"><i
                            class="fas fa-arrow-left"></i> Volver</a>
                <?php endif; ?>
            </div>

            <div class="admin-content">
                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] ?>"><i class="fas fa-info-circle"></i>
                        <?= $flash['message'] ?></div>
                <?php endif; ?>

                <?php if (!$cliente && isAdmin()): ?>
                    <!-- FORMULARIO CREAR CLIENTE (Colapsable o simple) -->
                    <div class="card mb-4" style="padding:1.5rem;">
                        <h3 style="margin-top:0;">Registrar Nuevo Cliente</h3>
                        <form method="POST"
                            style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:1rem;align-items:end;">
                            <input type="hidden" name="crear_cliente" value="1">
                            <div class="form-group" style="margin:0;">
                                <label>Nombre</label>
                                <input type="text" name="nombre" required placeholder="Nombre completo"
                                    style="padding:0.5rem;">
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label>Email</label>
                                <input type="email" name="email" required placeholder="email@cliente.com"
                                    style="padding:0.5rem;">
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label>Contraseña</label>
                                <input type="password" name="password" required placeholder="Contraseña"
                                    style="padding:0.5rem;">
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label>Teléfono</label>
                                <input type="text" name="telefono" placeholder="099..." style="padding:0.5rem;">
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label>Dirección</label>
                                <input type="text" name="direccion" placeholder="Dirección de envío"
                                    style="padding:0.5rem;">
                            </div>
                            <div class="form-group" style="margin:0;">
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Crear
                                    Cliente</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($cliente): ?>
                    <!-- VISTA DETALLE -->
                    <div class="card mb-4" style="padding:1.5rem;">
                        <div style="display:flex;align-items:center;gap:1.5rem;">
                            <div
                                style="width:80px;height:80px;border-radius:50%;background:#eee;display:flex;align-items:center;justify-content:center;font-size:2rem;color:#aaa;overflow:hidden;">
                                <?php if (!empty($cliente['avatar'])): ?>
                                    <img src="<?= $cliente['avatar'] ?>" alt=""
                                        style="width:100%;height:100%;object-fit:cover;">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 style="margin:0 0 .5rem 0;">
                                    <?= sanitize($cliente['nombre']) ?>
                                </h3>
                                <p style="margin:0;color:#666;"><i class="fas fa-envelope"></i>
                                    <?= sanitize($cliente['email']) ?>
                                </p>
                                <p style="margin:0;color:#666;font-size:0.9rem;">Registrado el:
                                    <?= date('d/m/Y', strtotime($cliente['created_at'])) ?>
                                </p>
                                <?php if (!empty($cliente['google_id'])): ?>
                                    <span class="badge badge-primary" style="margin-top:.5rem;"><i class="fab fa-google"></i>
                                        Cuenta Google</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <h3 style="margin-bottom:1rem;">Historial de Compras</h3>
                    <div style="overflow-x:auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Pedido #</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidosCliente as $p): ?>
                                    <tr>
                                        <td>#
                                            <?= $p['id'] ?>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y H:i', strtotime($p['fecha'])) ?>
                                        </td>
                                        <td>
                                            <?= getStatusBadge($p['estado']) ?>
                                        </td>
                                        <td>
                                            <?php
                                            // Conteo rápido de items (opcional, requeriría otra query o subquery, por ahora texto)
                                            // Para optimizar, asumimos ver detalle en pedido
                                            echo "Ver detalle";
                                            ?>
                                        </td>
                                        <td><strong>
                                                <?= formatPrice($p['total']) ?>
                                            </strong></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>admin/pedidos.php?ver=<?= $p['id'] ?>"
                                                class="btn btn-outline btn-sm">Ver Pedido</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($pedidosCliente)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted" style="padding:2rem;">Este cliente no ha
                                            realizado compras.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else: ?>
                    <!-- LISTADO -->
                    <div style="overflow-x:auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Email</th>
                                    <th>Registro</th>
                                    <th>Pedidos</th>
                                    <th>Total Gastado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientes as $c): ?>
                                    <tr>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:.75rem;">
                                                <div
                                                    style="width:32px;height:32px;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                                                    <?php if (!empty($c['avatar'])): ?>
                                                        <img src="<?= $c['avatar'] ?>" alt=""
                                                            style="width:100%;height:100%;object-fit:cover;">
                                                    <?php else: ?>
                                                        <i class="fas fa-user text-muted" style="font-size:0.8rem;"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <strong>
                                                    <?= sanitize($c['nombre']) ?>
                                                </strong>
                                            </div>
                                        </td>
                                        <td>
                                            <?= sanitize($c['email']) ?>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($c['created_at'])) ?>
                                        </td>
                                        <td><span class="badge badge-secondary">
                                                <?= $c['total_pedidos'] ?>
                                            </span></td>
                                        <td style="color:var(--success);font-weight:600;">
                                            <?= formatPrice($c['total_gastado']) ?>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <a href="?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm" title="Ver Detalle"><i class="fas fa-eye"></i></a>
                                                <?php if (isAdmin()): ?>
                                                    <a href="?action=delete&id=<?= $c['id'] ?>" class="btn btn-sm" style="background:var(--danger);color:white;" title="Eliminar Cliente" onclick="return confirm('¿Seguro que deseas eliminar este cliente? Se borrará su historial.')"><i class="fas fa-trash"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($clientes)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted" style="padding:3rem;">No hay clientes
                                            registrados.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>