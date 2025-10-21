document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id') || sessionStorage.getItem('servicioId');
  if (!id) {
    alert('No se especificó el servicio a editar');
    window.location.href = '../Views/PANTALLA_PUBLICAR.html';
    return;
  }

  document.getElementById('idServicio').value = id;
  cargarServicio(id);

  const form = document.getElementById('formEditarServicio');
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    guardarCambios();
  });

  // Event listener para nuevas fotos
  document.getElementById('nuevasFotos').addEventListener('change', manejarNuevasFotos);

  // Event listener para palabras clave
  document.getElementById('inputPalabra').addEventListener('keypress', manejarInputPalabra);
  
  // Event listener para el toggle del estado
  document.getElementById('estado').addEventListener('change', actualizarEstadoVisual);
});

let fotosAEliminar = [];
let fotosActuales = [];
let palabrasClaveActuales = [];
let ubicacionesActuales = [];
let ubicacionesAEliminar = [];
let nuevasUbicaciones = [];

function cargarServicio(id) {
  fetch('../../apps/Controllers/servicioController.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + encodeURIComponent(id)
  })
    .then(r => r.json())
    .then(data => {
      if (data.error) throw new Error(data.error);
      document.getElementById('nombre').value = data.nombre || data.titulo || '';
      document.getElementById('descripcion').value = data.descripcion || '';
      document.getElementById('precio').value = data.precio !== null ? data.precio : '0';
      document.getElementById('divisa').value = data.divisa || 'UYU';
      
      // Configurar el toggle switch del estado
      const estadoToggle = document.getElementById('estado');
      estadoToggle.checked = (data.estado === 'DISPONIBLE');
      
      // Actualizar el estado visual inicial
      setTimeout(actualizarEstadoVisual, 100);
      
      // Cargar fotos actuales
      fotosActuales = data.fotos || [];
      mostrarFotosActuales();
      
      // Cargar palabras clave actuales
      palabrasClaveActuales = data.palabrasClave || [];
      mostrarPalabrasClave();
      
      // Cargar ubicaciones actuales
      ubicacionesActuales = data.ubicaciones || [];
      mostrarUbicaciones();
    })
    .catch(err => {
      console.error('Error cargando servicio:', err);
      alert('No se pudo cargar el servicio');
      history.back();
    });
}

function guardarCambios() {
  const id = document.getElementById('idServicio').value;
  const nombre = document.getElementById('nombre').value.trim();
  const descripcion = document.getElementById('descripcion').value.trim();
  const precio = document.getElementById('precio').value;
  const divisa = document.getElementById('divisa').value;
  const estado = document.getElementById('estado').checked ? 'DISPONIBLE' : 'NO_DISPONIBLE';

  if (!nombre) {
    alert('El título es obligatorio');
    return;
  }

  if (precio === '') {
    alert('El precio es obligatorio (puede ser 0)');
    return;
  }

  // Usar FormData para enviar archivos
  const formData = new FormData();
  formData.append('actualizar', 'true');
  formData.append('id', id);
  formData.append('nombre', nombre);
  formData.append('descripcion', descripcion);
  formData.append('precio', precio);
  formData.append('divisa', divisa);
  formData.append('estado', estado);
  
  // Agregar fotos a eliminar
  if (fotosAEliminar.length > 0) {
    formData.append('fotosAEliminar', JSON.stringify(fotosAEliminar));
  }
  
  // Agregar nuevas fotos
  const nuevasFotos = document.getElementById('nuevasFotos').files;
  for (let i = 0; i < nuevasFotos.length; i++) {
    formData.append('nuevasFotos[]', nuevasFotos[i]);
  }
  
  // Agregar palabras clave
  if (palabrasClaveActuales.length > 0) {
    formData.append('palabrasClave', JSON.stringify(palabrasClaveActuales));
  }
  
  // Agregar ubicaciones a eliminar
  console.log('📍 Ubicaciones a eliminar:', ubicacionesAEliminar);
  if (ubicacionesAEliminar.length > 0) {
    formData.append('ubicacionesAEliminar', JSON.stringify(ubicacionesAEliminar));
  }
  
  // Agregar nuevas ubicaciones
  console.log('📍 Nuevas ubicaciones a enviar:', nuevasUbicaciones);
  if (nuevasUbicaciones.length > 0) {
    const ubicacionesJSON = JSON.stringify(nuevasUbicaciones);
    formData.append('nuevasUbicaciones', ubicacionesJSON);
    console.log('✅ Agregadas', nuevasUbicaciones.length, 'ubicaciones al FormData');
    console.log('📦 JSON enviado:', ubicacionesJSON);
  } else {
    console.warn('⚠️ No hay nuevas ubicaciones para enviar');
  }

  // Log de todo el FormData
  console.log('📤 Contenido completo del FormData:');
  for (let pair of formData.entries()) {
    if (pair[0] === 'nuevasUbicaciones') {
      console.log('  📍', pair[0] + ':', pair[1]);
    } else {
      console.log('  -', pair[0] + ':', typeof pair[1] === 'object' ? '[File]' : pair[1]);
    }
  }

  fetch('../../apps/Controllers/servicioController.php', {
    method: 'POST',
    body: formData
  })
    .then(response => {
      // Mantener debug temporalmente
      console.log('Response status:', response.status);
      console.log('Response headers:', response.headers.get('content-type'));
      
      return response.text().then(text => {
        console.log('Response text:', text);
        
        if (!text.trim()) {
          throw new Error('El servidor devolvió una respuesta vacía');
        }
        
        try {
          return JSON.parse(text);
        } catch (e) {
          throw new Error('Respuesta no válida del servidor: ' + text.substring(0, 200));
        }
      });
    })
    .then(data => {
      if (!data.success) throw new Error(data.message || data.error || 'No se pudo actualizar');
      alert('Servicio actualizado correctamente');
      window.location.href = '../Views/PANTALLA_PUBLICAR.html';
    })
    .catch(err => {
      console.error('Error guardando:', err);
      alert('Error al actualizar el servicio: ' + err.message);
    });
}

