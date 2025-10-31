document.addEventListener('DOMContentLoaded', function() {
    console.log('Script login.js cargado correctamente');
    
    const loginForm = document.getElementById('loginForm');
    console.log('Formulario encontrado:', loginForm);
    
    if (loginForm) {
        console.log('Event listener agregado al formulario');
        
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('Formulario enviado - preventDefault activado');
            
            const formData = new FormData(this);
            console.log('FormData creado');
            
            try {
                console.log('Enviando petición a loginController.php');
                const response = await fetch('/proyecto/apps/Controllers/loginController.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Respuesta recibida:', response);
                const data = await response.json();
                console.log('JSON parseado:', data);
                
                if (data.success) {
                    console.log('Login exitoso');
                    
                    // Guardar información relevante en sessionStorage
                    sessionStorage.setItem('usuario_rol', data.rol);
                    sessionStorage.setItem('rol', data.rol);
                    sessionStorage.setItem('usuario_nombre', data.nombre);
                    sessionStorage.setItem('IdUsuario', data.idUsuario);
                    
                    // También en localStorage para persistencia (opcional)
                    localStorage.setItem('usuario_rol', data.rol);
                    localStorage.setItem('usuario_nombre', data.nombre);
                    
                    console.log('Redirigiendo a:', data.redirect);
                    
                    // Asegurarse de que la ruta es absoluta desde la raíz del servidor
                    const baseUrl = window.location.origin; // Obtiene http://localhost
                    const redirectUrl = baseUrl + data.redirect;
                    
                    console.log('URL completa:', redirectUrl);
                    window.location.href = redirectUrl;
                } else {
                    // Mostrar mensaje de error
                    console.error('Error en login:', data.message);
                    alert(data.message || 'Error al iniciar sesión');
                }
            } catch (error) {
                console.error('Error en fetch:', error);
                alert('Error al procesar la solicitud: ' + error.message);
            }
        });
    } else {
        console.error('ERROR: No se encontró el formulario con id="loginForm"');
    }
});