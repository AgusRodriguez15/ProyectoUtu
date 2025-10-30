// JS para gestionarUsuarios.html
// Contiene carga de usuarios y acciones administrativas mediante fetch POST

async function cargarUsuarios() {
  const status = document.getElementById('status');
  const tbody = document.querySelector('#tablaUsuarios tbody');
  if (status) status.textContent = 'Cargando usuarios...';
  if (tbody) tbody.innerHTML = '';
  try {
    const res = await fetch('/proyecto/apps/Controllers/usuarioController.php?action=listar');
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch (e) { throw new Error('Respuesta inválida: ' + text); }
    if (!Array.isArray(data) || data.length === 0) {
      if (status) status.textContent = 'No hay usuarios registrados.';
      return;
    }
    if (status) status.textContent = '';

    data.forEach(u => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${u.IdUsuario}</td>
        <td>${u.Nombre || ''} ${u.Apellido || ''}</td>
        <td>${u.Email || ''}</td>
        <td>${u.Rol || 'N/A'}</td>
        <td>${u.EstadoCuenta || ''}</td>
      `;

      const tdActions = document.createElement('td');
      tdActions.className = 'actions';

      const sel = document.createElement('select');
  const banAction = (String(u.EstadoCuenta).toUpperCase() === 'BANEADO') ? 'desbanear' : 'banear';
  const banLabel = (String(u.EstadoCuenta).toUpperCase() === 'BANEADO') ? 'Desbanear' : 'Banear';
      sel.innerHTML = `
        <option value="">Acciones...</option>
        <option value="ver">Ver</option>
        <option value="editar">Editar</option>
        <option value="${banAction}">${banLabel}</option>
        <option value="cambiarContrasena">Cambiar contraseña</option>
        <option value="cambiarEmail">Cambiar email</option>
      `;

      sel.addEventListener('change', function () {
        const action = this.value;
        if (!action) return;
        performAction(u.IdUsuario, action);
        this.value = '';
      });

      tdActions.appendChild(sel);
      tr.appendChild(tdActions);
      tbody.appendChild(tr);
    });

  } catch (err) {
    if (document.getElementById('status')) document.getElementById('status').textContent = 'Error cargando usuarios: ' + err.message;
  }
}

// helper to POST admin actions
async function postAction(formData) {
  const resp = await fetch('/proyecto/apps/Controllers/usuarioController.php', { method: 'POST', body: formData });
  const text = await resp.text();
  try { return JSON.parse(text); } catch (e) { throw new Error('Respuesta inválida: ' + text); }
}

// Modal confirm helper: showConfirm({ title, message, showInput }) -> Promise<{confirmed, inputValue}>
    function showConfirm({ title = 'Confirmar acción', message = '¿Estás seguro?', showPrimaryInput = false, primaryLabel = 'Valor', showMotivo = false } = {}) {
  return new Promise(resolve => {
    const modal = document.getElementById('confirmModal');
    const titleEl = document.getElementById('confirmTitle');
    const msgEl = document.getElementById('confirmMessage');
    const primaryContainer = document.getElementById('confirmPrimaryContainer');
    const primaryLabelEl = document.getElementById('confirmPrimaryLabel');
    const primaryInput = document.getElementById('confirmInput');
    const motivoContainer = document.getElementById('confirmMotivoContainer');
    const motivoInput = document.getElementById('confirmMotivo');
    const okBtn = document.getElementById('confirmOk');
    const cancelBtn = document.getElementById('confirmCancel');

    if (!modal) {
      // Fallback to native confirm/prompt
      if (showPrimaryInput && showMotivo) {
        const primary = prompt(message + '\nValor:');
        if (primary === null) return resolve({ confirmed: false, primaryValue: null, motivoValue: null });
        const mot = prompt('Motivo (opcional):');
        if (mot === null) return resolve({ confirmed: false, primaryValue: null, motivoValue: null });
        return resolve({ confirmed: true, primaryValue: primary, motivoValue: mot });
      }
      if (showPrimaryInput) {
        const v = prompt(message);
        if (v === null) return resolve({ confirmed: false, primaryValue: null, motivoValue: null });
        return resolve({ confirmed: true, primaryValue: v, motivoValue: null });
      }
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
    primaryContainer.style.display = showPrimaryInput ? 'block' : 'none';
    primaryLabelEl.textContent = primaryLabel;
    primaryInput.value = '';
    motivoContainer.style.display = showMotivo ? 'block' : 'none';
    motivoInput.value = '';

    function cleanup() {
      okBtn.removeEventListener('click', onOk);
      cancelBtn.removeEventListener('click', onCancel);
      modal.style.display = 'none';
    }

    function onOk() {
      const p = showPrimaryInput ? primaryInput.value : null;
      const m = showMotivo ? motivoInput.value : null;
      cleanup();
      resolve({ confirmed: true, primaryValue: p, motivoValue: m });
    }

    function onCancel() {
      cleanup();
      resolve({ confirmed: false, primaryValue: null, motivoValue: null });
    }

    okBtn.addEventListener('click', onOk);
    cancelBtn.addEventListener('click', onCancel);
    modal.style.display = 'flex';
    if (showPrimaryInput) primaryInput.focus();
  });
}

async function banearUsuario(id) {
  const r = await showConfirm({ title: 'Confirmar baneo', message: '¿Confirma banear al usuario ' + id + '?', showMotivo: true });
  if (!r.confirmed) return;
  const motivo = r.motivoValue ?? '';
  const fd = new FormData(); fd.append('action','banear'); fd.append('id', id); fd.append('motivo', motivo);
  try { const res = await postAction(fd); alert(res.message || 'Hecho'); cargarUsuarios(); } catch (e) { alert('Error: ' + e.message); }
}

async function desbanearUsuario(id) {
  const r = await showConfirm({ title: 'Confirmar desbaneo', message: '¿Confirma desbanear al usuario ' + id + '?', showMotivo: true });
  if (!r.confirmed) return;
  const motivo = r.motivoValue ?? '';
  const fd = new FormData(); fd.append('action','desbanear'); fd.append('id', id); fd.append('motivo', motivo);
  try { const res = await postAction(fd); alert(res.message || 'Hecho'); cargarUsuarios(); } catch (e) { alert('Error: ' + e.message); }
}

async function eliminarUsuario(id) {
  // La acción eliminar fue removida del frontend por decisión administrativa.
  // Esta función se mantiene vacía como recordatorio y para evitar errores si se invoca accidentalmente.
  console.warn('Eliminar usuario no está disponible desde el frontend');
}

async function editarUsuarioPrompt(id) {
  // Redirigir a la página dedicada de edición
  // Para administradores preferimos la edición completa del perfil
  window.location.href = '/proyecto/apps/Views/editarPerfil.html?id=' + encodeURIComponent(id);
}

async function cambiarPasswordPrompt(id) {
  // Usar modal para pedir nueva contraseña y motivo (admin introduce la contraseña en claro)
  const r = await showConfirm({ title: 'Cambiar contraseña', message: 'Ingrese la nueva contraseña para el usuario ' + id + ':', showPrimaryInput: true, primaryLabel: 'Nueva contraseña', showMotivo: true });
  if (!r.confirmed) return;
  const nueva = (r.primaryValue || '').trim();
  const motivo = (r.motivoValue || '').trim();
  if (!nueva) { alert('La contraseña no puede estar vacía'); return; }
  const fd = new FormData(); fd.append('action','cambiarContrasena'); fd.append('id', id); fd.append('nueva', nueva); fd.append('motivo', motivo);
  try { const rr = await postAction(fd); alert(rr.message || 'Hecho'); cargarUsuarios(); } catch (e) { alert('Error: ' + e.message); }
}

async function cambiarEmailPrompt(id) {
  // Usar modal reutilizable para pedir nuevo email y motivo
  const r = await showConfirm({ title: 'Cambiar email', message: 'Ingrese el nuevo email para el usuario ' + id + ':', showPrimaryInput: true, primaryLabel: 'Nuevo email', showMotivo: true });
  if (!r.confirmed) return;
  const email = (r.primaryValue || '').trim();
  // Validación básica de email
  const emailRe = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
  if (!emailRe.test(email)) { alert('Email inválido'); return; }
  const motivo = (r.motivoValue || '').trim();
  const fd = new FormData(); fd.append('action','cambiarEmail'); fd.append('id', id); fd.append('Email', email); fd.append('motivo', motivo);
  try { const res = await postAction(fd); alert(res.message || 'Hecho'); cargarUsuarios(); } catch (e) { alert('Error: ' + e.message); }
}

function verPerfil(id) { window.location.href = 'verPerfil.html?id=' + id; }

// manejador central para el desplegable por fila
function performAction(id, action) {
  switch (action) {
    case 'ver':
      verPerfil(id);
      break;
    case 'editar':
      editarUsuarioPrompt(id);
      break;
    case 'banear':
      banearUsuario(id);
      break;
    case 'desbanear':
      desbanearUsuario(id);
      break;
    case 'cambiarContrasena':
      cambiarPasswordPrompt(id);
      break;
    case 'cambiarEmail':
      cambiarEmailPrompt(id);
      break;
    // 'eliminar' removido del frontend
    default:
      console.warn('Acción desconocida:', action);
  }
}

// Inicializar carga
document.addEventListener('DOMContentLoaded', cargarUsuarios);
