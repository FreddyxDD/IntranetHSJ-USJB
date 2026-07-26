/* =========================================================
    ADMIN UEeI - PANEL ADMINISTRADOR INTRANET HSJ
    Archivo: public/assets/js/admin-ueei.js
========================================================= */

(() => {
    "use strict";

    const API = window.ADMIN_UEEI || {};

    const state = {
        resumen: null,
        catalogos: {
            roles: [],
            areas: [],
            modulos: []
        },
        usuarios: [],
        usuariosFiltrados: [],
        editandoId: null,
        seleccionTodoActiva: false
    };

    /* =========================================================
        HELPERS DOM
    ========================================================= */

    const $ = (selector) => document.querySelector(selector);
    const $$ = (selector) => Array.from(document.querySelectorAll(selector));

    const elementos = {
        navItems: $$(".js-nav-item"),
        secciones: $$(".admin-section"),

        alerta: $("#adminAlerta"),

        totalUsuarios: $("#totalUsuarios"),
        usuariosActivos: $("#usuariosActivos"),
        solicitudesPendientes: $("#solicitudesPendientes"),
        totalAreas: $("#totalAreas"),
        totalModulos: $("#totalModulos"),

        btnNuevoUsuario: $("#btnNuevoUsuario"),
        btnCerrarSesionAdmin: $("#btnCerrarSesionAdmin"),

        formUsuario: $("#formUsuario"),
        usuarioId: $("#usuarioId"),
        correo: $("#correo"),
        password: $("#password"),
        grupoPassword: $("#grupoPassword"),
        rol: $("#rol"),
        areaId: $("#areaId"),
        modulosLista: $("#modulosLista"),
        btnSeleccionarTodo: $("#btnSeleccionarTodo"),
        btnCancelarEdicion: $("#btnCancelarEdicion"),
        btnGuardarUsuario: $("#btnGuardarUsuario"),
        btnCerrarModalUsuario: $("#btnCerrarModalUsuario"),

        formTitulo: $("#formTitulo"),
        formSubtitulo: $("#formSubtitulo"),

        buscarUsuario: $("#buscarUsuario"),
        tablaUsuarios: $("#tablaUsuarios"),

        modulosCards: $("#modulosCards"),
        modalUsuario: $("#modalUsuario"),

        modalPassword: $("#modalPassword"),
        formPassword: $("#formPassword"),
        passwordUsuarioId: $("#passwordUsuarioId"),
        nuevaPassword: $("#nuevaPassword"),
        btnCerrarModalPassword: $("#btnCerrarModalPassword"),
        btnCancelarPassword: $("#btnCancelarPassword")
    };

    /* =========================================================
        UTILIDADES
    ========================================================= */

    function escapeHtml(value) {
        return String(value ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    function mostrarAlerta(mensaje, tipo = "info") {
        if (!elementos.alerta) return;

        elementos.alerta.textContent = mensaje;
        elementos.alerta.className = `admin-alert ${tipo}`;

        window.clearTimeout(mostrarAlerta.timeout);

        mostrarAlerta.timeout = window.setTimeout(() => {
            elementos.alerta.classList.add("hidden");
        }, 4500);
    }
    function mostrarToastGuardado(mensaje = "Usuario actualizado correctamente.") {
        const toast = document.getElementById("toastAdmin");
        const titulo = document.getElementById("toastAdminTitulo");
        const texto = document.getElementById("toastAdminMensaje");

        if (!toast || !titulo || !texto) return;

        titulo.textContent = "!Guardado con éxito¡";
        texto.textContent = mensaje;

        toast.classList.remove("translate-y-[-12px]", "opacity-0");
        toast.classList.add("translate-y-0", "opacity-100");

        window.clearTimeout(mostrarToastGuardado.timeout);

        mostrarToastGuardado.timeout = window.setTimeout(() => {
            toast.classList.remove("translate-y-0", "opacity-100");
            toast.classList.add("translate-y-[-12px]", "opacity-0");
        }, 3000);
    }

    function mostrarToast(mensaje, tipo = "success") {
    const toast = document.getElementById("toastAdmin");
    const titulo = document.getElementById("toastAdminTitulo");
    const texto = document.getElementById("toastAdminMensaje");
    const icono = document.getElementById("toastAdminIcon");

    if (!toast || !titulo || !texto || !icono) return;

    texto.textContent = mensaje;

    if (tipo === "error") {
        titulo.textContent = "Error";
        icono.textContent = "!";

        toast.className =
            "pointer-events-none fixed right-6 top-6 z-[99999] flex translate-x-0 items-center gap-4 rounded-2xl border border-red-200 bg-white px-5 py-4 opacity-100 shadow-[0_24px_70px_rgba(15,23,42,0.22)] transition-all duration-300 ease-out";

        icono.className =
            "flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-red-100 text-2xl font-black text-red-600";
    } else {
        titulo.textContent = "Actualizado";
        icono.textContent = "✓";

        toast.className =
            "pointer-events-none fixed right-6 top-6 z-[99999] flex translate-x-0 items-center gap-4 rounded-2xl border border-emerald-200 bg-white px-5 py-4 opacity-100 shadow-[0_24px_70px_rgba(15,23,42,0.22)] transition-all duration-300 ease-out";

        icono.className =
            "flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-2xl font-black text-emerald-600";
    }

    window.clearTimeout(mostrarToast.timeout);

    mostrarToast.timeout = window.setTimeout(() => {
        toast.classList.remove("translate-x-0", "opacity-100");
        toast.classList.add("translate-x-[120%]", "opacity-0");
    }, 3200);
    }

    function setLoadingBoton(boton, cargando, textoNormal, textoCargando = "Procesando...") {
        if (!boton) return;

        boton.disabled = cargando;
        boton.textContent = cargando ? textoCargando : textoNormal;
    }

    function normalizarTexto(value) {
        return String(value ?? "")
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .trim();
    }

    function obtenerModulosSeleccionados() {
        return $$(".modulo-check-input:checked")
            .map((input) => Number(input.value))
            .filter((id) => Number.isInteger(id) && id > 0);
    }

    function marcarModulosSeleccionados(ids) {
        const setIds = new Set((ids || []).map((id) => Number(id)));

        $$(".modulo-check-input").forEach((input) => {
            input.checked = setIds.has(Number(input.value));
        });

        actualizarTextoSeleccionarTodo();
    }

    function actualizarTextoSeleccionarTodo() {
        const inputs = $$(".modulo-check-input");

        if (elementos.btnSeleccionarTodo?.disabled) {
            elementos.btnSeleccionarTodo.textContent = "Asignación central";
            return;
        }

        if (inputs.length === 0) {
            state.seleccionTodoActiva = false;
            if (elementos.btnSeleccionarTodo) {
                elementos.btnSeleccionarTodo.textContent = "Seleccionar todo";
            }
            return;
        }

        const todosMarcados = inputs.every((input) => input.checked);

        state.seleccionTodoActiva = todosMarcados;

        if (elementos.btnSeleccionarTodo) {
            elementos.btnSeleccionarTodo.textContent = todosMarcados
                ? "Quitar selección"
                : "Seleccionar todo";
        }
    }

    function formatoFecha(fecha) {
        if (!fecha) return "Sin fecha";

        const value = String(fecha);

        if (value.includes(" ")) {
            const [dia] = value.split(" ");
            return dia;
        }

        return value;
    }

    function nombreRol(rol) {
        const mapa = {
            admin: "Administrador",
            director: "Director",
            supervisor: "Supervisor",
            trabajador: "Trabajador"
        };

        return mapa[rol] || rol || "Sin rol";
    }

    /* =========================================================
        FETCH
    ========================================================= */

    async function requestJson(url, options = {}) {
        const config = {
            credentials: "same-origin",
            headers: {
                "Accept": "application/json",
                ...(options.body ? { "Content-Type": "application/json" } : {}),
                ...(options.headers || {})
            },
            ...options
        };

        const response = await fetch(url, config);

        let data = null;

        try {
            data = await response.json();
        } catch (error) {
            data = {
                ok: false,
                message: "La respuesta del servidor no es JSON válido."
            };
        }

        if (!response.ok || data?.ok === false || data?.success === false) {
            const mensaje = data?.message || data?.error || `Error HTTP ${response.status}`;
            throw new Error(mensaje);
        }

        return data;
    }

    async function cargarResumen() {
        const data = await requestJson(API.resumen);

        state.resumen = data.data || {};

        renderResumen();
    }

    async function cargarCatalogos() {
        const data = await requestJson(API.catalogos);

        state.catalogos.roles = data.roles || [];
        state.catalogos.areas = data.areas || [];
        state.catalogos.modulos = data.modulos || [];

        renderAreas();
        renderModulosFormulario();
        renderModulosCards();
    }

    async function cargarUsuarios() {
        const data = await requestJson(API.usuarios);

        state.usuarios = data.data || [];
        state.usuariosFiltrados = [...state.usuarios];

        renderUsuarios();
    }

    async function cargarTodo() {
        try {
            await Promise.all([
                cargarResumen(),
                cargarCatalogos(),
                cargarUsuarios()
            ]);
        } catch (error) {
            mostrarAlerta(error.message || "Error cargando panel administrador.", "error");
        }
    }

    /* =========================================================
        RENDER RESUMEN
    ========================================================= */

    function renderResumen() {
        const resumen = state.resumen || {};

        if (elementos.totalUsuarios) {
            elementos.totalUsuarios.textContent = resumen.totalUsuarios ?? 0;
        }

        if (elementos.usuariosActivos) {
            elementos.usuariosActivos.textContent = resumen.usuariosActivos ?? 0;
        }

        if (elementos.solicitudesPendientes) {
            elementos.solicitudesPendientes.textContent = resumen.solicitudesPendientes ?? 0;
        }

        if (elementos.totalAreas) {
            elementos.totalAreas.textContent = resumen.totalAreas ?? 0;
        }

        if (elementos.totalModulos) {
            elementos.totalModulos.textContent = resumen.totalModulos ?? 0;
        }
    }

    /* =========================================================
        RENDER CATÁLOGOS
    ========================================================= */

    function renderAreas() {
        if (!elementos.areaId) return;

        const options = [
            `<option value="">Selecciona un perfil</option>`,
            ...state.catalogos.areas.map((area) => {
                return `
                    <option value="${Number(area.id)}">
                        ${escapeHtml(area.nombre)}
                    </option>
                `;
            })
        ];

        elementos.areaId.innerHTML = options.join("");
    }

    function renderModulosFormulario() {
        if (!elementos.modulosLista) return;

        const modulos = state.catalogos.modulos || [];

        if (modulos.length === 0) {
            elementos.modulosLista.innerHTML = `
                <div class="empty-box">No hay módulos activos registrados.</div>
            `;
            return;
        }

        elementos.modulosLista.innerHTML = modulos.map((modulo) => {
            return `
                <label class="module-check">
                    <input
                        type="checkbox"
                        class="modulo-check-input"
                        value="${Number(modulo.id)}"
                        disabled
                    >
                    <strong>${escapeHtml(modulo.nombre)}</strong>
                    <span>${escapeHtml(modulo.descripcion || modulo.codigo || "Módulo del sistema")}</span>
                </label>
            `;
        }).join("");

        actualizarTextoSeleccionarTodo();
    }

    function aplicarPerfilSeleccionado() {
        const perfilId = Number(elementos.areaId?.value || 0);
        const perfil = state.catalogos.areas.find((item) => Number(item.id) === perfilId);

        if (!perfil) {
            marcarModulosSeleccionados([]);
            return;
        }

        if (elementos.rol) {
            elementos.rol.value = perfil.rol || "trabajador";
        }

        marcarModulosSeleccionados(perfil.modulo_ids || []);
    }

    function aplicarRolSeleccionado() {
        const rol = elementos.rol?.value || "trabajador";
        const perfil = state.catalogos.areas.find((item) => item.rol === rol);

        if (perfil && elementos.areaId) {
            elementos.areaId.value = String(perfil.id);
            aplicarPerfilSeleccionado();
        }
    }

    function renderModulosCards() {
        if (!elementos.modulosCards) return;

        const modulos = state.catalogos.modulos || [];

        if (modulos.length === 0) {
            elementos.modulosCards.innerHTML = `
                <div class="empty-box">No hay módulos activos registrados.</div>
            `;
            return;
        }

        elementos.modulosCards.innerHTML = modulos.map((modulo) => {
            const icono = modulo.icono
                ? `<img src="${escapeHtml((window.APP_BASE || "") + modulo.icono)}" alt="">`
                : `<span>📦</span>`;

            const ruta = modulo.ruta || "/";
            const urlModulo = `${window.APP_BASE || ""}${ruta}`;

          return `
            <a 
                href="${escapeHtml(urlModulo)}"
                class="module-card module-card-link"
                title="Ir al módulo ${escapeHtml(modulo.nombre)}"
            >
                <div class="module-card-icon">
                    ${icono}
                </div>

                <h4>${escapeHtml(modulo.nombre)}</h4>

                <p>
                    ${escapeHtml(modulo.descripcion || "Módulo disponible dentro del intranet.")}
                </p>
            </a>
            `;
        }).join("");
    }

    /* =========================================================
        RENDER USUARIOS
    ========================================================= */

    function renderUsuarios() {
        if (!elementos.tablaUsuarios) return;

        const usuarios = state.usuariosFiltrados || [];

        if (usuarios.length === 0) {
            elementos.tablaUsuarios.innerHTML = `
                <tr>
                    <td colspan="6" class="table-empty">
                        No se encontraron usuarios registrados.
                    </td>
                </tr>
            `;
            return;
        }

        elementos.tablaUsuarios.innerHTML = usuarios.map((usuario) => {
            const modulos = Array.isArray(usuario.modulo_nombres)
                ? usuario.modulo_nombres
                : [];

            const modulosHtml = modulos.length > 0
                ? `
                    <div class="modules-tags">
                        ${modulos.map((nombre) => `
                            <span class="module-tag">${escapeHtml(nombre)}</span>
                        `).join("")}
                    </div>
                `
                : `<span class="text-muted">Sin módulos</span>`;

            const pendiente = Boolean(usuario.solicitud_pendiente);
            const estadoTexto = pendiente ? "Pendiente de aprobación" : (Number(usuario.estado) === 1 ? "Activo" : "Inactivo");
            const estadoClase = pendiente ? "pending" : (Number(usuario.estado) === 1 ? "active" : "inactive");

            const estadoBotonTexto = pendiente ? "Revisar y aprobar" : (Number(usuario.estado) === 1 ? "Desactivar" : "Activar");
            const estadoBotonClase = pendiente ? "btn-warning" : (Number(usuario.estado) === 1 ? "btn-danger" : "btn-warning");

            return `
                <tr data-id="${Number(usuario.id)}">
                    <td>
                        <div class="user-cell">
                            <strong>${escapeHtml(usuario.nombre || usuario.correo)}</strong>
                            <span>DNI: ${escapeHtml(usuario.dni || "No informado")}</span>
                            <span>${escapeHtml(usuario.correo)}</span>
                            ${usuario.telefono ? `<span>Teléfono: ${escapeHtml(usuario.telefono)}</span>` : ""}
                            ${usuario.fecha_nacimiento ? `<span>Fecha de nacimiento: ${escapeHtml(usuario.fecha_nacimiento)}</span>` : ""}
                            <span>Creado: ${escapeHtml(formatoFecha(usuario.fecha_creacion))}</span>
                        </div>
                    </td>

                    <td>
                        <span class="badge ${escapeHtml(usuario.rol)}">
                            ${escapeHtml(nombreRol(usuario.rol))}
                        </span>
                    </td>

                    <td>
                        ${escapeHtml(usuario.area_nombre || "Sin área")}
                    </td>

                    <td>
                        ${modulosHtml}
                    </td>

                    <td>
                        <span class="badge ${estadoClase}">
                            ${estadoTexto}
                        </span>
                    </td>

                    <td>
                        <div class="action-buttons">
                            ${pendiente ? "" : `<button
                                type="button"
                                class="btn-mini btn-editar"
                                data-id="${Number(usuario.id)}"
                            >
                                Editar
                            </button>`}

                            ${pendiente ? "" : `<button
                                type="button"
                                class="btn-mini btn-password"
                                data-id="${Number(usuario.id)}"
                            >
                                Contraseña
                            </button>`}

                            <button
                                type="button"
                                class="${estadoBotonClase} btn-estado"
                                data-id="${Number(usuario.id)}"
                                data-estado="${Number(usuario.estado) === 1 ? 0 : 1}"
                            >
                                ${estadoBotonTexto}
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join("");

        activarEventosTabla();
    }

    function activarEventosTabla() {
        $$(".btn-editar").forEach((btn) => {
            btn.addEventListener("click", () => {
                const id = Number(btn.dataset.id);
                editarUsuario(id);
            });
        });

        $$(".btn-password").forEach((btn) => {
            btn.addEventListener("click", () => {
                const id = Number(btn.dataset.id);
                abrirModalPassword(id);
            });
        });

        $$(".btn-estado").forEach((btn) => {
            btn.addEventListener("click", () => {
                const id = Number(btn.dataset.id);
                const estado = Number(btn.dataset.estado);
                cambiarEstadoUsuario(id, estado);
            });
        });
    }

    function filtrarUsuarios() {
        const query = normalizarTexto(elementos.buscarUsuario?.value || "");

        if (query === "") {
            state.usuariosFiltrados = [...state.usuarios];
            renderUsuarios();
            return;
        }

        state.usuariosFiltrados = state.usuarios.filter((usuario) => {
            const texto = [
                usuario.correo,
                usuario.nombre,
                usuario.dni,
                usuario.telefono,
                usuario.fecha_nacimiento,
                usuario.estado_cuenta,
                usuario.rol,
                usuario.area_nombre,
                ...(usuario.modulo_nombres || [])
            ].map(normalizarTexto).join(" ");

            return texto.includes(query);
        });

        renderUsuarios();
    }

    /* =========================================================
        FORMULARIO USUARIO
    ========================================================= */
    function abrirModalUsuario() {
    elementos.modalUsuario?.classList.remove("hidden");

    setTimeout(() => {
        elementos.correo?.focus();
    }, 100);
    }

    function cerrarModalUsuario() {
    elementos.modalUsuario?.classList.add("hidden");
    }
    function limpiarFormulario() {
        state.editandoId = null;

        if (elementos.usuarioId) elementos.usuarioId.value = "";
        if (elementos.formUsuario) elementos.formUsuario.reset();

        if (elementos.password) {
            elementos.password.required = true;
            elementos.password.value = "";
        }

        if (elementos.grupoPassword) {
            elementos.grupoPassword.style.display = "";
        }

        if (elementos.formTitulo) {
            elementos.formTitulo.textContent = "Crear usuario";
        }

        if (elementos.formSubtitulo) {
            elementos.formSubtitulo.textContent = "Registra una nueva cuenta y asigna sus módulos.";
        }

        if (elementos.btnGuardarUsuario) {
            elementos.btnGuardarUsuario.textContent = "Guardar usuario";
        }

        marcarModulosSeleccionados([]);
    }

    function nuevoUsuario() {
    limpiarFormulario();
    abrirModalUsuario();
    }

    function editarUsuario(id) {
        const usuario = state.usuarios.find((item) => Number(item.id) === Number(id));

        if (!usuario) {
            mostrarAlerta("No se encontró el usuario seleccionado.", "error");
            return;
        }

        state.editandoId = Number(usuario.id);

        if (elementos.usuarioId) elementos.usuarioId.value = String(usuario.id);
        if (elementos.correo) elementos.correo.value = usuario.correo || "";
        if (elementos.rol) elementos.rol.value = usuario.rol || "trabajador";
        if (elementos.areaId) elementos.areaId.value = usuario.area_id ?? "";

        if (elementos.password) {
            elementos.password.required = false;
            elementos.password.value = "";
        }

        if (elementos.grupoPassword) {
            elementos.grupoPassword.style.display = "none";
        }

        if (elementos.formTitulo) {
            elementos.formTitulo.textContent = "Editar usuario";
        }

        if (elementos.formSubtitulo) {
            elementos.formSubtitulo.textContent = "Actualiza el rol, área y módulos asignados.";
        }

        if (elementos.btnGuardarUsuario) {
            elementos.btnGuardarUsuario.textContent = "Actualizar usuario";
        }

        aplicarPerfilSeleccionado();

        abrirModalUsuario();
    }

    async function guardarUsuario(event) {
        event.preventDefault();

        const id = Number(elementos.usuarioId?.value || 0);
        const editando = id > 0;

        const payload = {
            correo: elementos.correo?.value.trim() || "",
            rol: elementos.rol?.value || "trabajador",
            area_id: elementos.areaId?.value || null,
            modulos: obtenerModulosSeleccionados()
        };

        if (!editando) {
            payload.password = elementos.password?.value || "";
        }

        if (!payload.correo) {
            mostrarAlerta("Ingrese el correo institucional.", "error");
            elementos.correo?.focus();
            return;
        }

        if (!payload.area_id) {
            mostrarAlerta("Selecciona el perfil de acceso del usuario.", "error");
            elementos.areaId?.focus();
            return;
        }

        if (!editando && !payload.password) {
            mostrarAlerta("Ingrese la contraseña del nuevo usuario.", "error");
            elementos.password?.focus();
            return;
        }

        const url = editando
            ? `${API.usuarios}/${id}`
            : API.usuarios;

        const method = editando ? "PUT" : "POST";

        try {
            setLoadingBoton(
                elementos.btnGuardarUsuario,
                true,
                editando ? "Actualizar usuario" : "Guardar usuario"
            );

            const data = await requestJson(url, {
                method,
                body: JSON.stringify(payload)
            });

            const mensajeOk = editando
                ? "El usuario fue actualizado correctamente."
                : "El usuario fue creado correctamente.";

            mostrarAlerta(data.message || mensajeOk, "success");
            mostrarToastGuardado(data.message || mensajeOk);

            limpiarFormulario();
            cerrarModalUsuario();

            await Promise.all([
                cargarUsuarios(),
                cargarResumen()
            ]);
        } catch (error) {
            mostrarAlerta(error.message || "Error guardando usuario.", "error");
        } finally {
            setLoadingBoton(
                elementos.btnGuardarUsuario,
                false,
                editando ? "Actualizar usuario" : "Guardar usuario"
            );
        }
    }

    async function cambiarEstadoUsuario(id, estado) {
        const usuario = state.usuarios.find((item) => Number(item.id) === Number(id));
        const pendiente = Boolean(usuario?.solicitud_pendiente);
        const accion = pendiente ? "aprobar esta solicitud" : (Number(estado) === 1 ? "activar" : "desactivar");
        const detalle = pendiente
            ? `\n\n${usuario.nombre || ""}\nDNI: ${usuario.dni || ""}\nCorreo: ${usuario.correo || ""}\n\nAl aprobar se activará la persona y recibirá acceso de consulta.`
            : "";

        const confirmar = window.confirm(`¿Seguro que deseas ${accion}?${detalle}`);

        if (!confirmar) return;

        try {
            const data = await requestJson(`${API.usuarios}/${id}/estado`, {
                method: "PATCH",
                body: JSON.stringify({
                    estado
                })
            });

            mostrarAlerta(data.message || "Estado actualizado correctamente.", "success");

            await Promise.all([
                cargarUsuarios(),
                cargarResumen()
            ]);
        } catch (error) {
            mostrarAlerta(error.message || "Error cambiando estado del usuario.", "error");
        }
    }

    function seleccionarTodoModulos() {
        aplicarPerfilSeleccionado();
    }

    /* =========================================================
        MODAL CONTRASEÑA
    ========================================================= */

    function abrirModalPassword(id) {
        const usuario = state.usuarios.find((item) => Number(item.id) === Number(id));

        if (!usuario) {
            mostrarAlerta("No se encontró el usuario seleccionado.", "error");
            return;
        }

        if (elementos.passwordUsuarioId) {
            elementos.passwordUsuarioId.value = String(id);
        }

        if (elementos.nuevaPassword) {
            elementos.nuevaPassword.value = "";
        }

        elementos.modalPassword?.classList.remove("hidden");

        setTimeout(() => {
            elementos.nuevaPassword?.focus();
        }, 100);
    }

    function cerrarModalPassword() {
        elementos.modalPassword?.classList.add("hidden");

        if (elementos.passwordUsuarioId) {
            elementos.passwordUsuarioId.value = "";
        }

        if (elementos.nuevaPassword) {
            elementos.nuevaPassword.value = "";
        }
    }

    async function cambiarPassword(event) {
        event.preventDefault();

        const id = Number(elementos.passwordUsuarioId?.value || 0);
        const password = elementos.nuevaPassword?.value || "";

        if (id <= 0) {
            mostrarAlerta("Usuario inválido.", "error");
            return;
        }

        if (password.length < 8) {
            mostrarAlerta("La contraseña debe tener mínimo 8 caracteres.", "error");
            elementos.nuevaPassword?.focus();
            return;
        }

        try {
            const data = await requestJson(`${API.usuarios}/${id}/password`, {
                method: "PATCH",
                body: JSON.stringify({
                    password
                })
            });

            mostrarAlerta(data.message || "Contraseña actualizada correctamente.", "success");

            cerrarModalPassword();

            await cargarUsuarios();
        } catch (error) {
            mostrarAlerta(error.message || "Error actualizando contraseña.", "error");
        }
    }

    /* =========================================================
        NAVEGACIÓN SECCIONES
    ========================================================= */
    function activarSeccion(nombre) {
    const clasesActivas = [
        "bg-white/20",
        "shadow-lg"
    ];

    elementos.navItems.forEach((item) => {
        const activo = item.dataset.section === nombre;

        item.classList.remove(...clasesActivas);

        if (activo) {
            item.classList.add(...clasesActivas);
            item.setAttribute("aria-current", "page");
        } else {
            item.removeAttribute("aria-current");
        }
    });

    elementos.secciones.forEach((section) => {
        const idEsperado = `section${nombre.charAt(0).toUpperCase()}${nombre.slice(1)}`;
        section.classList.toggle("active", section.id === idEsperado);
    });
}

    function configurarNavegacion() {
        elementos.navItems.forEach((item) => {
            item.addEventListener("click", () => {
                activarSeccion(item.dataset.section || "usuarios");
            });
        });
    }

    /* =========================================================
        LOGOUT
    ========================================================= */

    async function cerrarSesion() {
    try {
        await requestJson(API.logout, {
            method: "POST"
        });
    } catch (error) {
        /*
            Aunque falle el logout, igual mandamos al login.
        */
    }

    window.location.href = window.APP_BASE || "/";
    }

    /* =========================================================
        EVENTOS
    ========================================================= */
    function configurarSidebarDesplegable() {
    const sidebar = document.getElementById("adminSidebar");
    const main = document.getElementById("adminMain");
    const btnToggle = document.getElementById("btnToggleSidebar");
    const labels = document.querySelectorAll(".sidebar-label");
    const logoTexto = document.getElementById("sidebarLogoTexto");
    const footerLinks = document.querySelectorAll(".sidebar-footer-link");

    if (!sidebar || !main || !btnToggle) return;

    let cerrado = false;

    btnToggle.addEventListener("click", () => {
        cerrado = !cerrado;

        if (cerrado) {
            sidebar.classList.remove("w-[250px]", "px-4");
            sidebar.classList.add("w-[64px]", "px-2");

            main.classList.remove("ml-[300px]");
            main.classList.add("ml-[110px]");

            labels.forEach((label) => {
                label.classList.add("opacity-0", "w-0", "overflow-hidden");
            });

            footerLinks.forEach((link) => {
                link.classList.remove("px-4", "gap-3");
                link.classList.add("px-0", "justify-center");
            });

            if (logoTexto) {
                logoTexto.classList.add("opacity-0", "w-0", "overflow-hidden");
            }
        } else {
            sidebar.classList.remove("w-[64px]", "px-2");
            sidebar.classList.add("w-[250px]", "px-4");

            main.classList.remove("ml-[110px]");
            main.classList.add("ml-[300px]");

            labels.forEach((label) => {
                label.classList.remove("opacity-0", "w-0", "overflow-hidden");
            });

            footerLinks.forEach((link) => {
                link.classList.remove("px-0", "justify-center");
                link.classList.add("px-4", "gap-3");
            });

            if (logoTexto) {
                logoTexto.classList.remove("opacity-0", "w-0", "overflow-hidden");
            }
        }
    });
}
    function configurarEventos() {
        configurarSidebarDesplegable();
        configurarNavegacion();
        activarSeccion("usuarios");

        elementos.btnNuevoUsuario?.addEventListener("click", nuevoUsuario);
        elementos.btnCancelarEdicion?.addEventListener("click", limpiarFormulario);
        elementos.btnSeleccionarTodo?.addEventListener("click", seleccionarTodoModulos);
        elementos.areaId?.addEventListener("change", aplicarPerfilSeleccionado);
        elementos.rol?.addEventListener("change", aplicarRolSeleccionado);

        elementos.formUsuario?.addEventListener("submit", guardarUsuario);

        elementos.buscarUsuario?.addEventListener("input", filtrarUsuarios);

        elementos.btnCerrarSesionAdmin?.addEventListener("click", cerrarSesion);

        elementos.btnCerrarModalPassword?.addEventListener("click", cerrarModalPassword);
        elementos.btnCancelarPassword?.addEventListener("click", cerrarModalPassword);
        elementos.formPassword?.addEventListener("submit", cambiarPassword);

        elementos.modalPassword?.addEventListener("click", (event) => {
            if (event.target === elementos.modalPassword) {
                cerrarModalPassword();
            }
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
            cerrarModalPassword();
            cerrarModalUsuario();
            }
        });

        elementos.btnCerrarModalUsuario?.addEventListener("click", cerrarModalUsuario);

        elementos.modalUsuario?.addEventListener("click", (event) => {
            if (event.target === elementos.modalUsuario) {
                cerrarModalUsuario();
            }
        });
    }

    /* =========================================================
        INIT
    ========================================================= */

    document.addEventListener("DOMContentLoaded", async () => {
        configurarEventos();
        limpiarFormulario();

        await cargarTodo();
    });
})();
