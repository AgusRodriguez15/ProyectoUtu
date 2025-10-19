document.addEventListener("DOMContentLoaded", () => {
  const contenedor = document.getElementById("contenidoDetalle");
  
  // Obtener el ID del servicio desde sessionStorage
  const servicioId = sessionStorage.getItem('servicioId');
  
  if (!servicioId) {
    contenedor.innerHTML = `
      <div class="error">
        <h2>Error</h2>
        <p>No se ha seleccionado ningún servicio.</p>
        <button class="btn-primary" onclick="window.location.href='PANTALLA_CONTRATAR.html'">
          Volver a Servicios
        </button>
      </div>
    `;
    return;
  }
  
  // Cargar los detalles del servicio
  cargarDetalleServicio(servicioId);
  
  function cargarDetalleServicio(id) {
    fetch("../../apps/Controllers/servicioController.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id)
    })
      .then(res => {
        // Primero obtener el texto de la respuesta
        return res.text();
      })
      .then(text => {
        console.log('Respuesta del servidor:', text);
        
        // Intentar parsear como JSON
        try {
          const data = JSON.parse(text);
          
          console.log('Datos parseados:', data);
          console.log('Proveedor:', data.proveedor);
          console.log('Contactos:', data.proveedor?.contactos);
          console.log('Habilidades:', data.proveedor?.habilidades);
          
          if (data.error) {
            contenedor.innerHTML = `
              <div class="error">
                <h2>Error</h2>
                <p>${data.error}</p>
                <button class="btn-primary" onclick="window.location.href='PANTALLA_CONTRATAR.html'">
                  Volver a Servicios
                </button>
              </div>
            `;
            return;
          }
          
          mostrarDetalle(data);
        } catch (e) {
          console.error('Error al parsear JSON:', e);
          console.error('Respuesta recibida:', text);
          contenedor.innerHTML = `
            <div class="error">
              <h2>Error del Servidor</h2>
              <p>El servidor devolvió una respuesta inválida.</p>
              <details>
                <summary>Ver detalles técnicos</summary>
                <pre style="max-height: 300px; overflow: auto; background: #f5f5f5; padding: 1rem; border-radius: 4px;">${text}</pre>
              </details>
              <button class="btn-primary" onclick="window.location.href='PANTALLA_CONTRATAR.html'">
                Volver a Servicios
              </button>
            </div>
          `;
        }
      })
      .catch(err => {
        console.error("Error:", err);
        contenedor.innerHTML = `
          <div class="error">
            <h2>Error</h2>
            <p>No se pudo cargar el detalle del servicio.</p>
            <button class="btn-primary" onclick="window.location.href='PANTALLA_CONTRATAR.html'">
              Volver a Servicios
            </button>
          </div>
        `;
      });
  }
  
  function mostrarDetalle(servicio) {
    // Clonar el template principal
    const template = document.getElementById('template-servicio-detalle');
    const servicioElement = template.content.cloneNode(true);
    
    // Imagen principal
    const imagenPrincipal = servicioElement.querySelector('.imagen-principal');
    imagenPrincipal.src = servicio.foto;
    imagenPrincipal.alt = servicio.nombre;
    imagenPrincipal.onerror = function() {
      this.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='600' height='400'%3E%3Crect width='600' height='400' fill='%23ccc'/%3E%3Ctext x='300' y='200' text-anchor='middle' fill='%23666' font-size='24'%3ESin Imagen%3C/text%3E%3C/svg%3E";
    };
    
    // Galería de fotos
    if (servicio.fotos && servicio.fotos.length > 0) {
      const galeriaContainer = servicioElement.querySelector('.galeria-fotos');
      const templateFoto = document.getElementById('template-foto-miniatura');
      
      servicio.fotos.forEach(foto => {
        const fotoElement = templateFoto.content.cloneNode(true);
        const img = fotoElement.querySelector('.foto-miniatura');
        img.src = foto;
        galeriaContainer.appendChild(fotoElement);
      });
    }
    
    // Información básica
    servicioElement.querySelector('.servicio-nombre').textContent = servicio.nombre;
    
    // Estado
    const estadoBadge = servicioElement.querySelector('.estado');
    const estado = servicio.estado || 'DISPONIBLE';
    estadoBadge.textContent = estado;
    estadoBadge.classList.add(`estado-${estado.toLowerCase()}`);
    
    // Fecha
    let fechaFormateada = 'Fecha no disponible';
    if (servicio.fechaPublicacion) {
      const fecha = new Date(servicio.fechaPublicacion);
      fechaFormateada = fecha.toLocaleDateString('es-ES', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      });
    }
    servicioElement.querySelector('.fecha-publicacion').textContent = `📅 Publicado: ${fechaFormateada}`;
    
    // Precio
    const precioContainer = servicioElement.querySelector('.servicio-precio');
    const precioDisplay = servicioElement.querySelector('.precio-display');
    
    if (servicio.precio !== undefined && servicio.precio !== null) {
      if (servicio.precio > 0) {
        precioDisplay.innerHTML = `<span class="precio-valor">$${parseFloat(servicio.precio).toLocaleString('es-UY', {minimumFractionDigits: 2, maximumFractionDigits: 2})} ${servicio.divisa || 'UYU'}</span>`;
      } else {
        precioDisplay.innerHTML = '<span class="precio-gratuito">🆓 Servicio Gratuito</span>';
      }
    } else {
      precioContainer.style.display = 'none';
    }
    
    // Descripción
    servicioElement.querySelector('.servicio-descripcion').textContent = servicio.descripcion || 'Sin descripción disponible';
    
    // Proveedor
    if (servicio.proveedor) {
      const prov = servicio.proveedor;
      
      // Foto del proveedor
      if (prov.foto) {
        const fotoContainer = servicioElement.querySelector('.proveedor-foto');
        const fotoImg = servicioElement.querySelector('.proveedor-imagen');
        fotoContainer.style.display = 'block';
        
        // Si la foto NO empieza con /, es solo el nombre del archivo
        if (!prov.foto.startsWith('/')) {
          fotoImg.src = `/proyecto/public/recursos/imagenes/perfil/${prov.foto}`;
        } else {
          // Si empieza con /, usar tal cual (para compatibilidad con datos antiguos)
          fotoImg.src = prov.foto;
        }
        
        fotoImg.onerror = function() {
          this.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3Crect width='100' height='100' fill='%23ddd'/%3E%3Ctext x='50' y='50' text-anchor='middle' fill='%23666' font-size='40'%3E👤%3C/text%3E%3C/svg%3E";
        };
      }
      
      // Nombre del proveedor
      servicioElement.querySelector('.proveedor-nombre').innerHTML = `<strong>${prov.nombre || 'Información no disponible'}</strong>`;
      
      // Descripción del proveedor
      if (prov.descripcion) {
        const descripcionElement = servicioElement.querySelector('.proveedor-descripcion');
        descripcionElement.textContent = prov.descripcion;
        descripcionElement.style.display = 'block';
      }
      
      // Contactos
      if (prov.contactos && prov.contactos.length > 0) {
        const contactosContainer = servicioElement.querySelector('.proveedor-contactos');
        const contactosLista = servicioElement.querySelector('.contactos-lista');
        const templateContacto = document.getElementById('template-contacto-item');
        
        prov.contactos.forEach(c => {
          const contactoElement = templateContacto.content.cloneNode(true);
          contactoElement.querySelector('.contacto-tipo').textContent = c.tipo + ':';
          contactoElement.querySelector('.contacto-valor').textContent = c.contacto;
          contactosLista.appendChild(contactoElement);
        });
        
        contactosContainer.style.display = 'block';
      }
      
      // Habilidades
      if (prov.habilidades && prov.habilidades.length > 0) {
        const habilidadesContainer = servicioElement.querySelector('.proveedor-habilidades');
        const habilidadesTags = servicioElement.querySelector('.habilidades-tags');
        const templateHabilidad = document.getElementById('template-habilidad-tag');
        
        prov.habilidades.forEach(h => {
          const habilidadElement = templateHabilidad.content.cloneNode(true);
          const tag = habilidadElement.querySelector('.habilidad-tag');
          tag.textContent = `${h.habilidad}${h.experiencia > 0 ? ` (${h.experiencia} ${h.experiencia === 1 ? 'año' : 'años'})` : ''}`;
          habilidadesTags.appendChild(habilidadElement);
        });
        
        habilidadesContainer.style.display = 'block';
      }
    }
    
    // Limpiar y agregar al contenedor
    contenedor.innerHTML = '';
    contenedor.appendChild(servicioElement);
    
    // Configurar event listeners después de agregar al DOM
    configurarEventListeners(servicio);
  }
  
  function configurarEventListeners(servicio) {
    const servicioId = sessionStorage.getItem('servicioId');
    
    // Event listeners para clientes
    document.querySelector('.btn-contratar')?.addEventListener('click', () => {
      alert('Función de contratación en desarrollo');
    });
    
    document.querySelector('.btn-mensaje')?.addEventListener('click', () => {
      alert('Función de mensajería en desarrollo');
    });
    
    document.querySelector('.btn-perfil')?.addEventListener('click', () => {
      if (servicio.proveedor?.idUsuario) {
        sessionStorage.setItem('vistaOrigen', 'cliente');
        window.location.href = `../../apps/Views/verPerfil.html?id=${servicio.proveedor.idUsuario}`;
      } else {
        alert('No se puede ver el perfil en este momento');
      }
    });
    
    // Event listeners para la galería de fotos
    const miniaturas = document.querySelectorAll('.foto-miniatura');
    const imagenPrincipal = document.querySelector('.imagen-principal');
    
    miniaturas.forEach(miniatura => {
      miniatura.addEventListener('click', () => {
        if (imagenPrincipal) {
          imagenPrincipal.src = miniatura.src;
        }
      });
    });
    
    // Event listeners para las pestañas
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
      button.addEventListener('click', () => {
        const tabName = button.getAttribute('data-tab');
        
        // Desactivar todas las pestañas
        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));
        
        // Activar la pestaña seleccionada
        button.classList.add('active');
        document.getElementById(`tab-${tabName}`).classList.add('active');
        
        // Si es la pestaña de comentarios, cargar los comentarios
        if (tabName === 'comentarios') {
          cargarComentarios(servicioId);
          verificarResenaUsuario(servicioId);
        }
      });
    });

    // Sistema de calificación con estrellas
    let calificacionSeleccionada = 0;
    const stars = document.querySelectorAll('.star');
    
    function actualizarEstrellas(rating) {
      stars.forEach((star, index) => {
        if (index < rating) {
          star.classList.add('selected');
        } else {
          star.classList.remove('selected');
        }
      });
    }
    
    stars.forEach(star => {
      star.addEventListener('click', () => {
        calificacionSeleccionada = parseInt(star.getAttribute('data-rating'));
        actualizarEstrellas(calificacionSeleccionada);
      });
      
      star.addEventListener('mouseenter', () => {
        const rating = parseInt(star.getAttribute('data-rating'));
        actualizarEstrellas(rating);
      });
    });
    
    document.querySelector('.stars').addEventListener('mouseleave', () => {
      actualizarEstrellas(calificacionSeleccionada);
    });

    // Event listener para enviar comentario
    document.querySelector('.btn-enviar-comentario')?.addEventListener('click', () => {
      const texto = document.getElementById('comentarioTexto').value.trim();
      
      if (!texto) {
        alert('Por favor, escribe un comentario');
        return;
      }
      
      if (calificacionSeleccionada === 0) {
        alert('Por favor, selecciona una calificación');
        return;
      }
      
      // Validaciones
      if (texto.length < 10) {
        alert('El comentario debe tener al menos 10 caracteres');
        return;
      }

      // Enviar al backend
      fetch('/proyecto/apps/Controllers/reseñaController.php?accion=agregar', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          comentario: texto,
          puntuacion: parseInt(calificacionSeleccionada),
          idServicio: parseInt(servicioId)
        })
      })
      .then(response => response.text())
      .then(text => {
        console.log('Response del servidor:', text);
        
        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          console.error('Error al parsear JSON:', e);
          console.error('Respuesta recibida:', text);
          alert('Error al procesar la respuesta del servidor');
          return;
        }
        
        if (!data.success) {
          alert(data.mensaje || 'Error al enviar el comentario');
          return;
        }

        // Mostrar mensaje de éxito
        alert('¡Comentario enviado exitosamente!');

        // Limpiar el formulario
        const comentarioTexto = document.getElementById('comentarioTexto');
        if (comentarioTexto) {
          comentarioTexto.value = '';
        }
        
        calificacionSeleccionada = 0;
        actualizarEstrellas(0);

        // Recargar los comentarios y verificar estado
        cargarComentarios(servicioId);
        verificarResenaUsuario(servicioId);
      })
      .catch(error => {
        console.error('Error al enviar comentario:', error);
        alert('Error al enviar el comentario. Por favor intenta de nuevo.');
      });
    });
  }

  // Función para cargar comentarios
  function cargarComentarios(idServicio) {
    const listaComentarios = document.getElementById('listaComentarios');
    
    // Hacer petición al servidor para obtener los comentarios
    fetch(`/proyecto/apps/Controllers/reseñaController.php?accion=obtenerPorServicio&idServicio=${idServicio}`, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => {
      console.log('Response status:', response.status);
      console.log('Response headers:', response.headers);
      return response.text(); // Primero obtener como texto para ver qué llega
    })
    .then(text => {
      console.log('Response text:', text);
      
      // Intentar parsear como JSON
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        console.error('Error al parsear JSON:', e);
        console.error('Respuesta recibida:', text);
        const template = document.getElementById('template-error-comentarios');
        listaComentarios.innerHTML = '';
        listaComentarios.appendChild(template.content.cloneNode(true));
        return;
      }
      
      if (!data.success) {
        console.error('Error al cargar comentarios:', data.mensaje);
        const template = document.getElementById('template-error-comentarios');
        listaComentarios.innerHTML = '';
        listaComentarios.appendChild(template.content.cloneNode(true));
        return;
      }

      if (!data.data || data.data.length === 0) {
        const template = document.getElementById('template-sin-comentarios');
        listaComentarios.innerHTML = '';
        listaComentarios.appendChild(template.content.cloneNode(true));
        return;
      }

      // Renderizar comentarios usando template
      listaComentarios.innerHTML = '';
      const templateComentario = document.getElementById('template-comentario-item');
      
      data.data.forEach(resena => {
        const comentarioElement = templateComentario.content.cloneNode(true);
        
        // Configurar datos del comentario
        const fecha = new Date(resena.fecha);
        const fechaFormateada = fecha.toLocaleDateString('es-ES', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });

        const estrellas = '★'.repeat(resena.puntuacion) + '☆'.repeat(5 - resena.puntuacion);

        // URL de la foto del usuario (con fallback a SVG por defecto)
        // Asumimos que en BD solo está el nombre del archivo, no la ruta completa
        let fotoUsuario;
        if (resena.usuario.foto && resena.usuario.foto !== '') {
          // Si la foto NO empieza con /, es solo el nombre del archivo
          if (!resena.usuario.foto.startsWith('/')) {
            fotoUsuario = `/proyecto/public/recursos/imagenes/perfil/${resena.usuario.foto}`;
          } else {
            // Si empieza con /, usar tal cual (para compatibilidad con datos antiguos)
            fotoUsuario = resena.usuario.foto;
          }
        } else {
          fotoUsuario = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"%3E%3Cpath fill="%23cccccc" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/%3E%3C/svg%3E';
        }

        // Rellenar el template
        comentarioElement.querySelector('.usuario-foto').src = fotoUsuario;
        comentarioElement.querySelector('.usuario-foto').alt = resena.usuario.nombreCompleto;
        comentarioElement.querySelector('.usuario-nombre').textContent = resena.usuario.nombreCompleto;
        comentarioElement.querySelector('.comentario-rating').textContent = estrellas;
        comentarioElement.querySelector('.comentario-texto').textContent = resena.comentario;
        comentarioElement.querySelector('.comentario-fecha').textContent = fechaFormateada;
        
        listaComentarios.appendChild(comentarioElement);
      });

      // Cargar y mostrar el promedio
      cargarPromedio(idServicio);
    })
    .catch(error => {
      console.error('Error en la petición:', error);
      const template = document.getElementById('template-error-comentarios');
      listaComentarios.innerHTML = '';
      listaComentarios.appendChild(template.content.cloneNode(true));
    });
  }

  // Función para cargar el promedio de calificación
  function cargarPromedio(idServicio) {
    fetch(`/proyecto/apps/Controllers/reseñaController.php?accion=obtenerPromedio&idServicio=${idServicio}`, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success && data.data) {
        // Puedes mostrar el promedio en alguna parte de la interfaz
        console.log('Promedio de calificación:', data.data);
        // Ejemplo: actualizar un elemento con el promedio
        // const promedioElement = document.getElementById('promedio-calificacion');
        // if (promedioElement) {
        //   promedioElement.textContent = `${data.data.promedio} (${data.data.total} reseñas)`;
        // }
      }
    })
    .catch(error => {
      console.error('Error al cargar promedio:', error);
    });
  }

  // Función para verificar si el usuario ya reseñó
  function verificarResenaUsuario(idServicio) {
    fetch(`/proyecto/apps/Controllers/reseñaController.php?accion=verificarResena&idServicio=${idServicio}`, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success && data.data && data.data.yaReseno) {
        // Ocultar el formulario de comentarios usando template
        const formularioComentario = document.querySelector('.formulario-comentario');
        if (formularioComentario) {
          const template = document.getElementById('template-ya-reseno');
          formularioComentario.innerHTML = '';
          formularioComentario.appendChild(template.content.cloneNode(true));
        }
      }
    })
    .catch(error => {
      console.error('Error al verificar reseña:', error);
    });
  }
});

