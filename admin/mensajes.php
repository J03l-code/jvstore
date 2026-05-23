<?php
/**
 * JVSTORE - Admin: Mensajes
 */
$pageTitle = 'Mensajes';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'leer') {
        $id = (int)$_POST['id'];
        $db->prepare("UPDATE mensajes SET leido = 1 WHERE id = ?")->execute([$id]);
        setFlash('success', 'Mensaje marcado como leído');
        redirect(BASE_URL . 'admin/mensajes.php');
    }
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $db->prepare("DELETE FROM mensajes WHERE id = ?")->execute([$id]);
        setFlash('success', 'Mensaje eliminado');
        redirect(BASE_URL . 'admin/mensajes.php');
    }
}

$mensajes = $db->query("SELECT * FROM mensajes ORDER BY fecha DESC")->fetchAll();
?>

<div class="adm-card">
  <div class="adm-card-header">
    <h2><i class="fas fa-envelope"></i> Bandeja de Entrada</h2>
  </div>
  <div style="overflow-x:auto;">
    <table class="adm-table">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Nombre</th>
          <th>Email / Teléfono</th>
          <th>Mensaje</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($mensajes)): ?>
          <tr><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8">No hay mensajes</td></tr>
        <?php else: foreach($mensajes as $m): ?>
          <tr style="<?= !$m['leido'] ? 'background-color:#f8fafc; font-weight:600;' : '' ?>">
            <td style="white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($m['fecha'])) ?></td>
            <td><?= sanitize($m['nombre']) ?></td>
            <td>
                <?= sanitize($m['email']) ?>
                <?= $m['telefono'] ? '<br><small class="text-muted"><i class="fas fa-phone"></i> '.sanitize($m['telefono']).'</small>' : '' ?>
            </td>
            <td style="max-width: 300px;">
                <?php if ($m['asunto']): ?>
                    <strong><?= sanitize($m['asunto']) ?></strong><br>
                <?php endif; ?>
                <?= sanitize(truncateText($m['mensaje'], 80)) ?>
            </td>
            <td>
                <?php if ($m['leido']): ?>
                    <span class="badge badge-gray">Leído</span>
                <?php else: ?>
                    <span class="badge badge-info">Nuevo</span>
                <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:6px">
                <?php if (!$m['leido']): ?>
                <form method="POST" style="margin:0;">
                  <input type="hidden" name="action" value="leer">
                  <input type="hidden" name="id" value="<?= $m['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-navy" title="Marcar como leído"><i class="fas fa-check"></i></button>
                </form>
                <?php endif; ?>
                <button onclick="alert('Mensaje completo:\n\n' + <?= htmlspecialchars(json_encode($m['mensaje'])) ?>)" class="btn btn-sm btn-outline" title="Ver completo"><i class="fas fa-eye"></i></button>
                <form method="POST" onsubmit="return confirm('¿Eliminar mensaje?')" style="margin:0;">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $m['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
