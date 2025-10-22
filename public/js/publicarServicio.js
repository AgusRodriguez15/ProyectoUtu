// Constantes de configuración
const CONFIG = {
    MAX_CATEGORIAS: 3,
    MAX_PALABRAS_CLAVE: 5,
    MAX_FOTOS: 5,
    MAX_UBICACIONES: 10,
    TIEMPO_MENSAJE: 3000,
    TIPOS_IMAGEN_PERMITIDOS: ['image/jpeg', 'image/png', 'image/gif'],
    MAX_TAMANO_FOTO: 5 * 1024 * 1024 // 5MB
};

document.addEventListener("DOMContentLoaded", () => {
const formulario = document.getElementById('formPublicarServicio');
const buscarCategoriaInput = document.getElementById('buscarCategoria');
const listaCategorias = document.getElementById('listaCategorias');
const categoriasSeleccionadas = document.getElementById('categoriasSeleccionadas');
const palabrasClaveInput = document.getElementById('palabrasClave');
const listaPalabrasClave = document.getElementById('listaPalabrasClave');
const inputFotos = document.getElementById('inputFotos');
const previewContainer = document.getElementById('previewFotos');

// Elementos de ubicación
const paisUbicacion = document.getElementById('paisUbicacion');
const ciudadUbicacion = document.getElementById('ciudadUbicacion');
const calleUbicacion = document.getElementById('calleUbicacion');
const numeroUbicacion = document.getElementById('numeroUbicacion');
const btnAgregarUbicacion = document.getElementById('btnAgregarUbicacion');
const listaUbicaciones = document.getElementById('listaUbicaciones');

// Elementos de disponibilidad
const fechaInicio = document.getElementById('fechaInicio');
const fechaFin = document.getElementById('fechaFin');
const estadoDisponibilidad = document.getElementById('estadoDisponibilidad');
const btnAgregarDisponibilidad = document.getElementById('btnAgregarDisponibilidad');
const listaDisponibilidades = document.getElementById('listaDisponibilidades');

let palabrasClave = [];
let categorias = [];
let categoriasElegidas = [];
let fotosSeleccionadas = [];
let ubicacionesAgregadas = [];
let disponibilidadesAgregadas = [];

// ==================== MÉTODOS PARA MANEJAR CATEGORÍAS ====================
const manejadorCategorias = {
    agregar: function(categoria) {
        console.log('Agregando categoría:', categoria);
        if (!this.puedeAgregar(categoria)) {
            return false;
        }
        categoriasElegidas.push({
            id: categoria.IdCategoria,
            nombre: categoria.Nombre
        });
        console.log('Categorías:', categoriasElegidas);
        return true;
    },
    puedeAgregar: function(categoria) {
        if (categoriasElegidas.length >= CONFIG.MAX_CATEGORIAS) {
            alert(`Máximo ${CONFIG.MAX_CATEGORIAS} categorías permitidas`);
            return false;
        }
        return !this.existeCategoria(categoria.IdCategoria);
    },
    existeCategoria: function(id) {
        return categoriasElegidas.some(cat => cat.id === id);
    },
    eliminar: function(id) {
        console.log('Eliminando categoría:', id);
        categoriasElegidas = categoriasElegidas.filter(cat => cat.id !== id);
    },
    obtenerIds: function() {
        return categoriasElegidas.map(cat => cat.id);
    }
};

// ==================== MÉTODOS PARA MANEJAR PALABRAS CLAVE ====================
const manejadorPalabrasClave = {
    agregar: function(palabra) {
        palabra = palabra.trim();
        console.log('Agregando palabra:', palabra);
        if (!this.puedeAgregar(palabra)) {
            return false;
        }
        palabrasClave.push(palabra);
        console.log('Palabras clave:', palabrasClave);
        return true;
    },
    puedeAgregar: function(palabra) {
        if (palabra.length === 0) return false;
        if (palabrasClave.length >= CONFIG.MAX_PALABRAS_CLAVE) {
            alert(`Máximo ${CONFIG.MAX_PALABRAS_CLAVE} palabras clave`);
            return false;
        }
        return !this.existePalabra(palabra);
    },
    existePalabra: function(palabra) {
        return palabrasClave.some(p => p.toLowerCase() === palabra.toLowerCase());
    },
    eliminar: function(palabra) {
        palabrasClave = palabrasClave.filter(p => p !== palabra);
    },
    obtenerTodas: function() {
        return palabrasClave;
    }
};

// ==================== MÉTODOS PARA MANEJAR FOTOS ====================
const manejadorFotos = {
    agregar: function(file, dataUrl) {
        console.log('Agregando foto:', file.name);
        if (!this.puedeAgregar()) {
            alert(`Máximo ${CONFIG.MAX_FOTOS} fotos`);
            return -1;
        }
        fotosSeleccionadas.push({ file: file, dataUrl: dataUrl });
        return fotosSeleccionadas.length - 1;
    },
    puedeAgregar: function() {
        return fotosSeleccionadas.length < CONFIG.MAX_FOTOS;
    },
    validarArchivo: function(file) {
        if (!file.type.startsWith('image/')) {
            throw new Error(`${file.name} no es una imagen válida`);
        }
        if (file.size > CONFIG.MAX_TAMANO_FOTO) {
            throw new Error(`${file.name} excede 5MB`);
        }
        return true;
    },
    eliminar: function(index) {
        console.log('Eliminando foto:', index);
        fotosSeleccionadas.splice(index, 1);
    },
    obtenerArchivos: function() {
        return fotosSeleccionadas.map(foto => foto.file);
    }
};

// ==================== MÉTODOS PARA MANEJAR UBICACIONES ====================
const manejadorUbicaciones = {
    agregar: function(ubicacion) {
        console.log('Agregando ubicación:', ubicacion);
        if (!this.puedeAgregar()) {
            alert(`Máximo ${CONFIG.MAX_UBICACIONES} ubicaciones`);
            return false;
        }
        if (!this.validarUbicacion(ubicacion)) {
            return false;
        }
        if (this.esRepetida(ubicacion)) {
            alert('Esta ubicación ya está agregada');
            return false;
        }
        ubicacionesAgregadas.push(ubicacion);
        console.log('Ubicaciones:', ubicacionesAgregadas);
        return true;
    },
    puedeAgregar: function() {
        return ubicacionesAgregadas.length < CONFIG.MAX_UBICACIONES;
    },
    validarUbicacion: function(ubicacion) {
        // País es obligatorio
        if (!ubicacion.pais || ubicacion.pais.trim() === '') {
            alert('El país es obligatorio');
            return false;
        }
        
        // Validar jerarquía: si un campo tiene valor, el anterior no puede estar vacío
        // Jerarquía: País → Ciudad → Calle → Número
        
        if (ubicacion.calle && (!ubicacion.ciudad || ubicacion.ciudad.trim() === '')) {
            alert('Si especificas una calle, debes especificar la ciudad');
            return false;
        }
        
        if (ubicacion.numero && (!ubicacion.calle || ubicacion.calle.trim() === '')) {
            alert('Si especificas un número, debes especificar la calle');
            return false;
        }
        
        return true;
    },
    esRepetida: function(nuevaUbicacion) {
        // Comparar con ubicaciones existentes
        return ubicacionesAgregadas.some(ubicacion => {
            return this.sonIguales(ubicacion, nuevaUbicacion);
        });
    },
    sonIguales: function(ub1, ub2) {
        // Normalizar valores (trim y lowercase para comparación)
        const normalizar = (val) => (val || '').trim().toLowerCase();
        
        return normalizar(ub1.pais) === normalizar(ub2.pais) &&
               normalizar(ub1.ciudad) === normalizar(ub2.ciudad) &&
               normalizar(ub1.calle) === normalizar(ub2.calle) &&
               normalizar(ub1.numero) === normalizar(ub2.numero);
    },
    eliminar: function(index) {
        console.log('Eliminando ubicación:', index);
        ubicacionesAgregadas.splice(index, 1);
    },
    obtenerTodas: function() {
        return ubicacionesAgregadas;
    },
    formatearParaMostrar: function(ubicacion) {
        // Crear texto descriptivo de la ubicación con jerarquía correcta
        const partes = [];
        
        // Orden: País → Ciudad → Calle Número
        if (ubicacion.pais) {
            partes.push(ubicacion.pais);
        }
        
        if (ubicacion.ciudad) {
            partes.push(ubicacion.ciudad);
        }
        
        if (ubicacion.calle) {
            let direccion = ubicacion.calle;
            if (ubicacion.numero) {
                direccion += ' ' + ubicacion.numero;
            }
            partes.push(direccion);
        } else if (ubicacion.numero) {
            partes.push('N° ' + ubicacion.numero);
        }
        
        return partes.length > 0 ? partes : ['Ubicación sin especificar'];
    }
};

// ==================== MÉTODOS PARA MANEJAR DISPONIBILIDADES ====================
const manejadorDisponibilidades = {
    agregar: function(disponibilidad) {
        console.log('Agregando disponibilidad:', disponibilidad);
        if (!this.validarDisponibilidad(disponibilidad)) {
            return false;
        }
        if (this.hayConflicto(disponibilidad)) {
            alert('Ya existe una disponibilidad que se superpone con este horario');
            return false;
        }
        disponibilidadesAgregadas.push(disponibilidad);
        console.log('Disponibilidades:', disponibilidadesAgregadas);
        return true;
    },
    validarDisponibilidad: function(disponibilidad) {
        // Validar que las fechas existan
        if (!disponibilidad.fechaInicio || !disponibilidad.fechaFin) {
            alert('Debes especificar fecha y hora de inicio y fin');
            return false;
        }
        
        const inicio = new Date(disponibilidad.fechaInicio);
        const fin = new Date(disponibilidad.fechaFin);
        
        // Validar que la fecha de inicio sea antes que la de fin
        if (inicio >= fin) {
            alert('La fecha de inicio debe ser anterior a la fecha de fin');
            return false;
        }
        
        // Validar que no sea en el pasado
        const ahora = new Date();
        if (inicio < ahora) {
            alert('No puedes agregar disponibilidades en el pasado');
            return false;
        }
        
        return true;
    },
    hayConflicto: function(nuevaDisp) {
        const nuevoInicio = new Date(nuevaDisp.fechaInicio);
        const nuevoFin = new Date(nuevaDisp.fechaFin);
        
        return disponibilidadesAgregadas.some(disp => {
            const dispInicio = new Date(disp.fechaInicio);
            const dispFin = new Date(disp.fechaFin);
            
            // Verificar si hay superposición
            return (nuevoInicio < dispFin && nuevoFin > dispInicio);
        });
    },
    eliminar: function(index) {
        console.log('Eliminando disponibilidad:', index);
        disponibilidadesAgregadas.splice(index, 1);
    },
    obtenerTodas: function() {
        return disponibilidadesAgregadas;
    },
    formatearFecha: function(fechaStr) {
        const fecha = new Date(fechaStr);
        const opciones = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        };
        return fecha.toLocaleString('es-UY', opciones);
    },
    obtenerNombreEstado: function(estado) {
        const nombres = {
            'disponible': 'Disponible',
            'ocupado': 'Ocupado',
            'no_disponible': 'No Disponible'
        };
        return nombres[estado] || estado;
    }
};

// ==================== CARGAR CATEGORÍAS ====================
async function cargarCategorias() {
    console.log('Cargando categorías desde el servidor...');
    
    try {
        const params = new URLSearchParams();
        params.append('action', 'obtenerTodas');
        
        const response = await fetch('/proyecto/apps/Controllers/categoriaController.php?' + params.toString(), {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        console.log('Respuesta recibida:', response);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Datos recibidos:', result);
        
        if (result.success && Array.isArray(result.data)) {
            categorias = result.data;
            console.log(`${categorias.length} categorías cargadas`);
        } else {
            console.error('Error en la respuesta:', result);
            alert('Error al cargar las categorías');
        }
    } catch (error) {
        console.error('Error al cargar categorías:', error);
        alert('No se pudieron cargar las categorías. Por favor, recargue la página.');
    }
}

// ==================== MOSTRAR CATEGORÍAS FILTRADAS ====================
function mostrarCategoriasFiltradas(filtro) {
    console.log('Filtrando categorías con:', filtro);
    
    // Filtrar categorías que coincidan y no estén seleccionadas
    const categoriasFiltradas = categorias.filter(cat => 
        cat.Nombre.toLowerCase().includes(filtro.toLowerCase()) &&
        !manejadorCategorias.existeCategoria(cat.IdCategoria)
    );
    
    listaCategorias.innerHTML = '';
    
    if (categoriasFiltradas.length === 0 && filtro.length > 0) {
        const div = document.createElement('div');
        div.className = 'categoria-opcion sin-resultados';
        div.textContent = 'No se encontraron categorías';
        listaCategorias.appendChild(div);
    } else {
        categoriasFiltradas.forEach(categoria => {
            const div = document.createElement('div');
            div.className = 'categoria-opcion';
            div.textContent = categoria.Nombre;
            div.title = categoria.Descripcion || '';
            div.onclick = () => seleccionarCategoria(categoria);
            listaCategorias.appendChild(div);
        });
    }
    
    listaCategorias.classList.toggle('active', filtro.length > 0);
}

// ==================== SELECCIONAR CATEGORÍA ====================
function seleccionarCategoria(categoria) {
    console.log('Seleccionando categoría:', categoria);
    
    if (manejadorCategorias.agregar(categoria)) {
        // Crear elemento visual
        const div = document.createElement('div');
        div.className = 'categoria-tag';
        div.innerHTML = `
            ${categoria.Nombre}
            <button type="button" class="eliminar" data-id="${categoria.IdCategoria}">&times;</button>
        `;
        
        // Agregar evento para eliminar
        div.querySelector('.eliminar').onclick = function() {
            manejadorCategorias.eliminar(categoria.IdCategoria);
            div.remove();
            
            // Actualizar la lista si hay filtro activo
            if (buscarCategoriaInput.value.length > 0) {
                mostrarCategoriasFiltradas(buscarCategoriaInput.value);
            }
        };
        
        categoriasSeleccionadas.appendChild(div);
        
        // Limpiar búsqueda
        buscarCategoriaInput.value = '';
        listaCategorias.classList.remove('active');
    }
}

// ==================== EVENTOS DE BÚSQUEDA DE CATEGORÍAS ====================
if (buscarCategoriaInput) {
    buscarCategoriaInput.addEventListener('input', (e) => {
        mostrarCategoriasFiltradas(e.target.value);
    });
    
    buscarCategoriaInput.addEventListener('blur', () => {
        // Delay para permitir que el click se registre
        setTimeout(() => {
            listaCategorias.classList.remove('active');
        }, 200);
    });
    
    buscarCategoriaInput.addEventListener('focus', () => {
        if (buscarCategoriaInput.value) {
            mostrarCategoriasFiltradas(buscarCategoriaInput.value);
        }
    });
}

// ==================== EVENTOS DE PALABRAS CLAVE ====================
if (palabrasClaveInput) {
    palabrasClaveInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const palabra = palabrasClaveInput.value.trim();
            
            if (palabra && manejadorPalabrasClave.agregar(palabra)) {
                // Crear elemento visual
                const div = document.createElement('div');
                div.className = 'palabra-clave';
                div.innerHTML = `
                    ${palabra}
                    <button type="button" class="btn-eliminar">&times;</button>
                `;
                
                // Agregar evento para eliminar
                div.querySelector('.btn-eliminar').onclick = function() {
                    manejadorPalabrasClave.eliminar(palabra);
                    div.remove();
                };
                
                listaPalabrasClave.appendChild(div);
                palabrasClaveInput.value = '';
            }
        }
    });
}

