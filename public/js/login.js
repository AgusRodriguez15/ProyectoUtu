document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".login-form");
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");

    form.addEventListener("submit", (e) => {
        let errores = [];

        // Validar email
        const emailValue = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailValue === "") {
            errores.push("El email es obligatorio.");
        } else if (!emailRegex.test(emailValue)) {
            errores.push("El email no es válido.");
        }

        // Validar contraseña
        const passwordValue = passwordInput.value.trim();
        if (passwordValue === "") {
            errores.push("La contraseña es obligatoria.");
        } else if (passwordValue.length < 6) {
            errores.push("La contraseña debe tener al menos 6 caracteres.");
        }

        // Mostrar errores si existen
        if (errores.length > 0) {
            e.preventDefault();
            alert(errores.join("\n"));
        }
    });
});
