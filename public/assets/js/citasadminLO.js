const API_URL = window.location.origin;

const formCitasAdminLogin = document.getElementById("formCitasAdminLogin");
const usuario = document.getElementById("usuario");
const password = document.getElementById("password");
const mensajeLogin = document.getElementById("mensajeLogin");
const btnIngresar = document.getElementById("btnIngresar");

const btnAbrirRegistro = document.getElementById("btnAbrirRegistro");
const fondoRegistro = document.getElementById("fondoRegistro");
const modalRegistro = document.getElementById("modalRegistro");
const btnCerrarRegistro = document.getElementById("btnCerrarRegistro");

const formCitasAdminRegistro = document.getElementById(
  "formCitasAdminRegistro",
);
const usuarioRegistro = document.getElementById("usuarioRegistro");
const passwordRegistro = document.getElementById("passwordRegistro");
const passwordRegistro2 = document.getElementById("passwordRegistro2");
const mensajeRegistro = document.getElementById("mensajeRegistro");
const btnRegistrar = document.getElementById("btnRegistrar");

document.addEventListener("DOMContentLoaded", async () => {
  await verificarSesionActiva();
});

formCitasAdminLogin.addEventListener("submit", async (event) => {
  event.preventDefault();
  await iniciarSesionCitasAdmin();
});

btnAbrirRegistro.addEventListener("click", abrirModalRegistro);
btnCerrarRegistro.addEventListener("click", cerrarModalRegistro);
fondoRegistro.addEventListener("click", cerrarModalRegistro);

formCitasAdminRegistro.addEventListener("submit", async (event) => {
  event.preventDefault();
  await registrarCitasAdmin();
});

async function verificarSesionActiva() {
  try {
    const response = await fetch(`${API_URL}/me-citas-admin`, {
      credentials: "include",
    });

    const result = await response.json();

    if (result.ok) {
      window.location.href = "/citas-admin";
    }
  } catch (error) {
    // Sin sesión activa, permanece en login.
  }
}

async function iniciarSesionCitasAdmin() {
  try {
    const datos = {
      usuario: usuario.value.trim(),
      password: password.value,
    };

    if (!datos.usuario || !datos.password) {
      mostrarMensaje(mensajeLogin, "Completa usuario y contraseña", false);
      return;
    }

    btnIngresar.disabled = true;
    btnIngresar.textContent = "Ingresando...";
    mostrarMensaje(mensajeLogin, "", false);

    const response = await fetch(`${API_URL}/login-citas-admin`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      credentials: "include",
      body: JSON.stringify(datos),
    });

    const result = await response.json();

    if (!result.success) {
      mostrarMensaje(
        mensajeLogin,
        result.message || "Usuario o contraseña incorrectos",
        false,
      );
      return;
    }

    mostrarMensaje(mensajeLogin, "Ingreso correcto. Redirigiendo...", true);

    setTimeout(() => {
      window.location.href = result.redirect || "/citas-admin";
    }, 500);
  } catch (error) {
    mostrarMensaje(mensajeLogin, "Error conectando con el servidor", false);
  } finally {
    btnIngresar.disabled = false;
    btnIngresar.textContent = "Ingresar";
  }
}

async function registrarCitasAdmin() {
  try {
    const datos = {
      usuario: usuarioRegistro.value.trim(),
      password: passwordRegistro.value,
    };

    const confirmarPassword = passwordRegistro2.value;

    if (!datos.usuario || !datos.password || !confirmarPassword) {
      mostrarMensaje(mensajeRegistro, "Completa todos los campos", false);
      return;
    }

    if (datos.password !== confirmarPassword) {
      mostrarMensaje(mensajeRegistro, "Las contraseñas no coinciden", false);
      return;
    }

    btnRegistrar.disabled = true;
    btnRegistrar.textContent = "Registrando...";
    mostrarMensaje(mensajeRegistro, "", false);

    const response = await fetch(`${API_URL}/registro-citas-admin`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      credentials: "include",
      body: JSON.stringify(datos),
    });

    const result = await response.json();

    if (!result.success) {
      mostrarMensaje(
        mensajeRegistro,
        result.message || "No se pudo crear la cuenta",
        false,
      );
      return;
    }

    mostrarMensaje(
      mensajeRegistro,
      "Cuenta creada correctamente. Ahora inicia sesión.",
      true,
    );

    usuario.value = datos.usuario;
    password.value = "";

    usuarioRegistro.value = "";
    passwordRegistro.value = "";
    passwordRegistro2.value = "";

    setTimeout(() => {
      cerrarModalRegistro();
      password.focus();
    }, 900);
  } catch (error) {
    mostrarMensaje(mensajeRegistro, "Error conectando con el servidor", false);
  } finally {
    btnRegistrar.disabled = false;
    btnRegistrar.textContent = "Registrar cuenta";
  }
}

function abrirModalRegistro() {
  fondoRegistro.classList.remove("oculto");
  modalRegistro.classList.remove("oculto");

  mensajeRegistro.textContent = "";
  usuarioRegistro.focus();
}

function cerrarModalRegistro() {
  fondoRegistro.classList.add("oculto");
  modalRegistro.classList.add("oculto");
}

function mostrarMensaje(elemento, texto, ok) {
  elemento.textContent = texto;

  if (ok) {
    elemento.classList.add("ok");
  } else {
    elemento.classList.remove("ok");
  }
}
