<?php
/**
 * JVSTORE - Admin: Gestión de Usuarios (Personal)
 * Solo accesible por Super Admins
 */
$pageTitle = 'Usuarios del Sistema';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAdmin(); // Solo Super Admin
$db = getDB();

// Acciones CRUD
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Eliminar usuario
if ($action === 'delete' && isset($_GET['id'])) {
    $idToDelete = (int) $_GET['id'];
    // Evitar auto-eliminación
    if ($idToDelete === getCurrentUser()['id']) {
        setFlash('danger', 'No puedes eliminar tu propia cuenta.');
    } else {
        // Verificar si es el último admin
        $adminCount = $db->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'")->fetchColumn();
        $userToDelete = $db->query("SELECT rol FROM usuarios WHERE id = $idToDelete")->fetch();

        if ($userToDelete['rol'] === 'admin' && $adminCount <= 1) {
            setFlash('danger', 'No puedes eliminar el último administrador.');
        } else {
            $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$idToDelete]);
            setFlash('success', 'Usuario eliminado correctamente.');
        }
    }
    redirect(BASE_URL . 'admin/usuarios.php');
}

// Guardar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $nombre = sanitize($_POST['nombre']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password']; // Se hashea abajo
    $rol = sanitize($_POST['rol']);

    if (!in_array($rol, ['admin', 'staff'])) {
        $error = "Rol inválido.";
    } else {
        try {
            if ($id > 0) {
                // Editar
                $updates = "nombre = ?, email = ?, rol = ?";
                $params = [$nombre, $email, $rol];

                if (!empty($password)) {
                    $updates .= ", password = ?";
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }

                $params[] = $id;
                $db->prepare("UPDATE usuarios SET $updates WHERE id = ?")->execute($params);
                setFlash('success', 'Usuario actualizado.');
            } else {
                // Crear
                if (empty($password)) {
                    $error = "La contraseña es obligatoria para nuevos usuarios.";
                } else {
                    // Verificar email duplicado
                    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $error = "El email ya está registrado.";
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $db->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)")
                            ->execute([$nombre, $email, $hash, $rol]);
                        setFlash('success', 'Usuario creado.');
                    }
                }
            }
            if (!$error)
                redirect(BASE_URL . 'admin/usuarios.php');
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Obtener datos para editar
$usuario = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([(int) $_GET['id']]);
    $usuario = $stmt->fetch();
}

// Listar usuarios (excluyendo clientes)
$usuarios = $db->query("SELECT * FROM usuarios WHERE rol IN ('admin', 'staff') ORDER BY rol, nombre")->fetchAll();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios | Admin
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
                    <?= $action === 'edit' ? 'Editar Usuario' : 'Personal del Sistema' ?>
                </h2>
                <?php if ($action !== 'list'): ?>
                    <a href="<?= BASE_URL ?>admin/usuarios.php" class="btn btn-outline btn-sm"><i
                            class="fas fa-arrow-left"></i> Volver</a>
                <?php endif; ?>
            </div>

            <div class="admin-content">
                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] ?>"><i class="fas fa-info-circle"></i>
                        <?= $flash['message'] ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <div style="display:grid;grid-template-columns: 1fr 2fr;gap:2rem;align-items:start;">
                    <!-- FORMULARIO (Siempre visible para crear/editar) -->
                    <div class="card" style="padding:1.5rem;">
                        <h3 style="margin-top:0;margin-bottom:1rem;font-size:1.1rem;">
                            <?= $usuario ? 'Editar Usuario' : 'Nuevo Usuario' ?>
                        </h3>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $usuario['id'] ?? 0 ?>">

                            <div class="form-group">
                                <label>Nombre</label>
                                <input type="text" name="nombre" required
                                    value="<?= sanitize($usuario['nombre'] ?? '') ?>" placeholder="Nombre completo">
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" required
                                    value="<?= sanitize($usuario['email'] ?? '') ?>"
                                    placeholder="usuario@jvstore.com">
                            </div>

                            <div class="form-group">
                                <label>Contraseña</label>
                                <input type="password" name="password" <?= $usuario ? '' : 'required' ?> placeholder="
                                <?= $usuario ? 'Dejar en blanco para mantener' : 'Contraseña segura' ?>">
                                <?php if ($usuario): ?>
                                    <small class="text-muted">Solo llenar si se desea cambiar.</small>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>Rol</label>
                                <select name="rol" required
                                    style="width:100%;padding:0.5rem;border:1px solid #ddd;border-radius:4px;">
                                    <option value="staff" <?= ($usuario['rol'] ?? '') === 'staff' ? 'selected' : '' ?>
                                        >Staff (Productos/Pedidos/Clientes)</option>
                                    <option value="admin" <?= ($usuario['rol'] ?? '') === 'admin' ? 'selected' : '' ?>
                                        >Administrador (Acceso Total)</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Guardar Usuario</button>
                            <?php if ($usuario): ?>
                                <a href="?action=list" class="btn btn-outline btn-block"
                                    style="text-align:center;margin-top:0.5rem;">Cancelar</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- LISTADO -->
                    <div class="card" style="padding:0;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td>
                                            <?= sanitize($u['nombre']) ?>
                                            <?php if ($u['id'] == getCurrentUser()['id']): ?>
                                                <span class="badge badge-success" style="font-size:0.7em;">Tú</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= sanitize($u['email']) ?>
                                        </td>
                                        <td>
                                            <?php if ($u['rol'] === 'admin'): ?>
                                                <span class="badge badge-primary">Admin</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Staff</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <a href="?action=edit&id=<?= $u['id'] ?>" class="btn btn-outline btn-sm"
                                                    title="Editar"><i class="fas fa-edit"></i></a>
                                                <?php if ($u['id'] != getCurrentUser()['id']): ?>
                                                    <a href="?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm"
                                                        style="background:var(--danger);color:white;" title="Eliminar"
                                                        onclick="return confirm('¿Eliminar este usuario?')"><i
                                                            class="fas fa-trash"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>