// ==================== EVENTOS DE UBICACIONES ====================
if (btnAgregarUbicacion) {
    btnAgregarUbicacion.addEventListener('click', () => {
        const ubicacion = {
            pais: paisUbicacion.value.trim(),
            ciudad: ciudadUbicacion.value.trim(),
            calle: calleUbicacion.value.trim(),
            numero: numeroUbicacion.value.trim()
        };
        
        if (manejadorUbicaciones.agregar(ubicacion)) {
            actualizarListaUbicaciones();
            
            // Limpiar campos
            paisUbicacion.value = '';
            ciudadUbicacion.value = '';
            calleUbicacion.value = '';
            numeroUbicacion.value = '';
        }
    });
}

// Función para actualizar la lista visual de ubicaciones
function actualizarListaUbicaciones() {
    listaUbicaciones.innerHTML = '';
    
    ubicacionesAgregadas.forEach((ubicacion, index) => {
        const partes = manejadorUbicaciones.formatearParaMostrar(ubicacion);
        
        const div = document.createElement('div');
        div.className = 'ubicacion-item';
        div.innerHTML = `
            <div class="ubicacion-info">
                <div class="ubicacion-principal">${partes[0]}</div>
                ${partes.length > 1 ? `<div class="ubicacion-detalles">${partes.slice(1).join(' • ')}</div>` : ''}
            </div>
            <button type="button" class="btn-eliminar-ubicacion" data-index="${index}">
                🗑️ Eliminar
            </button>
        `;
        
        // Agregar evento para eliminar
        div.querySelector('.btn-eliminar-ubicacion').onclick = function() {
            manejadorUbicaciones.eliminar(index);
            actualizarListaUbicaciones();
        };
        
        listaUbicaciones.appendChild(div);
    });
}