function mostrarFotosActuales() {
  const container = document.getElementById('fotosActuales');
  
  if (!fotosActuales || fotosActuales.length === 0) {
    container.innerHTML = '<div class="sin-fotos">📷 Este servicio no tiene fotos</div>';
    return;
  }
  
  container.innerHTML = '';
  fotosActuales.forEach((foto, index) => {
    const div = document.createElement('div');
    div.className = 'foto-actual';
    div.innerHTML = `
      <img src="${foto}" alt="Foto del servicio" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'150\\' height=\\'150\\'%3E%3Crect width=\\'150\\' height=\\'150\\' fill=\\'%23ddd\\'/%3E%3Ctext x=\\'75\\' y=\\'75\\' text-anchor=\\'middle\\' fill=\\'%23666\\' font-size=\\'20\\'%3E❌%3C/text%3E%3C/svg%3E'">
      <button type="button" class="btn-eliminar-foto" onclick="eliminarFotoActual(${index})" title="Eliminar foto">
        ✕
      </button>
    `;
    container.appendChild(div);
  });
}

function eliminarFotoActual(index) {
  if (confirm('¿Estás seguro de que quieres eliminar esta foto?')) {
    const fotoEliminada = fotosActuales[index];
    fotosAEliminar.push(fotoEliminada);
    fotosActuales.splice(index, 1);
    mostrarFotosActuales();
  }
}

function manejarNuevasFotos(event) {
  const files = event.target.files;
  const previewContainer = document.getElementById('previewNuevas');
  
  // Limpiar preview anterior
  previewContainer.innerHTML = '';
  
  Array.from(files).forEach((file, index) => {
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = (e) => {
        const div = document.createElement('div');
        div.className = 'preview-imagen';
        div.innerHTML = `
          <img src="${e.target.result}" alt="Nueva foto">
          <button type="button" class="btn-quitar" onclick="quitarNuevaFoto(${index})" title="Quitar">
            ✕
          </button>
        `;
        previewContainer.appendChild(div);
      };
      reader.readAsDataURL(file);
    }
  });
}

function quitarNuevaFoto(index) {
  const input = document.getElementById('nuevasFotos');
  const dt = new DataTransfer();
  
  // Recrear FileList sin el archivo eliminado
  Array.from(input.files).forEach((file, i) => {
    if (i !== index) {
      dt.items.add(file);
    }
  });
  
  input.files = dt.files;
  
  // Actualizar preview
  manejarNuevasFotos({ target: input });
}

