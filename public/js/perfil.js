document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formPerfil");
    const contactosDiv = document.getElementById("contactos");
    const habilidadesDiv = document.getElementById("habilidades");
    const fotoInput = document.getElementById('foto');
    const fotoActualDiv = document.getElementById('fotoActual');

    // Inicializar la carga de datos
    cargarDatos();

    // =================================================================
    // GESTIÓN DE EVENTOS
    // =================================================================

    // 1. Envío del Formulario (Guardar)
    form.addEventListener("submit", (e) => {
        e.preventDefault();
        const formData = new FormData(form);

        fetch("../../apps/controllers/perfilController.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Actualizar la UI con los datos recién guardados (incluidas las rutas de fotos, etc.)
                mostrarDatos(data); 
                alert("✅ Perfil actualizado correctamente.");
            } else {
                // Mostrar el mensaje de error que idealmente proviene del servidor
                alert(`❌ Error al guardar: ${data.message || 'Inténtalo de nuevo.'}`);
            }
        })
        .catch(error => console.error('Error en el fetch de guardar:', error));
    });

    // 2. Previsualización de la foto
    fotoInput.addEventListener('change', (event) => {
        const input = event.target;
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                // Muestra la vista previa de la nueva foto
                fotoActualDiv.innerHTML = `<img src="${e.target.result}" alt="Vista previa" style="max-width: 100px; border-radius: 50%;">`;
            };

            reader.readAsDataURL(input.files[0]);
        }
    });

    // =================================================================
    // FUNCIONES DE CARGA Y MOSTRAR DATOS
    // =================================================================

    // Función para obtener los datos iniciales del servidor
    function cargarDatos() {
        console.log('Iniciando carga de datos...');
        // Petición GET al controlador
        fetch("../../apps/Controllers/perfilController.php")
            .then(res => {
                console.log('Respuesta recibida:', res.status, res.statusText);
                if (!res.ok) throw new Error('Respuesta de red no válida');
                return res.text(); // Primero obtenemos el texto crudo
            })
            .then(texto => {
                console.log('Respuesta cruda del servidor:', texto);
                const data = JSON.parse(texto); // Luego lo parseamos a JSON
                console.log('Datos parseados:', JSON.stringify(data, null, 2));
                
                if (data.success === false) {
                    alert('Error al cargar los datos: ' + (data.message || 'Error desconocido'));
                    return;
                }
                mostrarDatos(data);
            })
            .catch(error => {
                console.error('Error al cargar los datos:', error);
                alert('Error al cargar los datos del perfil. Por favor, recarga la página.');
            });
    }

    let isLoadingData = false;

    // Función para rellenar el formulario (y sus secciones dinámicas)
    function mostrarDatos(data) {
        if (isLoadingData) return; // Evitar múltiples cargas simultáneas
        isLoadingData = true;
        
        console.log('Mostrando datos:', data); // Para depuración
        
        // A. Campos estáticos
        if (data.usuario) {
            const campos = {
                "nombre": data.usuario.nombre,
                "apellido": data.usuario.apellido,
                "email": data.usuario.email,
                "descripcion": data.usuario.descripcion
            };

            // Iterar sobre cada campo y establecer su valor
            for (const [id, valor] of Object.entries(campos)) {
                const elemento = document.getElementById(id);
                if (elemento) {
                    elemento.value = valor || '';
                    console.log(`Campo ${id} establecido a:`, valor); // Para depuración
                } else {
                    console.error(`Elemento ${id} no encontrado`);
                }
            }
            
            // Mostrar foto actual
            if (data.usuario.rutaFoto) {
                const rutaFoto = data.usuario.rutaFoto.startsWith('http') ? 
                    data.usuario.rutaFoto : 
                    '../../' + data.usuario.rutaFoto.replace(/^\//, '');
                fotoActualDiv.innerHTML = `<img src="${rutaFoto}" alt="Foto actual" style="max-width: 100px; border-radius: 50%;">`;
                console.log('Foto establecida:', rutaFoto);
            } else {
                console.log('No hay foto de perfil');
                fotoActualDiv.innerHTML = '<p>Sin foto de perfil</p>';
            }
        }

        // B. Contactos Dinámicos
        contactosDiv.innerHTML = ""; // Limpia el contenedor
        if (data.contactos && data.contactos.length > 0) {
            data.contactos.forEach(c => crearCampoContacto(c.Tipo, c.Contacto));
        }
        // Botón de añadir (siempre se recrea)
        contactosDiv.innerHTML += '<button type="button" id="addContacto" class="btn-add">➕ Añadir Contacto</button>';

        // C. Habilidades Dinámicas
        habilidadesDiv.innerHTML = ""; // Limpia el contenedor
        if (data.habilidades && data.habilidades.length > 0) {
            data.habilidades.forEach(h => crearCampoHabilidad(h.Habilidad, h.AniosExperiencia));
        }
        // Botón de añadir (siempre se recrea)
        habilidadesDiv.innerHTML += '<button type="button" id="addHabilidad" class="btn-add">➕ Añadir Habilidad</button>';
        
        // Re-adjuntar listeners a los botones dinámicos
        document.getElementById('addContacto').addEventListener('click', () => crearCampoContacto('', ''));
        document.getElementById('addHabilidad').addEventListener('click', () => crearCampoHabilidad('', ''));
        
        isLoadingData = false; // Permitir nuevas cargas
    }

    // =================================================================
    // FUNCIONES DE CREACIÓN DE CAMPOS DE EDICIÓN
    // =================================================================

    // Añade un grupo de inputs para un contacto
    function crearCampoContacto(tipo = '', contacto = '') {
        const div = document.createElement('div');
        div.className = 'item-group contacto-item';
        div.innerHTML = `
            <input type="text" name="Tipos[]" placeholder="Tipo (Ej: Teléfono)" value="${tipo}" required>
            <input type="text" name="Contactos[]" placeholder="Contacto (Ej: 123-456)" value="${contacto}" required>
            <button type="button" class="remove-item btn-delete">X</button>
        `;
        // Adjunta el listener de eliminación inmediatamente
        div.querySelector('.remove-item').addEventListener('click', (e) => e.target.closest('.item-group').remove());
        
        // Insertar el nuevo campo antes del botón de añadir (si existe, o al final)
        const addButton = document.getElementById('addContacto');
        contactosDiv.insertBefore(div, addButton || null);
    }

    // Añade un grupo de inputs para una habilidad
    function crearCampoHabilidad(habilidad = '', anios = '') {
        const div = document.createElement('div');
        div.className = 'item-group habilidad-item';
        // Asegúrate de que el name="Anios[]" acepta 0 como valor
        div.innerHTML = `
            <input type="text" name="Habilidades[]" placeholder="Habilidad (Ej: JavaScript)" value="${habilidad}" required>
            <input type="number" name="Anios[]" placeholder="Años Exp." value="${anios}" min="0">
            <button type="button" class="remove-item btn-delete">X</button>
        `;
        // Adjunta el listener de eliminación inmediatamente
        div.querySelector('.remove-item').addEventListener('click', (e) => e.target.closest('.item-group').remove());
        
        // Insertar el nuevo campo antes del botón de añadir (si existe, o al final)
        const addButton = document.getElementById('addHabilidad');
        habilidadesDiv.insertBefore(div, addButton || null);
    }
});