// ==================== EVENTOS DE DISPONIBILIDADES ====================
if (btnAgregarDisponibilidad) {
    btnAgregarDisponibilidad.addEventListener('click', () => {
        const disponibilidad = {
            fechaInicio: fechaInicio.value,
            fechaFin: fechaFin.value,
            estado: estadoDisponibilidad.value
        };
        
        if (manejadorDisponibilidades.agregar(disponibilidad)) {
            actualizarListaDisponibilidades();
            
            // Limpiar campos
            fechaInicio.value = '';
            fechaFin.value = '';
            estadoDisponibilidad.value = 'disponible';
        }
    });
}

// Función para actualizar la lista visual de disponibilidades
function actualizarListaDisponibilidades() {
    listaDisponibilidades.innerHTML = '';
    
    disponibilidadesAgregadas.forEach((disponibilidad, index) => {
        const div = document.createElement('div');
        div.className = 'disponibilidad-item';
        div.innerHTML = `
            <div class="disponibilidad-info">
                <div class="disponibilidad-fechas">
                    Horario ${index + 1}
                </div>
                <span class="disponibilidad-inicio">📍 Inicio: ${manejadorDisponibilidades.formatearFecha(disponibilidad.fechaInicio)}</span>
                <span class="disponibilidad-fin">🏁 Fin: ${manejadorDisponibilidades.formatearFecha(disponibilidad.fechaFin)}</span>
                <span class="disponibilidad-estado ${disponibilidad.estado}">
                    ${manejadorDisponibilidades.obtenerNombreEstado(disponibilidad.estado)}
                </span>
            </div>
            <button type="button" class="btn-eliminar-disponibilidad" data-index="${index}">
                🗑️ Eliminar
            </button>
        `;
        
        // Agregar evento para eliminar
        div.querySelector('.btn-eliminar-disponibilidad').onclick = function() {
            manejadorDisponibilidades.eliminar(index);
            actualizarListaDisponibilidades();
        };
        
        listaDisponibilidades.appendChild(div);
    });
}

