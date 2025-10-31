// mensajeria_simple.js
// Minimal chat client: fetch current user, list chats, open chat target from session (server), send messages via POST

let usuarioActual = null;
let chatActivoId = null;
let miIdUsuario = null;

// Normaliza el valor de foto de perfil.
// Si 'foto' ya es una URL absoluta o comienza con '/', la devuelve tal cual.
// Si es solo un nombre de archivo, la convierte en la ruta dentro de recursos/imagenes/perfil.
function buildPerfilFoto(foto) {
  if (!foto) return '/proyecto/public/recursos/default/user-default.png';
  try {
    // Si es URL absoluta (http...) o empieza con '/', devolver tal cual
    if (foto.startsWith('http://') || foto.startsWith('https://') || foto.startsWith('/')) return foto;
  } catch (e) {
    return '/proyecto/public/recursos/default/user-default.png';
  }
  // Si viene solo el nombre del archivo
  return '/proyecto/public/recursos/imagenes/perfil/' + foto;
}

async function obtenerUsuarioSesion(){
  try{
    const r = await fetch('/proyecto/public/php/get_current_user.php');
    const j = await r.json();
    if(j.ok){
      usuarioActual = parseInt(j.id);
      return j;
    }
  }catch(e){ console.warn('get_current_user failed', e); }
  return null;
}

async function inicializar(){
  const sessionInfo = await obtenerUsuarioSesion();
  if(!usuarioActual){
    alert('Necesitas iniciar sesión para usar mensajería');
    disableForm();
    return;
  }
  miIdUsuario = usuarioActual;
  await cargarChats();
  const target = sessionInfo && sessionInfo.chatTarget ? sessionInfo.chatTarget : new URLSearchParams(window.location.search).get('id');
  if(target) abrirChat(target, 'Proveedor');
  setInterval(()=>{ if(chatActivoId) cargarMensajes(); }, 3000);
  const form = document.getElementById('chatInputForm');
  if(form) form.addEventListener('submit', enviarMensaje);
  // Preparar preview y handlers para archivo
  const fileInput = document.getElementById('mensajeFile');
  const preview = document.getElementById('mensajePreview');
  const previewImg = document.getElementById('mensajePreviewImg');
  const previewName = document.getElementById('mensajePreviewName');
  const previewRemove = document.getElementById('mensajePreviewRemove');
  const enviarBtn = document.getElementById('mensajeEnviarBtn');
  const textoInput = document.getElementById('mensajeInput');

  if (fileInput) {
    fileInput.addEventListener('change', (e) => {
      const f = fileInput.files && fileInput.files[0];
      if (f) {
        // Mostrar preview
        const reader = new FileReader();
        reader.onload = function(ev) {
          previewImg.src = ev.target.result;
        };
        reader.readAsDataURL(f);
        previewName.textContent = f.name;
        preview.style.display = 'block';
        // Habilitar enviar
        if (enviarBtn) enviarBtn.disabled = false;
      } else {
        // No hay archivo
        preview.style.display = 'none';
        previewImg.src = '';
        if (enviarBtn) enviarBtn.disabled = !(textoInput && textoInput.value.trim().length>0);
      }
    });
  }

  if (previewRemove) {
    previewRemove.addEventListener('click', () => {
      if (fileInput) fileInput.value = '';
      preview.style.display = 'none';
      previewImg.src = '';
      // Deshabilitar enviar si no hay texto
      if (enviarBtn) enviarBtn.disabled = !(textoInput && textoInput.value.trim().length>0);
    });
  }

  // Controlar habilitado del botón enviar según texto
  if (textoInput) {
    textoInput.addEventListener('input', () => {
      const hasText = textoInput.value.trim().length > 0;
      const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
      if (enviarBtn) enviarBtn.disabled = !(hasText || hasFile);
    });
  }
}

function disableForm(){
  const f = document.getElementById('chatInputForm');
  if(!f) return;
  Array.from(f.querySelectorAll('input,button')).forEach(i=>i.disabled=true);
}

async function cargarChats(){
  try{
    const res = await fetch(`/proyecto/apps/Controllers/mensajeController.php?accion=obtenerChats&id=${miIdUsuario}`);
    const data = await res.json();
    const list = document.getElementById('chatListUsers');
    list.innerHTML='';
    if(!data || data.length===0){ list.textContent='No hay chats aún.'; return; }
    data.forEach(u=>{
      const d = document.createElement('div');
      d.className='user'; d.dataset.id = u.id;
      const fotoSrc = buildPerfilFoto(u.foto);
      d.innerHTML = `<img src="${fotoSrc}" width="40" height="40" style="border-radius:50%"> <div><strong>${u.nombre||''}</strong></div>`;
      d.addEventListener('click', ()=> abrirChat(u.id, `${u.nombre||''} ${u.apellido||''}`, u.foto));
      list.appendChild(d);
    });
  }catch(e){ console.error('cargarChats', e); }
}

async function abrirChat(id, nombre, foto){
  chatActivoId = id;
  const nameEl = document.getElementById('chatUserNombre');
  const fotoEl = document.getElementById('chatUserFoto');
  if(nameEl) nameEl.textContent = nombre || `Usuario ${id}`;
  if(fotoEl) fotoEl.src = buildPerfilFoto(foto);
  await cargarMensajes();
}

