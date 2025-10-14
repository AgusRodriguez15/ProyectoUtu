document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('../../apps/Controllers/loginController.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Guardar información relevante en localStorage si es necesario
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
                    alert(data.message || 'Error al iniciar sesión');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            }
        });
    }
});