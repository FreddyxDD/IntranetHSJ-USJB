document.addEventListener("DOMContentLoaded", () => {
  if (window.lucide) {
    lucide.createIcons();
  }

  const formCrearUsuario = document.getElementById("formCrearUsuario");
  const tablaUsuarios = document.getElementById("tablaUsuarios");
  const mensajeForm = document.getElementById("mensajeForm");
  const btnRecargar = document.getElementById("btnRecargar");
  const btnLogout = document.getElementById("btnLogout");

  cargarUsuarios();

  if (btnRecargar) {
    btnRecargar.addEventListener("click", cargarUsuarios);
  }

  if (btnLogout) {
    btnLogout.addEventListener("click", cerrarSesion);
  }

  if (formCrearUsuario) {
    formCrearUsuario.addEventListener("submit", async (e) => {
      e.preventDefault();

      const usuario = document.getElementById("usuario").value.trim();
      const password = document.getElementById("password").value;
      const rol = Number(document.getElementById("rol").value);

      limpiarMensaje();

      if (!usuario || !password) {
        mostrarMensaje("Completa todos los campos.", false);
        return;
      }

      if (password.length < 6) {
        mostrarMensaje("La contraseña debe tener mínimo 6 caracteres.", false);
        return;
      }

      const btn = formCrearUsuario.querySelector("button[type='submit']");
      const textoOriginal = btn.innerHTML;

      try {
        btn.disabled = true;
        btn.innerHTML = "Creando...";

        const response = await fetch(window.CIRUGIAS_ADMIN_CREAR_USUARIO_URL, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
          body: JSON.stringify({
            usuario,
            password,
            rol,
          }),
        });

        const data = await response.json();

        if (!response.ok || (!data.ok && !data.success)) {
          mostrarMensaje(data.message || "No se pudo crear el usuario.", false);
          return;
        }

        mostrarMensaje(data.message || "Usuario creado correctamente.", true);
        formCrearUsuario.reset();
        cargarUsuarios();
      } catch (error) {
        console.error(error);
        mostrarMensaje("Error de conexión con el servidor.", false);
      } finally {
        btn.disabled = false;
        btn.innerHTML = textoOriginal;

        if (window.lucide) {
          lucide.createIcons();
        }
      }
    });
  }

  if (tablaUsuarios) {
    tablaUsuarios.addEventListener("click", async (e) => {
      const btnEstado = e.target.closest("[data-estado-id]");
      const btnEliminar = e.target.closest("[data-eliminar-id]");

      if (btnEstado) {
        const id = Number(btnEstado.dataset.estadoId);
        const estado = Number(btnEstado.dataset.estadoNuevo);

        await cambiarEstadoUsuario(id, estado);
      }

      if (btnEliminar) {
        const id = Number(btnEliminar.dataset.eliminarId);
        const usuario = btnEliminar.dataset.usuario || "este usuario";

        const confirmar = confirm(
          `¿Seguro que deseas eliminar al usuario "${usuario}"?\n\nEsta acción eliminará la cuenta de la base de datos.`
        );

        if (!confirmar) return;

        await eliminarUsuario(id);
      }
    });
  }

  async function cargarUsuarios() {
    if (!tablaUsuarios) return;

    tablaUsuarios.innerHTML = `
      <tr>
        <td colspan="5" class="empty">Cargando usuarios...</td>
      </tr>
    `;

    try {
      const response = await fetch(window.CIRUGIAS_ADMIN_LISTAR_USUARIOS_URL, {
        method: "GET",
        credentials: "include",
      });

      const data = await response.json();

      if (!response.ok || (!data.ok && !data.success)) {
        tablaUsuarios.innerHTML = `
          <tr>
            <td colspan="5" class="empty error">
              ${escapeHtml(data.message || "No se pudieron cargar los usuarios.")}
            </td>
          </tr>
        `;
        return;
      }

      renderUsuarios(data.usuarios || []);
    } catch (error) {
      console.error(error);

      tablaUsuarios.innerHTML = `
        <tr>
          <td colspan="5" class="empty error">Error de conexión.</td>
        </tr>
      `;
    }
  }

function renderUsuarios(usuarios) {
  if (!usuarios.length) {
    tablaUsuarios.innerHTML = `
      <tr>
        <td colspan="5" class="empty">No hay usuarios registrados.</td>
      </tr>
    `;
    return;
  }

  tablaUsuarios.innerHTML = usuarios
    .map((u) => {
      const id = Number(u.id);
      const rol = Number(u.rol);
      const estado = Number(u.estado);

      const esAdmin = rol === 0;

      const rolTexto = esAdmin ? "Administrador" : "Usuario";
      const estadoTexto = estado === 1 ? "Activo" : "Inactivo";
      const estadoNuevo = estado === 1 ? 0 : 1;
      const textoBotonEstado = estado === 1 ? "Desactivar" : "Activar";

      let acciones = "";

      if (esAdmin) {
        acciones = `
          <span class="admin-protegido">
            Protegido
          </span>
        `;
      } else {
        acciones = `
          <div class="acciones-usuario">
            <button
              type="button"
              class="btn-table"
              data-estado-id="${id}"
              data-estado-nuevo="${estadoNuevo}"
            >
              ${textoBotonEstado}
            </button>

            <button
              type="button"
              class="btn-table danger"
              data-eliminar-id="${id}"
              data-usuario="${escapeHtml(u.usuario)}"
            >
              Eliminar
            </button>
          </div>
        `;
      }

      return `
        <tr>
          <td>${id}</td>

          <td>
            <strong>${escapeHtml(u.usuario)}</strong>
          </td>

          <td>
            <span class="badge ${esAdmin ? "admin" : "user"}">
              ${rolTexto}
            </span>
          </td>

          <td>
            <span class="estado ${estado === 1 ? "activo" : "inactivo"}">
              ${estadoTexto}
            </span>
          </td>

          <td>
            ${acciones}
          </td>
        </tr>
      `;
    })
    .join("");
}

  async function cambiarEstadoUsuario(id, estado) {
    try {
      const response = await fetch(window.CIRUGIAS_ADMIN_ESTADO_USUARIO_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({
          id,
          estado,
        }),
      });

      const data = await response.json();

      if (!response.ok || (!data.ok && !data.success)) {
        alert(data.message || "No se pudo actualizar el estado.");
        return;
      }

      cargarUsuarios();
    } catch (error) {
      console.error(error);
      alert("Error de conexión con el servidor.");
    }
  }

  async function eliminarUsuario(id) {
    try {
      const response = await fetch(window.CIRUGIAS_ADMIN_ELIMINAR_USUARIO_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({
          id,
        }),
      });

      const data = await response.json();

      if (!response.ok || (!data.ok && !data.success)) {
        alert(data.message || "No se pudo eliminar el usuario.");
        return;
      }

      alert(data.message || "Usuario eliminado correctamente.");
      cargarUsuarios();
    } catch (error) {
      console.error(error);
      alert("Error de conexión con el servidor.");
    }
  }

  async function cerrarSesion() {
    try {
      const response = await fetch(window.LOGOUT_CIRUGIAS_URL, {
        method: "POST",
        credentials: "include",
      });

      const data = await response.json();

      window.location.href = data.redirect || window.LOGIN_LS_URL || "/cirugias-login";
    } catch (error) {
      console.error(error);
      window.location.href = window.LOGIN_LS_URL || "/cirugias-login";
    }
  }

  function mostrarMensaje(texto, ok) {
    if (!mensajeForm) return;

    mensajeForm.textContent = texto;
    mensajeForm.className = ok ? "mensaje-form ok" : "mensaje-form error";
  }

  function limpiarMensaje() {
    if (!mensajeForm) return;

    mensajeForm.textContent = "";
    mensajeForm.className = "mensaje-form";
  }

  function escapeHtml(texto) {
    return String(texto ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }
});