function mostrarPalabrasClave() {
  const container = document.getElementById('palabrasClaveActuales');
  
  if (!palabrasClaveActuales || palabrasClaveActuales.length === 0) {
    container.innerHTML = '<div class="sin-palabras">🏷️ Sin palabras clave</div>';
    return;
  }
  
  container.innerHTML = '';
  palabrasClaveActuales.forEach((palabra, index) => {
    const span = document.createElement('span');
    span.className = 'palabra-tag';
    span.innerHTML = `
      ${palabra}
      <button type="button" class="btn-eliminar-palabra" onclick="eliminarPalabraClave(${index})" title="Eliminar">
        ✕
      </button>
    `;
    container.appendChild(span);
  });
}

function manejarInputPalabra(event) {
  if (event.key === 'Enter') {
    event.preventDefault();
    const input = event.target;
    const palabra = input.value.trim();
    
    if (palabra === '') {
      return;
    }
    
    // Verificar que no esté duplicada
    if (palabrasClaveActuales.includes(palabra.toLowerCase())) {
      alert('Esta palabra clave ya existe');
      input.value = '';
      return;
    }
    
    // Agregar la palabra
    palabrasClaveActuales.push(palabra);
    input.value = '';
    mostrarPalabrasClave();
  }
}

function eliminarPalabraClave(index) {
  palabrasClaveActuales.splice(index, 1);
  mostrarPalabrasClave();
}

function actualizarEstadoVisual() {
  const toggle = document.getElementById('estado');
  const labelActivo = document.querySelector('.estado-label.activo');
  const labelInactivo = document.querySelector('.estado-label.inactivo');
  
  if (toggle.checked) {
    // Estado ACTIVO
    labelActivo.style.opacity = '1';
    labelActivo.style.transform = 'scale(1.1)';
    labelInactivo.style.opacity = '0.5';
    labelInactivo.style.transform = 'scale(1)';
  } else {
    // Estado INACTIVO
    labelActivo.style.opacity = '0.5';
    labelActivo.style.transform = 'scale(1)';
    labelInactivo.style.opacity = '1';
    labelInactivo.style.transform = 'scale(1.1)';
  }
}

// ==================== FUNCIONES PARA UBICACIONES ====================

function mostrarUbicaciones() {
  const container = document.getElementById('ubicacionesActuales');
  
  // Combinar ubicaciones actuales (no eliminadas) y nuevas
  const todasUbicaciones = [
    ...ubicacionesActuales.filter(ub => !ubicacionesAEliminar.includes(ub.idUbicacion)),
    ...nuevasUbicaciones
  ];
  
  if (todasUbicaciones.length === 0) {
    container.innerHTML = '<div class="sin-ubicaciones">📍 Este servicio no tiene ubicaciones</div>';
    return;
  }
  
  container.innerHTML = '';
  
  todasUbicaciones.forEach((ubicacion, index) => {
    const esNueva = !ubicacion.idUbicacion;
    const div = document.createElement('div');
    div.className = 'ubicacion-actual-item';
    
    // Construir texto descriptivo
    let textoPrincipal = ubicacion.direccion || ubicacion.pais;
    let textoDetalles = [];
    
    if (ubicacion.direccion && ubicacion.ciudad) {
      textoPrincipal = ubicacion.direccion;
      textoDetalles.push(ubicacion.ciudad);
    } else if (ubicacion.ciudad) {
      if (ubicacion.direccion) {
        textoDetalles.push(ubicacion.ciudad);
      } else {
        textoPrincipal = ubicacion.ciudad;
      }
    }
    
    div.innerHTML = `
      <div class="ubicacion-actual-info">
        <div class="ubicacion-actual-icono">📍</div>
        <div class="ubicacion-actual-texto">
          <div class="ubicacion-actual-principal">${textoPrincipal}</div>
          ${textoDetalles.length > 0 ? `<div class="ubicacion-actual-detalles">${textoDetalles.join(' • ')}</div>` : ''}
        </div>
      </div>
      <button type="button" class="btn-eliminar-ubicacion" onclick="eliminarUbicacion(${esNueva ? -1 : ubicacion.idUbicacion}, ${esNueva}, ${index})" title="Eliminar ubicación">
        🗑️ Eliminar
      </button>
    `;
    
    container.appendChild(div);
  });
}

