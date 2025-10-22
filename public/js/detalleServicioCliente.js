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
          
          // Cargar disponibilidades y configurar modal DESPUÉS de renderizar
          cargarDisponibilidades(servicioId);
          configurarModalReserva(servicioId);
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
    
    // Ubicaciones del servicio
    if (servicio.ubicaciones && servicio.ubicaciones.length > 0) {
      const ubicacionesContainer = servicioElement.querySelector('.servicio-ubicaciones');
      const ubicacionesLista = servicioElement.querySelector('.ubicaciones-lista');
      const templateUbicacion = document.getElementById('template-ubicacion-item');
      
      servicio.ubicaciones.forEach(ub => {
        const ubicacionElement = templateUbicacion.content.cloneNode(true);
        
        // Si hay dirección, mostrarla
        if (ub.direccion) {
          ubicacionElement.querySelector('.ubicacion-direccion').textContent = ub.direccion;
        } else {
          // Si no hay dirección, ocultar ese elemento
          const direccionElement = ubicacionElement.querySelector('.ubicacion-direccion');
          if (direccionElement) {
            direccionElement.style.display = 'none';
          }
        }
        
        // Mostrar ciudad/país
        if (ub.ciudad) {
          ubicacionElement.querySelector('.ubicacion-ciudad').textContent = ub.ciudad;
        } else {
          const ciudadElement = ubicacionElement.querySelector('.ubicacion-ciudad');
          if (ciudadElement) {
            ciudadElement.style.display = 'none';
          }
        }
        
        ubicacionesLista.appendChild(ubicacionElement);
      });
      
      ubicacionesContainer.style.display = 'block';
    }
    
    // Limpiar y agregar al contenedor
    contenedor.innerHTML = '';
    contenedor.appendChild(servicioElement);
    
    // Configurar event listeners después de agregar al DOM
    configurarEventListeners(servicio);
    
    // Inicializar carrusel de ubicaciones si hay más de una
    if (servicio.ubicaciones && servicio.ubicaciones.length > 0) {
      inicializarCarruselUbicaciones(servicio.ubicaciones.length);
    }
  }
  
  function configurarEventListeners(servicio) {
    const servicioId = sessionStorage.getItem('servicioId');
    
    // Event listeners para el botón de mensaje
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

  // Función para inicializar el carrusel de ubicaciones
  function inicializarCarruselUbicaciones(totalUbicaciones) {
    if (totalUbicaciones <= 1) {
      // Si solo hay una ubicación, ocultar los controles
      const controles = document.querySelector('.carrusel-controles');
      if (controles) {
        controles.style.display = 'none';
      }
      return;
    }

    let indiceActual = 0;
    const ubicacionesLista = document.querySelector('.ubicaciones-lista');
    const btnAnterior = document.querySelector('.btn-anterior');
    const btnSiguiente = document.querySelector('.btn-siguiente');
    const indicadoresContainer = document.querySelector('.carrusel-indicadores');
    const contador = document.querySelector('.carrusel-contador');

    // Crear indicadores
    for (let i = 0; i < totalUbicaciones; i++) {
      const indicador = document.createElement('div');
      indicador.classList.add('indicador');
      if (i === 0) indicador.classList.add('activo');
      indicador.addEventListener('click', () => irAUbicacion(i));
      indicadoresContainer.appendChild(indicador);
    }

    // Actualizar contador
    function actualizarContador() {
      contador.textContent = `${indiceActual + 1} / ${totalUbicaciones}`;
    }

    // Actualizar indicadores
    function actualizarIndicadores() {
      const indicadores = document.querySelectorAll('.indicador');
      indicadores.forEach((ind, idx) => {
        if (idx === indiceActual) {
          ind.classList.add('activo');
        } else {
          ind.classList.remove('activo');
        }
      });
    }

    // Actualizar botones
    function actualizarBotones() {
      btnAnterior.disabled = indiceActual === 0;
      btnSiguiente.disabled = indiceActual === totalUbicaciones - 1;
    }

    // Mover el carrusel
    function moverCarrusel() {
      const desplazamiento = -indiceActual * 100;
      ubicacionesLista.style.transform = `translateX(${desplazamiento}%)`;
      actualizarIndicadores();
      actualizarBotones();
      actualizarContador();
    }

    // Ir a una ubicación específica
    function irAUbicacion(indice) {
      indiceActual = indice;
      moverCarrusel();
    }

    // Event listeners para los botones
    btnAnterior.addEventListener('click', () => {
      if (indiceActual > 0) {
        indiceActual--;
        moverCarrusel();
      }
    });

    btnSiguiente.addEventListener('click', () => {
      if (indiceActual < totalUbicaciones - 1) {
        indiceActual++;
        moverCarrusel();
      }
    });

    // Soporte para teclado
    document.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowLeft' && indiceActual > 0) {
        indiceActual--;
        moverCarrusel();
      } else if (e.key === 'ArrowRight' && indiceActual < totalUbicaciones - 1) {
        indiceActual++;
        moverCarrusel();
      }
    });

    // Soporte para gestos táctiles
    let touchStartX = 0;
    let touchEndX = 0;

    ubicacionesLista.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0].screenX;
    });

    ubicacionesLista.addEventListener('touchend', (e) => {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe();
    });

    function handleSwipe() {
      const swipeThreshold = 50;
      const diff = touchStartX - touchEndX;

      if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0 && indiceActual < totalUbicaciones - 1) {
          // Swipe izquierda - siguiente
          indiceActual++;
          moverCarrusel();
        } else if (diff < 0 && indiceActual > 0) {
          // Swipe derecha - anterior
          indiceActual--;
          moverCarrusel();
        }
      }
    }

    // Inicializar
    actualizarBotones();
    actualizarContador();
  }
  
  // Función para cargar y mostrar disponibilidades
  function cargarDisponibilidades(idServicio) {
    const contenedorDisp = document.querySelector('.disponibilidades-lista');
    
    if (!contenedorDisp) {
      console.error('No se encontró el contenedor de disponibilidades');
      return;
    }
    
    fetch(`../../apps/Controllers/disponibilidadController.php?idServicio=${idServicio}`)
      .then(res => res.json())
      .then(data => {
        if (data.success && data.disponibilidades && data.disponibilidades.length > 0) {
          mostrarDisponibilidades(data.disponibilidades);
        } else {
          contenedorDisp.innerHTML = '<p class="sin-disponibilidades">No hay disponibilidades registradas para este servicio.</p>';
        }
      })
      .catch(err => {
        console.error('Error al cargar disponibilidades:', err);
        contenedorDisp.innerHTML = '<p class="sin-disponibilidades">Error al cargar disponibilidades.</p>';
      });
  }
  
  function mostrarDisponibilidades(disponibilidades) {
    const contenedorDisp = document.querySelector('.disponibilidades-lista');
    
    if (!contenedorDisp) {
      console.error('No se encontró el contenedor de disponibilidades');
      return;
    }
    
    contenedorDisp.innerHTML = '';
    
    const template = document.getElementById('template-disponibilidad-item');
    
    if (!template) {
      console.error('No se encontró el template de disponibilidad');
      return;
    }
    
    // Filtrar solo disponibilidades con estado 'disponible' (case insensitive)
    const dispDisponibles = disponibilidades.filter(d => {
      const estado = (d.estado || '').toLowerCase();
      return estado === 'disponible';
    });
    
    console.log('Total disponibilidades recibidas:', disponibilidades.length);
    console.log('Disponibilidades filtradas (disponible):', dispDisponibles.length);
    
    if (dispDisponibles.length === 0) {
      contenedorDisp.innerHTML = '<p class="sin-disponibilidades">No hay horarios disponibles actualmente.</p>';
      return;
    }
    
    dispDisponibles.forEach(disp => {
      const dispElement = template.content.cloneNode(true);
      
      const fechaInicio = new Date(disp.fechaInicio);
      const fechaFin = new Date(disp.fechaFin);
      
      const fechasTexto = `${formatearFecha(fechaInicio)} - ${formatearFecha(fechaFin)}`;
      dispElement.querySelector('.disponibilidad-fechas').textContent = fechasTexto;
      
      const badge = dispElement.querySelector('.badge-estado');
      badge.textContent = '✓ Disponible';
      badge.classList.add('badge', 'disponible');
      
      contenedorDisp.appendChild(dispElement);
    });
  }
  
  function formatearFecha(fecha) {
    const opciones = { 
      year: 'numeric', 
      month: 'short', 
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    };
    return fecha.toLocaleDateString('es-ES', opciones);
  }
  
  // Variables globales para el modal
  let disponibilidadesServicio = [];
  let servicioActualId = null;
  let horarioSeleccionado = null;
  
  // Función para configurar el modal de reserva
  function configurarModalReserva(idServicio) {
    servicioActualId = idServicio;
    const modal = document.getElementById('modalReserva');
    const btnContratar = document.querySelector('.btn-contratar');
    const btnCerrar = document.querySelector('.modal-cerrar');
    const btnCancelar = document.querySelector('.btn-cancelar-reserva');
    const formReserva = document.getElementById('formReserva');
    
    // Validar que todos los elementos existan
    if (!modal || !btnContratar || !btnCerrar || !btnCancelar || !formReserva) {
      console.error('No se encontraron todos los elementos necesarios para el modal');
      return;
    }
    
    // Cargar disponibilidades del servicio
    fetch(`../../apps/Controllers/disponibilidadController.php?idServicio=${idServicio}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // Filtrar solo disponibilidades con estado 'disponible' (case insensitive)
          disponibilidadesServicio = data.disponibilidades.filter(d => {
            const estado = (d.estado || '').toLowerCase();
            return estado === 'disponible';
          });
          console.log('Disponibilidades para modal:', disponibilidadesServicio.length);
          mostrarHorariosDisponibles();
        }
      })
      .catch(err => console.error('Error al cargar disponibilidades:', err));
    
    // Abrir modal
    btnContratar.addEventListener('click', () => {
      modal.style.display = 'flex';
      modal.classList.add('mostrar');
      horarioSeleccionado = null; // Resetear selección
      limpiarFormularioReserva();
    });
    
    // Cerrar modal
    const cerrarModal = () => {
      modal.style.display = 'none';
      modal.classList.remove('mostrar');
      formReserva.reset();
      horarioSeleccionado = null;
      limpiarFormularioReserva();
    };
    
    btnCerrar.addEventListener('click', cerrarModal);
    btnCancelar.addEventListener('click', cerrarModal);
    
    // Cerrar al hacer clic fuera del contenido
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        cerrarModal();
      }
    });
    
    // Manejar envío del formulario
    formReserva.addEventListener('submit', async (e) => {
      e.preventDefault();
      await crearReserva();
    });
  }
  
  function mostrarHorariosDisponibles() {
    const contenedor = document.querySelector('.lista-horarios-disponibles');
    
    if (!contenedor) {
      console.error('No se encontró el contenedor de horarios disponibles');
      return;
    }
    
    contenedor.innerHTML = '';
    
    if (disponibilidadesServicio.length === 0) {
      contenedor.innerHTML = '<p style="color: #666; text-align: center;">No hay horarios disponibles</p>';
      return;
    }
    
    disponibilidadesServicio.forEach((disp, index) => {
      const div = document.createElement('div');
      div.className = 'horario-disponible-item';
      div.dataset.index = index;
      
      const fechaInicio = new Date(disp.fechaInicio);
      const fechaFin = new Date(disp.fechaFin);
      
      div.textContent = `${formatearFecha(fechaInicio)} - ${formatearFecha(fechaFin)}`;
      
      // Agregar evento de clic para seleccionar
      div.addEventListener('click', () => seleccionarHorario(disp, div));
      
      contenedor.appendChild(div);
    });
  }
  
  function seleccionarHorario(disponibilidad, elemento) {
    // Remover selección previa
    document.querySelectorAll('.horario-disponible-item').forEach(item => {
      item.classList.remove('seleccionado');
    });
    
    // Marcar como seleccionado
    elemento.classList.add('seleccionado');
    horarioSeleccionado = disponibilidad;
    
    // Llenar los campos hidden
    const fechaInicio = new Date(disponibilidad.fechaInicio);
    const fechaFin = new Date(disponibilidad.fechaFin);
    
    document.getElementById('fechaInicioReserva').value = formatearParaInput(fechaInicio);
    document.getElementById('fechaFinReserva').value = formatearParaInput(fechaFin);
    
    // Mostrar el cuadro de información
    const infoBox = document.querySelector('.horario-seleccionado-info');
    const textoHorario = document.getElementById('textoHorarioSeleccionado');
    
    if (infoBox && textoHorario) {
      textoHorario.textContent = `${formatearFecha(fechaInicio)} - ${formatearFecha(fechaFin)}`;
      infoBox.style.display = 'block';
    }
  }
  
  function formatearParaInput(fecha) {
    const year = fecha.getFullYear();
    const month = String(fecha.getMonth() + 1).padStart(2, '0');
    const day = String(fecha.getDate()).padStart(2, '0');
    const hours = String(fecha.getHours()).padStart(2, '0');
    const minutes = String(fecha.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
  }
  
  function limpiarFormularioReserva() {
    document.getElementById('fechaInicioReserva').value = '';
    document.getElementById('fechaFinReserva').value = '';
    document.getElementById('observacionReserva').value = '';
    
    // Ocultar el cuadro de información
    const infoBox = document.querySelector('.horario-seleccionado-info');
    if (infoBox) {
      infoBox.style.display = 'none';
    }
    
    // Remover todas las selecciones
    document.querySelectorAll('.horario-disponible-item').forEach(item => {
      item.classList.remove('seleccionado');
    });
  }
  
  async function crearReserva() {
    // Validar que se haya seleccionado un horario
    if (!horarioSeleccionado) {
      alert('Por favor, seleccione un horario disponible');
      return;
    }
    
    const observacion = document.getElementById('observacionReserva').value;
    
    // Crear la reserva con el ID de disponibilidad
    try {
      const response = await fetch('../../apps/Controllers/reservaController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          idServicio: servicioActualId,
          idDisponibilidad: horarioSeleccionado.idDisponibilidad,
          observacion: observacion
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        alert('✓ Reserva creada exitosamente\n\nSu reserva está pendiente de confirmación por el proveedor.');
        document.getElementById('modalReserva').style.display = 'none';
        document.getElementById('modalReserva').classList.remove('mostrar');
        limpiarFormularioReserva();
        horarioSeleccionado = null;
        
        // Recargar disponibilidades
        cargarDisponibilidades(servicioActualId);
      } else {
        alert('Error: ' + data.message);
      }
    } catch (err) {
      console.error('Error al crear reserva:', err);
      alert('Error al procesar la reserva. Por favor, intente nuevamente.');
    }
  }
});
