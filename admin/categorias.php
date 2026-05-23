<?php
/**
 * JVSTORE Admin - Categorías
 */
$pageTitle = 'Categorías';
require_once __DIR__ . '/includes/header.php';
$db = getDB();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $action = $_POST['action'] ?? '';

  if($action === 'save'){
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    if(!$nombre){ setFlash('danger','Nombre requerido'); redirect(BASE_URL.'admin/categorias.php'); }
    $data = [
      'nombre'      => $nombre,
      'slug'        => generateSlug($nombre),
      'descripcion' => trim($_POST['descripcion'] ?? ''),
      'icono'       => trim($_POST['icono'] ?? 'fas fa-box'),
      'color'       => trim($_POST['color'] ?? '#1B2A4A'),
      'tipo'        => $_POST['tipo'] ?? 'producto',
      'orden'       => (int)($_POST['orden'] ?? 0),
      'activo'      => isset($_POST['activo']) ? 1 : 0,
    ];
    if($id){
      $sets = implode(',', array_map(fn($k)=>"$k=:$k", array_keys($data)));
      $db->prepare("UPDATE categorias SET $sets WHERE id=:id")->execute(array_merge($data,['id'=>$id]));
      setFlash('success','Categoría actualizada');
    } else {
      $cols = implode(',', array_keys($data));
      $vals = ':'.implode(',:', array_keys($data));
      $db->prepare("INSERT INTO categorias ($cols) VALUES ($vals)")->execute($data);
      setFlash('success','Categoría creada');
    }
    redirect(BASE_URL.'admin/categorias.php');
  }

  if($action === 'delete'){
    $id = (int)$_POST['id'];
    $count = $db->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id=?");
    $count->execute([$id]); $count = $count->fetchColumn();
    if($count > 0){ setFlash('danger',"No se puede eliminar: tiene $count producto(s) asociado(s)"); }
    else { $db->prepare("DELETE FROM categorias WHERE id=?")->execute([$id]); setFlash('success','Categoría eliminada'); }
    redirect(BASE_URL.'admin/categorias.php');
  }
  if($action === 'toggle'){
    $db->prepare("UPDATE categorias SET activo=NOT activo WHERE id=?")->execute([(int)$_POST['id']]);
    redirect(BASE_URL.'admin/categorias.php');
  }
}

$editCat = null;
if(isset($_GET['edit'])){
  $s = $db->prepare("SELECT * FROM categorias WHERE id=?");
  $s->execute([(int)$_GET['edit']]); $editCat = $s->fetch();
}
$showForm = (isset($_GET['action']) && $_GET['action']==='new') || $editCat;

