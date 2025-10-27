// Actualización automática de mensajes cada 3 segundos
setInterval(cargarMensajes, 3000);
// mensajeria.js
// Lógica para la nueva interfaz de mensajería

const chatListUsers = document.getElementById('chatListUsers');
const buscadorUsuario = document.getElementById('buscadorUsuario');
const buscarUsuarioBtn = document.getElementById('buscarUsuarioBtn');
const chatHeader = document.getElementById('chatHeader');
const chatUserFoto = document.getElementById('chatUserFoto');
const chatUserNombre = document.getElementById('chatUserNombre');
const chatMessages = document.getElementById('chatMessages');
const chatInputForm = document.getElementById('chatInputForm');
const mensajeInput = document.getElementById('mensajeInput');
const fotoInput = document.getElementById('fotoInput');

let usuarioActualId = 1; // Cambia esto por el usuario logueado
let chatSeleccionado = null;
let chatSeleccionadoData = null;

// Listeners y funciones deben ir después de la inicialización de los elementos

// Buscar usuarios
buscarUsuarioBtn.addEventListener('click', buscarUsuarios);
buscadorUsuario.addEventListener('keyup', function(e) {
  if (e.key === 'Enter') buscarUsuarios();
});

// Enviar con el botón
const enviarBtn = document.querySelector('.chat-input button');
if (enviarBtn) {
  enviarBtn.addEventListener('click', function(e) {
    e.preventDefault();
    chatInputForm.dispatchEvent(new Event('submit'));
  });
}

// Enviar con la tecla Enter
mensajeInput.addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    chatInputForm.dispatchEvent(new Event('submit'));
  }
});

function buscarUsuarios() {
  const q = buscadorUsuario.value.trim();
  if (!q) {
    chatListUsers.innerHTML = '';
    return;
  }
  fetch(`../Controllers/usuarioController.php?action=buscar&q=${encodeURIComponent(q)}`)
    .then(res => {
      console.log('Respuesta usuarioController:', res);
      return res.text();
    })
    .then(texto => {
      console.log('Texto recibido:', texto);
      let data;
      try {
        data = JSON.parse(texto);
      } catch (err) {
        console.error('Error al parsear JSON:', err, texto);
        chatListUsers.innerHTML = '<div style="color:red;">Error al procesar respuesta del servidor.</div>';
        return;
      }
      if (!Array.isArray(data) || data.length === 0) {
        chatListUsers.innerHTML = '<div style="text-align:center;color:#888;">No se encontraron usuarios.</div>';
        return;
      }
      chatListUsers.innerHTML = data.map(u => `
        <div class='chat-list-user' data-id='${u.IdUsuario}'>
          <img src='../../public/recursos/imagenes/perfil/${u.FotoPerfil || "default.png"}' alt='Foto' />
          <div class='user-info'>
            <span class='user-name'>${u.Nombre} ${u.Apellido}</span>
            <span class='user-email'>${u.Email}</span>
            <span class='user-rol'>${u.Rol}</span>
          </div>
        </div>
      `).join('');
      document.querySelectorAll('.chat-list-user').forEach(el => {
        el.onclick = () => seleccionarChat(el.dataset.id, data.find(u => u.IdUsuario == el.dataset.id));
      });
    });
}

// Cargar chats existentes
function cargarChats() {
  fetch(`../Controllers/mensajeController.php?action=chats&idUsuario=${usuarioActualId}`)
    .then(res => {
      console.log('Respuesta mensajeController (chats):', res);
      return res.text();
    })
    .then(texto => {
      console.log('Texto recibido (chats):', texto);
      let chats;
      try {
        chats = JSON.parse(texto);
      } catch (err) {
        console.error('Error al parsear JSON (chats):', err, texto);
        chatListUsers.innerHTML = '<div style="color:red;">Error al procesar respuesta del servidor.</div>';
        return;
      }
      if (!Array.isArray(chats) || chats.length === 0) {
        chatListUsers.innerHTML = '<div style="text-align:center;color:#888;">No tienes chats.</div>';
        return;
      }
      chatListUsers.innerHTML = chats.map(chat => `
        <div class='chat-list-user' data-id='${chat.id}'>
          <img src='../../public/recursos/imagenes/perfil/${chat.foto || "default.png"}' alt='Foto' />
          <div class='user-info'>
            <span class='user-name'>Usuario ${chat.id}</span>
          </div>
        </div>
      `).join('');
      document.querySelectorAll('.chat-list-user').forEach(el => {
        el.onclick = () => seleccionarChat(el.dataset.id, { IdUsuario: el.dataset.id, FotoPerfil: chat.foto, Nombre: 'Usuario', Apellido: chat.id });
      });
    });
}

// Seleccionar chat
function seleccionarChat(id, userData) {
  chatSeleccionado = id;
  chatSeleccionadoData = userData;
  chatUserFoto.src = `../../public/recursos/imagenes/perfil/${userData.FotoPerfil || "default.png"}`;
  chatUserNombre.textContent = `${userData.Nombre} ${userData.Apellido}`;
  cargarMensajes();
}

// Cargar mensajes del chat seleccionado
function cargarMensajes() {
  if (!chatSeleccionado) {
    chatMessages.innerHTML = '<div style="text-align:center;color:#888;">Selecciona un chat.</div>';
    return;
  }
  fetch(`../Controllers/mensajeController.php?action=listar&id1=${usuarioActualId}&id2=${chatSeleccionado}`)
    .then(res => {
      console.log('Respuesta mensajeController (listar):', res);
      return res.text();
    })
    .then(texto => {
      console.log('Texto recibido (listar):', texto);
      let mensajes;
      try {
        mensajes = JSON.parse(texto);
      } catch (err) {
        console.error('Error al parsear JSON (listar):', err, texto);
        chatMessages.innerHTML = '<div style="color:red;">Error al procesar respuesta del servidor.</div>';
        return;
      }
      if (!Array.isArray(mensajes) || mensajes.length === 0) {
        chatMessages.innerHTML = '<div style="text-align:center;color:#888;">¡Manda tu primer mensaje!</div>';
        return;
      }
      chatMessages.innerHTML = mensajes.map(m => `
        <div class='message ${m.IdEmisor == usuarioActualId ? "sent" : "received"}'>
          <div class='message-content'>${m.Contenido}
            ${m.Foto ? `<br><img src='../../public/recursos/imagenes/mensajes/${m.Foto}' class='message-photo' />` : ''}
          </div>
        </div>
      `).join('');
      chatMessages.scrollTop = chatMessages.scrollHeight;
    });
}

// Enviar mensaje o foto
chatInputForm.addEventListener('submit', function(e) {
  e.preventDefault();
  if (!chatSeleccionado) return;
  const contenido = mensajeInput.value.trim();
  const foto = fotoInput.files[0];
  if (!contenido && !foto) return;
  const formData = new FormData();
  formData.append('contenido', contenido);
  formData.append('idEmisor', usuarioActualId);
  formData.append('idReceptor', chatSeleccionado);
  if (foto) formData.append('foto', foto);
  fetch('../Controllers/mensajeController.php', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(data => {
      if (data.ok) {
        mensajeInput.value = '';
        fotoInput.value = '';
        cargarMensajes();
      }
    });
});

// Inicializar
cargarChats();
