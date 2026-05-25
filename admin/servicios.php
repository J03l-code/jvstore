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

  function uploadServiceGalleryItems($files){
    $items = [];
    if(!isset($files['name']) || !is_array($files['name'])) return $items;
    $dir = __DIR__ . '/../uploads/servicios/';
    if(!is_dir($dir)) mkdir($dir, 0755, true);
    foreach($files['name'] as $i => $name){
      if($files['error'][$i] !== UPLOAD_ERR_OK) continue;
      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      $isImg = in_array($ext,['jpg','jpeg','png','gif','webp']);
      $isVid = in_array($ext,['mp4','webm','ogg','mov','avi']);
      if(!$isImg && !$isVid) continue;
      $newName = uniqid('gal_').'.'.$ext;
      if(move_uploaded_file($files['tmp_name'][$i], $dir.$newName)){
        $items[] = [
          'tipo' => $isVid ? 'video' : 'imagen',
          'url' => 'uploads/servicios/'.$newName
        ];
      }
    }
    return $items;
  }

  if($action === 'save'){
    $id = (int)($_POST['id'] ?? 0);
    
    // Procesar galería de fotos y videos
    $galeriaItems = [];
    $existentesUrls = $_POST['galeria_existente_url'] ?? [];
    $existentesTipos = $_POST['galeria_existente_tipo'] ?? [];
    foreach ($existentesUrls as $idx => $url) {
        $url = trim($url);
        if ($url !== '') {
            $galeriaItems[] = [
                'tipo' => $existentesTipos[$idx] ?? 'imagen',
                'url'  => $url
            ];
        }
    }
    if (!empty($_FILES['galeria_archivos'])) {
        $nuevosSubidos = uploadServiceGalleryItems($_FILES['galeria_archivos']);
        $galeriaItems = array_merge($galeriaItems, $nuevosSubidos);
    }
    $videosExternos = $_POST['galeria_videos_externos'] ?? [];
    foreach ($videosExternos as $vidUrl) {
        $vidUrl = trim($vidUrl);
        if ($vidUrl !== '') {
            $galeriaItems[] = [
                'tipo' => 'video',
                'url'  => $vidUrl
            ];
        }
    }
    $galeriaJson = !empty($galeriaItems) ? json_encode($galeriaItems, JSON_UNESCAPED_UNICODE) : null;

    $data = [
      'categoria_id'      => ($_POST['categoria_id'] ?: null),
      'titulo'            => trim($_POST['titulo'] ?? ''),
      'slug'              => generateSlug($_POST['titulo'] ?? ''),
      'descripcion'       => trim($_POST['descripcion'] ?? ''),
      'descripcion_corta' => trim($_POST['descripcion_corta'] ?? ''),
      'precio_desde'      => ($_POST['precio_desde'] !== '' ? (float)$_POST['precio_desde'] : null),
      'icono'             => trim($_POST['icono'] ?? 'fas fa-cog'),
      'caracteristicas'   => trim($_POST['caracteristicas'] ?? '[]'),
      'parent_id'         => ($_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null),
      'destacado'         => isset($_POST['destacado']) ? 1 : 0,
      'orden'             => (int)($_POST['orden'] ?? 0),
      'activo'            => isset($_POST['activo']) ? 1 : 0,
      'galeria'           => $galeriaJson,
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

$servicios  = $db->query("SELECT s.*, c.nombre AS cat_nombre, p.titulo AS padre_titulo FROM servicios s LEFT JOIN categorias c ON s.categoria_id=c.id LEFT JOIN servicios p ON s.parent_id=p.id ORDER BY s.orden,s.id")->fetchAll();
$categorias = $db->query("SELECT * FROM categorias WHERE activo=1 AND tipo IN ('servicio','ambos') ORDER BY nombre")->fetchAll();
$allCats    = $db->query("SELECT * FROM categorias WHERE activo=1 ORDER BY nombre")->fetchAll();

$parentQuery = "SELECT * FROM servicios WHERE parent_id IS NULL";
$parentParams = [];
if($editServ){
  $parentQuery .= " AND id != ?"; $parentParams[]=$editServ['id'];
}
$parentQuery .= " ORDER BY titulo";
$parentStmt = $db->prepare($parentQuery); $parentStmt->execute($parentParams);
$parentServices = $parentStmt->fetchAll();
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
      <div class="form-group">
        <label class="form-label">Servicio Padre (Para sub-servicios)</label>
        <select name="parent_id" class="form-control">
          <option value="">Ninguno (Es un servicio principal)</option>
          <?php foreach($parentServices as $p): ?>
          <option value="<?=$p['id']?>" <?= ($editServ['parent_id']??'')==$p['id']?'selected':'' ?>><?= sanitize($p['titulo']) ?></option>
          <?php endforeach; ?>
        </select>
        <small style="color:#94a3b8;font-size:11px">Asócialo a otro servicio si es un sub-servicio independiente.</small>
      </div>
      <div class="form-group full">
        <label class="form-label">Descripción Corta</label>
        <input type="text" name="descripcion_corta" class="form-control" value="<?= sanitize($editServ['descripcion_corta']??'') ?>" maxlength="300">
      </div>
      <div class="form-group full">
        <label class="form-label">Descripción Completa</label>
        <textarea name="descripcion" class="form-control" rows="5"><?= sanitize($editServ['descripcion']??'') ?></textarea>
      </div>
      <div class="form-group full" style="border: 1px solid #e2e8f0; padding: 18px; border-radius: 12px; background: #f8fafc; margin-top: 10px;">
        <label class="form-label" style="font-weight: 700; color: var(--navy-dark); font-size: 14px;">Sub-servicios / Qué incluye (Ej: Poda, Riego, Abono)</label>
        <div style="display: flex; gap: 8px; margin-bottom: 12px; margin-top: 8px;">
          <input type="text" id="subservicioInput" class="form-control" style="flex:1" placeholder="Ej: Poda estética de arbustos">
          <button type="button" class="btn btn-navy" onclick="addSubservicio()" style="white-space: nowrap; height: 42px;"><i class="fas fa-plus"></i> Agregar</button>
        </div>
        <div id="subserviciosContainer" style="display: flex; flex-direction: column; gap: 8px;">
          <!-- Items representados por JS -->
        </div>
        <input type="hidden" name="caracteristicas" id="caracteristicasInput" value="<?= sanitize($editServ['caracteristicas'] ?? '[]') ?>">
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
      <div class="form-group full" style="border-top:1px solid #f1f5f9;padding-top:15px;margin-top:15px">
        <label class="form-label" style="color:var(--navy);font-weight:700;margin-bottom:12px"><i class="fas fa-images"></i> Galería de Imágenes y Videos Adicionales</label>
        
        <!-- Grid de Items Existentes -->
        <div id="galeria-preview-grid" style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:15px">
          <?php
          $galItems = [];
          if ($editServ && !empty($editServ['galeria'])) {
              $galItems = json_decode($editServ['galeria'], true) ?: [];
          }
          foreach ($galItems as $gi => $item):
              $isVid = ($item['tipo'] ?? 'imagen') === 'video';
              $thumbUrl = $item['url'];
              $displayUrl = (strpos($thumbUrl, 'http') === 0) ? $thumbUrl : BASE_URL . $thumbUrl;
          ?>
          <div class="galeria-item-card" style="position:relative;width:100px;height:100px;border-radius:8px;border:1px solid #cbd5e1;overflow:hidden;background:#f1f5f9">
            <input type="hidden" name="galeria_existente_url[]" value="<?= sanitize($item['url']) ?>">
            <input type="hidden" name="galeria_existente_tipo[]" value="<?= sanitize($item['tipo'] ?? 'imagen') ?>">
            
            <?php if ($isVid): ?>
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#0f172a;color:#fff">
                <i class="fas fa-video" style="font-size:24px;color:#0ea5e9"></i>
              </div>
            <?php else: ?>
              <img src="<?= sanitize($displayUrl) ?>" style="width:100%;height:100%;object-fit:cover">
            <?php endif; ?>
            
            <button type="button" onclick="this.closest('.galeria-item-card').remove()" style="position:absolute;top:4px;right:4px;width:24px;height:24px;border-radius:50%;background:rgba(239,68,68,0.9);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px" title="Eliminar de la galería">
              <i class="fas fa-trash"></i>
            </button>
          </div>
          <?php endforeach; ?>
        </div>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
          <div>
            <label class="form-label" style="font-size:12px;color:#64748b">Subir fotos o videos locales (Múltiples)</label>
            <input type="file" name="galeria_archivos[]" class="form-control" accept="image/*,video/*" multiple>
            <small style="color:#94a3b8;font-size:11px">Formatos: JPG, PNG, WEBP, MP4, WEBM</small>
          </div>
          <div>
            <label class="form-label" style="font-size:12px;color:#64748b;display:flex;justify-content:space-between;align-items:center">
              <span>Enlaces de Video Externos (ej: YouTube)</span>
              <button type="button" onclick="addExternalVideoRow()" class="btn btn-sm btn-gold" style="padding:2px 8px;font-size:10px"><i class="fas fa-plus"></i></button>
            </label>
            <div id="videos-externos-container" style="display:flex;flex-direction:column;gap:6px">
              <input type="text" name="galeria_videos_externos[]" class="form-control" placeholder="https://www.youtube.com/watch?v=..." style="font-size:12px">
            </div>
          </div>
        </div>
      </div>
      <script>
      function addExternalVideoRow() {
          const container = document.getElementById('videos-externos-container');
          const input = document.createElement('input');
          input.type = 'text';
          input.name = 'galeria_videos_externos[]';
          input.className = 'form-control';
          input.placeholder = 'https://www.youtube.com/watch?v=...';
          input.style.cssText = 'font-size:12px;margin-top:4px';
          container.appendChild(input);
      }
      </script>
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
        <?php if($s['padre_titulo']): ?>
          <div style="font-size:11px;color:#64748b;margin-top:2px;">
            <i class="fas fa-level-up-alt fa-rotate-90" style="margin-right:4px"></i> Sub-servicio de: <strong><?= sanitize($s['padre_titulo']) ?></strong>
          </div>
        <?php endif; ?>
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

<script>
let subservicios = <?= $editServ ? ($editServ['caracteristicas'] ?: '[]') : '[]' ?>;

function renderSubservicios() {
    const container = document.getElementById('subserviciosContainer');
    const hiddenInput = document.getElementById('caracteristicasInput');
    if (!container || !hiddenInput) return;
    
    container.innerHTML = '';
    
    // Validar formato array
    if (!Array.isArray(subservicios)) {
        subservicios = [];
    }
    
    if (subservicios.length === 0) {
        container.innerHTML = '<div style="color: #94a3b8; font-size: 13px; font-style: italic;">No se han agregado sub-servicios o características aún.</div>';
    } else {
        subservicios.forEach((item, index) => {
            const div = document.createElement('div');
            div.style = 'display: flex; align-items: center; justify-content: space-between; background: #fff; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02);';
            div.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px; color: var(--navy-dark); font-weight: 600; font-size: 13px;">
                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                    <span>${escapeHtml(item)}</span>
                </div>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeSubservicio(${index})" style="padding: 4px 8px; font-size: 11px; margin-left: auto; height: 28px; width: 28px; display: flex; align-items: center; justify-content: center; border-radius: 6px;">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(div);
        });
    }
    
    hiddenInput.value = JSON.stringify(subservicios);
}

function addSubservicio() {
    const input = document.getElementById('subservicioInput');
    if (!input) return;
    const val = input.value.trim();
    if (val) {
        subservicios.push(val);
        input.value = '';
        renderSubservicios();
    }
}

function removeSubservicio(index) {
    subservicios.splice(index, 1);
    renderSubservicios();
}

function escapeHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('subservicioInput');
    if (input) {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addSubservicio();
            }
        });
        renderSubservicios();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
