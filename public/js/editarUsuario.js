// JS para editarUsuario.html
document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');
  const status = document.getElementById('status');
  const form = document.getElementById('editarForm');

  if (!id) {
    if (status) status.textContent = 'Id de usuario no provisto.';
    return;
  }

  async function fetchUser() {
    if (status) status.textContent = 'Cargando usuario...';
    try {
      const res = await fetch(`/proyecto/apps/Controllers/usuarioController.php?action=obtener&id=${encodeURIComponent(id)}`);
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      document.getElementById('IdUsuario').value = data.IdUsuario || id;
      document.getElementById('Nombre').value = data.Nombre || '';
      document.getElementById('Apellido').value = data.Apellido || '';
      document.getElementById('Email').value = data.Email || '';
      if (status) status.textContent = '';
    } catch (e) {
      if (status) status.textContent = 'Error cargando usuario: ' + e.message;
    }
  }

  form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const fd = new FormData();
    fd.append('action', 'editar');
    fd.append('id', document.getElementById('IdUsuario').value);
    fd.append('Nombre', document.getElementById('Nombre').value);
    fd.append('Apellido', document.getElementById('Apellido').value);
    fd.append('Email', document.getElementById('Email').value);
    if (status) status.textContent = 'Guardando...';
    try {
      const resp = await fetch('/proyecto/apps/Controllers/usuarioController.php', { method: 'POST', body: fd });
      const text = await resp.text();
      let json;
      try { json = JSON.parse(text); } catch (e) { throw new Error('Respuesta inválida: ' + text); }
      if (json && json.success) {
        alert(json.message || 'Usuario actualizado');
        // Redirección inteligente: volver al referrer si existe, sino al panel de gestión, sino al verPerfil
        try {
          const ref = document.referrer || '';
          if (ref.includes('gestionarUsuarios')) {
            window.location.href = '/proyecto/apps/Views/gestionarUsuarios.html';
          } else if (ref) {
            // si venía desde una vista de perfil u otra página, volver a ella
            window.location.href = ref;
          } else {
            // fallback: ir al verPerfil del usuario editado
            const editedId = encodeURIComponent(document.getElementById('IdUsuario').value);
            window.location.href = '/proyecto/apps/Views/verPerfil.html?id=' + editedId;
          }
        } catch (e) {
          const editedId = encodeURIComponent(document.getElementById('IdUsuario').value);
          window.location.href = '/proyecto/apps/Views/verPerfil.html?id=' + editedId;
        }
      } else {
        alert(json.message || 'Error actualizando usuario');
        if (status) status.textContent = '';
      }
    } catch (e) {
      if (status) status.textContent = 'Error al guardar: ' + e.message;
    }
  });

  fetchUser();
});
