document.addEventListener("DOMContentLoaded", () => {
    const APP_BASE = (window.APP_BASE || "").replace(/\/$/, "");

    function apiUrl(path) {
        return `${APP_BASE}${path}`;
    }

    document.getElementById("year").textContent = new Date().getFullYear();

    const form = document.getElementById("authForm");
    const formTitle = document.getElementById("formTitle");
    const formSubtitle = document.getElementById("formSubtitle");
    const submitBtn = document.getElementById("submitBtn");
    const switchModeBtn = document.getElementById("switchModeBtn");
    const confirmField = document.getElementById("confirmField");
    const rememberWrap = document.getElementById("rememberWrap");
    const messageBox = document.getElementById("messageBox");
    const correoInput = document.getElementById("correo");
    const passwordInput = document.getElementById("password");
    const confirmarPasswordInput = document.getElementById("confirmarPassword");
    const togglePass = document.getElementById("togglePass");
    const toggleConfirmPass = document.getElementById("toggleConfirmPass");

    let modoCrearCuenta = false;
    let enviando = false;

    function mostrarMensaje(texto, tipo = "error") {
        messageBox.textContent = texto;
        messageBox.className = `message message--${tipo}`;
    }

    function limpiarMensaje() {
        messageBox.textContent = "";
        messageBox.className = "message";
    }

    function setLoading(loading) {
        enviando = loading;
        submitBtn.disabled = loading;
        switchModeBtn.disabled = loading;

        submitBtn.textContent = loading
            ? modoCrearCuenta ? "Creando cuenta..." : "Ingresando..."
            : modoCrearCuenta ? "Crear cuenta" : "Ingresar";
    }

    function validarCorreo(correo) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
    }

    function validarPasswordFuerte(password) {
        return password.length >= 8
            && /[a-z]/.test(password)
            && /[A-Z]/.test(password)
            && /\d/.test(password);
    }

    function activarModoCrearCuenta() {
        modoCrearCuenta = true;

        formTitle.textContent = "Crear cuenta";
        formSubtitle.textContent = "Completa tus datos para registrarte.";
        submitBtn.textContent = "Crear cuenta";
        switchModeBtn.textContent = "¿Ya tienes cuenta? Inicia sesión";

        confirmField.hidden = false;
        rememberWrap.hidden = true;

        confirmarPasswordInput.required = true;
        passwordInput.autocomplete = "new-password";
        confirmarPasswordInput.autocomplete = "new-password";

        limpiarMensaje();
    }

    function activarModoLogin() {
        modoCrearCuenta = false;

        formTitle.textContent = "Iniciar sesión";
        formSubtitle.textContent = "Ingresa con tu correo y contraseña.";
        submitBtn.textContent = "Ingresar";
        switchModeBtn.textContent = "¿No tienes cuenta? Créate una";

        confirmField.hidden = true;
        rememberWrap.hidden = false;

        confirmarPasswordInput.required = false;
        confirmarPasswordInput.value = "";
        passwordInput.autocomplete = "current-password";

        limpiarMensaje();
    }

    function alternarVisibilidadPassword(input, button) {
        const isHidden = input.type === "password";

        input.type = isHidden ? "text" : "password";
        button.textContent = isHidden ? "Ocultar" : "Mostrar";
    }

    async function requestJSON(url, options = {}) {
        const response = await fetch(url, {
            credentials: "include",
            headers: {
                "Content-Type": "application/json",
                ...(options.headers || {}),
            },
            ...options,
        });

        let data;

        try {
            data = await response.json();
        } catch {
            throw new Error("Respuesta inválida del servidor");
        }

        if (!response.ok) {
            throw new Error(data.message || "Error en la solicitud");
        }

        return data;
    }

    async function verificarSesion() {
        try {
            const data = await requestJSON(apiUrl("/me-ueei"), {
                method: "GET",
            });

            if (data.ok) {
                if (data.rol === "admin") {
                    window.location.href = apiUrl("/admin-ueei");
                } else {
                    window.location.href = apiUrl("/pages/principal.html");
                }
            }
        } catch {
            // Sin sesión activa.
        }
    }

    switchModeBtn.addEventListener("click", () => {
        if (enviando) return;

        if (modoCrearCuenta) {
            activarModoLogin();
        } else {
            activarModoCrearCuenta();
        }
    });

    togglePass.addEventListener("click", () => {
        alternarVisibilidadPassword(passwordInput, togglePass);
    });

    toggleConfirmPass.addEventListener("click", () => {
        alternarVisibilidadPassword(confirmarPasswordInput, toggleConfirmPass);
    });

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        if (enviando) return;

        limpiarMensaje();

        const correo = correoInput.value.trim();
        const password = passwordInput.value.trim();
        const confirmarPassword = confirmarPasswordInput.value.trim();

        if (!correo || !password) {
            mostrarMensaje("Completa los campos obligatorios.");
            return;
        }

        if (!validarCorreo(correo)) {
            mostrarMensaje("Ingresa un correo electrónico válido.");
            return;
        }

        if (modoCrearCuenta) {
            if (!confirmarPassword) {
                mostrarMensaje("Debes confirmar la contraseña.");
                return;
            }

            if (!validarPasswordFuerte(password)) {
                mostrarMensaje("La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número.");
                return;
            }

            if (password !== confirmarPassword) {
                mostrarMensaje("Las contraseñas no coinciden.");
                return;
            }
        }

        try {
            setLoading(true);

            if (modoCrearCuenta) {
                const data = await requestJSON(apiUrl("/crear-cuenta-ueei"), {
                    method: "POST",
                    body: JSON.stringify({
                        correo,
                        password,
                        confirmarPassword,
                    }),
                });

                if (!data.success) {
                    mostrarMensaje(data.message || "No se pudo crear la cuenta.");
                    return;
                }

                activarModoLogin();

                correoInput.value = correo;
                passwordInput.value = "";
                confirmarPasswordInput.value = "";

                mostrarMensaje("Cuenta creada correctamente. Ahora inicia sesión.", "success");
                return;
            }

            const data = await requestJSON(apiUrl("/login-ueei"), {
                method: "POST",
                body: JSON.stringify({
                    correo,
                    password,
                }),
            });

            if (!data.success) {
                mostrarMensaje(data.message || "Correo o contraseña incorrectos.");
                return;
            }

            if (data.rol === "admin") {
                window.location.href = apiUrl("/admin-ueei");
            } else {
                window.location.href = apiUrl("/pages/principal.html");
            }
        } catch (error) {
            console.error(error);
            mostrarMensaje(error.message || "Error de conexión con el servidor.");
        } finally {
            setLoading(false);
        }
    });

    verificarSesion();
});