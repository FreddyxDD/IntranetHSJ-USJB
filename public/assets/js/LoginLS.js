document.addEventListener("DOMContentLoaded", () => {
  if (window.lucide) {
    lucide.createIcons();
  }

  const form = document.getElementById("formLogin");
  const usuarioInput = document.getElementById("usuario");
  const passwordInput = document.getElementById("password");
  const togglePasswordBtn = document.getElementById("togglePassword");
  const recordarUsuario = document.getElementById("recordarUsuario");
  const errorMsg = document.getElementById("error-msg");

  const BASE = window.APP_BASE || "";

  const LOGIN_URL =
    window.LOGIN_LS_URL || `${BASE}/login-ls`;

  const PRINCIPAL_URL =
    window.PRINCIPAL_CIRUGIAS_URL || `${BASE}/principal-cirugias`;

  const ADMIN_URL =
    window.CIRUGIAS_ADMIN_URL || `${BASE}/cirugias-admin`;

  const usuarioGuardado = localStorage.getItem("cirugias_usuario");

  if (usuarioGuardado && usuarioInput) {
    usuarioInput.value = usuarioGuardado;
  }

  if (togglePasswordBtn && passwordInput) {
    togglePasswordBtn.addEventListener("click", () => {
      const esPassword = passwordInput.type === "password";

      passwordInput.type = esPassword ? "text" : "password";

      togglePasswordBtn.innerHTML = esPassword
        ? `<i data-lucide="eye-off"></i>`
        : `<i data-lucide="eye"></i>`;

      if (window.lucide) {
        lucide.createIcons();
      }
    });
  }

  if (!form) {
    console.error("No se encontró el formulario #formLogin");
    return;
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const usuario = usuarioInput.value.trim();
    const password = passwordInput.value;

    limpiarError();

    if (!usuario || !password) {
      mostrarError("Completa todos los campos.");
      return;
    }

    const btnLogin = form.querySelector(".btn-login");
    const textoOriginal = btnLogin ? btnLogin.innerHTML : "";

    try {
      if (btnLogin) {
        btnLogin.disabled = true;
        btnLogin.innerHTML = `
          <i data-lucide="loader-circle"></i>
          Validando...
        `;

        if (window.lucide) {
          lucide.createIcons();
        }
      }

      const res = await fetch(LOGIN_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({
          usuario,
          password,
        }),
      });

      const text = await res.text();

      let data;

      try {
        data = JSON.parse(text);
      } catch (error) {
        console.error("Respuesta no JSON del servidor:", text);

        mostrarError(
          "El servidor respondió con HTML o error PHP. Revisa public/index.php, CirugiasAuthController.php o la conexión MySQL."
        );
        return;
      }

      if (!res.ok || (!data.success && !data.ok)) {
        console.log("ERROR LOGIN:", data);

        const mensaje = data.message || "Usuario o contraseña incorrectos.";

        mostrarError(mensaje);
        return;
      }

      if (recordarUsuario && recordarUsuario.checked) {
        localStorage.setItem("cirugias_usuario", usuario);
      } else {
        localStorage.removeItem("cirugias_usuario");
      }

      /*
        Prioridad:
        1. Si PHP manda redirect, usamos ese.
        2. Si no manda redirect, usamos rol:
           rol 0 = admin
           rol 1 = usuario normal
      */

      if (data.redirect) {
        window.location.href = data.redirect;
        return;
      }

      if (Number(data.rol) === 0) {
        window.location.href = ADMIN_URL;
      } else {
        window.location.href = PRINCIPAL_URL;
      }
    } catch (error) {
      console.error("Error real del login:", error);
      mostrarError("Error de conexión con el servidor.");
    } finally {
      if (btnLogin) {
        btnLogin.disabled = false;
        btnLogin.innerHTML = textoOriginal;

        if (window.lucide) {
          lucide.createIcons();
        }
      }
    }
  });

  function mostrarError(mensaje) {
    if (!errorMsg) {
      alert(mensaje);
      return;
    }

    errorMsg.textContent = mensaje;
    errorMsg.style.visibility = "visible";
  }

  function limpiarError() {
    if (!errorMsg) return;

    errorMsg.textContent = "";
    errorMsg.style.visibility = "hidden";
  }
});
