<?php
/**
 * JVSTORE Admin - Gestión de Marcas
 */
$pageTitle = 'Marcas / Patrocinadores';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Auto-crear tabla
$db->exec("CREATE TABLE IF NOT EXISTS marcas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    imagen_url VARCHAR(255) NOT NULL,
    orden INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';
    
    if($action === 'save'){
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre']);
        $orden = (int)$_POST['orden'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        $imagen_url = $_POST['imagen_url_actual'] ?? '';
        if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK){
            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg','jpeg','png','gif','svg','webp'])){
                $dir = __DIR__ . '/../uploads/marcas/';
                if(!is_dir($dir)) mkdir($dir, 0755, true);
                $name = uniqid('marca_').'.'.$ext;
                if(move_uploaded_file($_FILES['imagen']['tmp_name'], $dir.$name)){
                    $imagen_url = 'uploads/marcas/'.$name;
                }
            }
        }
        
        if($id){
            $stmt = $db->prepare("UPDATE marcas SET nombre=?, imagen_url=?, orden=?, activo=? WHERE id=?");
            $stmt->execute([$nombre, $imagen_url, $orden, $activo, $id]);
            setFlash('success', 'Marca actualizada');
        } else {
            $stmt = $db->prepare("INSERT INTO marcas (nombre, imagen_url, orden, activo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $imagen_url, $orden, $activo]);
            setFlash('success', 'Marca añadida');
        }
        redirect('marcas.php');
    }
    
    if($action === 'delete'){
        $stmt = $db->prepare("DELETE FROM marcas WHERE id=?");
        $stmt->execute([(int)$_POST['id']]);
        setFlash('success', 'Marca eliminada');
        redirect('marcas.php');
    }
}

$marcas = $db->query("SELECT * FROM marcas ORDER BY orden ASC, id DESC")->fetchAll();

$editMarca = null;
if(isset($_GET['edit'])){
    $stmt = $db->prepare("SELECT * FROM marcas WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editMarca = $stmt->fetch();
}
$showForm = isset($_GET['action']) && $_GET['action'] === 'new' || $editMarca;
?>

<?php if($showForm): ?>
<div class="adm-card" style="max-width:600px">
    <div class="adm-card-header">
        <h2><?= $editMarca ? 'Editar' : 'Nueva' ?> Marca</h2>
        <a href="marcas.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
    <div class="adm-card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <?php if($editMarca): ?>
            <input type="hidden" name="id" value="<?= $editMarca['id'] ?>">
            <input type="hidden" name="imagen_url_actual" value="<?= $editMarca['imagen_url'] ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Nombre de la marca</label>
                <input type="text" name="nombre" class="form-control" required value="<?= sanitize($editMarca['nombre']??'') ?>">
            </div>
            
            <div class="form-group">
                <label>Logo de la marca (SVG, PNG, WebP recomendados)</label>
                <input type="file" name="imagen" class="form-control" <?= $editMarca ? '' : 'required' ?> accept="image/*">
                <?php if($editMarca && $editMarca['imagen_url']): ?>
                <div style="margin-top:10px; background:#f1f5f9; padding:10px; border-radius:8px; display:inline-block;">
                    <img src="<?= BASE_URL . $editMarca['imagen_url'] ?>" style="max-height:40px; display:block;">
                </div>
                <?php endif; ?>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label>Orden</label>
                    <input type="number" name="orden" class="form-control" value="<?= $editMarca['orden'] ?? 0 ?>">
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:10px;margin-top:25px;">
                        <label class="toggle"><input type="checkbox" name="activo" <?= ($editMarca['activo'] ?? 1) ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
                        <span>Activo</span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-gold" style="margin-top:20px;"><i class="fas fa-save"></i> Guardar Marca</button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="adm-card">
    <div class="adm-card-header">
        <h2><i class="fas fa-copyright"></i> Marcas y Patrocinadores</h2>
        <a href="?action=new" class="btn btn-gold"><i class="fas fa-plus"></i> Añadir Marca</a>
    </div>
    <div class="adm-card-body">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Logo</th>
                    <th>Nombre</th>
                    <th>Orden</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($marcas)): ?>
                <tr><td colspan="5" class="text-center" style="padding:20px;">No hay marcas registradas.</td></tr>
                <?php else: foreach($marcas as $m): ?>
                <tr>
                    <td style="background:#f8fafc;"><img src="<?= BASE_URL . $m['imagen_url'] ?>" style="max-height:30px; max-width:80px; object-fit:contain;"></td>
                    <td><strong><?= sanitize($m['nombre']) ?></strong></td>
                    <td><?= $m['orden'] ?></td>
                    <td><span class="badge badge-<?= $m['activo']?'success':'danger' ?>"><?= $m['activo']?'Activo':'Inactivo' ?></span></td>
                    <td>
                        <div style="display:flex;gap:5px;">
                            <a href="?edit=<?= $m['id'] ?>" class="btn btn-sm btn-navy"><i class="fas fa-edit"></i></a>
                            <form method="POST" onsubmit="return confirm('¿Eliminar esta marca?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
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
