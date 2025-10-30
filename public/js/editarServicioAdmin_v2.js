// editarServicioAdmin_v2.js
document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');
  const form = document.getElementById('formEditarAdminV2');
  const btnVolver = document.getElementById('btnVolver');
  const btnCancelar = document.getElementById('btnCancelar');

  if (btnVolver) btnVolver.addEventListener('click', () => { window.history.back(); });
  if (btnCancelar) btnCancelar.addEventListener('click', () => { window.history.back(); });

  if (!id) {
    alert('Falta id de servicio en la URL');
    return;
  }

  async function cargar() {
    try {
      const fd = new FormData(); fd.append('id', id);
      const res = await fetch('/proyecto/apps/Controllers/servicioController.php', { method: 'POST', body: fd });
      const text = await res.text(); const data = JSON.parse(text);
      document.getElementById('idServicio').value = data.id || id;
      document.getElementById('Nombre').value = data.nombre || '';
      document.getElementById('Descripcion').value = data.descripcion || '';
      if (data.precio !== null && typeof data.precio !== 'undefined') document.getElementById('Precio').value = data.precio;
      if (data.divisa) document.getElementById('Divisa').value = data.divisa;
      if (data.estado) document.getElementById('Estado').value = data.estado;
      if (Array.isArray(data.palabrasClave) && data.palabrasClave.length) document.getElementById('PalabrasClave').value = data.palabrasClave.join(',');
    } catch (e) {
      alert('Error cargando servicio: ' + e.message);
    }
  }

  form.addEventListener('submit', async (ev) => {
    ev.preventDefault();

    const r = await showConfirm({ title: 'Confirmar guardado', message: '¿Desea guardar los cambios en el servicio?', showMotivo: true });
    if (!r.confirmed) return;
    const motivo = (r.motivoValue || '').trim();

    const fd = new FormData();
    fd.append('editarAdmin','1');
    fd.append('idServicio', document.getElementById('idServicio').value);
    fd.append('Nombre', document.getElementById('Nombre').value);
    fd.append('Descripcion', document.getElementById('Descripcion').value);
    fd.append('Precio', document.getElementById('Precio').value || '0');
    fd.append('Divisa', document.getElementById('Divisa').value);
    fd.append('Estado', document.getElementById('Estado').value);
    fd.append('motivo', motivo);

    const pkInput = document.getElementById('PalabrasClave').value.trim();
    if (pkInput) {
      const arr = pkInput.split(',').map(s => s.trim()).filter(Boolean);
      fd.append('palabrasClave', JSON.stringify(arr));
    }

    // Adjuntar nuevas fotos (campo name 'nuevasFotos[]')
    const fileInput = document.getElementById('nuevasFotos');
    if (fileInput && fileInput.files && fileInput.files.length) {
      for (let i = 0; i < fileInput.files.length; i++) {
        fd.append('nuevasFotos[]', fileInput.files[i]);
      }
    }

    try {
      const res = await fetch('/proyecto/apps/Controllers/servicioController.php', { method: 'POST', body: fd });
      const json = await res.json();
      if (json.success) {
        alert(json.message || 'Guardado');
        window.location.href = '/proyecto/apps/Views/gestionarServicios.html';
      } else {
        alert(json.message || 'Error al guardar');
      }
    } catch (e) {
      alert('Error: ' + e.message);
    }
  });

  cargar();
});

// showConfirm modal helper (mismo patrón que en gestionarUsuarios.js)
function showConfirm({ title = 'Confirmar acción', message = '¿Estás seguro?', showPrimaryInput = false, primaryLabel = 'Valor', showMotivo = false } = {}) {
  return new Promise(resolve => {
    const modal = document.getElementById('confirmModal');
    const titleEl = document.getElementById('confirmTitle');
    const msgEl = document.getElementById('confirmMessage');
    const motivoInput = document.getElementById('confirmMotivo');
    const okBtn = document.getElementById('confirmOk');
    const cancelBtn = document.getElementById('confirmCancel');

    if (!modal) {
      if (showMotivo) {
        const mot = prompt(message + '\nMotivo (opcional):');
        if (mot === null) return resolve({ confirmed: false, primaryValue: null, motivoValue: null });
        return resolve({ confirmed: true, primaryValue: null, motivoValue: mot });
      }
      const ok = confirm(message);
      return resolve({ confirmed: ok, primaryValue: null, motivoValue: null });
    }

    titleEl.textContent = title;
    msgEl.textContent = message;
    motivoInput.value = '';

    function cleanup() { okBtn.removeEventListener('click', onOk); cancelBtn.removeEventListener('click', onCancel); modal.style.display = 'none'; }
    function onOk() { const m = motivoInput.value || null; cleanup(); resolve({ confirmed: true, primaryValue: null, motivoValue: m }); }
    function onCancel() { cleanup(); resolve({ confirmed: false, primaryValue: null, motivoValue: null }); }

    okBtn.addEventListener('click', onOk);
    cancelBtn.addEventListener('click', onCancel);
    modal.style.display = 'flex';
    motivoInput.focus();
  });
}
