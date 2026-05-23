<?php
/**
 * JVSTORE Admin - Banners Hero
 */
$pageTitle = 'Banners';
require_once __DIR__ . '/includes/header.php';
$db = getDB();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $action = $_POST['action'] ?? '';

  function uploadBanner($file){
    if(!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if(!in_array($ext,['jpg','jpeg','png','gif','webp'])) return null;
    $dir = __DIR__ . '/../uploads/banners/';
    if(!is_dir($dir)) mkdir($dir, 0755, true);
    $name = uniqid('banner_').'.'.$ext;
    if(move_uploaded_file($file['tmp_name'], $dir.$name)) return 'banners/'.$name;
    return null;
  }

  if($action === 'save'){
    $id = (int)($_POST['id'] ?? 0);
    $data = [
      'titulo'      => trim($_POST['titulo'] ?? ''),
      'subtitulo'   => trim($_POST['subtitulo'] ?? ''),
      'enlace'      => trim($_POST['enlace'] ?? ''),
      'boton_texto' => trim($_POST['boton_texto'] ?? 'Ver más'),
      'posicion'    => $_POST['posicion'] ?? 'principal',
      'orden'       => (int)($_POST['orden'] ?? 0),
      'activo'      => isset($_POST['activo']) ? 1 : 0,
    ];
    // Imagen (subida o URL externa)
    $imgUrl = trim($_POST['imagen_url'] ?? '');
    $imgFile = uploadBanner($_FILES['imagen'] ?? []);
    if($imgFile) $data['imagen_url'] = UPLOAD_URL . $imgFile;
    elseif($imgUrl) $data['imagen_url'] = $imgUrl;

    if($id){
      $sets = implode(',', array_map(fn($k)=>"$k=:$k", array_keys($data)));
      $db->prepare("UPDATE banners SET $sets WHERE id=:id")->execute(array_merge($data,['id'=>$id]));
      setFlash('success','Banner actualizado');
    } else {
      if(empty($data['imagen_url'])){ setFlash('danger','La imagen es requerida'); redirect(BASE_URL.'admin/banners.php'); }
      $cols = implode(',', array_keys($data));
      $vals = ':'.implode(',:', array_keys($data));
      $db->prepare("INSERT INTO banners ($cols) VALUES ($vals)")->execute($data);
      setFlash('success','Banner creado');
    }
    redirect(BASE_URL.'admin/banners.php');
  }

  if($action === 'delete'){
    $db->prepare("DELETE FROM banners WHERE id=?")->execute([(int)$_POST['id']]);
    setFlash('success','Banner eliminado'); redirect(BASE_URL.'admin/banners.php');
  }
  if($action === 'toggle'){
    $db->prepare("UPDATE banners SET activo=NOT activo WHERE id=?")->execute([(int)$_POST['id']]);
    redirect(BASE_URL.'admin/banners.php');
  }
}

$editBanner = null;
if(isset($_GET['edit'])){
  $s = $db->prepare("SELECT * FROM banners WHERE id=?");
  $s->execute([(int)$_GET['edit']]); $editBanner = $s->fetch();
}
$showForm = (isset($_GET['action']) && $_GET['action']==='new') || $editBanner;
$banners  = $db->query("SELECT * FROM banners ORDER BY posicion,orden")->fetchAll();
?>

