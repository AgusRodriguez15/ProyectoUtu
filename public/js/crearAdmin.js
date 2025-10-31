// JS para crearAdmin.html
// Mueve la lógica inline a este archivo para separar responsabilidades

async function submitCrearAdmin(redirectToGestiones = false) {
  const msg = document.getElementById('msg');
  if (msg) msg.textContent = '';
  const formEl = document.getElementById('crearAdminForm');
  if (!formEl) return;
  const fd = new FormData(formEl);

  // Validación simple de confirmación de contraseña
  const contrasena = fd.get('contrasena') || '';
  const confirmar = fd.get('confirmarContrasena') || '';
  console.log('Valor contrasena:', contrasena);
  console.log('Valor confirmarContrasena:', confirmar);
  if (contrasena !== confirmar) {
    if (msg) { msg.style.color = 'red'; msg.textContent = 'Las contraseñas no coinciden.'; }
    console.log('Las contraseñas no coinciden');
    return;
  }

  const payload = new URLSearchParams();
  payload.append('nombre', fd.get('nombre') || '');
  payload.append('apellido', fd.get('apellido') || '');
  payload.append('email', fd.get('email') || '');
  payload.append('contrasena', contrasena);
  payload.append('action', 'crearAdmin');
  console.log('Payload enviado:', payload.toString());

  try {
    const endpoint = '/proyecto/apps/Controllers/usuarioController.php?action=crearAdmin';
    console.log('Endpoint:', endpoint);
    const res = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: payload.toString() });
    const text = await res.text();
    console.log('Respuesta cruda del servidor:', text);
    let data;
    try { data = JSON.parse(text); } catch (err) { 
      console.log('Error al parsear JSON:', err);
      throw new Error('Respuesta inválida del servidor: ' + text); 
    }

    console.log('Respuesta parseada:', data);
    if (data.success) {
      if (msg) { msg.style.color = 'green'; msg.textContent = data.message + (data.IdUsuario ? (' (IdUsuario: ' + data.IdUsuario + ')') : ''); }
      formEl.reset();
      console.log('Administrador creado correctamente');
      if (redirectToGestiones) setTimeout(() => { window.location.href = '/proyecto/apps/Views/gestiones.html'; }, 800);
    } else {
      if (msg) { msg.style.color = 'red'; msg.textContent = data.message || 'Error al crear administrador'; }
      console.log('Error en respuesta:', data.message);
    }
  } catch (err) {
    if (msg) { msg.style.color = 'red'; msg.textContent = 'Error: ' + err.message; }
    console.log('Error en try/catch principal:', err);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const btnCrear = document.getElementById('btnCrear');
  const btnCrearVerGestiones = document.getElementById('btnCrearVerGestiones');
  if (btnCrear) btnCrear.addEventListener('click', () => submitCrearAdmin(false));
  if (btnCrearVerGestiones) btnCrearVerGestiones.addEventListener('click', () => submitCrearAdmin(true));
});
