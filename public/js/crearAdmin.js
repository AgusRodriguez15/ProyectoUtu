// JS para crearAdmin.html
// Mueve la lógica inline a este archivo para separar responsabilidades

async function submitCrearAdmin(redirectToGestiones = false) {
  const msg = document.getElementById('msg');
  if (msg) msg.textContent = '';
  const formEl = document.getElementById('crearAdminForm');
  if (!formEl) return;
  const form = new FormData(formEl);
  try {
    // Intentar endpoint temporal primero (si existe)
    let endpoint = '/proyecto/apps/Controllers/crearAdminTemporal.php';
    let res = await fetch(endpoint, { method: 'POST', body: new URLSearchParams([...form]) });
    if (!res.ok) {
      endpoint = '/proyecto/apps/Controllers/usuarioController.php';
      res = await fetch(endpoint, { method: 'POST', body: new URLSearchParams([...form]) });
    }

    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch (err) { throw new Error('Respuesta inválida del servidor: ' + text); }

    if (data.success) {
      if (msg) { msg.style.color = 'green'; msg.textContent = data.message + (data.IdUsuario ? (' (IdUsuario: ' + data.IdUsuario + ')') : ''); }
      if (redirectToGestiones) {
        setTimeout(() => { window.location.href = '/proyecto/apps/Views/gestiones.html'; }, 800);
      }
    } else {
      if (msg) { msg.style.color = 'red'; msg.textContent = data.message || 'Error al crear administrador'; }
    }
  } catch (err) {
    if (msg) { msg.style.color = 'red'; msg.textContent = 'Error: ' + err.message; }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const btnCrear = document.getElementById('btnCrear');
  const btnCrearVerGestiones = document.getElementById('btnCrearVerGestiones');
  if (btnCrear) btnCrear.addEventListener('click', () => submitCrearAdmin(false));
  if (btnCrearVerGestiones) btnCrearVerGestiones.addEventListener('click', () => submitCrearAdmin(true));
});
