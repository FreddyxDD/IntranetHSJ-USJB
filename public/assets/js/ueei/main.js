document.addEventListener("DOMContentLoaded", () => {
    const APP_BASE = (window.APP_BASE || "").replace(/\/$/, "");
    const apiUrl = (path) => `${APP_BASE}${path}`;
    const byId = (id) => document.getElementById(id);

    byId("year").textContent = new Date().getFullYear();

    const form = byId("authForm");
    const formTitle = byId("formTitle");
    const formSubtitle = byId("formSubtitle");
    const submitBtn = byId("submitBtn");
    const switchModeBtn = byId("switchModeBtn");
    const rememberWrap = byId("rememberWrap");
    const messageBox = byId("messageBox");
    const identifierInput = byId("correo");
    const passwordInput = byId("password");
    const togglePass = byId("togglePass");
    const loginIdentifierField = byId("loginIdentifierField");
    const loginPasswordField = byId("loginPasswordField");
    const registrationPanel = byId("registrationPanel");
    const registrationDni = byId("registrationDni");
    const validateDniBtn = byId("validateDniBtn");
    const identityResult = byId("identityResult");
    const validatedPersonName = byId("validatedPersonName");
    const activationDialog = byId("activationDialog");
    const activationUsername = byId("activationUsername");
    const activationPassword = byId("activationPassword");
    const activationAcknowledgement = byId("activationAcknowledgement");
    const confirmActivationBtn = byId("confirmActivationBtn");
    const activationMessage = byId("activationMessage");

    let registrationMode = false;
    let registrationValidated = false;
    let sending = false;

    function showMessage(text, type = "error") {
        messageBox.textContent = text;
        messageBox.className = `message message--${type}`;
    }

    function clearMessage() {
        messageBox.textContent = "";
        messageBox.className = "message";
    }

    function setLoading(loading) {
        sending = loading;
        submitBtn.disabled = loading || (registrationMode && !registrationValidated);
        switchModeBtn.disabled = loading;
        validateDniBtn.disabled = loading;
        submitBtn.textContent = loading
            ? registrationMode ? "Creando cuenta..." : "Ingresando..."
            : registrationMode ? "Crear y activar mi cuenta" : "Ingresar";
    }

    function activateRegistrationMode() {
        registrationMode = true;
        registrationValidated = false;
        formTitle.textContent = "Crear cuenta";
        formSubtitle.textContent = "Valida tu DNI con la identidad institucional.";
        switchModeBtn.textContent = "¿Ya tienes cuenta? Inicia sesión";
        loginIdentifierField.hidden = true;
        loginPasswordField.hidden = true;
        rememberWrap.hidden = true;
        registrationPanel.hidden = false;
        identifierInput.required = false;
        passwordInput.required = false;
        registrationDni.required = true;
        identityResult.hidden = true;
        validatedPersonName.textContent = "";
        submitBtn.disabled = true;
        submitBtn.textContent = "Crear y activar mi cuenta";
        clearMessage();
        registrationDni.focus();
    }

    function activateLoginMode() {
        registrationMode = false;
        registrationValidated = false;
        formTitle.textContent = "Iniciar sesión";
        formSubtitle.textContent = "Ingresa con tu DNI, correo o usuario.";
        switchModeBtn.textContent = "¿No tienes cuenta? Créate una";
        loginIdentifierField.hidden = false;
        loginPasswordField.hidden = false;
        rememberWrap.hidden = false;
        registrationPanel.hidden = true;
        identifierInput.required = true;
        passwordInput.required = true;
        registrationDni.required = false;
        registrationDni.value = "";
        identityResult.hidden = true;
        submitBtn.disabled = false;
        submitBtn.textContent = "Ingresar";
        clearMessage();
    }

    function togglePasswordVisibility() {
        const isHidden = passwordInput.type === "password";
        passwordInput.type = isHidden ? "text" : "password";
        togglePass.textContent = isHidden ? "Ocultar" : "Mostrar";
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
            throw new Error("El servidor devolvió una respuesta inválida.");
        }

        if (!response.ok) {
            throw new Error(data.message || "No se pudo completar la solicitud.");
        }

        return data;
    }

    function redirectAuthenticated(data) {
        window.location.href = data.rol === "admin"
            ? apiUrl("/admin-ueei")
            : apiUrl("/pages/principal.html");
    }

    function showActivation(data) {
        const instructions = data.account_instructions || {};
        activationUsername.textContent = instructions.username || "Tu DNI";
        activationPassword.textContent = instructions.initial_password || "Fecha de nacimiento + últimos 4 dígitos del DNI";
        activationAcknowledgement.checked = false;
        confirmActivationBtn.disabled = true;
        activationMessage.textContent = "";
        activationMessage.className = "message";

        if (!activationDialog.open) {
            activationDialog.showModal();
        }
    }

    async function verifySession() {
        try {
            const data = await requestJSON(apiUrl("/me-ueei"), { method: "GET" });
            if (!data.ok) return;

            if (data.requires_account_confirmation) {
                showActivation(data);
                return;
            }

            redirectAuthenticated(data);
        } catch {
            // La pantalla de acceso permanece disponible cuando no hay sesión.
        }
    }

    switchModeBtn.addEventListener("click", () => {
        if (sending) return;
        registrationMode ? activateLoginMode() : activateRegistrationMode();
    });

    togglePass.addEventListener("click", togglePasswordVisibility);

    registrationDni.addEventListener("input", () => {
        registrationDni.value = registrationDni.value.replace(/\D/g, "").slice(0, 8);
        registrationValidated = false;
        identityResult.hidden = true;
        submitBtn.disabled = true;
        clearMessage();
    });

    validateDniBtn.addEventListener("click", async () => {
        const dni = registrationDni.value.trim();

        if (!/^\d{8}$/.test(dni)) {
            showMessage("Ingresa un DNI válido de 8 dígitos.");
            return;
        }

        try {
            setLoading(true);
            validateDniBtn.textContent = "Validando...";
            const data = await requestJSON(apiUrl("/validar-dni-ueei"), {
                method: "POST",
                body: JSON.stringify({ dni }),
            });
            registrationValidated = true;
            validatedPersonName.textContent = data.data?.masked_name || "Personal institucional";
            identityResult.hidden = false;
            showMessage("Identidad validada. Ya puedes crear y activar tu cuenta.", "success");
        } catch (error) {
            registrationValidated = false;
            identityResult.hidden = true;
            showMessage(error.message || "No se pudo validar el DNI.");
        } finally {
            validateDniBtn.textContent = "Validar DNI";
            setLoading(false);
        }
    });

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        if (sending) return;
        clearMessage();

        try {
            setLoading(true);

            if (registrationMode) {
                if (!registrationValidated) {
                    showMessage("Primero debes validar tu DNI.");
                    return;
                }

                const data = await requestJSON(apiUrl("/crear-cuenta-ueei"), {
                    method: "POST",
                    body: JSON.stringify({ dni: registrationDni.value.trim() }),
                });
                showActivation(data);
                return;
            }

            const identifier = identifierInput.value.trim();
            const password = passwordInput.value;
            if (!identifier || !password) {
                showMessage("Completa los campos obligatorios.");
                return;
            }

            const data = await requestJSON(apiUrl("/login-ueei"), {
                method: "POST",
                body: JSON.stringify({ correo: identifier, password }),
            });

            if (data.requires_account_confirmation) {
                showActivation(data);
                return;
            }

            redirectAuthenticated(data);
        } catch (error) {
            console.error(error);
            showMessage(error.message || "Error de conexión con el servidor.");
        } finally {
            setLoading(false);
        }
    });

    activationDialog.addEventListener("cancel", (event) => event.preventDefault());

    activationAcknowledgement.addEventListener("change", () => {
        confirmActivationBtn.disabled = !activationAcknowledgement.checked;
    });

    confirmActivationBtn.addEventListener("click", async () => {
        if (!activationAcknowledgement.checked) return;

        try {
            confirmActivationBtn.disabled = true;
            confirmActivationBtn.textContent = "Registrando confirmación...";
            const data = await requestJSON(apiUrl("/confirmar-cuenta-ueei"), {
                method: "POST",
                body: JSON.stringify({ acknowledged: true }),
            });
            activationDialog.close();
            window.location.href = data.redirect || apiUrl("/pages/principal.html");
        } catch (error) {
            activationMessage.textContent = error.message || "No se pudo registrar la confirmación.";
            activationMessage.className = "message message--error";
            confirmActivationBtn.disabled = false;
        } finally {
            confirmActivationBtn.textContent = "Entendido, ingresar al portal";
        }
    });

    verifySession();
});
