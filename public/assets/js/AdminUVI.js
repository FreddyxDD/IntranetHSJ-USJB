document.addEventListener("DOMContentLoaded", () => {

  const form = document.getElementById("crearUsuarioForm");
  const mensaje = document.getElementById("mensajeAdmin");

  if (!form) {
    console.error("No existe crearUsuarioForm en el HTML");
    return;
  }

  // ==============================
  // MENSAJES
  // ==============================

  function mostrarMensaje(texto, tipo = "error") {
    mensaje.textContent = texto;

    if (tipo === "success") {
      mensaje.style.background = "rgba(34,197,94,0.1)";
      mensaje.style.border = "1px solid rgba(34,197,94,0.2)";
      mensaje.style.color = "#166534";
    } else {
      mensaje.style.background = "rgba(239,68,68,0.1)";
      mensaje.style.border = "1px solid rgba(239,68,68,0.2)";
      mensaje.style.color = "#991b1b";
    }
  }

  // ==============================
  // CREAR USUARIO
  // ==============================

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const usuario = document.getElementById("nuevoUsuario").value.trim();
    const password = document.getElementById("nuevoPassword").value.trim();
    const rol = Number(document.getElementById("nuevoRol").value);

    if (!usuario || !password) {
      mostrarMensaje("Completa todos los campos.");
      return;
    }

    try {

      const response = await fetch("/crear-cuenta-uvi", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        credentials: "include",
        body: JSON.stringify({
          usuario,
          password,
          rol
        })
      });

      const data = await response.json();

      if (!response.ok || !data.success) {
        mostrarMensaje(data.message || "No se pudo crear la cuenta.");
        return;
      }

      mostrarMensaje("Cuenta creada correctamente.", "success");

      form.reset();

      cargarUsuarios();

    } catch (error) {

      console.error(error);
      mostrarMensaje("Error de conexión con el servidor.");

    }

  });

  // ==============================
  // MOSTRAR / OCULTAR CONTRASEÑA
  // ==============================

  const btn = document.getElementById("togglePassword");
  const input = document.getElementById("nuevoPassword");

  if (btn && input) {

    let visible = false;

    lucide.createIcons();

    btn.addEventListener("click", () => {

      visible = !visible;

      input.type = visible ? "text" : "password";

      btn.innerHTML = visible
        ? '<i data-lucide="eye-off"></i>'
        : '<i data-lucide="eye"></i>';

      lucide.createIcons();

    });

  }

  // ==============================
  // MODAL DE USUARIOS
  // ==============================

  const btnUsuarios = document.getElementById("btnUsuarios");
  const modalUsuarios = document.getElementById("modalUsuarios");
  const cerrarModal = document.getElementById("cerrarModal");

  if (btnUsuarios && modalUsuarios && cerrarModal) {

    btnUsuarios.addEventListener("click", () => {

      modalUsuarios.classList.add("active");

      cargarUsuarios();

    });

    cerrarModal.addEventListener("click", () => {

      modalUsuarios.classList.remove("active");

    });

  }

  // ==============================
  // CARGAR USUARIOS
  // ==============================

  async function cargarUsuarios() {

    const tabla = document.getElementById("tablaUsuarios");

    tabla.innerHTML = "";

    try {

      const res = await fetch("/usuarios-uvi", {
        credentials: "include"
      });

      if (!res.ok) {
        console.error("Error cargando usuarios:", res.status);
        return;
      }

      const usuarios = await res.json();

      usuarios.forEach(u => {

        const rol =
          Number(u.rol) === 1
            ? "Administrador"
            : "Usuario";

        const estado =
          Number(u.estado) === 1
            ? "Activo"
            : "Inactivo";

        const claseEstado =
          Number(u.estado) === 1
            ? "estado-activo"
            : "estado-inactivo";

        const iconoEstado =
          Number(u.estado) === 1
            ? "toggle-right"
            : "toggle-left";

        const textoEstado =
          Number(u.estado) === 1
            ? "Desactivar"
            : "Activar";

        tabla.innerHTML += `
          <tr>
            <td>${u.usuario}</td>

            <td>${rol}</td>

            <td>
                <button
                    class="estado-switch ${Number(u.estado) === 1 ? 'activo' : 'inactivo'}"
                    data-id="${u.id}"
                >
                    <i data-lucide="${Number(u.estado) === 1 ? 'toggle-right' : 'toggle-left'}"></i>
                </button>
            </td>

            <td class="acciones">
                <button
                    class="btn-icon btn-edit"
                    data-id="${u.id}"
                >
                    <i data-lucide="pencil"></i>
                </button>
            </td>
          </tr>
        `;

      });

      lucide.createIcons();

    } catch (err) {

      console.error("Error:", err);

    }

  }

  // ==============================
  // EDITAR USUARIO
  // ==============================

    const formEditar = document.getElementById("formEditarUsuario");
    const modalEditar = document.getElementById("modalEditar");
    const cerrarEditar = document.getElementById("cerrarEditar");

    if (formEditar && modalEditar && cerrarEditar) {

    formEditar.addEventListener("submit", async (e) => {

        e.preventDefault();

        const id = document.getElementById("editId").value;
        const usuario = document.getElementById("editUsuario").value.trim();
        const rol = Number(document.getElementById("editRol").value);

        if (!usuario) {
        alert("Ingresa un usuario.");
        return;
        }

        try {

        const res = await fetch(`/usuarios-uvi/${id}`, {
            method: "PUT",
            headers: {
            "Content-Type": "application/json"
            },
            credentials: "include",
            body: JSON.stringify({
            usuario,
            rol
            })
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
            alert(data.message || "Error actualizando usuario");
            return;
        }

        const fila = document.querySelector(`.btn-edit[data-id="${id}"]`)?.closest("tr");

        if (fila) {
            fila.children[0].textContent = usuario;
            fila.children[1].textContent = rol === 1 ? "Administrador" : "Usuario";
        }

        modalEditar.classList.remove("active");

        } catch (err) {

        console.error(err);
        alert("Error de conexión con el servidor.");

        }

    });

    cerrarEditar.addEventListener("click", () => {

        modalEditar.classList.remove("active");

    });

    }

  // ==============================
  // ABRIR MODAL EDITAR
  // ==============================

  document.addEventListener("click", (e) => {

    if (!e.target.closest(".btn-edit")) return;

    const btn = e.target.closest(".btn-edit");
    const fila = btn.closest("tr");

    const id = btn.dataset.id;
    const usuario = fila.children[0].textContent;
    const rolTexto = fila.children[1].textContent;

    const rol = rolTexto === "Administrador" ? 1 : 0;

    document.getElementById("editId").value = id;
    document.getElementById("editUsuario").value = usuario;
    document.getElementById("editRol").value = rol;

    modalEditar.classList.add("active");

  });

  // ==============================
  // ACTIVAR / DESACTIVAR USUARIO
  // ==============================

    document.addEventListener("click", async (e) => {

    if (!e.target.closest(".estado-switch")) return;

    const btn = e.target.closest(".estado-switch");
    const id = btn.dataset.id;

    btn.disabled = true;

    try {

        const res = await fetch(`/usuarios-uvi/${id}/estado`, {
        method: "PATCH",
        credentials: "include"
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
        alert(data.message || "Error actualizando estado.");
        btn.disabled = false;
        return;
        }

        const estaActivo = btn.classList.contains("activo");

        btn.classList.toggle("activo", !estaActivo);
        btn.classList.toggle("inactivo", estaActivo);

        btn.innerHTML = estaActivo
        ? '<i data-lucide="toggle-left"></i>'
        : '<i data-lucide="toggle-right"></i>';

        lucide.createIcons();

        btn.disabled = false;

    } catch (err) {

        console.error(err);
        alert("Error de conexión con el servidor.");
        btn.disabled = false;

    }

    });
});