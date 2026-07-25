document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("loginForm");
  const usuarioInput = document.getElementById("usuario");
  const passwordInput = document.getElementById("password");
  const togglePasswordBtn = document.getElementById("togglePassword");
  const messageBox = document.getElementById("messageBox");

  function mostrarMensaje(texto, esError = false) {
    messageBox.textContent = texto;
    messageBox.style.color = esError ? "#b00020" : "#155724";
    messageBox.style.backgroundColor = esError ? "#fdecea" : "#eafaf1";
    messageBox.style.border = esError
      ? "1px solid #f5c6cb"
      : "1px solid #c3e6cb";
  }

  togglePasswordBtn.addEventListener("click", () => {
    const esPassword = passwordInput.type === "password";
    passwordInput.type = esPassword ? "text" : "password";
    togglePasswordBtn.textContent = esPassword ? "Ocultar" : "Mostrar";
  });

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const usuario = usuarioInput.value.trim();
    const password = passwordInput.value;

    if (!usuario || !password) {
      mostrarMensaje("Completa usuario y contraseña.", true);
      return;
    }

    try {
      mostrarMensaje("Validando credenciales...");

      const response = await fetch("/login-uvi", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        credentials: "include",
        body: JSON.stringify({
          usuario,
          password
        })
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        mostrarMensaje(data.message || "No se pudo iniciar sesión.", true);
        return;
      }

      mostrarMensaje("Inicio de sesión correcto. Redirigiendo...");

      if (data.redirect) {
        window.location.href = data.redirect;
      } else {
        mostrarMensaje("No se recibió una ruta de redirección.", true);
      }
    } catch (error) {
      console.error("Error en login UVI:", error);
      mostrarMensaje("Error de conexión con el servidor.", true);
    }
  });
});