// ==================== EVENTOS DE FOTOS ====================
if (inputFotos) {
    inputFotos.addEventListener('change', function(e) {
        const archivos = Array.from(this.files);
        console.log('Archivos seleccionados:', archivos.length);
        
        archivos.forEach(file => {
            try {
                // Validar archivo
                manejadorFotos.validarArchivo(file);
                
                // Leer archivo
                const reader = new FileReader();
                reader.onload = function(e) {
                    const index = manejadorFotos.agregar(file, e.target.result);
                    
                    if (index >= 0) {
                        // Crear previsualización
                        const previewDiv = document.createElement('div');
                        previewDiv.className = 'preview-imagen';
                        previewDiv.innerHTML = `
                            <img src="${e.target.result}" alt="Foto ${index + 1}">
                            <button type="button" class="btn-eliminar" data-index="${index}">&times;</button>
                            <div class="contador">Foto ${index + 1}</div>
                        `;
                        
                        // Agregar evento para eliminar
                        previewDiv.querySelector('.btn-eliminar').onclick = function() {
                            manejadorFotos.eliminar(index);
                            actualizarPrevisualizacionesFotos();
                        };
                        
                        previewContainer.appendChild(previewDiv);
                    }
                };
                reader.readAsDataURL(file);
            } catch (error) {
                alert(error.message);
            }
        });
        
        // Limpiar input
        this.value = '';
    });
}

