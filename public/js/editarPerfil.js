// JS para editarPerfil.html - carga datos, permite agregar/remover contactos y habilidades y envía formulario como 'editarCompleto'
document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');
  const status = document.getElementById('status');
  const form = document.getElementById('perfilForm');
  const listaContactos = document.getElementById('listaContactos');
  const listaHabilidades = document.getElementById('listaHabilidades');
  // flags for resets
  let resetNombreFlag = false;
  let resetApellidoFlag = false;
  let resetDescripcionFlag = false;
  let resetFotoFlag = false;
  let resetContactosFlag = false;
  let resetHabilidadesFlag = false;
  // id de ubicacion actual
  let currentIdUbic = null;

  // reset buttons
  const resetNombreBtn = document.getElementById('resetNombre');
  const resetApellidoBtn = document.getElementById('resetApellido');
  const resetDescripcionBtn = document.getElementById('resetDescripcion');
  const resetFotoBtn = document.getElementById('resetFoto');
  // el botón de reiniciar ubicación fue eliminado; mantenemos solo el botón eliminar
  const resetContactosBtn = document.getElementById('resetContactos');
  const resetHabilidadesBtn = document.getElementById('resetHabilidades');

  if (resetNombreBtn) resetNombreBtn.addEventListener('click', () => { document.getElementById('Nombre').value = ''; resetNombreFlag = true; resetNombreBtn.textContent = 'Reiniciado'; });
  if (resetApellidoBtn) resetApellidoBtn.addEventListener('click', () => { document.getElementById('Apellido').value = ''; resetApellidoFlag = true; resetApellidoBtn.textContent = 'Reiniciado'; });
  if (resetDescripcionBtn) resetDescripcionBtn.addEventListener('click', () => { document.getElementById('Descripcion').value = ''; resetDescripcionFlag = true; resetDescripcionBtn.textContent = 'Reiniciado'; });
  if (resetFotoBtn) resetFotoBtn.addEventListener('click', () => {
    resetFotoFlag = true;
    resetFotoBtn.textContent = 'Marcado';
    const img = document.getElementById('fotoPerfilImg');
    const fotoContainer = document.getElementById('fotoContainer');
    if (img) img.src = '/proyecto/public/recursos/imagenes/perfil/default.png';
    // ocultar el contenedor de la foto cuando se marca para eliminar
    if (fotoContainer) fotoContainer.style.display = 'none';
  });
  // Nota: ya no existe 'resetUbicacion' en la vista; para eliminar la ubicación se usa el botón 'eliminarUbicacionBtn' más abajo.
  if (resetContactosBtn) resetContactosBtn.addEventListener('click', () => { listaContactos.innerHTML = ''; resetContactosFlag = true; resetContactosBtn.textContent = 'Reiniciados'; });
  if (resetHabilidadesBtn) resetHabilidadesBtn.addEventListener('click', () => { listaHabilidades.innerHTML = ''; resetHabilidadesFlag = true; resetHabilidadesBtn.textContent = 'Reiniciadas'; });

  if (!id) { if (status) status.textContent = 'Falta id de usuario en la URL'; return; }

  function crearContactoRow(tipo = '', contacto = '') {
    // Mostrar contacto en modo sólo-lectura con botón eliminar puntual
    const div = document.createElement('div'); div.className = 'item display-item';
    const span = document.createElement('span'); span.textContent = `${tipo}: ${contacto}`;
    span.style.flex = '1';
    const btn = document.createElement('button'); btn.type = 'button'; btn.textContent = 'Eliminar'; btn.className = 'btn btn-small btn-danger';
    btn.addEventListener('click', async () => {
      if (!confirm('Eliminar contacto ' + tipo + ' - ' + contacto + ' ?')) return;
      try {
        const fd = new FormData(); fd.append('action','eliminarDato'); fd.append('id', document.getElementById('IdUsuario').value); fd.append('Tipo', tipo); fd.append('Contacto', contacto);
        const res = await fetch('/proyecto/apps/Controllers/usuarioController.php', { method: 'POST', body: fd });
        const text = await res.text(); const json = JSON.parse(text);
        if (json && json.success) { div.remove(); alert(json.message || 'Contacto eliminado'); } else { alert(json.message || 'Error eliminando contacto'); }
      } catch (e) { alert('Error: ' + e.message); }
    });
    div.appendChild(span); div.appendChild(btn);
    return div;
  }

  function crearHabilidadRow(nombre = '', anios = '') {
    const div = document.createElement('div'); div.className = 'item display-item';
    const span = document.createElement('span'); span.textContent = `${nombre} (${anios} años)`; span.style.flex = '1';
    const btn = document.createElement('button'); btn.type = 'button'; btn.textContent = 'Eliminar'; btn.className = 'btn btn-small btn-danger';
    btn.addEventListener('click', async () => {
      if (!confirm('Eliminar habilidad ' + nombre + ' ?')) return;
      try {
        const fd = new FormData(); fd.append('action','eliminarHabilidad'); fd.append('id', document.getElementById('IdUsuario').value); fd.append('Habilidad', nombre);
        const res = await fetch('/proyecto/apps/Controllers/usuarioController.php', { method: 'POST', body: fd });
        const text = await res.text(); const json = JSON.parse(text);
        if (json && json.success) { div.remove(); alert(json.message || 'Habilidad eliminada'); } else { alert(json.message || 'Error eliminando habilidad'); }
      } catch (e) { alert('Error: ' + e.message); }
    });
    div.appendChild(span); div.appendChild(btn);
    return div;
  }

  // botón para añadir contacto eliminado: no se añade listener

  async function fetchUser() {
    if (status) status.textContent = 'Cargando...';
    try {
      const res = await fetch(`/proyecto/apps/Controllers/usuarioController.php?action=obtener&id=${encodeURIComponent(id)}`);
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      document.getElementById('IdUsuario').value = data.IdUsuario || id;
      document.getElementById('Nombre').value = data.Nombre || '';
      document.getElementById('Apellido').value = data.Apellido || '';
      document.getElementById('Descripcion').value = data.Descripcion || '';
      // Mostrar foto de perfil si existe; si no, ocultar el contenedor
      const fotoContainer = document.getElementById('fotoContainer');
      const img = document.getElementById('fotoPerfilImg');
      if (img && fotoContainer) {
        if (data.FotoPerfil && String(data.FotoPerfil).trim() !== '') {
          img.src = `/proyecto/public/recursos/imagenes/perfil/${data.FotoPerfil}`;
          fotoContainer.style.display = '';
        } else {
          // No hay foto: ocultamos el bloque para evitar mostrar el placeholder
          fotoContainer.style.display = 'none';
        }
      }
      // Ubicación primaria
      currentIdUbic = data.IdUbicacion || null;
      if (data.ubicacion) {
        const u = data.ubicacion;
        document.getElementById('pais').value = u.Pais || '';
        document.getElementById('ciudad').value = u.Ciudad || '';
        document.getElementById('calle').value = u.Calle || '';
        document.getElementById('numero').value = u.Numero || '';
      } else {
        document.getElementById('pais').value = '';
        document.getElementById('ciudad').value = '';
        document.getElementById('calle').value = '';
        document.getElementById('numero').value = '';
      }
      // Contactos (modo sólo lectura con eliminar puntual)
      listaContactos.innerHTML = '';
      if (Array.isArray(data.contactos)) {
        data.contactos.forEach(c => listaContactos.appendChild(crearContactoRow(c.Tipo, c.Contacto)));
      }
      // Habilidades (modo sólo lectura con eliminar puntual)
      listaHabilidades.innerHTML = '';
      if (Array.isArray(data.habilidades)) {
        data.habilidades.forEach(h => listaHabilidades.appendChild(crearHabilidadRow(h.Habilidad, h.AniosExperiencia)));
      }
      if (status) status.textContent = '';
    } catch (e) {
      if (status) status.textContent = 'Error cargando usuario: ' + e.message;
    }
  }

  form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const fd = new FormData();
    fd.append('action','editarCompleto');
    fd.append('id', document.getElementById('IdUsuario').value);
    fd.append('Nombre', document.getElementById('Nombre').value);
    fd.append('Apellido', document.getElementById('Apellido').value);
    fd.append('Descripcion', document.getElementById('Descripcion').value);
  // El checkbox 'removeFoto' fue eliminado de la vista; usamos el flag resetFotoFlag (botón) para marcar eliminación
  // selector de archivo removido: no se adjunta archivo
    // Ubicación
    fd.append('pais', document.getElementById('pais').value);
    fd.append('ciudad', document.getElementById('ciudad').value);
    fd.append('calle', document.getElementById('calle').value);
    fd.append('numero', document.getElementById('numero').value);

  // reset flags
  if (resetNombreFlag) fd.append('resetNombre','1');
  if (resetApellidoFlag) fd.append('resetApellido','1');
  if (resetDescripcionFlag) fd.append('resetDescripcion','1');
  if (resetFotoFlag) fd.append('resetFoto','1');
  // resetUbicacionFlag eliminado: no se envía ese campo. Las operaciones sobre la ubicación se realizan vía 'eliminarUbicacion'.
  if (resetContactosFlag) fd.append('resetContactos','1');
  if (resetHabilidadesFlag) fd.append('resetHabilidades','1');

    // NOTA: en este modo admin sólo permitimos reiniciar listas completas o eliminar items puntuales usando botones;
    // por eso NO reenviamos los contactos/habilidades desde el formulario (evita sobrescribir accidentalmente).

    if (status) status.textContent = 'Guardando...';
    try {
      const resp = await fetch('/proyecto/apps/Controllers/usuarioController.php', { method: 'POST', body: fd });
      const text = await resp.text();
      let json; try { json = JSON.parse(text); } catch (e) { throw new Error('Respuesta inválida: ' + text); }
      if (json && json.success) {
        alert(json.message || 'Guardado');
        // Redirección inteligente: si vinimos del panel de gestión, volver allí;
        // si vinimos desde una vista de perfil, volver al referrer; si no, abrir verPerfil del usuario.
        try {
          const ref = document.referrer || '';
          if (ref.includes('gestionarUsuarios')) {
            window.location.href = '/proyecto/apps/Views/gestionarUsuarios.html';
          } else if (ref) {
            window.location.href = ref;
          } else {
            const editedId = encodeURIComponent(document.getElementById('IdUsuario').value);
            window.location.href = '/proyecto/apps/Views/verPerfil.html?id=' + editedId;
          }
        } catch (e) {
          const editedId = encodeURIComponent(document.getElementById('IdUsuario').value);
          window.location.href = '/proyecto/apps/Views/verPerfil.html?id=' + editedId;
        }
      } else {
        alert(json.message || 'Error guardando');
        if (status) status.textContent = '';
      }
    } catch (e) {
      if (status) status.textContent = 'Error: ' + e.message;
    }
  });

  fetchUser();

  // Botón volver: intenta volver al referrer; si viene del panel de gestión, redirige allí; si no hay referrer, fallback a gestionarUsuarios
  const btnVolver = document.getElementById('btnVolver');
  if (btnVolver) btnVolver.addEventListener('click', () => {
    try {
      const ref = document.referrer || '';
      if (ref.includes('gestionarUsuarios')) {
        window.location.href = '/proyecto/apps/Views/gestionarUsuarios.html';
        return;
      }
      if (ref) {
        window.history.back();
        return;
      }
      // fallback
      window.location.href = '/proyecto/apps/Views/gestionarUsuarios.html';
    } catch (e) {
      window.history.back();
    }
  });
});

  // eliminar ubicacion puntual (botón) - moved inside same DOMContentLoaded so it can access currentIdUbic
  const eliminarUbicacionBtn = document.getElementById('eliminarUbicacionBtn');
  if (eliminarUbicacionBtn) eliminarUbicacionBtn.addEventListener('click', async () => {
    if (!confirm('¿Eliminar la ubicación principal del usuario?')) return;
    try {
      const id = document.getElementById('IdUsuario').value;
      const fd2 = new FormData(); fd2.append('action','eliminarUbicacion'); fd2.append('id', id); fd2.append('IdUbicacion', currentIdUbic || 0);
      const res = await fetch('/proyecto/apps/Controllers/usuarioController.php', { method: 'POST', body: fd2 });
      const text = await res.text(); const json = JSON.parse(text);
      if (json && json.success) {
        alert(json.message || 'Ubicación eliminada');
        document.getElementById('pais').value = ''; document.getElementById('ciudad').value = ''; document.getElementById('calle').value = ''; document.getElementById('numero').value = '';
        currentIdUbic = null;
      } else alert(json.message || 'Error al eliminar ubicación');
    } catch (e) { alert('Error: ' + e.message); }
  });
