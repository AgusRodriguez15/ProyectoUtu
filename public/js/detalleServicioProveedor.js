document.addEventListener("DOMContentLoaded", () => {
  const rol = sessionStorage.getItem('rol');
  console.log('Rol actual:', rol);
  const contenedor = document.getElementById("contenidoDetalle");

  // Obtener ID del servicio desde sessionStorage o URL
  let servicioId = sessionStorage.getItem('servicioId') || new URLSearchParams(window.location.search).get('id');

  if (!servicioId) return mostrarError('No se ha seleccionado ningún servicio.');

  cargarDetalleServicio(servicioId);

  async function cargarDetalleServicio(id) {
    try {
      const res = await fetch("../../apps/Controllers/servicioController.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `id=${encodeURIComponent(id)}`
      });
      const text = await res.text();
      console.log('Respuesta del servidor:', text);

      const data = JSON.parse(text);

      if (data.error) return mostrarError(data.error);

      mostrarDetalle(data);
    } catch (err) {
      console.error(err);
      mostrarError('No se pudo cargar el detalle del servicio.', err.message);
    }
  }

  function mostrarDetalle(servicio) {
    const usuarioActualId = sessionStorage.getItem('IdUsuario');
    const esDueno = usuarioActualId && servicio.proveedor?.idUsuario &&
                     parseInt(usuarioActualId) === parseInt(servicio.proveedor.idUsuario);

    console.log('¿Es dueño del servicio?:', esDueno);

    // Galería de fotos
    const galeriaHTML = servicio.fotos?.length
      ? `<div class="galeria-fotos">${servicio.fotos.map(f => `<img src="${f}" class="foto-miniatura">`).join('')}</div>`
      : '';

    // Fecha formateada
    const fechaFormateada = servicio.fechaPublicacion
      ? new Date(servicio.fechaPublicacion).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' })
      : 'Fecha no disponible';

    // HTML principal
    contenedor.innerHTML = `
      <div class="servicio-detalle">
        <div class="servicio-imagen">
          <img src="${servicio.foto}" alt="${servicio.nombre}" 
               onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'600\\' height=\\'400\\'%3E%3Crect width=\\'600\\' height=\\'400\\' fill=\\'%23ccc\\'/%3E%3Ctext x=\\'300\\' y=\\'200\\' text-anchor=\\'middle\\' fill=\\'%23666\\' font-size=\\'24\\'%3ESin Imagen%3C/text%3E%3C/svg%3E'">
          ${galeriaHTML}
        </div>

        <div class="servicio-info">
          <h1>${servicio.nombre}</h1>
          <div class="servicio-meta">
            <span class="badge estado-${servicio.estado?.toLowerCase() || 'disponible'}">
              ${servicio.estado || 'DISPONIBLE'}
            </span>
            <span class="fecha-publicacion">📅 Publicado: ${fechaFormateada}</span>
          </div>

          ${servicio.precio ? `<div class="servicio-precio">
            <h3>💰 Precio</h3>
            <span class="precio-valor">${servicio.precio > 0 ? `$${parseFloat(servicio.precio).toLocaleString('es-UY', {minimumFractionDigits:2})} ${servicio.divisa||'UYU'}` : '🆓 Servicio Gratuito'}</span>
          </div>` : ''}

          <div class="servicio-descripcion-container">
            <h3>Descripción</h3>
            <p>${servicio.descripcion || 'Sin descripción disponible'}</p>
          </div>

          <div class="servicio-proveedor">
            <h3>👤 Proveedor</h3>
            <div class="proveedor-card">
              ${servicio.proveedor?.foto ? `<img src="${servicio.proveedor.foto}" class="proveedor-foto" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'100\\' height=\\'100\\'%3E%3Crect width=\\'100\\' height=\\'100\\' fill=\\'%23ddd\\'/%3E%3Ctext x=\\'50\\' y=\\'50\\' text-anchor=\\'middle\\' fill=\\'%23666\\' font-size=\\'40\\'%3E👤%3C/text%3E%3C/svg%3E'">` : ''}
              <div class="proveedor-info">
                <p><strong>${servicio.proveedor?.nombre || 'Información no disponible'}</strong></p>
                ${servicio.proveedor?.descripcion ? `<p>${servicio.proveedor.descripcion}</p>` : ''}
              </div>
            </div>
          </div>

          <div class="servicio-acciones">
            ${esDueno ? `
              <button class="btn-primary btn-editar">✏️ Editar Servicio</button>
              <button class="btn-eliminar btn-eliminar-servicio">🗑️ Eliminar Servicio</button>
            ` : `
              <button class="btn-secondary btn-perfil">👤 Ver Perfil del Proveedor</button>
            `}
          </div>
        </div>
      </div>
    `;

    // Event listeners
    if (esDueno) {
      document.querySelector('.btn-editar')?.addEventListener('click', () => {
        window.location.href = `editarServicio.html?id=${servicio.id}`;
      });
      document.querySelector('.btn-eliminar-servicio')?.addEventListener('click', () => {
        if (confirm('⚠️ ¿Deseas eliminar este servicio?')) eliminarServicio(servicio.id);
      });
    } else {
      document.querySelector('.btn-perfil')?.addEventListener('click', () => {
        if (servicio.proveedor?.idUsuario) {
          sessionStorage.setItem('vistaOrigen','proveedor');
          window.location.href = `../../apps/Views/verPerfil.html?id=${servicio.proveedor.idUsuario}`;
        }
      });
    }

    // Galería fotos
    const miniaturas = document.querySelectorAll('.foto-miniatura');
    const imagenPrincipal = document.querySelector('.servicio-imagen > img');
    miniaturas.forEach(m => m.addEventListener('click', () => imagenPrincipal && (imagenPrincipal.src = m.src)));

    // Cargar reseñas (solo lectura) y mostrar contenedor específico para proveedor
    try {
      const idToUse = servicio.id || servicio.IdServicio;
      if (idToUse) cargarComentariosProveedor(idToUse);
    } catch (e) {
      console.warn('No se pudo iniciar carga de reseñas para proveedor', e);
    }
  }

  // Cargar y renderizar reseñas para la vista proveedor (solo lectura)
  function cargarComentariosProveedor(idServicio) {
    const contenedorGlobal = document.getElementById('contenedorResenasProveedor');
    const listaComentarios = document.getElementById('listaComentariosProveedor');
    if (!contenedorGlobal || !listaComentarios) return;
    contenedorGlobal.style.display = 'block';
    listaComentarios.innerHTML = '<p class="cargando-comentarios">Cargando comentarios...</p>';

    fetch(`/proyecto/apps/Controllers/reseñaController.php?accion=obtenerPorServicio&idServicio=${idServicio}`, {
      method: 'GET',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.text())
    .then(text => {
      let data;
      try { data = JSON.parse(text); } catch (e) { console.error('Error parseando reseñas:', e, text); listaComentarios.innerHTML = ''; listaComentarios.appendChild(document.getElementById('template-sin-comentarios-prov').content.cloneNode(true)); return; }
      if (!data.success || !data.data || data.data.length === 0) {
        listaComentarios.innerHTML = '';
        listaComentarios.appendChild(document.getElementById('template-sin-comentarios-prov').content.cloneNode(true));
        return;
      }

      // Renderizar cada reseña
      listaComentarios.innerHTML = '';
      const tpl = document.getElementById('template-comentario-item-prov');
      data.data.forEach(resena => {
        const item = tpl.content.cloneNode(true);
        const fotoEl = item.querySelector('.usuario-foto');
        let fotoUsuario = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"%3E%3Cpath fill="%23cccccc" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/%3E%3C/svg%3E';
        if (resena.usuario && resena.usuario.foto) {
          if (!resena.usuario.foto.startsWith('/')) {
            fotoUsuario = `/proyecto/public/recursos/imagenes/perfil/${resena.usuario.foto}`;
          } else {
            fotoUsuario = resena.usuario.foto;
          }
        }
        fotoEl.src = fotoUsuario;
        item.querySelector('.usuario-nombre').textContent = resena.usuario?.nombreCompleto || '';
        item.querySelector('.comentario-texto').textContent = resena.comentario || '';
        // Fecha
        try {
          const fecha = new Date(resena.fecha);
          item.querySelector('.comentario-fecha').textContent = fecha.toLocaleDateString('es-ES', { year:'numeric', month:'long', day:'numeric' });
        } catch (e) { item.querySelector('.comentario-fecha').textContent = resena.fecha || ''; }
        // Rating
        const estrellas = '★'.repeat(resena.puntuacion) + '☆'.repeat(5 - resena.puntuacion);
        item.querySelector('.comentario-rating').textContent = estrellas;

        listaComentarios.appendChild(item);
      });
    })
    .catch(err => {
      console.error('Error al cargar reseñas proveedor:', err);
      listaComentarios.innerHTML = '';
      listaComentarios.appendChild(document.getElementById('template-sin-comentarios-prov').content.cloneNode(true));
    });
  }
  async function eliminarServicio(idServicio) {
    try {
      const res = await fetch("../../apps/Controllers/servicioController.php", {
        method: "POST",
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `eliminar=true&idServicio=${idServicio}`
      });
      const text = await res.text();
      const data = JSON.parse(text);

      if (!data.success) throw new Error(data.message || data.error || 'Error desconocido');

      alert('✅ Servicio eliminado correctamente');
      window.location.href = 'PANTALLA_PUBLICAR.html';
    } catch (err) {
      console.error(err);
      alert('❌ Error al eliminar el servicio: ' + err.message);
    }
  }

  function mostrarError(msg, detalle='') {
    contenedor.innerHTML = `
      <div class="error">
        <h2>Error</h2>
        <p>${msg}</p>
        ${detalle ? `<details><summary>Detalles técnicos</summary><pre>${detalle}</pre></details>` : ''}
        <button class="btn-primary" onclick="window.location.href='PANTALLA_PUBLICAR.html'">Volver a Servicios</button>
      </div>
    `;
  }
});
