document.addEventListener('DOMContentLoaded', () => {
  // Encuentra todos los inputs tipo password dentro de .input-group
  const passwordInputs = document.querySelectorAll('.input-group input[type="password"]');

  passwordInputs.forEach(input => {
    // evitar duplicados si ya existe el botón
    if (input.parentElement.querySelector('.btn-toggle-pass')) return;

    // crear botón
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn-toggle-pass';
    btn.setAttribute('aria-label', 'Mostrar contraseña');
    btn.innerHTML = '👁️'; // ícono simple; podés cambiar por SVG

    // click toggle
    btn.addEventListener('click', () => {
      const isPwd = input.type === 'password';
      input.type = isPwd ? 'text' : 'password';
      btn.setAttribute('aria-label', isPwd ? 'Ocultar contraseña' : 'Mostrar contraseña');
      btn.innerHTML = isPwd ? '🙈' : '👁️';
      // mantener foco en el input
      input.focus();
      // mover el cursor al final (para compatibilidad)
      if (typeof input.selectionStart === 'number') {
        const len = input.value.length;
        input.setSelectionRange(len, len);
      }
    });

    // Insertar el botón dentro del .input-group
    const wrapper = input.parentElement;
    wrapper.style.position = wrapper.style.position || 'relative';
    wrapper.appendChild(btn);
  });
});