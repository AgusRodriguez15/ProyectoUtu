// Obtener el ID del usuario logueado de forma dinámica y, si hace falta, pedirlo al servidor
let usuarioActual = null;
// 1. Intentar obtener de sessionStorage
if (sessionStorage.getItem('usuarioActualId')) {
  usuarioActual = parseInt(sessionStorage.getItem('usuarioActualId'));
}
// 2. Si no está en sessionStorage, intentar obtener de variable global
if (!usuarioActual && window.usuarioActualId) {
  usuarioActual = parseInt(window.usuarioActualId);
}
// 3. Si no está, intentar obtener de cookie
if (!usuarioActual) {
  const match = document.cookie.match(/(?:^|; )usuarioActualId=(\d+)/);
  if (match) usuarioActual = parseInt(match[1]);
}

// Función para inicializar la mensajería una vez tengamos usuarioActual
let miIdUsuario = null;
let chatActivoId = null;

async function inicializarMensajeria() {
  let chatTargetFromServer = null;
  if (!usuarioActual) {
    // Intentar obtener desde el servidor (usa la sesión PHP)
    try {
      const resp = await fetch('/proyecto/public/php/get_current_user.php');
      const json = await resp.json();
      if (json.ok && json.id) {
        usuarioActual = parseInt(json.id);
        try { sessionStorage.setItem('usuarioActualId', usuarioActual); } catch (e) {}
        if (json.chatTarget) chatTargetFromServer = json.chatTarget;
      }
    } catch (e) {
      console.warn('No se pudo obtener usuario desde servidor', e);
    }
  }

  if (!usuarioActual) {
    alert('No se pudo obtener el ID del usuario logueado. Inicia sesión e intenta de nuevo.');
    // Desactivar formulario si existe
    const form = document.getElementById('chatInputForm');
    if (form) form.querySelectorAll('input,button').forEach(el => el.disabled = true);
    return;
  }

  miIdUsuario = usuarioActual;

  // Cargar lista de chats y establecer refresco
  await cargarChats();
  setInterval(() => { if (chatActivoId) cargarMensajes(); }, 3000);

  // Si el servidor indicó un chat target por POST, abrirlo
  if (chatTargetFromServer) {
    // buscar en la lista de chats
    const userDiv = Array.from(document.querySelectorAll('.user')).find(div => div.dataset.id == chatTargetFromServer);
    if (userDiv) {
      userDiv.click();
    } else {
      // No está en la lista: abrir directamente (nombre desconocido por ahora)
      abrirChat(chatTargetFromServer, 'Proveedor', null);
    }
  } else {
    // Si no hay target en sesión, verificar si URL tiene id
    const params = new URLSearchParams(window.location.search);
    const idFromUrl = params.get('id');
    if (idFromUrl) {
      const userDiv2 = Array.from(document.querySelectorAll('.user')).find(div => div.dataset.id == idFromUrl);
      if (userDiv2) userDiv2.click();
      else abrirChat(idFromUrl, 'Proveedor', null);
    }
  }

  // Configurar envío de mensajes
  const form = document.getElementById('chatInputForm');
  if (form) {
    form.addEventListener('submit', async e => {
      e.preventDefault();
      const contenido = document.getElementById('mensajeInput').value.trim();
      if (!contenido || !chatActivoId) return;
      try {
        const res = await fetch('/proyecto/apps/Controllers/mensajeController.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ contenido, idEmisor: miIdUsuario, idReceptor: chatActivoId })
        });
        const data = await res.json();
        if (data.ok) {
          document.getElementById('mensajeInput').value = '';
          agregarMensaje(contenido, 'sent', new Date().toLocaleString());
        } else {
          alert(data.error || 'Error al enviar mensaje');
        }
      } catch (err) {
        console.error('Error enviando mensaje', err);
      }
    });
  }
}

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', inicializarMensajeria);

// Funciones de chat: cargarChats, abrirChat, cargarMensajes, agregarMensaje
async function cargarChats() {
  try {
    const res = await fetch(`/proyecto/apps/Controllers/mensajeController.php?action=chats&idUsuario=${miIdUsuario}`);
    const data = await res.json();
    const chatList = document.getElementById('chatListUsers');
    if (!chatList) return;
    chatList.innerHTML = '';
    (data || []).forEach(u => {
      const div = document.createElement('div');
      div.className = 'user';
      div.dataset.id = u.id;
      div.innerHTML = `<img src="/proyecto/public/recursos/imagenes/perfil/${u.foto || 'default.png'}" alt=""><div class="user-info"><strong>${u.nombre || ''} ${u.apellido || ''}</strong></div>`;
      div.addEventListener('click', () => abrirChat(u.id, `${u.nombre || ''} ${u.apellido || ''}`, u.foto));
      chatList.appendChild(div);
    });
  } catch (e) {
    console.error('Error cargando chats', e);
  }
}

async function abrirChat(id, nombre, foto) {
  chatActivoId = id;
  const chatUserNombre = document.getElementById('chatUserNombre');
  const chatUserFoto = document.getElementById('chatUserFoto');
  const chatUserEstado = document.getElementById('chatUserEstado');
  if (chatUserNombre) chatUserNombre.textContent = nombre;
  if (chatUserFoto) chatUserFoto.src = `/proyecto/public/recursos/imagenes/perfil/${foto || 'default.png'}`;
  // Actualizar estado: si el proveedor está disponible lo marcamos En línea, si no dejar en vacío
  if (chatUserEstado) {
    chatUserEstado.textContent = 'En línea';
    chatUserEstado.classList.add('online');
  }
  await cargarMensajes();
}

async function cargarMensajes() {
  if (!chatActivoId) return;
  try {
    const res = await fetch(`/proyecto/apps/Controllers/mensajeController.php?id1=${miIdUsuario}&id2=${chatActivoId}`);
    const json = await res.json();
    const mensajes = json || [];
    const chatBox = document.getElementById('chatMessages');
    if (!chatBox) return;
    chatBox.innerHTML = '';
    mensajes.forEach(m => {
      agregarMensaje(m.Contenido, m.IdUsuarioEmisor == miIdUsuario ? 'sent' : 'received', m.Fecha);
    });
  } catch (e) {
    console.error('Error cargando mensajes', e);
  }
}

function agregarMensaje(contenido, tipo, fecha) {
  const chatBox = document.getElementById('chatMessages');
  if (!chatBox) return;
  const div = document.createElement('div');
  div.classList.add('message', tipo);
  div.innerHTML = `${contenido}<small>${fecha || ''}</small>`;
  chatBox.appendChild(div);
  chatBox.scrollTop = chatBox.scrollHeight;
}