function agregarUbicacion() {
  const pais = document.getElementById('paisEdit').value.trim();
  const ciudad = document.getElementById('ciudadEdit').value.trim();
  const calle = document.getElementById('calleEdit').value.trim();
  const numero = document.getElementById('numeroEdit').value.trim();
  
  // Validar país obligatorio
  if (!pais) {
    alert('❌ El país es obligatorio');
    return;
  }
  
  // Validar jerarquía
  if (calle && !ciudad) {
    alert('❌ Si especificas una calle, debes especificar la ciudad');
    return;
  }
  
  if (numero && !calle) {
    alert('❌ Si especificas un número, debes especificar la calle');
    return;
  }
  
  // Construir objeto de ubicación
  const nuevaUbicacion = {
    pais: pais,
    ciudad: ciudad || '',
    calle: calle || '',
    numero: numero || ''
  };
  
  // Construir direccion para mostrar
  if (calle) {
    nuevaUbicacion.direccion = calle + (numero ? ' ' + numero : '');
  }
  
  // Verificar duplicados
  const esDuplicada = [...ubicacionesActuales, ...nuevasUbicaciones].some(ub => {
    return ub.pais === pais && 
           (ub.ciudad || '') === ciudad && 
           (ub.calle || '') === calle && 
           (ub.numero || '') === numero;
  });
  
  if (esDuplicada) {
    alert('⚠️ Esta ubicación ya existe');
    return;
  }
  
  // Agregar a la lista de nuevas ubicaciones
  nuevasUbicaciones.push(nuevaUbicacion);
  
  // Limpiar campos
  document.getElementById('paisEdit').value = '';
  document.getElementById('ciudadEdit').value = '';
  document.getElementById('calleEdit').value = '';
  document.getElementById('numeroEdit').value = '';
  
  // Actualizar vista
  mostrarUbicaciones();
}

function eliminarUbicacion(idUbicacion, esNueva, index) {
  if (!confirm('¿Estás seguro de que quieres eliminar esta ubicación?')) {
    return;
  }
  
  if (esNueva) {
    // Es una ubicación nueva que aún no se ha guardado
    // Calcular el índice real en el array de nuevas ubicaciones
    const indiceReal = index - ubicacionesActuales.filter(ub => !ubicacionesAEliminar.includes(ub.idUbicacion)).length;
    nuevasUbicaciones.splice(indiceReal, 1);
  } else {
    // Es una ubicación existente en la BD
    if (!ubicacionesAEliminar.includes(idUbicacion)) {
      ubicacionesAEliminar.push(idUbicacion);
    }
  }
  
  mostrarUbicaciones();
}

// Función para eliminar el servicio
function eliminarServicio() {
  const id = document.getElementById('idServicio').value;
  
  if (!confirm('⚠️ ¿Estás seguro de que deseas eliminar este servicio? Esta acción no se puede deshacer.')) {
    return;
  }

  const formData = new FormData();
  formData.append('eliminar', 'true');
  formData.append('idServicio', id);

  fetch('../../apps/Controllers/servicioController.php', {
    method: 'POST',
    body: formData
  })
    .then(response => {
      console.log('Response status:', response.status);
      
      return response.text().then(text => {
        console.log('Response text:', text);
        
        if (!text.trim()) {
          throw new Error('El servidor devolvió una respuesta vacía');
        }
        
        try {
          return JSON.parse(text);
        } catch (e) {
          throw new Error('Respuesta no válida del servidor: ' + text.substring(0, 200));
        }
      });
    })
    .then(data => {
      console.log('Data recibida:', data);
      if (!data.success) {
        const errorMsg = data.message || data.error || 'Error desconocido';
        console.error('Error del servidor:', errorMsg);
        throw new Error(errorMsg);
      }
      alert('✅ Servicio eliminado correctamente');
      window.location.href = '../Views/PANTALLA_PUBLICAR.html';
    })
    .catch(err => {
      console.error('Error eliminando:', err);
      alert('❌ Error al eliminar el servicio: ' + err.message);
    });
}