// ==================== ACTUALIZAR PREVISUALIZACIONES ====================
function actualizarPrevisualizacionesFotos() {
    previewContainer.innerHTML = '';
    
    manejadorFotos.obtenerArchivos().forEach((file, index) => {
        const foto = fotosSeleccionadas[index];
        
        const previewDiv = document.createElement('div');
        previewDiv.className = 'preview-imagen';
        previewDiv.innerHTML = `
            <img src="${foto.dataUrl}" alt="Foto ${index + 1}">
            <button type="button" class="btn-eliminar" data-index="${index}">&times;</button>
            <div class="contador">Foto ${index + 1}</div>
        `;
        
        previewDiv.querySelector('.btn-eliminar').onclick = function() {
            manejadorFotos.eliminar(index);
            actualizarPrevisualizacionesFotos();
        };
        
        previewContainer.appendChild(previewDiv);
    });
}

// ==================== INICIALIZACIÓN ====================
// Cargar categorías al iniciar
cargarCategorias();

// Funciones auxiliares
formulario.addEventListener("submit", async (e) => {
    e.preventDefault();
    console.log('Enviando formulario...');
    
    try {
        const formData = new FormData(formulario);

        // Validar que hay al menos una categoría
        const categorias = manejadorCategorias.obtenerIds();
        if (categorias.length === 0) {
            alert('❌ Debe seleccionar al menos una categoría');
            return;
        }

        // Validar que hay al menos una foto
        const fotos = manejadorFotos.obtenerArchivos();
        if (fotos.length === 0) {
            alert('❌ Debe subir al menos una foto');
            return;
        }

        // Agregar categorías usando el manejador
        categorias.forEach(id => {
            formData.append('categoria[]', id);
        });
        console.log('Categorías agregadas:', categorias);

        // Agregar palabras clave usando el manejador
        const palabras = manejadorPalabrasClave.obtenerTodas();
        if (palabras.length > 0) {
            formData.append('palabrasClave', palabras.join(','));
            console.log('Palabras clave agregadas:', palabras);
        }

        // Agregar ubicaciones usando el manejador
        const ubicaciones = manejadorUbicaciones.obtenerTodas();
        if (ubicaciones.length > 0) {
            const ubicacionesJSON = JSON.stringify(ubicaciones);
            formData.append('ubicaciones', ubicacionesJSON);
            console.log('Ubicaciones agregadas:', ubicaciones);
            console.log('Ubicaciones JSON:', ubicacionesJSON);
        } else {
            console.log('No hay ubicaciones para agregar');
        }

        // Agregar disponibilidades usando el manejador
        const disponibilidades = manejadorDisponibilidades.obtenerTodas();
        if (disponibilidades.length > 0) {
            const disponibilidadesJSON = JSON.stringify(disponibilidades);
            formData.append('disponibilidades', disponibilidadesJSON);
            console.log('Disponibilidades agregadas:', disponibilidades);
            console.log('Disponibilidades JSON:', disponibilidadesJSON);
        } else {
            console.log('No hay disponibilidades para agregar');
        }

        // Agregar fotos usando el manejador
        fotos.forEach(file => {
            formData.append('fotos[]', file);
        });
        console.log('Fotos agregadas:', fotos.length);

        // Verificar FormData - método simplificado
        const camposFormData = [];
        for (let pair of formData.entries()) {
            camposFormData.push(pair[0]); // Solo el nombre del campo
        }
        
        console.log('========== RESUMEN FORMDATA ==========');
        console.log('Total de campos:', camposFormData.length);
        console.log('Campos presentes:', camposFormData.join(', '));
        console.log('¿Tiene ubicaciones?', camposFormData.includes('ubicaciones'));
        
        // Si tiene ubicaciones, mostrar su contenido
        if (camposFormData.includes('ubicaciones')) {
            // Obtener el valor de ubicaciones del FormData
            const ubicacionesValue = formData.get('ubicaciones');
            console.log('✅ Ubicaciones se están enviando:');
            console.log(ubicacionesValue);
        } else {
            console.log('❌ ERROR: Ubicaciones NO está en FormData');
        }
        console.log('======================================');

        const response = await fetch("/proyecto/apps/Controllers/publicarServicioController.php", {
            method: "POST",
            body: formData
        });

        console.log('Respuesta HTTP:', response.status, response.statusText);

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
        }

        // Obtener el texto crudo primero para ver qué está devolviendo
        const textoRespuesta = await response.text();
        console.log('Respuesta cruda del servidor:', textoRespuesta);

        // Intentar parsear como JSON
        let data;
        try {
            data = JSON.parse(textoRespuesta);
            console.log('Respuesta JSON parseada:', data);
        } catch (e) {
            console.error('Error al parsear JSON:', e);
            console.error('Respuesta que causó el error:', textoRespuesta.substring(0, 500));
            throw new Error('El servidor no devolvió una respuesta JSON válida. Ver consola para detalles.');
        }
        
        if (data.success) {
            alert("✅ Servicio publicado exitosamente.");
            setTimeout(() => {
                window.location.href = '/proyecto/apps/Views/PANTALLA_PUBLICAR.html';
            }, 2000);
        } else {
            alert(`❌ Error: ${data.message || 'Inténtalo de nuevo.'}`);
        }
    } catch (error) {
        console.error('Error completo:', error);
        console.error('Error stack:', error.stack);
        alert(`❌ Error al enviar los datos: ${error.message}`);
    }
});

});