<?php if($showForm): ?>
<div class="adm-card" style="max-width:750px">
  <div class="adm-card-header">
    <h2><i class="fas fa-image"></i> <?= $editBanner?'Editar':'Nuevo' ?> Banner</h2>
    <a href="<?= BASE_URL ?>admin/banners.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
  </div>
  <div class="adm-card-body">
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save">
    <?php if($editBanner): ?><input type="hidden" name="id" value="<?=$editBanner['id']?>"> <?php endif; ?>
    <div class="adm-form-grid">
      <div class="form-group full">
        <label class="form-label">Título del Banner</label>
        <input type="text" name="titulo" class="form-control" value="<?= sanitize($editBanner['titulo']??'') ?>">
      </div>
      <div class="form-group full">
        <label class="form-label">Subtítulo / Descripción</label>
        <input type="text" name="subtitulo" class="form-control" value="<?= sanitize($editBanner['subtitulo']??'') ?>">
      </div>
      <div class="form-group full">
        <label class="form-label">Imagen — Subir archivo</label>
        <input type="file" name="imagen" class="form-control" accept="image/*" onchange="previewImage(this,'prevBanner')">
        <?php if($editBanner && $editBanner['imagen_url']): ?>
        <img src="<?= sanitize($editBanner['imagen_url']) ?>" class="img-preview show" id="prevBanner" style="max-height:150px">
        <?php else: ?>
        <img id="prevBanner" class="img-preview">
        <?php endif; ?>
      </div>
      <div class="form-group full">
        <label class="form-label">— O pegar URL de imagen externa</label>
        <input type="text" name="imagen_url" class="form-control" value="<?= sanitize($editBanner['imagen_url']??'') ?>" placeholder="https://...">
      </div>
      <div class="form-group">
        <label class="form-label">Enlace al hacer clic</label>
        <input type="text" name="enlace" class="form-control" value="<?= sanitize($editBanner['enlace']??'') ?>" placeholder="tienda.php">
      </div>
      <div class="form-group">
        <label class="form-label">Texto del Botón</label>
        <input type="text" name="boton_texto" class="form-control" value="<?= sanitize($editBanner['boton_texto']??'Ver más') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Posición</label>
        <select name="posicion" class="form-control">
          <option value="principal"   <?= ($editBanner['posicion']??'principal')==='principal'?'selected':'' ?>>Principal (Hero Slider)</option>
          <option value="secundario"  <?= ($editBanner['posicion']??'')==='secundario'?'selected':'' ?>>Secundario (Lateral)</option>
          <option value="mini"        <?= ($editBanner['posicion']??'')==='mini'?'selected':'' ?>>Mini (Promo bar)</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Orden</label>
        <input type="number" name="orden" class="form-control" value="<?= $editBanner['orden']??0 ?>" min="0">
        <div style="margin-top:14px">
          <label style="display:flex;align-items:center;gap:10px">
            <label class="toggle"><input type="checkbox" name="activo" <?= ($editBanner['activo']??1)?'checked':'' ?>><span class="toggle-slider"></span></label>
            Activo / Visible
          </label>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:12px;margin-top:24px">
      <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> Guardar Banner</button>
      <a href="<?= BASE_URL ?>admin/banners.php" class="btn btn-outline">Cancelar</a>
    </div>
  </form>
  </div>
</div>

<?php else: ?>
<div class="adm-card">
  <div class="adm-card-header">
    <h2><i class="fas fa-images"></i> Banners del Sitio (<?= count($banners) ?>)</h2>
    <a href="?action=new" class="btn btn-gold"><i class="fas fa-plus"></i> Nuevo Banner</a>
  </div>
  <div style="overflow-x:auto">
  <table class="adm-table">
    <thead><tr><th style="width:120px">Vista Previa</th><th>Título</th><th>Posición</th><th>Orden</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php if(empty($banners)): ?>
    <tr><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8">No hay banners creados</td></tr>
    <?php else: foreach($banners as $b): ?>
    <tr>
      <td>
        <img src="<?= sanitize($b['imagen_url']) ?>" alt="" style="width:100px;height:56px;object-fit:cover;border-radius:6px;background:#f1f5f9">
      </td>
      <td>
        <strong><?= sanitize(truncateText($b['titulo']??'Sin título',35)) ?></strong>
        <?php if($b['subtitulo']): ?><br><span style="font-size:12px;color:#94a3b8"><?= sanitize(truncateText($b['subtitulo'],40)) ?></span><?php endif; ?>
        <?php if($b['enlace']): ?><br><span style="font-size:11px;color:#3498db"><i class="fas fa-link"></i> <?= sanitize($b['enlace']) ?></span><?php endif; ?>
      </td>
      <td><span class="badge badge-<?= $b['posicion']==='principal'?'info':($b['posicion']==='secundario'?'warning':'gray') ?>"><?= ucfirst($b['posicion']) ?></span></td>
      <td><?= $b['orden'] ?></td>
      <td>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$b['id']?>">
          <button type="submit" class="badge badge-<?= $b['activo']?'success':'gray' ?>" style="border:none;cursor:pointer"><?= $b['activo']?'Activo':'Inactivo' ?></button>
        </form>
      </td>
      <td>
        <div style="display:flex;gap:6px">
          <a href="?edit=<?=$b['id']?>" class="btn btn-sm btn-navy"><i class="fas fa-edit"></i></a>
          <form method="POST" onsubmit="return confirm('¿Eliminar este banner?')">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$b['id']?>">
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
