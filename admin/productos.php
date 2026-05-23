<?php
/**
 * JVSTORE Admin - Gestión de Productos
 */
$pageTitle = 'Productos';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Auto-migración silenciosa de la columna galeria
try {
  $q_gal = $db->query("SHOW COLUMNS FROM productos LIKE 'galeria'");
  if (!$q_gal->fetch()) {
      $db->exec("ALTER TABLE productos ADD COLUMN galeria TEXT DEFAULT NULL");
  }
} catch (Exception $e) {
  error_log("Error auto-migración galeria: " . $e->getMessage());
}

// ── ACCIONES POST ──────────────────────────────────────────
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $action = $_POST['action'] ?? '';

  // Subir imagen
  function uploadProductImage($file){
    if(!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if(!in_array($ext,['jpg','jpeg','png','gif','webp'])) return null;
    $dir = __DIR__ . '/../uploads/productos/';
    if(!is_dir($dir)) mkdir($dir, 0755, true);
    $name = uniqid('prod_').'.'.$ext;
    if(move_uploaded_file($file['tmp_name'], $dir.$name)) return $name;
    return null;
  }

  // Subir archivos de galería (imágenes y videos)
  function uploadGalleryItems($files){
    $items = [];
    if(!isset($files['name']) || !is_array($files['name'])) return $items;
    $dir = __DIR__ . '/../uploads/productos/';
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
          'url' => 'uploads/productos/'.$newName
        ];
      }
    }
    return $items;
  }

  if($action === 'save'){
    $id      = (int)($_POST['id'] ?? 0);
    
    // Procesar atributos dinámicos específicos de la categoría
    $atributosDinamicos = $_POST['atributos_dinamicos'] ?? [];
    $atributosJson = null;
    if (is_array($atributosDinamicos) && !empty($atributosDinamicos)) {
        $processed = [];
        foreach ($atributosDinamicos as $key => $val) {
            if (is_array($val)) {
                $filteredVal = array_filter(array_map('trim', $val), fn($v) => $v !== '');
                if (!empty($filteredVal)) {
                    $processed[$key] = implode(', ', $filteredVal);
                }
            } else {
                $t = trim($val);
                if ($t !== '') {
                    $processed[$key] = $t;
                }
            }
        }
        if (!empty($processed)) {
            $atributosJson = json_encode($processed, JSON_UNESCAPED_UNICODE);
        }
    }

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
        $nuevosSubidos = uploadGalleryItems($_FILES['galeria_archivos']);
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
      'nombre'            => trim($_POST['nombre'] ?? ''),
      'slug'              => generateSlug($_POST['nombre'] ?? ''),
      'descripcion'       => trim($_POST['descripcion'] ?? ''),
      'descripcion_corta' => trim($_POST['descripcion_corta'] ?? ''),
      'precio'            => (float)($_POST['precio'] ?? 0),
      'precio_oferta'     => ($_POST['precio_oferta'] !== '' ? (float)$_POST['precio_oferta'] : null),
      'stock'             => (int)($_POST['stock'] ?? 0),
      'sku'               => trim($_POST['sku'] ?? ''),
      'marca'             => trim($_POST['marca'] ?? ''),
      'modelo'            => trim($_POST['modelo'] ?? ''),
      'destacado'         => isset($_POST['destacado']) ? 1 : 0,
      'nuevo'             => isset($_POST['nuevo']) ? 1 : 0,
      'activo'            => isset($_POST['activo']) ? 1 : 0,
      'atributos'         => $atributosJson,
      'galeria'           => $galeriaJson,
    ];
    if(!$data['nombre']){ setFlash('danger','El nombre es obligatorio'); redirect(BASE_URL.'admin/productos.php'); }

    // Imagen
    $imagen = uploadProductImage($_FILES['imagen'] ?? []);
    if($imagen) $data['imagen_url'] = $imagen;

    if($id){
      $sets = implode(',', array_map(fn($k)=>"$k=:$k", array_keys($data)));
      $stmt = $db->prepare("UPDATE productos SET $sets WHERE id=:id");
      $data['id'] = $id;
      $stmt->execute($data);
      setFlash('success','Producto actualizado correctamente');
    } else {
      $cols = implode(',', array_keys($data));
      $vals = ':'.implode(',:', array_keys($data));
      $stmt = $db->prepare("INSERT INTO productos ($cols) VALUES ($vals)");
      $stmt->execute($data);
      setFlash('success','Producto creado correctamente');
    }
    redirect(BASE_URL.'admin/productos.php');
  }

  if($action === 'delete'){
    $id = (int)($_POST['id'] ?? 0);
    try {
      // Eliminar de detalle_pedidos primero para evitar error de llave foránea
      $db->prepare("DELETE FROM detalle_pedidos WHERE producto_id = ?")->execute([$id]);
      $db->prepare("DELETE FROM productos WHERE id=?")->execute([$id]);
      setFlash('success','Producto eliminado permanentemente');
    } catch (Exception $e) {
      // Si falla el hard-delete, hacer soft-delete
      $db->prepare("UPDATE productos SET activo=0 WHERE id=?")->execute([$id]);
      setFlash('warning','Producto desactivado (no se pudo eliminar por estar en pedidos activos)');
    }
    redirect(BASE_URL.'admin/productos.php');
  }

  if($action === 'toggle'){
    $id = (int)($_POST['id'] ?? 0);
    $db->prepare("UPDATE productos SET activo = NOT activo WHERE id=?")->execute([$id]);
    redirect(BASE_URL.'admin/productos.php');
  }
}

