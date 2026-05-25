<?php
/**
 * JVSTORE Admin - Gestión de Opiniones / Testimonios
 */
$pageTitle = 'Opiniones de Clientes';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';
    
    if($action === 'save'){
        $id = (int)($_POST['id'] ?? 0);
        $nombre_cliente = trim($_POST['nombre_cliente']);
        $cargo_empresa = trim($_POST['cargo_empresa']);
        $comentario = trim($_POST['comentario']);
        $estrellas = (int)($_POST['estrellas'] ?? 5);
        $orden = (int)($_POST['orden'] ?? 0);
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        $imagen_url = $_POST['imagen_url_actual'] ?? '';
        if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK){
            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg','jpeg','png','gif','webp'])){
                $dir = __DIR__ . '/../uploads/opiniones/';
                if(!is_dir($dir)) mkdir($dir, 0755, true);
                $name = uniqid('opinion_').'.'.$ext;
                if(move_uploaded_file($_FILES['imagen']['tmp_name'], $dir.$name)){
                    $imagen_url = 'uploads/opiniones/'.$name;
                }
            }
        }
        
        if($id){
            $stmt = $db->prepare("UPDATE testimonios SET nombre_cliente=?, cargo_empresa=?, comentario=?, estrellas=?, imagen_url=?, orden=?, activo=? WHERE id=?");
            $stmt->execute([$nombre_cliente, $cargo_empresa, $comentario, $estrellas, $imagen_url, $orden, $activo, $id]);
            setFlash('success', 'Opinión actualizada correctamente');
        } else {
            $stmt = $db->prepare("INSERT INTO testimonios (nombre_cliente, cargo_empresa, comentario, estrellas, imagen_url, orden, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre_cliente, $cargo_empresa, $comentario, $estrellas, $imagen_url, $orden, $activo]);
            setFlash('success', 'Opinión agregada correctamente');
        }
        redirect('opiniones.php');
    }
    
    if($action === 'delete'){
        $stmt = $db->prepare("DELETE FROM testimonios WHERE id=?");
        $stmt->execute([(int)$_POST['id']]);
        setFlash('success', 'Opinión eliminada');
        redirect('opiniones.php');
    }
}

$opiniones = $db->query("SELECT * FROM testimonios ORDER BY orden ASC, id DESC")->fetchAll();

$editOp = null;
if(isset($_GET['edit'])){
    $stmt = $db->prepare("SELECT * FROM testimonios WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editOp = $stmt->fetch();
}
$showForm = (isset($_GET['action']) && $_GET['action'] === 'new') || $editOp;
?>

<?php if($showForm): ?>
<div class="adm-card" style="max-width:700px">
    <div class="adm-card-header">
        <h2><?= $editOp ? 'Editar' : 'Nueva' ?> Opinión</h2>
        <a href="opiniones.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    <div class="adm-card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <?php if($editOp): ?>
            <input type="hidden" name="id" value="<?= $editOp['id'] ?>">
            <input type="hidden" name="imagen_url_actual" value="<?= $editOp['imagen_url'] ?>">
            <?php endif; ?>
            
            <div class="adm-form-grid">
                <div class="form-group">
                    <label class="form-label">Nombre del Cliente *</label>
                    <input type="text" name="nombre_cliente" class="form-control" required value="<?= sanitize($editOp['nombre_cliente']??'') ?>" placeholder="Ej: Joel Gómez">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Cargo / Empresa (opcional)</label>
                    <input type="text" name="cargo_empresa" class="form-control" value="<?= sanitize($editOp['cargo_empresa']??'') ?>" placeholder="Ej: CEO de Jiyane Design">
                </div>

                <div class="form-group">
                    <label class="form-label">Calificación (Estrellas)</label>
                    <select name="estrellas" class="form-control">
                        <?php for($i=5; $i>=1; $i--): ?>
                        <option value="<?= $i ?>" <?= ($editOp['estrellas']??5)==$i ? 'selected' : '' ?>><?= str_repeat('★', $i) . str_repeat('☆', 5-$i) ?> (<?= $i ?>/5)</option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Foto de perfil (opcional)</label>
                    <input type="file" name="imagen" class="form-control" accept="image/*">
                    <?php if($editOp && $editOp['imagen_url']): ?>
                    <div style="margin-top:10px;">
                        <img src="<?= BASE_URL . $editOp['imagen_url'] ?>" style="width:60px; height:60px; border-radius:50%; object-fit:cover;">
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group full">
                    <label class="form-label">Comentario / Opinión *</label>
                    <textarea name="comentario" class="form-control" rows="4" required placeholder="Excelente servicio y atención de primera..."><?= sanitize($editOp['comentario']??'') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Orden</label>
                    <input type="number" name="orden" class="form-control" value="<?= $editOp['orden'] ?? 0 ?>">
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:10px;margin-top:25px;">
                        <label class="toggle"><input type="checkbox" name="activo" <?= ($editOp['activo'] ?? 1) ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                        <span style="font-weight:600; color:var(--navy)">Visible en la Web</span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-gold" style="margin-top:20px;"><i class="fas fa-save"></i> Guardar Opinión</button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="adm-card">
    <div class="adm-card-header">
        <h2><i class="fas fa-comment-dots"></i> Opiniones de Clientes</h2>
        <a href="?action=new" class="btn btn-gold"><i class="fas fa-plus"></i> Añadir Opinión</a>
    </div>
    <div class="adm-card-body">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Cargo / Empresa</th>
                    <th>Comentario</th>
                    <th>Valoración</th>
                    <th>Orden</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($opiniones)): ?>
                <tr><td colspan="7" class="text-center" style="padding:20px;">No hay opiniones registradas.</td></tr>
                <?php else: foreach($opiniones as $op): ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <?php if($op['imagen_url']): ?>
                            <img src="<?= BASE_URL . $op['imagen_url'] ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                            <?php else: ?>
                            <div style="width:40px; height:40px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; color:#94a3b8;"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                            <strong><?= sanitize($op['nombre_cliente']) ?></strong>
                        </div>
                    </td>
                    <td><?= sanitize($op['cargo_empresa'] ?? '-') ?></td>
                    <td style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= sanitize($op['comentario']) ?></td>
                    <td style="color:#d4af37; font-size:1.1rem;"><?= str_repeat('★', $op['estrellas']) . str_repeat('☆', 5-$op['estrellas']) ?></td>
                    <td><?= $op['orden'] ?></td>
                    <td><span class="badge badge-<?= $op['activo']?'success':'danger' ?>"><?= $op['activo']?'Visible':'Oculto' ?></span></td>
                    <td>
                        <div style="display:flex;gap:5px;">
                            <a href="?edit=<?= $op['id'] ?>" class="btn btn-sm btn-navy"><i class="fas fa-edit"></i></a>
                            <form method="POST" onsubmit="return confirm('¿Eliminar esta opinión?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $op['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
