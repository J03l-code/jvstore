<?php
/**
 * JVSTORE Admin - Gestión de Servicios
 */
$pageTitle = 'Servicios';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// ── ACCIONES POST ──────────────────────────────────────────
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $action = $_POST['action'] ?? '';

  function uploadServiceImage($file){
    if(!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if(!in_array($ext,['jpg','jpeg','png','gif','webp'])) return null;
    $dir = __DIR__ . '/../uploads/servicios/';
    if(!is_dir($dir)) mkdir($dir, 0755, true);
    $name = uniqid('serv_').'.'.$ext;
    if(move_uploaded_file($file['tmp_name'], $dir.$name)) return $name;
    return null;
  }

  if($action === 'save'){
    $id = (int)($_POST['id'] ?? 0);
    $data = [
      'categoria_id'      => ($_POST['categoria_id'] ?: null),
      'titulo'            => trim($_POST['titulo'] ?? ''),
      'slug'              => generateSlug($_POST['titulo'] ?? ''),
      'descripcion'       => trim($_POST['descripcion'] ?? ''),
      'descripcion_corta' => trim($_POST['descripcion_corta'] ?? ''),
      'precio_desde'      => ($_POST['precio_desde'] !== '' ? (float)$_POST['precio_desde'] : null),
      'icono'             => trim($_POST['icono'] ?? 'fas fa-cog'),
      'destacado'         => isset($_POST['destacado']) ? 1 : 0,
      'orden'             => (int)($_POST['orden'] ?? 0),
      'activo'            => isset($_POST['activo']) ? 1 : 0,
    ];
    if(!$data['titulo']){ setFlash('danger','El título es obligatorio'); redirect(BASE_URL.'admin/servicios.php'); }

    $imagen = uploadServiceImage($_FILES['imagen'] ?? []);
    if($imagen) $data['imagen_url'] = $imagen;

    if($id){
      $sets = implode(',', array_map(fn($k)=>"$k=:$k", array_keys($data)));
      $stmt = $db->prepare("UPDATE servicios SET $sets WHERE id=:id");
      $data['id'] = $id; $stmt->execute($data);
      setFlash('success','Servicio actualizado');
    } else {
      $cols = implode(',', array_keys($data));
      $vals = ':'.implode(',:', array_keys($data));
      $stmt = $db->prepare("INSERT INTO servicios ($cols) VALUES ($vals)");
      $stmt->execute($data);
      setFlash('success','Servicio creado');
    }
    redirect(BASE_URL.'admin/servicios.php');
  }

  if($action === 'delete'){
    $id = (int)$_POST['id'];
    try {
      $db->prepare("DELETE FROM servicios WHERE id=?")->execute([$id]);
      setFlash('success','Servicio eliminado permanentemente');
    } catch (Exception $e) {
      $db->prepare("UPDATE servicios SET activo=0 WHERE id=?")->execute([$id]);
      setFlash('warning','Servicio desactivado (hay dependencias que impiden eliminarlo)');
    }
    redirect(BASE_URL.'admin/servicios.php');
  }
  if($action === 'toggle'){
    $db->prepare("UPDATE servicios SET activo = NOT activo WHERE id=?")->execute([(int)$_POST['id']]);
    redirect(BASE_URL.'admin/servicios.php');
  }
}

// ── GET ─────────────────────────────────────────────────────
$editServ = null;
if(isset($_GET['edit'])){
  $s = $db->prepare("SELECT * FROM servicios WHERE id=?");
  $s->execute([(int)$_GET['edit']]); $editServ = $s->fetch();
}
$showForm = (isset($_GET['action']) && $_GET['action']==='new') || $editServ;

$servicios  = $db->query("SELECT s.*, c.nombre AS cat_nombre FROM servicios s LEFT JOIN categorias c ON s.categoria_id=c.id ORDER BY s.orden,s.id")->fetchAll();
$categorias = $db->query("SELECT * FROM categorias WHERE activo=1 AND tipo IN ('servicio','ambos') ORDER BY nombre")->fetchAll();
$allCats    = $db->query("SELECT * FROM categorias WHERE activo=1 ORDER BY nombre")->fetchAll();
?>

