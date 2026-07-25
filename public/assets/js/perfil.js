(() => {
    const $ = (id) => document.getElementById(id);

    const body = document.body;
    const loginUrl = body.dataset.loginUrl || "/ueei-login";

    const year = $("year");
    if (year) {
        year.textContent = new Date().getFullYear();
    }

    function setText(id, value) {
        const element = $(id);
        if (element) {
            element.textContent = value;
        }
    }

    function nombreDesdeCorreo(correo) {
        if (!correo) {
            return "Usuario autorizado";
        }

        const base = correo.split("@")[0] || "usuario";
        return base
            .replace(/[._-]/g, " ")
            .replace(/\b\w/g, letra => letra.toUpperCase());
    }

    function normalizarUsuario(data) {
        if (data.usuario) {
            return data.usuario;
        }

        return {
            nombre: nombreDesdeCorreo(data.correo),
            correo: data.correo || "correo@hospital.gob.pe",
            rol: data.rol || "Personal autorizado",
            cargo: data.rol === "admin" ? "Administrador del sistema" : "Trabajador autorizado",
            area: "Unidad de Estadística e Información",
            sede: "Hospital San José de Chincha",
            telefono: "No registrado",
            estado: "Activo",
            foto: null
        };
    }

    function completarPerfil(usuario) {
        const nombre = usuario.nombre || "Usuario autorizado";
        const correo = usuario.correo || "correo@hospital.gob.pe";
        const area = usuario.area || "Unidad de Estadística e Información";
        const sede = usuario.sede || "Hospital San José de Chincha";
        const rol = usuario.rol || "Personal autorizado";
        const cargo = usuario.cargo || "Trabajador autorizado";
        const telefono = usuario.telefono || "No registrado";
        const estado = usuario.estado || "Activo";

        setText("nombreUsuario", nombre);
        setText("correoUsuario", correo);
        setText("areaUsuario", "Área: " + area);
        setText("sedeUsuario", "Sede: " + sede);
        setText("rolUsuario", rol);

        setText("infoNombre", nombre);
        setText("infoCorreo", correo);
        setText("infoCargo", cargo);
        setText("infoArea", area);
        setText("infoTelefono", telefono);
        setText("infoEstado", estado);

        const fotoPerfil = $("fotoPerfil");
        if (fotoPerfil && usuario.foto) {
            fotoPerfil.src = usuario.foto;
        }

        const ultimoAcceso = $("ultimoAcceso");
        if (ultimoAcceso) {
            ultimoAcceso.textContent = new Date().toLocaleString("es-PE", {
                dateStyle: "medium",
                timeStyle: "short"
            });
        }
    }

    async function verificarSesion() {
        try {
            const res = await fetch("/me-ueei", {
                method: "GET",
                credentials: "include",
                headers: {
                    "Accept": "application/json"
                }
            });

            const data = await res.json();

            if (!res.ok || !data.ok) {
                window.location.href = loginUrl;
                return;
            }

            completarPerfil(normalizarUsuario(data));
        } catch (error) {
            window.location.href = loginUrl;
        }
    }

    async function cerrarSesion() {
        try {
            /*
             | Si tu ruta de logout se llama /logout-ueei, cambia aquí /logout por /logout-ueei.
             */
            const res = await fetch("/logout", {
                method: "POST",
                credentials: "include",
                headers: {
                    "Accept": "application/json"
                }
            });

            const data = await res.json();

            if (res.ok && data.ok) {
                window.location.href = loginUrl;
                return;
            }

            alert("No se pudo cerrar la sesión.");
        } catch (error) {
            alert("Error al cerrar la sesión.");
        }
    }

    const logoutBtn = $("logoutBtn");
    if (logoutBtn) {
        logoutBtn.addEventListener("click", cerrarSesion);
    }

    verificarSesion();
})();