async function cargarMensajes(){
  if(!chatActivoId) return;
  try{
    const res = await fetch(`/proyecto/apps/Controllers/mensajeController.php?accion=obtenerMensajes&emisor=${miIdUsuario}&receptor=${chatActivoId}`);
    const msgs = await res.json();
    const box = document.getElementById('chatMessages');
    box.innerHTML='';
    if(!msgs || msgs.length===0){ box.textContent='Sin mensajes aún.'; return; }
    msgs.forEach(m=>{
      const div = document.createElement('div');
      const emisorId = m.Emisor || m.IdUsuarioEmisor || m.EmisorId;
      div.className = 'message ' + (emisorId==miIdUsuario ? 'sent' : 'received');
      // Contenido de texto
      const texto = document.createElement('div');
      texto.className = 'message-text';
      texto.innerHTML = escapeHtml(m.Contenido || m.contenido || '');
      div.appendChild(texto);

      // Si el mensaje tiene imagen, mostrarla debajo
      if (m.Imagen) {
        try{
          const img = document.createElement('img');
          img.className = 'message-image';
          img.src = m.Imagen; // controller devuelve la ruta completa
          img.alt = 'Adjunto';
          img.style.maxWidth = '320px';
          img.style.display = 'block';
          img.style.marginTop = '6px';
          div.appendChild(img);
        }catch(e){ console.warn('Error mostrando imagen de mensaje', e); }
      }

      const meta = document.createElement('div');
      meta.style.fontSize = '10px';
      meta.style.color = '#666';
      meta.textContent = m.Fecha||m.fecha||'';
      div.appendChild(meta);

      box.appendChild(div);
    });
    box.scrollTop = box.scrollHeight;
  }catch(e){ console.error('cargarMensajes', e); }
}

async function enviarMensaje(e){
  e && e.preventDefault();
  const inp = document.getElementById('mensajeInput');
  if(!inp) return;
  const text = inp.value.trim();
  if(!text && !document.getElementById('mensajeFile').files.length) return; // no texto ni imagen
  if(!chatActivoId) return;
  try{
    const fileInput = document.getElementById('mensajeFile');
    let fetchOpts;
    if (fileInput && fileInput.files && fileInput.files.length > 0) {
      console.log('[enviarMensaje] Enviando imagen:', fileInput.files[0]);
      const fd = new FormData();
      fd.append('accion', 'enviarMensaje');
      fd.append('contenido', text);
      fd.append('emisor', miIdUsuario);
      fd.append('receptor', chatActivoId);
      fd.append('imagen', fileInput.files[0]);
      fetchOpts = { method: 'POST', body: fd };
    } else {
      console.log('[enviarMensaje] Enviando solo texto:', text);
      const form = new URLSearchParams();
      form.append('accion', 'enviarMensaje');
      form.append('contenido', text);
      form.append('emisor', miIdUsuario);
      form.append('receptor', chatActivoId);
      fetchOpts = { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: form.toString() };
    }

    const res = await fetch('/proyecto/apps/Controllers/mensajeController.php', fetchOpts);
    const j = await res.json();
    console.log('[enviarMensaje] Respuesta del servidor:', j);
    if(j.ok){
      // Limpiar campos
      inp.value='';
      const fileInputEl = document.getElementById('mensajeFile');
      const previewImgEl = document.getElementById('mensajePreviewImg');
      const previewEl = document.getElementById('mensajePreview');

      // Si se envió una imagen pero el servidor/BD no la devuelve inmediatamente,
      // mostramos una previsualización temporal en el chat usando la imagen que ya teníamos en cliente.
      const hasFile = fileInputEl && fileInputEl.files && fileInputEl.files.length > 0;
      let appendedTemp = false;
      if (hasFile) {
        let src = j.imagenUrl || '';
        console.log('[enviarMensaje] imagenUrl devuelta:', src);
        if (!src) {
          if (previewImgEl && previewImgEl.src) src = previewImgEl.src;
          else {
            try { src = URL.createObjectURL(fileInputEl.files[0]); } catch(e){ src = ''; }
          }
        }
        if (src) {
          try {
            const box = document.getElementById('chatMessages');
            const div = document.createElement('div');
            div.className = 'message sent';
            const texto = document.createElement('div');
            texto.className = 'message-text';
            texto.textContent = text || '';
            div.appendChild(texto);
            const img = document.createElement('img');
            img.className = 'message-image';
            img.src = src;
            img.alt = 'Adjunto';
            div.appendChild(img);
            const meta = document.createElement('div'); meta.style.fontSize='10px'; meta.style.color='#666'; meta.textContent = new Date().toLocaleString();
            div.appendChild(meta);
            box.appendChild(div);
            box.scrollTop = box.scrollHeight;
            appendedTemp = true;
            console.log('[enviarMensaje] Mensaje temporal con imagen añadido al DOM');
          } catch(e) { console.warn('No se pudo añadir previsualización temporal en el chat', e); }
        }
      }

      // Limpiar input y preview
      if (fileInputEl) fileInputEl.value = '';
      if (previewImgEl) previewImgEl.src = '';
      if (previewEl) previewEl.style.display = 'none';

      try { await cargarMensajes(); } catch(e){ console.warn('[enviarMensaje] Error al recargar mensajes', e); }

      if (!appendedTemp && !hasFile) {
        // nothing special
      }
    } else {
      console.error('[enviarMensaje] Error al enviar:', j.error||'Error desconocido');
      alert(j.error||'Error al enviar');
    }
  }catch(e){ console.error('[enviarMensaje] Excepción:', e); }
}

function agregarMensaje(contenido, tipo, fecha){
  const box = document.getElementById('chatMessages');
  if(!box) return;
  const d = document.createElement('div'); d.className = 'message ' + tipo;
  d.innerHTML = `${escapeHtml(contenido)} <div style="font-size:10px;color:#666">${fecha||''}</div>`;
  box.appendChild(d); box.scrollTop = box.scrollHeight;
}

function escapeHtml(unsafe){ return unsafe.replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]; }); }

document.addEventListener('DOMContentLoaded', inicializar);