<?php if($showForm): ?>
<div class="adm-card" style="max-width:800px">
  <div class="adm-card-header">
    <h2><i class="fas fa-<?= $editServ?'edit':'plus' ?>"></i> <?= $editServ?'Editar':'Nuevo' ?> Servicio</h2>
    <a href="<?= BASE_URL ?>admin/servicios.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
  </div>
  <div class="adm-card-body">
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save">
    <?php if($editServ): ?><input type="hidden" name="id" value="<?=$editServ['id']?>"> <?php endif; ?>
    <div class="adm-form-grid">
      <div class="form-group full">
        <label class="form-label">Título del Servicio *</label>
        <input type="text" name="titulo" class="form-control" value="<?= sanitize($editServ['titulo']??'') ?>" required placeholder="Ej: Consultoría Empresarial">
      </div>
      <div class="form-group">
        <label class="form-label">Categoría</label>
        <select name="categoria_id" class="form-control">
          <option value="">Sin categoría</option>
          <?php foreach($allCats as $c): ?>
          <option value="<?=$c['id']?>" <?= ($editServ['categoria_id']??'')==$c['id']?'selected':'' ?>><?= sanitize($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Ícono (FontAwesome)</label>
        <input type="text" name="icono" class="form-control" value="<?= sanitize($editServ['icono']??'fas fa-cog') ?>" placeholder="fas fa-cog">
        <small style="color:#94a3b8;font-size:11px">Ej: fas fa-briefcase, fas fa-truck, fas fa-headset</small>
      </div>
      <div class="form-group">
        <label class="form-label">Precio Desde (opcional)</label>
        <input type="number" name="precio_desde" step="0.01" min="0" class="form-control" value="<?= $editServ['precio_desde']??'' ?>" placeholder="Dejar vacío si es por cotización">
      </div>
      <div class="form-group full">
        <label class="form-label">Descripción Corta</label>
        <input type="text" name="descripcion_corta" class="form-control" value="<?= sanitize($editServ['descripcion_corta']??'') ?>" maxlength="300">
      </div>
      <div class="form-group full">
        <label class="form-label">Descripción Completa</label>
        <textarea name="descripcion" class="form-control" rows="5"><?= sanitize($editServ['descripcion']??'') ?></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Imagen (opcional)</label>
        <input type="file" name="imagen" class="form-control" accept="image/*" onchange="previewImage(this,'prevServImg')">
        <?php if($editServ && $editServ['imagen_url']): ?>
        <img src="<?= getProductImage($editServ['imagen_url']) ?>" class="img-preview show" id="prevServImg" style="max-height:120px">
        <?php else: ?>
        <img id="prevServImg" class="img-preview">
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Orden de aparición</label>
        <input type="number" name="orden" class="form-control" value="<?= $editServ['orden']??0 ?>" min="0">
        <div style="display:flex;flex-direction:column;gap:12px;margin-top:16px">
          <label style="display:flex;align-items:center;gap:10px">
            <label class="toggle"><input type="checkbox" name="activo" <?= ($editServ['activo']??1)?'checked':'' ?>><span class="toggle-slider"></span></label>
            <span>Activo</span>
          </label>
          <label style="display:flex;align-items:center;gap:10px">
            <label class="toggle"><input type="checkbox" name="destacado" <?= ($editServ['destacado']??0)?'checked':'' ?>><span class="toggle-slider"></span></label>
            <span>Destacado en inicio</span>
          </label>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:12px;margin-top:24px">
      <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Guardar Servicio</button>
      <a href="<?= BASE_URL ?>admin/servicios.php" class="btn btn-outline">Cancelar</a>
    </div>
  </form>
  </div>
</div>

<?php else: ?>
<div class="adm-card">
  <div class="adm-card-header">
    <h2><i class="fas fa-cogs"></i> Servicios (<?= count($servicios) ?>)</h2>
    <a href="<?= BASE_URL ?>admin/servicios.php?action=new" class="btn btn-gold"><i class="fas fa-plus"></i> Nuevo Servicio</a>
  </div>
  <div style="overflow-x:auto">
  <table class="adm-table">
    <thead><tr><th>Img</th><th>Ícono</th><th>Título</th><th>Categoría</th><th>Precio Desde</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php if(empty($servicios)): ?>
    <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8">No hay servicios. <a href="?action=new">Crear uno</a></td></tr>
    <?php else: foreach($servicios as $s): ?>
    <tr>
      <td>
        <?php if($s['imagen_url']): ?>
        <img src="<?= getProductImage($s['imagen_url']) ?>" alt="">
        <?php else: ?>
        <div style="width:44px;height:44px;background:var(--offwhite);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--navy)">
          <i class="<?= sanitize($s['icono']) ?>" style="font-size:18px"></i>
        </div>
        <?php endif; ?>
      </td>
      <td><code style="font-size:11px;background:#f1f5f9;padding:3px 8px;border-radius:4px"><?= sanitize($s['icono']) ?></code></td>
      <td>
        <strong><?= sanitize(truncateText($s['titulo'],40)) ?></strong>
        <?php if($s['destacado']): ?><span class="badge badge-info" style="margin-left:4px">Destacado</span><?php endif; ?>
      </td>
      <td><?= sanitize($s['cat_nombre']??'—') ?></td>
      <td><?= $s['precio_desde'] ? formatPrice($s['precio_desde']) : '<span style="color:#94a3b8">Por cotización</span>' ?></td>
      <td>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="id" value="<?=$s['id']?>">
          <button type="submit" class="badge badge-<?= $s['activo']?'success':'gray' ?>" style="border:none;cursor:pointer"><?= $s['activo']?'Activo':'Inactivo' ?></button>
        </form>
      </td>
      <td>
        <div style="display:flex;gap:6px">
          <a href="?edit=<?=$s['id']?>" class="btn btn-sm btn-navy"><i class="fas fa-edit"></i></a>
          <a href="<?= BASE_URL ?>servicio.php?id=<?=$s['id']?>" target="_blank" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
          <form method="POST" onsubmit="return confirm('¿Eliminar?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?=$s['id']?>">
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