$categorias = $db->query("
  SELECT c.*, 
    (SELECT COUNT(*) FROM productos p WHERE p.categoria_id=c.id AND p.activo=1) AS total_productos,
    (SELECT COUNT(*) FROM servicios s WHERE s.categoria_id=c.id AND s.activo=1) AS total_servicios
  FROM categorias c ORDER BY c.orden, c.id
")->fetchAll();
?>

<?php if($showForm): ?>
<div class="adm-card" style="max-width:700px">
  <div class="adm-card-header">
    <h2><i class="fas fa-<?= $editCat?'edit':'plus' ?>"></i> <?= $editCat?'Editar':'Nueva' ?> Categoría</h2>
    <a href="<?= BASE_URL ?>admin/categorias.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
  </div>
  <div class="adm-card-body">
  <form method="POST">
    <input type="hidden" name="action" value="save">
    <?php if($editCat): ?><input type="hidden" name="id" value="<?=$editCat['id']?>"> <?php endif; ?>
    <div class="adm-form-grid">
      <div class="form-group full">
        <label class="form-label">Nombre *</label>
        <input type="text" name="nombre" class="form-control" value="<?= sanitize($editCat['nombre']??'') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-control">
          <option value="producto" <?= ($editCat['tipo']??'producto')==='producto'?'selected':'' ?>>Productos</option>
          <option value="servicio" <?= ($editCat['tipo']??'')==='servicio'?'selected':'' ?>>Servicios</option>
          <option value="ambos"    <?= ($editCat['tipo']??'')==='ambos'?'selected':'' ?>>Ambos</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Ícono FontAwesome</label>
        <input type="text" name="icono" class="form-control" value="<?= sanitize($editCat['icono']??'fas fa-box') ?>" placeholder="fas fa-laptop">
      </div>
      <div class="form-group">
        <label class="form-label">Color (hex)</label>
        <div style="display:flex;gap:8px;align-items:center">
          <input type="color" name="color" value="<?= $editCat['color']??'#1B2A4A' ?>" style="width:48px;height:40px;border:none;border-radius:8px;cursor:pointer">
          <input type="text" id="colorText" value="<?= $editCat['color']??'#1B2A4A' ?>" class="form-control" style="flex:1" oninput="document.querySelector('[name=color]').value=this.value">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Orden</label>
        <input type="number" name="orden" class="form-control" value="<?= $editCat['orden']??0 ?>" min="0">
      </div>
      <div class="form-group">
        <label class="form-label" style="margin-bottom:12px">Estado</label>
        <label style="display:flex;align-items:center;gap:10px">
          <label class="toggle"><input type="checkbox" name="activo" <?= ($editCat['activo']??1)?'checked':'' ?>><span class="toggle-slider"></span></label>
          Activa / Visible
        </label>
      </div>
      <div class="form-group full">
        <label class="form-label">Descripción</label>
        <textarea name="descripcion" class="form-control" rows="3"><?= sanitize($editCat['descripcion']??'') ?></textarea>
      </div>
    </div>
    <div style="display:flex;gap:12px;margin-top:20px">
      <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Guardar</button>
      <a href="<?= BASE_URL ?>admin/categorias.php" class="btn btn-outline">Cancelar</a>
    </div>
  </form>
  </div>
</div>

<?php else: ?>
<div class="adm-card">
  <div class="adm-card-header">
    <h2><i class="fas fa-tags"></i> Categorías (<?= count($categorias) ?>)</h2>
    <a href="<?= BASE_URL ?>admin/categorias.php?action=new" class="btn btn-gold"><i class="fas fa-plus"></i> Nueva Categoría</a>
  </div>
  <div style="overflow-x:auto">
  <table class="adm-table">
    <thead><tr><th>Ícono</th><th>Nombre</th><th>Tipo</th><th>Productos</th><th>Servicios</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach($categorias as $c): ?>
    <tr>
      <td>
        <div style="width:40px;height:40px;background:<?= sanitize($c['color']) ?>;border-radius:8px;display:flex;align-items:center;justify-content:center">
          <i class="<?= sanitize($c['icono']) ?>" style="color:#fff;font-size:16px"></i>
        </div>
      </td>
      <td><strong><?= sanitize($c['nombre']) ?></strong><br><span style="font-size:11px;color:#94a3b8"><?= sanitize($c['slug']) ?></span></td>
      <td><span class="badge badge-<?= $c['tipo']==='producto'?'info':($c['tipo']==='servicio'?'success':'warning') ?>"><?= ucfirst($c['tipo']) ?></span></td>
      <td><?= $c['total_productos'] ?></td>
      <td><?= $c['total_servicios'] ?></td>
      <td>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$c['id']?>">
          <button type="submit" class="badge badge-<?= $c['activo']?'success':'gray' ?>" style="border:none;cursor:pointer"><?= $c['activo']?'Activa':'Inactiva' ?></button>
        </form>
      </td>
      <td>
        <div style="display:flex;gap:6px">
          <a href="?edit=<?=$c['id']?>" class="btn btn-sm btn-navy"><i class="fas fa-edit"></i></a>
          <form method="POST" onsubmit="return confirm('¿Eliminar esta categoría?')">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$c['id']?>">
            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
