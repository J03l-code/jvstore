  </div><!-- /adm-content -->
</div><!-- /adm-main -->
</div><!-- /adm-wrap -->

<script>
// Mobile sidebar toggle
const menuToggle = document.getElementById('menuToggle');
if(menuToggle) menuToggle.style.display = window.innerWidth < 768 ? 'block' : 'none';
window.addEventListener('resize', () => {
  if(menuToggle) menuToggle.style.display = window.innerWidth < 768 ? 'block' : 'none';
});

// Image preview helper
function previewImage(input, previewId) {
  const preview = document.getElementById(previewId);
  if(input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { preview.src = e.target.result; preview.classList.add('show'); };
    reader.readAsDataURL(input.files[0]);
  }
}

// Confirm delete
function confirmDelete(form) {
  if(confirm('¿Estás seguro de que deseas eliminar este elemento? Esta acción no se puede deshacer.')) {
    form.submit();
  }
}

// Toggle active state via AJAX
function toggleActive(id, table) {
  fetch('<?= BASE_URL ?>api/admin_toggle.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({id, table})
  }).then(r => r.json()).then(d => {
    if(!d.success) alert('Error al actualizar');
  });
}

// Modal helpers
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
document.querySelectorAll('.adm-modal-bg').forEach(m => {
  m.addEventListener('click', e => { if(e.target === m) m.classList.remove('open'); });
});
</script>
</body>
</html>
