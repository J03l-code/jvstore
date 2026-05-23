<?php
/**
 * JVSTORE Admin - Categorías
 */
$pageTitle = 'Categorías';
require_once __DIR__ . '/includes/header.php';
$db = getDB();

// Auto-migración silenciosa de columnas de atributos
try {
  $q1 = $db->query("SHOW COLUMNS FROM categorias LIKE 'atributos'");
  if (!$q1->fetch()) {
      $db->exec("ALTER TABLE categorias ADD COLUMN atributos TEXT DEFAULT NULL");
  }
  $q2 = $db->query("SHOW COLUMNS FROM productos LIKE 'atributos'");
  if (!$q2->fetch()) {
      $db->exec("ALTER TABLE productos ADD COLUMN atributos TEXT DEFAULT NULL");
  }
  $q3 = $db->query("SHOW COLUMNS FROM servicios LIKE 'atributos'");
  if (!$q3->fetch()) {
      $db->exec("ALTER TABLE servicios ADD COLUMN atributos TEXT DEFAULT NULL");
  }
} catch (Exception $e) {
  error_log("Error auto-migración: " . $e->getMessage());
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $action = $_POST['action'] ?? '';

  if($action === 'save'){
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    if(!$nombre){ setFlash('danger','Nombre requerido'); redirect(BASE_URL.'admin/categorias.php'); }
    
    // Procesar filtros con nombre + icono (nuevo formato JSON de objetos)
    $filtroNombres = $_POST['filtro_nombre'] ?? [];
    $filtroIconos  = $_POST['filtro_icono']  ?? [];
    $atributosArr  = [];
    foreach ($filtroNombres as $i => $fn) {
        $fn = trim($fn);
        if ($fn !== '') {
            $atributosArr[] = [
                'nombre' => $fn,
                'icono'  => trim($filtroIconos[$i] ?? 'fas fa-filter'),
            ];
        }
    }
    $atributosJson = !empty($atributosArr) ? json_encode($atributosArr, JSON_UNESCAPED_UNICODE) : null;

    $data = [
      'nombre'      => $nombre,
      'slug'        => generateSlug($nombre),
      'descripcion' => trim($_POST['descripcion'] ?? ''),
      'icono'       => trim($_POST['icono'] ?? 'fas fa-box'),
      'color'       => trim($_POST['color'] ?? '#1B2A4A'),
      'tipo'        => $_POST['tipo'] ?? 'producto',
      'orden'       => (int)($_POST['orden'] ?? 0),
      'activo'      => isset($_POST['activo']) ? 1 : 0,
      'atributos'   => $atributosJson,
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
    // Desvincular productos y servicios antes de borrar
    $db->prepare("UPDATE productos SET categoria_id = NULL WHERE categoria_id = ?")->execute([$id]);
    $db->prepare("UPDATE servicios SET categoria_id = NULL WHERE categoria_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM categorias WHERE id=?")->execute([$id]);
    setFlash('success','Categoría eliminada. Los productos asociados fueron desvinculados.');
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
      <div class="form-group full" style="border-top:1px solid #f1f5f9;padding-top:15px;margin-top:10px">
        <label class="form-label" style="color:var(--navy);font-weight:700;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between">
          <span><i class="fas fa-sliders-h"></i> Filtros de Búsqueda de la Categoría</span>
          <button type="button" onclick="addFilterRow()" class="btn btn-sm btn-gold" style="font-size:12px">
            <i class="fas fa-plus"></i> Agregar Filtro
          </button>
        </label>
        <div id="filtros-container" style="display:flex;flex-direction:column;gap:10px">
          <?php
          $filtrosExistentes = [];
          if (!empty($editCat['atributos'])) {
              $decoded = json_decode($editCat['atributos'], true);
              if (is_array($decoded)) {
                  // Nuevo formato: array de objetos {nombre, icono}
                  if (isset($decoded[0]) && is_array($decoded[0])) {
                      $filtrosExistentes = $decoded;
                  } else {
                      // Formato viejo: array de strings → convertir al nuevo
                      foreach ($decoded as $s) {
                          $filtrosExistentes[] = ['nombre' => $s, 'icono' => 'fas fa-filter'];
                      }
                  }
              }
          }
          if (empty($filtrosExistentes)) {
              $filtrosExistentes = [['nombre' => '', 'icono' => 'fas fa-filter']];
          }
          foreach ($filtrosExistentes as $fi => $frow): ?>
          <div class="filtro-row" style="display:grid;grid-template-columns:1fr auto auto;gap:8px;align-items:center;background:#f8fafc;padding:10px 12px;border-radius:10px;border:1px solid #e2e8f0">
            <div style="display:flex;align-items:center;gap:10px">
              <i class="<?= sanitize($frow['icono']) ?> filtro-icon-preview" style="color:#1B2A4A;font-size:18px;width:24px;text-align:center"></i>
              <input type="text" name="filtro_nombre[]" class="form-control" value="<?= sanitize($frow['nombre']) ?>" placeholder="Nombre del filtro (ej: Talla, Color, Olor)" style="flex:1">
            </div>
            <input type="text" name="filtro_icono[]" class="form-control filtro-icono-input" value="<?= sanitize($frow['icono']) ?>" placeholder="fas fa-tshirt" style="width:160px;font-size:12px" oninput="updateIconPreview(this)">
            <button type="button" onclick="this.closest('.filtro-row').remove()" class="btn btn-sm btn-danger" title="Eliminar">
              <i class="fas fa-trash"></i>
            </button>
          </div>
          <?php endforeach; ?>
        </div>
        <small style="color:#64748b;font-size:12px;margin-top:8px;display:block">
          Cada filtro tiene un <strong>nombre</strong> (ej: Talla, Olor, Color) y un <strong>ícono FontAwesome</strong> (ej: fas fa-tshirt, fas fa-flask, fas fa-palette).<br>
          Al editar los productos de esta categoría, podrás asignar múltiples valores a cada filtro separados por coma (ej: L, XL, M).
        </small>
      </div>
    </div>
    <script>
    function addFilterRow() {
        const container = document.getElementById('filtros-container');
        const div = document.createElement('div');
        div.className = 'filtro-row';
        div.style.cssText = 'display:grid;grid-template-columns:1fr auto auto;gap:8px;align-items:center;background:#f8fafc;padding:10px 12px;border-radius:10px;border:1px solid #e2e8f0';
        div.innerHTML = `
          <div style="display:flex;align-items:center;gap:10px">
            <i class="fas fa-filter filtro-icon-preview" style="color:#1B2A4A;font-size:18px;width:24px;text-align:center"></i>
            <input type="text" name="filtro_nombre[]" class="form-control" value="" placeholder="Nombre del filtro (ej: Talla, Color, Olor)" style="flex:1">
          </div>
          <input type="text" name="filtro_icono[]" class="form-control filtro-icono-input" value="fas fa-filter" placeholder="fas fa-tshirt" style="width:160px;font-size:12px" oninput="updateIconPreview(this)">
          <button type="button" onclick="this.closest('.filtro-row').remove()" class="btn btn-sm btn-danger" title="Eliminar">
            <i class="fas fa-trash"></i>
          </button>
        `;
        container.appendChild(div);
    }
    function updateIconPreview(input) {
        const row = input.closest('.filtro-row');
        const preview = row.querySelector('.filtro-icon-preview');
        if (preview) {
            preview.className = input.value.trim() + ' filtro-icon-preview';
            preview.style.cssText = 'color:#1B2A4A;font-size:18px;width:24px;text-align:center';
        }
    }
    </script>
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