// ── GET: Editar ─────────────────────────────────────────────
$editProd = null;
if(isset($_GET['edit'])){
  $editProd = $db->prepare("SELECT * FROM productos WHERE id=?");
  $editProd->execute([(int)$_GET['edit']]);
  $editProd = $editProd->fetch();
}
$showForm = isset($_GET['action']) && $_GET['action']==='new' || $editProd;

// ── Filtros & listado ───────────────────────────────────────
$buscar = trim($_GET['buscar'] ?? '');
$filtCat = $_GET['cat'] ?? '';
$page = max(1,(int)($_GET['page'] ?? 1));
$perPage = 15; $offset = ($page-1)*$perPage;

$where = "WHERE 1=1";
$params = [];
if($buscar){ $where .= " AND (p.nombre LIKE ? OR p.sku LIKE ? OR p.marca LIKE ?)"; $params = array_merge($params,["%$buscar%","%$buscar%","%$buscar%"]); }
if($filtCat){ $where .= " AND c.slug=?"; $params[] = $filtCat; }

$total = $db->prepare("SELECT COUNT(*) FROM productos p LEFT JOIN categorias c ON p.categoria_id=c.id $where");
$total->execute($params); $total = $total->fetchColumn();
$totalPages = ceil($total/$perPage);

$stmt = $db->prepare("SELECT p.*, c.nombre AS cat_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id=c.id $where ORDER BY p.id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$productos = $stmt->fetchAll();

$categorias = $db->query("SELECT * FROM categorias WHERE activo=1 ORDER BY nombre")->fetchAll();
?>

<?php if($showForm): ?>
<!-- FORMULARIO CREAR/EDITAR -->
<div class="adm-card" style="max-width:900px">
  <div class="adm-card-header">
    <h2><i class="fas fa-<?= $editProd?'edit':'plus' ?>"></i> <?= $editProd?'Editar':'Nuevo' ?> Producto</h2>
    <a href="<?= BASE_URL ?>admin/productos.php" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
  </div>
  <div class="adm-card-body">
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save">
    <?php if($editProd): ?><input type="hidden" name="id" value="<?=$editProd['id']?>"> <?php endif; ?>
    <div class="adm-form-grid">
      <div class="form-group full">
        <label class="form-label">Nombre del Producto *</label>
        <input type="text" name="nombre" class="form-control" value="<?= sanitize($editProd['nombre']??'') ?>" required placeholder="Ej: Audífonos Bluetooth Premium">
      </div>
      <div class="form-group">
        <label class="form-label">Categoría</label>
        <select name="categoria_id" class="form-control">
          <option value="">Sin categoría</option>
          <?php foreach($categorias as $c): ?>
          <option value="<?=$c['id']?>" <?= ($editProd['categoria_id']??'')==$c['id']?'selected':'' ?>><?= sanitize($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">SKU / Código</label>
        <input type="text" name="sku" class="form-control" value="<?= sanitize($editProd['sku']??'') ?>" placeholder="PROD-001">
      </div>
      <div class="form-group">
        <label class="form-label">Precio Regular *</label>
        <input type="number" name="precio" step="0.01" min="0" class="form-control" value="<?= $editProd['precio']??'' ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Precio de Oferta (opcional)</label>
        <input type="number" name="precio_oferta" step="0.01" min="0" class="form-control" value="<?= $editProd['precio_oferta']??'' ?>" placeholder="Dejar vacío si no tiene oferta">
      </div>
      <div class="form-group">
        <label class="form-label">Stock</label>
        <input type="number" name="stock" min="0" class="form-control" value="<?= $editProd['stock']??0 ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Marca</label>
        <input type="text" name="marca" class="form-control" value="<?= sanitize($editProd['marca']??'') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Imagen Principal del Producto</label>
        <input type="file" name="imagen" class="form-control" accept="image/*" onchange="previewImage(this,'prevImg')">
        <?php if($editProd && $editProd['imagen_url']): ?>
        <img src="<?= getProductImage($editProd['imagen_url']) ?>" class="img-preview show" id="prevImg" style="max-height:120px">
        <?php else: ?>
        <img id="prevImg" class="img-preview">
        <?php endif; ?>
      </div>
      <div class="form-group full" style="border-top:1px solid #f1f5f9;padding-top:15px;margin-top:15px">
        <label class="form-label" style="color:var(--navy);font-weight:700;margin-bottom:12px"><i class="fas fa-images"></i> Galería de Imágenes y Videos Adicionales</label>
        
        <!-- Grid de Items Existentes -->
        <div id="galeria-preview-grid" style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:15px">
          <?php
          $galItems = [];
          if ($editProd && !empty($editProd['galeria'])) {
              $galItems = json_decode($editProd['galeria'], true) ?: [];
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
        <label class="form-label">Opciones</label>
        <div style="display:flex;flex-direction:column;gap:12px;margin-top:4px">
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
            <label class="toggle"><input type="checkbox" name="activo" <?= ($editProd['activo']??1)?'checked':'' ?>><span class="toggle-slider"></span></label>
            <span>Activo / Visible</span>
          </label>
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
            <label class="toggle"><input type="checkbox" name="destacado" <?= ($editProd['destacado']??0)?'checked':'' ?>><span class="toggle-slider"></span></label>
            <span>Producto Destacado</span>
          </label>
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
            <label class="toggle"><input type="checkbox" name="nuevo" <?= ($editProd['nuevo']??0)?'checked':'' ?>><span class="toggle-slider"></span></label>
            <span>Marcar como Nuevo</span>
          </label>
        </div>
      </div>
      
      <!-- Atributos Dinámicos según la Categoría Seleccionada -->
      <div class="form-group full" id="dynamic-attributes-container" style="display:none;background:#f8fafc;padding:18px;border-radius:12px;border:1.5px dashed #cbd5e1;margin-top:15px">
        <label class="form-label" style="color:var(--navy);font-weight:700;margin-bottom:12px"><i class="fas fa-filter"></i> Especificaciones y Filtros Dinámicos</label>
        <div id="dynamic-attributes-fields" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(250px, 1fr));gap:16px">
          <!-- Campos dinámicos inyectados por Javascript -->
        </div>
      </div>

    </div>

    <?php
    // Generar mapa de atributos por categoria (nuevo formato: [{nombre, icono}])
    $catAttrs = [];
    $allCategories = $db->query("SELECT id, atributos FROM categorias")->fetchAll();
    foreach ($allCategories as $catItem) {
        $decoded = json_decode($catItem['atributos'] ?? '[]', true) ?: [];
        // Normalizar: si son strings (formato viejo) convertirlos
        $normalized = [];
        foreach ($decoded as $item) {
            if (is_string($item)) {
                $normalized[] = ['nombre' => $item, 'icono' => 'fas fa-filter'];
            } else {
                $normalized[] = $item;
            }
        }
        $catAttrs[$catItem['id']] = $normalized;
    }
    ?>
    <script>
    const categoryAttributes = <?= json_encode($catAttrs, JSON_UNESCAPED_UNICODE) ?>;
    const activeAttributes = <?= json_encode(json_decode($editProd['atributos'] ?? '{}', true) ?: (object)[], JSON_UNESCAPED_UNICODE) ?>;

    function renderDynamicAttributes() {
        const select = document.querySelector('[name=categoria_id]');
        const container = document.getElementById('dynamic-attributes-container');
        const fieldsDiv = document.getElementById('dynamic-attributes-fields');
        
        if (!select || !container || !fieldsDiv) return;
        
        const catId = select.value;
        const attrs = categoryAttributes[catId] || [];
        
        if (attrs.length === 0) {
            container.style.display = 'none';
            fieldsDiv.innerHTML = '';
            return;
        }
        
        container.style.display = 'block';
        let html = '';
        attrs.forEach(attr => {
            const attrName = typeof attr === 'object' ? attr.nombre : attr;
            const attrIcon = typeof attr === 'object' ? (attr.icono || 'fas fa-filter') : 'fas fa-filter';
            const attrOpciones = typeof attr === 'object' ? (attr.opciones || '') : '';
            
            const val = activeAttributes[attrName] || '';
            const activeVals = val.split(',').map(s => s.trim()).filter(s => s !== '');

            let htmlField = '';
            if (attrOpciones.trim() !== '') {
                const opcionesArr = attrOpciones.split(',').map(s => s.trim()).filter(s => s !== '');
                htmlField += `<div style="display:flex;flex-wrap:wrap;gap:12px;background:#fff;padding:8px 12px;border-radius:8px;border:1px solid #cbd5e1;margin-top:6px;width:100%">`;
                opcionesArr.forEach(opt => {
                    const isChecked = activeVals.includes(opt) ? 'checked' : '';
                    htmlField += `
                      <label style="display:inline-flex;align-items:center;gap:6px;font-size:12.5px;color:#334155;cursor:pointer;margin:4px 0">
                        <input type="checkbox" name="atributos_dinamicos[${attrName}][]" value="${opt}" ${isChecked} style="width:16px;height:16px;accent-color:#1B2A4A">
                        <span>${opt}</span>
                      </label>
                    `;
                });
                htmlField += `</div>`;
            } else {
                htmlField += `<input type="text" name="atributos_dinamicos[${attrName}]" class="form-control" value="${val}" placeholder="Ej: L, XL, M (separa con comas)">`;
            }

            html += `
              <div class="form-group full">
                <label class="form-label" style="font-size:12px;color:#475569;display:flex;align-items:center;gap:6px;font-weight:600;margin-bottom:2px">
                  <i class="${attrIcon}" style="color:#1B2A4A;width:16px;text-align:center"></i> ${attrName}
                </label>
                ${htmlField}
              </div>
            `;
        });
        fieldsDiv.innerHTML = html;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const select = document.querySelector('[name=categoria_id]');
        if (select) {
            select.addEventListener('change', renderDynamicAttributes);
            renderDynamicAttributes(); // Render first time
        }
    });
    </script>

    <div style="display:flex;gap:12px;margin-top:24px">
      <button type="submit" class="btn btn-gold"><i class="fas fa-save"></i> <?= $editProd?'Actualizar':'Crear' ?> Producto</button>
      <a href="<?= BASE_URL ?>admin/productos.php" class="btn btn-outline">Cancelar</a>
    </div>
  </form>
  </div>
</div>

<?php else: ?>
<!-- LISTADO -->
<div class="adm-card">
  <div class="adm-card-header">
    <h2><i class="fas fa-box"></i> Todos los Productos (<?= $total ?>)</h2>
    <a href="<?= BASE_URL ?>admin/productos.php?action=new" class="btn btn-gold"><i class="fas fa-plus"></i> Nuevo Producto</a>
  </div>
  <div class="adm-card-body" style="padding-bottom:0">
    <div class="adm-search-bar">
      <form method="GET" style="display:contents">
        <input type="text" name="buscar" value="<?= sanitize($buscar) ?>" placeholder="Buscar por nombre, SKU o marca...">
        <select name="cat">
          <option value="">Todas las categorías</option>
          <?php foreach($categorias as $c): ?>
          <option value="<?=$c['slug']?>" <?= $filtCat===$c['slug']?'selected':'' ?>><?= sanitize($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-navy"><i class="fas fa-search"></i> Filtrar</button>
        <?php if($buscar||$filtCat): ?><a href="<?= BASE_URL ?>admin/productos.php" class="btn btn-outline">Limpiar</a><?php endif; ?>
      </form>
    </div>
  </div>
  <div style="overflow-x:auto">
  <table class="adm-table">
    <thead>
      <tr>
        <th>Img</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php if(empty($productos)): ?>
    <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8">No se encontraron productos</td></tr>
    <?php else: foreach($productos as $p): ?>
    <tr>
      <td><img src="<?= getProductImage($p['imagen_url']) ?>" alt="<?= sanitize($p['nombre']) ?>"></td>
      <td>
        <strong style="display:block"><?= sanitize(truncateText($p['nombre'],35)) ?></strong>
        <?php if($p['sku']): ?><span style="font-size:11px;color:#94a3b8">SKU: <?= sanitize($p['sku']) ?></span><?php endif; ?>
        <?php if($p['destacado']): ?><span class="badge badge-info" style="margin-left:4px">Dest.</span><?php endif; ?>
        <?php if($p['nuevo']): ?><span class="badge badge-success" style="margin-left:4px">Nuevo</span><?php endif; ?>
      </td>
      <td><?= sanitize($p['cat_nombre']??'—') ?></td>
      <td>
        <strong><?= formatPrice($p['precio']) ?></strong>
        <?php if($p['precio_oferta']): ?><br><span class="badge badge-danger"><?= formatPrice($p['precio_oferta']) ?></span><?php endif; ?>
      </td>
      <td><span class="badge badge-<?= $p['stock']<=0?'danger':($p['stock']<=5?'warning':'success') ?>"><?= $p['stock'] ?></span></td>
      <td>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="id" value="<?= $p['id'] ?>">
          <button type="submit" class="badge badge-<?= $p['activo']?'success':'gray' ?>" style="border:none;cursor:pointer">
            <?= $p['activo']?'Activo':'Inactivo' ?>
          </button>
        </form>
      </td>
      <td>
        <div style="display:flex;gap:6px">
          <a href="<?= BASE_URL ?>admin/productos.php?edit=<?= $p['id'] ?>" class="btn btn-sm btn-navy" title="Editar"><i class="fas fa-edit"></i></a>
          <a href="<?= BASE_URL ?>producto.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-outline" title="Ver"><i class="fas fa-eye"></i></a>
          <form method="POST" onsubmit="return confirm('¿Eliminar este producto?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
  <!-- Paginación -->
  <?php if($totalPages > 1): ?>
  <div class="adm-pagination">
    <?php for($i=1;$i<=$totalPages;$i++): ?>
    <?php if($i===$page): ?>
    <span class="active"><?=$i?></span>
    <?php else: ?>
    <a href="?page=<?=$i?>&buscar=<?= urlencode($buscar) ?>&cat=<?= urlencode($filtCat) ?>"><?=$i?></a>
    <?php endif; ?>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>