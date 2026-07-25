// Variables de gráficos en scope GLOBAL para que todas las funciones puedan accederlas
let graficoOrden = null;
let graficoEspecialidad = null;
let graficoDistribucionOrdenes = null;
let graficoOrdenModal = null;
let datosGraficoOrdenActual = null;
let chart = null;

let detalleMesAnioActual = "";
let detalleTipoOrdenActual = "";
let detalleEspecialidadActual = "";
let detalleTipoCirugiaActual = "";

document.addEventListener("DOMContentLoaded", () => {
  const tbody = document.getElementById("tablaCirugias");
  const mensaje = document.getElementById("mensajeTabla");

  const btnEliminar = document.getElementById("btnEliminar");
  const archivoExcel = document.getElementById("archivoExcel");
  const btnSeleccionar = document.getElementById("btnSeleccionar");
  const btnImportar = document.getElementById("btnImportar");

  const tabsHojas = document.getElementById("tabsHojas");
  const tabsMeses = document.getElementById("tabsMeses");

  const txtBusqueda = document.getElementById("txtBusqueda");
  const btnBuscar = document.getElementById("btnBuscar");
  const btnLimpiarBusqueda = document.getElementById("btnLimpiarBusqueda");

  //CAMPOS PARA CAMPO RAM
  const tieneRam = document.getElementById("tiene_ram");
  const campoRamMedicamento = document.getElementById("campoRamMedicamento");
  const ramMedicamentos = document.getElementById("ram_medicamentos");

  //FILTROS PARA LA TABLA DE REGISTRO
  const filtroEspecialidadCirugia = document.getElementById(
    "filtroEspecialidadCirugia",
  );
  const filtroTipoOrdenCirugia = document.getElementById(
    "filtroTipoOrdenCirugia",
  );

  const totalRegistros = document.getElementById("totalRegistros");
  const regValidos = document.getElementById("regValidos");
  const regObs = document.getElementById("regObs");
  const ultimaImportacion = document.getElementById("ultimaImportacion");

  /* =========================================================
     TOGGLE SIDEBAR - OCULTAR / MOSTRAR PANEL IZQUIERDO
  ========================================================= */

  const btnToggleSidebar = document.getElementById("btnToggleSidebar");

  if (btnToggleSidebar) {
    const estadoGuardado = localStorage.getItem("sidebarCollapsed");

    if (estadoGuardado === "true") {
      document.body.classList.add("sidebar-collapsed");
      btnToggleSidebar.innerHTML = '<i class="fa-solid fa-bars"></i>';
      btnToggleSidebar.setAttribute("title", "Mostrar panel");
      btnToggleSidebar.setAttribute("aria-label", "Mostrar panel lateral");
    } else {
      btnToggleSidebar.innerHTML = '<i class="fa-solid fa-bars-staggered"></i>';
      btnToggleSidebar.setAttribute("title", "Ocultar panel");
      btnToggleSidebar.setAttribute("aria-label", "Ocultar panel lateral");
    }

    btnToggleSidebar.addEventListener("click", () => {
      document.body.classList.toggle("sidebar-collapsed");

      const estaOculto = document.body.classList.contains("sidebar-collapsed");

      localStorage.setItem("sidebarCollapsed", estaOculto ? "true" : "false");

      btnToggleSidebar.innerHTML = estaOculto
        ? '<i class="fa-solid fa-bars"></i>'
        : '<i class="fa-solid fa-bars-staggered"></i>';

      btnToggleSidebar.setAttribute(
        "title",
        estaOculto ? "Mostrar panel" : "Ocultar panel",
      );

      btnToggleSidebar.setAttribute(
        "aria-label",
        estaOculto ? "Mostrar panel lateral" : "Ocultar panel lateral",
      );

      setTimeout(() => {
        if (graficoOrden) graficoOrden.resize();
        if (graficoEspecialidad) graficoEspecialidad.resize();
        if (graficoDistribucionOrdenes) graficoDistribucionOrdenes.resize();
        if (graficoOrdenModal) graficoOrdenModal.resize();
      }, 320);
    });
  }

  let hojaSeleccionada = null;
  let mesSeleccionado = "";

  cargarResumen();
  cargarMeses();
  cargarCirugias();
  cargarEspecialidades();
  cargarTablaEspecialidades();
  cargarFiltrosCIE10().then(() => {
    buscarCIE10Gestion();
  });
  cargarPersonalMedico();
  cargarProfesionesPersonal();

  // PESTAÑAS INTERNAS DE GESTIÓN
  const tabsGestion = document.querySelectorAll(".gestion-tab");
  const panelesGestion = document.querySelectorAll(".gestion-panel");

  tabsGestion.forEach((tab) => {
    tab.addEventListener("click", () => {
      const panel = tab.dataset.tab;

      tabsGestion.forEach((t) => t.classList.remove("activo"));
      panelesGestion.forEach((p) => p.classList.remove("activo"));

      tab.classList.add("activo");

      const panelSeleccionado = document.querySelector(
        `.gestion-panel[data-panel="${panel}"]`,
      );

      if (panelSeleccionado) {
        panelSeleccionado.classList.add("activo");
      }

      // Cargar los datos cuando se abre la pestaña Procedimientos
      if (panel === "procedimientos") {
        cargarSeccionesProcedimientos();
        cargarProcedimientos();
      }

      if (panel === "cie10") {
        buscarCIE10Gestion();
      }
    });
  });

  const txtBuscarPersonal = document.getElementById("txtBuscarPersonal");
  const filtroProfesionPersonal = document.getElementById(
    "filtroProfesionPersonal",
  );
  const btnAgregarPersonal = document.getElementById("btnAgregarPersonal");
  const btnGuardarPersonal = document.getElementById("btnGuardarPersonal");

  if (txtBuscarPersonal) {
    txtBuscarPersonal.addEventListener("input", () => {
      cargarPersonalMedico();
    });
  }

  if (filtroProfesionPersonal) {
    filtroProfesionPersonal.addEventListener("change", () => {
      cargarPersonalMedico();
    });
  }

  if (btnAgregarPersonal) {
    btnAgregarPersonal.addEventListener("click", async () => {
      await cargarProfesionesPersonal();

      document.getElementById("formPersonalMedico").reset();
      document.getElementById("personal_id").value = "";
      document.getElementById("personal_estado").value = "ACTIVO";
      document.getElementById("tituloModalPersonal").textContent =
        "Agregar personal médico";
      document.getElementById("subtituloModalPersonal").textContent =
        "Registra la información del nuevo personal médico.";
      btnGuardarPersonal.textContent = "Agregar personal";

      abrirModalElemento(document.getElementById("modalPersonalMedico"));
    });
  }

  function abrirModalElemento(modalElemento) {
    if (!modalElemento) return;

    modalElemento.classList.add("activo");
  }

  function cerrarModalElemento(modalElemento) {
    if (!modalElemento) return;

    modalElemento.classList.remove("activo");
  }

  //EVENTOS PERSONAL MEDICO
  const modalPersonalMedico = document.getElementById("modalPersonalMedico");
  const formPersonalMedico = document.getElementById("formPersonalMedico");
  const btnCerrarModalPersonal = document.getElementById(
    "btnCerrarModalPersonal",
  );
  const btnCancelarPersonal = document.getElementById("btnCancelarPersonal");

  if (btnCerrarModalPersonal) {
    btnCerrarModalPersonal.addEventListener("click", cerrarModalPersonalMedico);
  }

  if (btnCancelarPersonal) {
    btnCancelarPersonal.addEventListener("click", cerrarModalPersonalMedico);
  }

  if (modalPersonalMedico) {
    modalPersonalMedico.addEventListener("click", (e) => {
      if (e.target === modalPersonalMedico) {
        cerrarModalPersonalMedico();
      }
    });
  }

  if (formPersonalMedico) {
    formPersonalMedico.addEventListener("submit", async (e) => {
      e.preventDefault();

      const id = document.getElementById("personal_id").value;

      const esNuevo = !id;

      const data = {
        dni: document.getElementById("personal_dni").value.trim(),
        apellidos_nombres: document
          .getElementById("personal_apellidos_nombres")
          .value.trim(),
        profesion: document.getElementById("personal_profesion").value.trim(),
        modalidad_contrato: document
          .getElementById("personal_modalidad_contrato")
          .value.trim(),
        colegio_profesional: document
          .getElementById("personal_colegio_profesional")
          .value.trim(),
        numero_colegiatura: document
          .getElementById("personal_numero_colegiatura")
          .value.trim(),
        registro_especialidad: document
          .getElementById("personal_registro_especialidad")
          .value.trim(),
        estado: document.getElementById("personal_estado").value,
      };

      if (!data.apellidos_nombres) {
        alert("Los apellidos y nombres son obligatorios");
        return;
      }

      try {
        const url = esNuevo ? "/personal-medico" : `/personal-medico/${id}`;

        const metodo = esNuevo ? "POST" : "PUT";

        const res = await fetch(url, {
          method: metodo,
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
          body: JSON.stringify(data),
        });

        const json = await res.json();

        if (!res.ok || !json.success) {
          alert(json.message || "No se pudo guardar");
          return;
        }

        cerrarModalPersonalMedico();

        cargarPersonalMedico();
        cargarProfesionesPersonal();
      } catch (error) {
        console.error("Error guardando personal médico:", error);

        alert("Error de conexión al guardar");
      }
    });
  }

  //LO DE BUSQUEDA DEL PERSONAL EN EL FORMULARIO
  [
    "cirujano_1",
    "cirujano_2",
    "anestesiologo",
    "anestesiologo_recuperacion",
    "enfermera_instrumentista",
    "enfermera_recuperacion",
    "tecnico_enfermeria_1",
    "tecnico_enfermeria_2",
  ].forEach((campo) => {
    configurarBusquedaPersonal(campo);
  });

  async function cargarEspecialidades() {
    const select = document.getElementById("especialidad");

    try {
      const res = await fetch("/especialidades", {
        credentials: "include",
      });

      const respuesta = await res.json();
      const data = Array.isArray(respuesta.data) ? respuesta.data : [];

      select.innerHTML = '<option value="">Seleccione</option>';

      data.forEach((item) => {
        const option = document.createElement("option");
        option.value = item.nombre;
        option.textContent = item.nombre;
        select.appendChild(option);
      });

      if (filtroEspecialidadCirugia) {
        filtroEspecialidadCirugia.innerHTML =
          '<option value="">Todas las especialidades</option>';

        data.forEach((item) => {
          const option = document.createElement("option");
          option.value = item.nombre;
          option.textContent = item.nombre;
          filtroEspecialidadCirugia.appendChild(option);
        });
      }
    } catch (error) {
      console.error("Error cargando especialidades:", error);
    }
  }

  async function cargarTablaEspecialidades() {
    const tabla = document.getElementById("tablaEspecialidades");

    if (!tabla) return;

    try {
      const res = await fetch("/especialidades", {
        credentials: "include",
      });

      const respuesta = await res.json();
      const data = Array.isArray(respuesta.data) ? respuesta.data : [];

      tabla.innerHTML = "";

      data.forEach((item) => {
        tabla.innerHTML += `
          <tr>
            <td>${item.nombre || ""}</td>
          </tr>
        `;
      });
    } catch (error) {
      console.error("Error cargando tabla especialidades:", error);
    }
  }

  //CIE10
  async function cargarTablaCIE10() {
    const tabla = document.getElementById("tablaCIE10");

    if (!tabla) return;

    try {
      const res = await fetch("/cie10", {
        credentials: "include",
      });

      const data = await res.json();

      tabla.innerHTML = "";

      data.forEach((item) => {
        tabla.innerHTML += `
                    <tr>
                        <td>${item.codigo}</td>
                        <td>${item.descripcion}</td>
                    </tr>
                `;
      });
    } catch (error) {
      console.error("Error cargando CIE10:", error);
    }
  }

  const txtBuscarCIE = document.getElementById("txtBuscarCIE");

  const filtroEstadoCIE = document.getElementById("filtroEstadoCIE");
  const filtroSexoCIE = document.getElementById("filtroSexoCIE");

  async function buscarCIE10Gestion() {
    const tabla = document.getElementById("tablaCIE10");

    if (!tabla || !txtBuscarCIE) return;

    try {
      const texto = txtBuscarCIE.value.trim();
      const params = new URLSearchParams();

      if (texto.length >= 2) {
        params.append("q", texto);
      }

      if (filtroEstadoCIE && filtroEstadoCIE.value !== "") {
        params.append("estado", filtroEstadoCIE.value);
      }

      const url = params.toString()
        ? `/cie10?${params.toString()}`
        : "/cie10";

      tabla.innerHTML = `
        <tr>
          <td colspan="2">Cargando códigos CIE 10...</td>
        </tr>
      `;

      const res = await fetch(url, {
        credentials: "include",
      });

      const resultado = await res.json();

      if (!res.ok) {
        console.error("Error del servidor CIE10:", resultado);

        tabla.innerHTML = `
          <tr>
            <td colspan="2">
              ${resultado.message || "No se pudo cargar el CIE 10."}
            </td>
          </tr>
        `;

        return;
      }

      const data = Array.isArray(resultado)
        ? resultado
        : [];

      tabla.innerHTML = "";

      if (data.length === 0) {
        tabla.innerHTML = `
          <tr>
            <td colspan="2">
              No se encontraron códigos CIE 10.
            </td>
          </tr>
        `;

        return;
      }

      data.forEach((item) => {
        tabla.innerHTML += `
          <tr>
            <td>${item.codigo || ""}</td>
            <td>${item.descripcion || ""}</td>
          </tr>
        `;
      });
    } catch (error) {
      console.error("Error buscando CIE10 en gestión:", error);

      tabla.innerHTML = `
        <tr>
          <td colspan="2">
            Error de conexión al cargar el CIE 10.
          </td>
        </tr>
      `;
    }
  }

  txtBuscarCIE?.addEventListener("input", buscarCIE10Gestion);
  filtroEstadoCIE?.addEventListener("change", buscarCIE10Gestion);
  filtroSexoCIE?.addEventListener("change", buscarCIE10Gestion);

  async function cargarFiltrosCIE10(estadoDefault = "") {
    const filtroEstadoCIE = document.getElementById("filtroEstadoCIE");
    const filtroSexoCIE = document.getElementById("filtroSexoCIE");

    if (!filtroEstadoCIE || !filtroSexoCIE) return;

    try {
      const [resEstados, resSexos] = await Promise.all([
        fetch("/cie10/estados", { credentials: "include" }),
        fetch("/cie10/sexos", { credentials: "include" }),
      ]);

      const estados = await resEstados.json();
      const sexos = await resSexos.json();

      filtroEstadoCIE.innerHTML = `
                <option value="">Todos los estados</option>
            `;

      estados.forEach((item) => {
        filtroEstadoCIE.innerHTML += `
                    <option value="${item.id}">
                      ${item.nombre}
                    </option>
                `;
      });

      filtroSexoCIE.innerHTML = `
                <option value="">Todos los sexos</option>
            `;

      sexos.forEach((item) => {
        filtroSexoCIE.innerHTML += `
                    <option value="${item.id}">
                      ${item.nombre}
                    </option>
                  `;
      });

      filtroSexoCIE.value = "";

      filtroEstadoCIE.value = estadoDefault;
    } catch (error) {
      console.error("Error cargando filtros CIE10:", error);
    }
  }

  const btnLimpiarCIE10 = document.getElementById("btnLimpiarCIE10");

  btnLimpiarCIE10?.addEventListener("click", async () => {
    txtBuscarCIE.value = "";

    await cargarFiltrosCIE10();

    filtroEstadoCIE.value = "";
    filtroSexoCIE.value = "";

    buscarCIE10Gestion();
  });

  const btnLimpiarPersonal = document.getElementById("btnLimpiarPersonal");
  btnLimpiarPersonal?.addEventListener("click", () => {
    document.getElementById("txtBuscarPersonal").value = "";
    document.getElementById("filtroProfesionPersonal").value = "";
    cargarPersonalMedico();
  });

  //PERSONAL MEDICO
  async function cargarPersonalMedico() {
    const tabla = document.getElementById("tablaPersonalMedico");
    const txtBuscar = document.getElementById("txtBuscarPersonal");
    const filtroProfesion = document.getElementById("filtroProfesionPersonal");

    if (!tabla) return;

    try {
      const params = new URLSearchParams();

      if (txtBuscar && txtBuscar.value.trim()) {
        params.append("busqueda", txtBuscar.value.trim());
      }

      if (filtroProfesion && filtroProfesion.value) {
        params.append("profesion", filtroProfesion.value);
      }

      const url = params.toString()
        ? `/personal-medico?${params.toString()}`
        : "/personal-medico";

      const res = await fetch(url, {
        credentials: "include",
      });

      const respuesta = await res.json();

      const data = Array.isArray(respuesta.data)
        ? respuesta.data
        : [];

      tabla.innerHTML = "";

      data.forEach((item) => {
        tabla.innerHTML += `
          <tr>
            <td class="col-dni-personal">
              ${item.dni || ""}
            </td>

            <td class="col-nombre-personal">
              <span
                class="texto-nombre-personal"
                data-id="${item.id || ""}"
              >
                ${item.apellidos_nombres || ""}
              </span>
            </td>

            <td class="col-profesion-personal">
              ${item.profesion || ""}
            </td>

            <td class="col-estado-personal">
              <span
                class="texto-estado-personal ${
                  item.estado === "ACTIVO" ? "activo" : "inactivo"
                }"
                data-id="${item.id || ""}"
              >
                ${item.estado || ""}
              </span>
            </td>
          </tr>
        `;
      });

      document.querySelectorAll(".texto-estado-personal").forEach((estado) => {
        estado.addEventListener("click", async () => {
          const id = estado.dataset.id;

          await fetch(`/personal-medico/${id}/estado`, {
            method: "PUT",
            credentials: "include",
          });

          cargarPersonalMedico();
        });
      });

      document.querySelectorAll(".texto-nombre-personal").forEach((nombre) => {
        nombre.addEventListener("click", () => {
          const id = nombre.dataset.id;
          abrirModalPersonalMedico(id);
        });
      });
    } catch (error) {
      console.error("Error cargando personal médico:", error);
    }
  }

  async function abrirModalPersonalMedico(id) {
    try {
      const res = await fetch(`/personal-medico/${id}`, {
        credentials: "include",
      });

      const json = await res.json();

      if (!res.ok || !json.success) {
        alert(json.message || "No se pudo cargar el personal médico");
        return;
      }

      const item = json.data;

      document.getElementById("tituloModalPersonal").textContent =
        "Editar personal médico";
      document.getElementById("subtituloModalPersonal").textContent =
        "Actualiza la información registrada.";
      btnGuardarPersonal.textContent = "Guardar cambios";

      document.getElementById("personal_id").value = item.id || "";
      document.getElementById("personal_dni").value = item.dni || "";
      document.getElementById("personal_apellidos_nombres").value =
        item.apellidos_nombres || "";
      document.getElementById("personal_profesion").value =
        item.profesion || "";
      document.getElementById("personal_modalidad_contrato").value =
        item.modalidad_contrato || "";
      document.getElementById("personal_colegio_profesional").value =
        item.colegio_profesional || "";
      document.getElementById("personal_numero_colegiatura").value =
        item.numero_colegiatura || "";
      document.getElementById("personal_registro_especialidad").value =
        item.registro_especialidad || "";
      document.getElementById("personal_estado").value =
        item.estado || "ACTIVO";

      abrirModalElemento(document.getElementById("modalPersonalMedico"));
    } catch (error) {
      console.error("Error abriendo modal personal médico:", error);
      alert("Error al abrir el formulario");
    }
  }

  function cerrarModalPersonalMedico() {
    cerrarModalElemento(document.getElementById("modalPersonalMedico"));
    document.getElementById("formPersonalMedico").reset();
  }

  //FILTRO PROFESIONES
  async function cargarProfesionesPersonal() {
    const select = document.getElementById("filtroProfesionPersonal");
    const datalist = document.getElementById("listaProfesionesPersonal");

    try {
      const res = await fetch("/personal-medico/profesiones", {
        credentials: "include",
      });

      const data = await res.json();

      if (select) {
        select.innerHTML = '<option value="">Todas las profesiones</option>';

        data.forEach((item) => {
          select.innerHTML += `
                        <option value="${item.profesion}">
                            ${item.profesion}
                        </option>
                    `;
        });
      }

      if (datalist) {
        datalist.innerHTML = "";

        data.forEach((item) => {
          datalist.innerHTML += `
                        <option value="${item.profesion}">
                    `;
        });
      }
    } catch (error) {
      console.error("Error cargando profesiones:", error);
    }
  }

  // BUSCAR PROCEDIMIENTO FORMULARIO
  // BUSCAR PROCEDIMIENTO FORMULARIO DESDE SIGH + MARCAR MAYOR / MENOR
  const inputProcedimiento = document.getElementById("operacion_realizada");
  const sugerenciasProcedimientos = document.getElementById(
    "sugerenciasProcedimientos",
  );

  function limpiarTipoCirugia() {
    const radioMayor = document.querySelector(
      'input[name="tipo_cirugia"][value="MAYOR"]',
    );
    const radioMenor = document.querySelector(
      'input[name="tipo_cirugia"][value="MENOR"]',
    );

    if (radioMayor) radioMayor.checked = false;
    if (radioMenor) radioMenor.checked = false;
  }

  function marcarTipoCirugia(tipo) {
    const radioMayor = document.querySelector(
      'input[name="tipo_cirugia"][value="MAYOR"]',
    );
    const radioMenor = document.querySelector(
      'input[name="tipo_cirugia"][value="MENOR"]',
    );

    limpiarTipoCirugia();

    if (tipo === "MAYOR" && radioMayor) {
      radioMayor.checked = true;
    }

    if (tipo === "MENOR" && radioMenor) {
      radioMenor.checked = true;
    }
  }

  if (inputProcedimiento && sugerenciasProcedimientos) {
    inputProcedimiento.addEventListener("input", async () => {
      const texto = inputProcedimiento.value.trim();

      limpiarTipoCirugia();

      if (texto.length < 2) {
        sugerenciasProcedimientos.innerHTML = "";
        sugerenciasProcedimientos.style.display = "none";
        return;
      }

      try {
        const res = await fetch(
          `/sigh/procedimientos/sugerencias?q=${encodeURIComponent(texto)}`,
          {
            credentials: "include",
          },
        );

        const data = await res.json();

        sugerenciasProcedimientos.innerHTML = "";

        if (!Array.isArray(data) || data.length === 0) {
          sugerenciasProcedimientos.style.display = "none";
          return;
        }

        data.forEach((item) => {
          const opcion = document.createElement("button");
          opcion.type = "button";
          opcion.className = "opcion-procedimiento";

          const tipo = item.tipo_cirugia_sugerido || "";

          opcion.innerHTML = `
                    <strong>${item.codigo || ""}</strong>
                    <span>${item.nombre || ""}</span>
                    <small>
                        ${item.grupo || ""}
                        ${item.seccion ? " / " + item.seccion : ""}
                        ${tipo ? " — " + tipo : ""}
                    </small>
                `;

          opcion.addEventListener("click", () => {
            inputProcedimiento.value = `${item.codigo || ""} - ${item.nombre || ""}`;

            marcarTipoCirugia(tipo);

            sugerenciasProcedimientos.innerHTML = "";
            sugerenciasProcedimientos.style.display = "none";
          });

          sugerenciasProcedimientos.appendChild(opcion);
        });

        sugerenciasProcedimientos.style.display = "block";
      } catch (error) {
        console.error("Error buscando procedimientos en SIGH:", error);
        sugerenciasProcedimientos.innerHTML = "";
        sugerenciasProcedimientos.style.display = "none";

        mostrarToast(
          "error",
          "Error",
          "No se pudo buscar el procedimiento en SIGH.",
        );
      }
    });
  }

  function aplicarRespuestaOperacionCIE10(info, inputOperacion) {
    if (!info) {
      if (inputOperacion) inputOperacion.value = "";
      limpiarTipoCirugia();
      pintarSugerenciasOperacionCIE10([]);
      return;
    }

    // 1. Mayor/Menor se marca siempre que venga del backend
    marcarTipoCirugia(info.tipo_cirugia_sugerido || "");

    // 2. Operación realizada SOLO se llena si el backend confirma confianza alta
    if (info.operacion_autocompletada && info.operacion_realizada) {
      if (inputOperacion) {
        inputOperacion.value = info.operacion_realizada;
      }
    } else {
      if (inputOperacion) {
        inputOperacion.value = "";
      }
    }

    // 3. Si no autocompletó, muestra sugerencias
    pintarSugerenciasOperacionCIE10(info.sugerencias_operacion || []);

    if (info.mensaje) {
      console.log("CIE10:", info.mensaje);
    }
  }

  function pintarSugerenciasOperacionCIE10(sugerencias = []) {
    if (!sugerenciasProcedimientos) return;

    sugerenciasProcedimientos.innerHTML = "";

    if (!Array.isArray(sugerencias) || sugerencias.length === 0) {
      sugerenciasProcedimientos.style.display = "none";
      return;
    }

    sugerencias.forEach((item) => {
      const opcion = document.createElement("button");
      opcion.type = "button";
      opcion.className = "opcion-procedimiento";

      const tipo =
        item.tipo_cirugia_por_procedimiento || item.tipo_cirugia_sugerido || "";

      opcion.innerHTML = `
      <strong>${item.codigo || ""}</strong>
      <span>${item.nombre || ""}</span>
      <small>
        Coincidencia: ${item.confianza_operacion || ""}
        ${tipo ? " — " + tipo : ""}
      </small>
    `;

      opcion.addEventListener("click", () => {
        const inputOperacion = document.getElementById("operacion_realizada");

        if (inputOperacion) {
          inputOperacion.value =
            item.operacion_realizada ||
            `${item.codigo || ""} - ${item.nombre || ""}`.trim();
        }

        marcarTipoCirugia(tipo);

        sugerenciasProcedimientos.innerHTML = "";
        sugerenciasProcedimientos.style.display = "none";
      });

      sugerenciasProcedimientos.appendChild(opcion);
    });

    sugerenciasProcedimientos.style.display = "block";
  }

  // ==========================================================
  // REPORTES MENSUALES - TABLA ESTILO CENTRO QUIRÚRGICO
  // ==========================================================

  function obtenerNombreMesReporteUI(mes) {
    const meses = [
      "Enero",
      "Febrero",
      "Marzo",
      "Abril",
      "Mayo",
      "Junio",
      "Julio",
      "Agosto",
      "Septiembre",
      "Octubre",
      "Noviembre",
      "Diciembre",
    ];

    return meses[Number(mes) - 1] || "Mes";
  }

  function formatearNumeroReporte(valor) {
    const numero = Number(valor || 0);

    if (!Number.isFinite(numero)) return "0";

    if (Math.abs(numero - Math.round(numero)) < 0.01) {
      return String(Math.round(numero));
    }

    return numero.toFixed(1);
  }

  function valorMesReporteActual() {
    const select = document.getElementById("selectMesReporte");
    return select ? select.value : "";
  }

  async function leerJsonReportes(res) {
    const texto = await res.text();

    try {
      return texto ? JSON.parse(texto) : {};
    } catch (error) {
      console.error("Respuesta no JSON del servidor:", texto);
      return {
        ok: false,
        success: false,
        message: "El servidor devolvió una respuesta inválida.",
        raw: texto,
      };
    }
  }

  function limpiarReporteMensualUI(periodo = "MES SELECCIONADO") {
    pintarReporteMensual({
      periodo,
      resumen: {
        total_cirugias: 0,
        electiva_total: 0,
        emergencia_total: 0,
        total_horas: 0,
        electiva_mayor: 0,
        electiva_menor: 0,
        emergencia_mayor: 0,
        emergencia_menor: 0,
        electiva_mayor_detalle: {},
        electiva_menor_detalle: {},
        emergencia_mayor_detalle: {},
        emergencia_menor_detalle: {},
      },
      data: [],
    });
  }

async function cargarMesesReportes() {
  const select = document.getElementById("selectMesReporte");

  if (!select) return;

  select.innerHTML = `<option value="">Cargando meses...</option>`;

  try {
    const res = await fetch("/api/reportes/meses-disponibles", {
      method: "GET",
      credentials: "include",
      headers: {
        "Accept": "application/json",
      },
    });

    const texto = await res.text();

    let json = null;

    try {
      json = JSON.parse(texto);
    } catch (e) {
      console.error("La ruta /api/reportes/meses-disponibles no devolvió JSON:");
      console.error(texto);

      select.innerHTML = `<option value="">Error backend reportes</option>`;
      pintarReporteMensual(null);
      return;
    }

    if (!res.ok || !json.ok) {
      console.error("Respuesta incorrecta meses:", json);
      select.innerHTML = `<option value="">No se pudieron cargar meses</option>`;
      pintarReporteMensual(null);
      return;
    }

    const meses = Array.isArray(json.data) ? json.data : [];

    select.innerHTML = "";

    if (meses.length === 0) {
      select.innerHTML = `<option value="">No existen registros</option>`;
      pintarReporteMensual(null);
      return;
    }

    const mesesOrdenados = [...meses].sort((a, b) => {
      const anioA = Number(a.anio);
      const anioB = Number(b.anio);
      const mesA = Number(a.mes);
      const mesB = Number(b.mes);

      if (anioA !== anioB) return anioA - anioB;
      return mesA - mesB;
    });

    mesesOrdenados.forEach((item) => {
      const mes = Number(item.mes);
      const anio = Number(item.anio);
      const total = Number(item.total_registros || item.total || 0);

      const option = document.createElement("option");
      option.value = `${anio}-${String(mes).padStart(2, "0")}`;
      option.textContent = `${obtenerNombreMesReporteUI(mes)} ${anio} (${total} registros)`;

      select.appendChild(option);
    });

    select.selectedIndex = 0;

    await cargarReporteMensual(select.value);
  } catch (error) {
    console.error("Error cargando meses de reportes:", error);
    select.innerHTML = `<option value="">Error al cargar meses</option>`;
    pintarReporteMensual(null);
  }
}

  async function cargarReporteMensual(valorMesAnio = valorMesReporteActual()) {
    const tabla = document.getElementById("tablaReporteMensual");

    if (!tabla) return;

    if (!valorMesAnio) {
      limpiarReporteMensualUI();
      return;
    }

    const [anio, mes] = valorMesAnio.split("-").map(Number);

    if (!anio || !mes) {
      limpiarReporteMensualUI();
      return;
    }

    try {
      tabla.innerHTML = `
        <tr>
          <td colspan="17">Cargando reporte mensual...</td>
        </tr>
      `;

      const res = await fetch(
        `/api/reportes/cirugias-mensual?anio=${encodeURIComponent(anio)}&mes=${encodeURIComponent(mes)}`,
        {
          method: "GET",
          credentials: "include",
          headers: {
            Accept: "application/json",
          },
        },
      );

      const json = await leerJsonReportes(res);

      if (!res.ok || !(json.ok || json.success)) {
        console.error("Error backend reporte mensual:", json);

        tabla.innerHTML = `
          <tr>
            <td colspan="17">No se pudo cargar el reporte mensual.</td>
          </tr>
        `;

        limpiarReporteMensualUI();
        return;
      }

      if (json.existe_reporte === false) {
        pintarReporteMensual({
          periodo: json.periodo || `${obtenerNombreMesReporteUI(mes)} ${anio}`,
          resumen: json.resumen || {},
          data: [],
        });
        return;
      }

      pintarReporteMensual(json);
    } catch (error) {
      console.error("Error cargando reporte mensual:", error);

      tabla.innerHTML = `
        <tr>
          <td colspan="17">Error de conexión al cargar el reporte.</td>
        </tr>
      `;

      limpiarReporteMensualUI();
    }
  }

  function pintarReporteMensual(json) {
    const tabla = document.getElementById("tablaReporteMensual");

    if (!tabla) return;

    const data = Array.isArray(json?.data) ? json.data : [];
    const resumen = json?.resumen || {};
    const periodo = json?.periodo || "MES SELECCIONADO";

    const titulo = document.getElementById("tituloReporteMensual");
    const tituloElectivas = document.getElementById("tituloElectivasReporte");
    const tituloEmergencias = document.getElementById("tituloEmergenciasReporte");

    if (titulo) titulo.textContent = `MES DE ${String(periodo).toUpperCase()}`;

    if (tituloElectivas) {
      tituloElectivas.textContent = `CIRUGÍAS ELECTIVAS REALIZADAS ${String(periodo).toUpperCase()}`;
    }

    if (tituloEmergencias) {
      tituloEmergencias.textContent = `CIRUGÍAS EMERGENCIAS REALIZADAS ${String(periodo).toUpperCase()}`;
    }

    const setText = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.textContent = value;
    };

    setText("reporteTotalCirugias", resumen.total_cirugias || 0);
    setText("reporteTotalElectivas", resumen.electiva_total || 0);
    setText("reporteTotalEmergencias", resumen.emergencia_total || 0);
    setText("reporteTotalHoras", formatearNumeroReporte(resumen.total_horas || 0));

    setText("reporteElectivaMayor", resumen.electiva_mayor || 0);
    setText("reporteElectivaMenor", resumen.electiva_menor || 0);
    setText("reporteElectivaTotal", resumen.electiva_total || 0);

    setText("reporteEmergenciaMayor", resumen.emergencia_mayor || 0);
    setText("reporteEmergenciaMenor", resumen.emergencia_menor || 0);
    setText("reporteEmergenciaTotal", resumen.emergencia_total || 0);

    if (data.length === 0) {
      tabla.innerHTML = `
        <tr>
          <td colspan="17">No hay registros para el mes seleccionado.</td>
        </tr>
      `;
      return;
    }

    const celdasGrupo = (grupo = {}) => `
      <td>${grupo.numero || 0}</td>
      <td>${formatearNumeroReporte(grupo.t_operatorio || 0)}</td>
      <td>${formatearNumeroReporte(grupo.t_anestesico || 0)}</td>
      <td>${formatearNumeroReporte(grupo.t_total_hrs || 0)}</td>
    `;

    tabla.innerHTML = data
      .map(
        (fila) => `
          <tr>
            <td class="servicio-reporte">${fila.servicio || "SIN SERVICIO"}</td>
            ${celdasGrupo(fila.electiva_mayor)}
            ${celdasGrupo(fila.electiva_menor)}
            ${celdasGrupo(fila.emergencia_mayor)}
            ${celdasGrupo(fila.emergencia_menor)}
          </tr>
        `,
      )
      .join("");

    tabla.innerHTML += `
      <tr class="fila-total-reporte">
        <td>TOTAL</td>
        ${celdasGrupo(resumen.electiva_mayor_detalle || {})}
        ${celdasGrupo(resumen.electiva_menor_detalle || {})}
        ${celdasGrupo(resumen.emergencia_mayor_detalle || {})}
        ${celdasGrupo(resumen.emergencia_menor_detalle || {})}
      </tr>
    `;
  }

  const selectMesReporte = document.getElementById("selectMesReporte");
  const btnActualizarReporte = document.getElementById("btnActualizarReporte");
  const btnImprimirReporte = document.getElementById("btnImprimirReporte");

  if (selectMesReporte) {
    selectMesReporte.addEventListener("change", () => {
      cargarReporteMensual(selectMesReporte.value);
    });
  }

  if (btnActualizarReporte) {
    btnActualizarReporte.addEventListener("click", async () => {
      await cargarMesesReportes();
    });
  }

  if (btnImprimirReporte) {
    btnImprimirReporte.addEventListener("click", () => {
      window.print();
    });
  }

  // ===============================
  // GRÁFICO ANALISIS MENSUAL
  // ===============================

  async function cargarGrafico() {
    try {
      const res = await fetch("/api/analisis/cirugias-mensual?anio=2026");
      const json = await res.json();

      if (!json.ok) return;

      const data = json.data;

      const meses = [...new Set(data.map((d) => d.mes))];
      const tipos = [...new Set(data.map((d) => d.tipo_orden))];

      const datasets = tipos.map((tipo) => ({
        label: tipo,
        data: meses.map((mes) => {
          const item = data.find((d) => d.mes === mes && d.tipo_orden === tipo);
          return item ? item.total : 0;
        }),
      }));

      const ctx = document.getElementById("graficoCirugias");

      if (!ctx) return;

      if (chart) chart.destroy();

      chart = new Chart(ctx, {
        type: "bar",
        data: {
          labels: meses,
          datasets: datasets,
        },
        options: {
          responsive: true,
          plugins: {
            title: {
              display: true,
              text: "Cirugías por mes y tipo de orden (2026)",
            },
          },
        },
      });
    } catch (error) {
      console.error("Error cargando gráfico:", error);
    }
  }

  btnSeleccionar.addEventListener("click", () => {
    archivoExcel.click();
  });

  archivoExcel.addEventListener("change", async () => {
    const archivo = archivoExcel.files[0];

    if (!archivo) {
      mensaje.textContent = "No seleccionaste ningún archivo.";
      mensaje.className = "mensaje error";
      return;
    }

    const formData = new FormData();
    formData.append("archivo", archivo);

    try {
      mensaje.textContent = "Leyendo hojas del Excel...";
      mensaje.className = "mensaje info";

      const res = await fetch("/excel-hojas", {
        method: "POST",
        body: formData,
        credentials: "include",
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        mensaje.textContent =
          data.message || "No se pudieron leer las hojas del Excel.";
        mensaje.className = "mensaje error";
        return;
      }

      tabsHojas.innerHTML = "";

      data.hojas.forEach((nombre, index) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.textContent = nombre;

        if (index === 0) {
          btn.classList.add("activo");
          hojaSeleccionada = nombre;
        }

        btn.addEventListener("click", () => {
          hojaSeleccionada = nombre;

          document.querySelectorAll("#tabsHojas button").forEach((b) => {
            b.classList.remove("activo");
          });

          btn.classList.add("activo");
        });

        tabsHojas.appendChild(btn);
      });

      mensaje.textContent =
        "Archivo seleccionado. Elige una hoja para importar.";
      mensaje.className = "mensaje success";
    } catch (error) {
      console.error("Error leyendo hojas:", error);
      mensaje.textContent = "Error al leer las hojas del Excel.";
      mensaje.className = "mensaje error";
    }
  });

  btnImportar.addEventListener("click", async () => {
    const archivo = archivoExcel.files[0];

    if (!archivo) {
      mensaje.textContent = "Selecciona un archivo Excel primero.";
      mensaje.className = "mensaje error";
      return;
    }

    if (!hojaSeleccionada) {
      mensaje.textContent = "Selecciona una hoja del Excel.";
      mensaje.className = "mensaje error";
      return;
    }

    const formData = new FormData();
    formData.append("archivo", archivo);
    formData.append("hoja", hojaSeleccionada);

    try {
      mensaje.textContent = `Importando hoja "${hojaSeleccionada}"...`;
      mensaje.className = "mensaje info";

      const res = await fetch("/importar-cirugias", {
        method: "POST",
        body: formData,
        credentials: "include",
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        mensaje.textContent = data.message || "Error al importar.";
        mensaje.className = "mensaje error";
        return;
      }

      mensaje.textContent = data.message;
      mensaje.className = "mensaje success";

      archivoExcel.value = "";
      hojaSeleccionada = null;
      tabsHojas.innerHTML = "";

      cargarResumen();
      cargarMeses();
      cargarCirugias();
    } catch (error) {
      console.error("Error importando:", error);
      mensaje.textContent = "Error de conexión con el servidor.";
      mensaje.className = "mensaje error";
    }
  });

  btnEliminar.addEventListener("click", async () => {
    const confirmar = confirm(
      "¿Seguro que deseas eliminar todos los datos importados?",
    );

    if (!confirmar) return;

    try {
      mensaje.textContent = "Eliminando datos...";
      mensaje.className = "mensaje info";

      const res = await fetch("/cirugias", {
        method: "DELETE",
        credentials: "include",
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        mensaje.textContent =
          data.message || "No se pudieron eliminar los datos.";
        mensaje.className = "mensaje error";
        return;
      }

      tbody.innerHTML = "";
      mesSeleccionado = "";
      tabsMeses.innerHTML = "";

      actualizarKPIsVacios();

      mensaje.textContent = data.message;
      mensaje.className = "mensaje success";

      cargarResumen();
    } catch (error) {
      console.error("Error eliminando datos:", error);
      mensaje.textContent = "Error de conexión con el servidor.";
      mensaje.className = "mensaje error";
    }
  });

  btnBuscar.addEventListener("click", () => {
    cargarCirugias();
  });

  btnLimpiarBusqueda.addEventListener("click", () => {
    txtBusqueda.value = "";
    filtroEspecialidadCirugia.value = "";
    filtroTipoOrdenCirugia.value = "";

    mesSeleccionado = "";

    cargarMeses();
    cargarCirugias();
  });

  txtBusqueda.addEventListener("keyup", (e) => {
    if (e.key === "Enter") {
      cargarCirugias();
    }
  });

  async function cargarResumen() {
    try {
      const res = await fetch("/cirugias-resumen", {
        method: "GET",
        credentials: "include",
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        console.warn("No se pudo cargar resumen:", data.message);
        return;
      }

      const r = data.resumen || {};

      if (totalRegistros) {
        totalRegistros.textContent = Number(r.totalRegistros || 0);
      }

      if (regValidos) {
        regValidos.textContent = Number(r.registrosValidos || 0);
      }

      if (regObs) {
        regObs.textContent = Number(r.conObservaciones || 0);
      }

      if (ultimaImportacion) {
        if (r.ultimaImportacion) {
          const fecha = new Date(r.ultimaImportacion);

          ultimaImportacion.textContent = fecha.toLocaleDateString("es-PE", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
          });
        } else {
          ultimaImportacion.textContent = "--";
        }
      }
    } catch (error) {
      console.error("Error cargando resumen:", error);
    }
  }

  function actualizarKPIsVacios() {
    if (totalRegistros) totalRegistros.textContent = "0";
    if (regValidos) regValidos.textContent = "0";
    if (regObs) regObs.textContent = "0";
    if (ultimaImportacion) ultimaImportacion.textContent = "--";
  }

  async function cargarMeses() {
    try {
      if (!tabsMeses) {
        console.error("No existe #tabsMeses en el HTML");
        return;
      }

      const res = await fetch("/cirugias-hojas", {
        credentials: "include",
      });

      const data = await res.json();

      console.log("Respuesta /cirugias-hojas:", data);

      if (!res.ok || !data.success) {
        console.error("No se pudieron cargar los meses:", data);
        tabsMeses.innerHTML = "";
        return;
      }

      tabsMeses.innerHTML = "";
      tabsMeses.style.display = "flex";
      tabsMeses.style.visibility = "visible";
      tabsMeses.style.opacity = "1";

      const btnTodos = document.createElement("button");
      btnTodos.type = "button";
      btnTodos.textContent = "Todos";

      if (!mesSeleccionado) {
        btnTodos.classList.add("activo");
      }

      btnTodos.addEventListener("click", () => {
        mesSeleccionado = "";

        document.querySelectorAll("#tabsMeses button").forEach((b) => {
          b.classList.remove("activo");
        });

        btnTodos.classList.add("activo");
        cargarCirugias();
      });

      tabsMeses.appendChild(btnTodos);

      const hojas = Array.isArray(data.hojas) ? data.hojas : [];

      hojas.forEach((hoja) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.textContent = hoja;

        if (mesSeleccionado === hoja) {
          btn.classList.add("activo");
        }

        btn.addEventListener("click", () => {
          mesSeleccionado = hoja;

          document.querySelectorAll("#tabsMeses button").forEach((b) => {
            b.classList.remove("activo");
          });

          btn.classList.add("activo");
          cargarCirugias();
        });

        tabsMeses.appendChild(btn);
      });
    } catch (error) {
      console.error("Error cargando meses:", error);
    }
  }

  async function cargarCirugias() {
    try {
      mensaje.textContent = "Cargando datos...";
      mensaje.className = "mensaje info";

      const params = new URLSearchParams();

      if (txtBusqueda.value.trim()) {
        params.append("busqueda", txtBusqueda.value.trim());
      }

      if (filtroEspecialidadCirugia.value) {
        params.append("especialidad", filtroEspecialidadCirugia.value);
      }

      if (filtroTipoOrdenCirugia.value) {
        params.append("tipo_orden", filtroTipoOrdenCirugia.value);
      }

      if (mesSeleccionado) {
        params.append("hoja", mesSeleccionado);
      }

      const url = params.toString()
        ? `/cirugias?${params.toString()}`
        : "/cirugias";

      const res = await fetch(url, {
        method: "GET",
        credentials: "include",
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        mensaje.textContent =
          data.message || "No se pudieron cargar los datos.";
        mensaje.className = "mensaje error";
        return;
      }

      tbody.innerHTML = "";

      if (data.data.length === 0) {
        mensaje.textContent = "No hay registros para mostrar.";
        mensaje.className = "mensaje info";
        return;
      }

      const tieneTiempoUrpa = data.data.some(
        (item) => item.tiempo_urpa && String(item.tiempo_urpa).trim() !== "",
      );

      const colTiempoUrpa = document.getElementById("colTiempoUrpa");

      if (colTiempoUrpa) {
        colTiempoUrpa.style.display = tieneTiempoUrpa ? "" : "none";
      }

      data.data.forEach((item) => {
        const fila = document.createElement("tr");

        fila.innerHTML = `
                    <td>${formatearFecha(item.fecha)}</td>
                    <td>${formatearHora(item.hora)}</td>
                    <td>
                        <button class="btn-ver-detalle" data-id="${item.id}">
                            ${item.historia_clinica || "Ver"}
                        </button>
                    </td>
                    <td>${item.dni || ""}</td>
                    <td>${item.nombres_apellidos || ""}</td>
                    <td>${item.tipo_orden || ""}</td>
                    <td>${item.especialidad || ""}</td>
                    <td>${item.edad || ""}</td>
                    <td>${item.sexo || ""}</td>
                    <td>${item.tipo_seguro || ""}</td>
                    <td>${item.prueba_covid || ""}</td>
                    <td>${item.suspension || ""}</td>
                    <td>${item.motivo_suspension || ""}</td>
                    <td>${item.diagnostico_preoperatorio || ""}</td>
                    <td>${item.codigo_cie10 || ""}</td>
                    <td>${item.operacion_realizada || ""}</td>
                    <td>${item.comorbilidad || ""}</td>
                    <td>${item.reintervencion || ""}</td>
                    <td>${item.ram_medicamentos || ""}</td>
                    <td>${item.discrepancia_diagnostica || ""}</td>
                    <td>${item.tiempo_total || ""}</td>
                    <td>${item.tiempo_anestesia || ""}</td>
                    <td>${item.tiempo_operacion || ""}</td>
                    <td>${item.complicaciones_intraoperatorias || ""}</td>
                    <td>${item.cirujano_1 || ""}</td>
                    <td>${item.cirujano_2 || ""}</td>
                    <td>${item.anestesiologo || ""}</td>
                    <td>${item.enfermera_instrumentista || ""}</td>
                    <td>${item.anestesiologo_recuperacion || ""}</td>
                    <td>${item.enfermera_recuperacion || ""}</td>
                    <td>${item.tecnico_enfermeria_1 || ""}</td>
                    <td>${item.tecnico_enfermeria_2 || ""}</td>
                    <td>${item.tipo_anestesia || ""}</td>
                    <td>${item.cirugia_mayor || ""}</td>
                    <td>${item.cirugia_menor || ""}</td>
                    <td>${item.sop || ""}</td>
                    <td>${item.destino || ""}</td>
                    <td class="tdTiempoUrpa">${item.tiempo_urpa || ""}</td>
                    <td>${item.observaciones || ""}</td>
                `;

        tbody.appendChild(fila);
      });

      document.querySelectorAll(".tdTiempoUrpa").forEach((td) => {
        td.style.display = tieneTiempoUrpa ? "" : "none";
      });

      document.querySelectorAll(".btn-ver-detalle").forEach((btn) => {
        btn.addEventListener("click", () => {
          const id = btn.dataset.id;
          const registro = data.data.find(
            (item) => String(item.id) === String(id),
          );
          modoEdicionVista = false;
          abrirModalVista(registro);
        });
      });

      mensaje.textContent = `Se cargaron ${data.data.length} registros.`;
      mensaje.className = "mensaje success";
    } catch (error) {
      console.error("Error cargando cirugías:", error);
      mensaje.textContent = "Error de conexión con el servidor.";
      mensaje.className = "mensaje error";
    }
  }

  let pasoActual = 0;

  const pasos = document.querySelectorAll(".form-step");
  const indicadores = document.querySelectorAll(".step");

  const btnNext = document.getElementById("btnNext");
  const btnPrev = document.getElementById("btnPrev");
  const btnGuardar = document.getElementById("btnGuardar");

  function validarPasoActual() {
    const paso = pasos[pasoActual];

    const campos = paso.querySelectorAll("input, select, textarea");

    for (const campo of campos) {
      if (campo.offsetParent === null || campo.disabled) continue;

      if (campo.type === "radio") {
        const seleccionado = paso.querySelector(
          `input[name="${campo.name}"]:checked`,
        );

        if (!seleccionado) {
          mostrarToast(
            "error",
            "Campo obligatorio",
            "Complete todos los campos antes de continuar.",
          );
          return false;
        }

        continue;
      }

      // CAMPOS NO OBLIGATORIOS
      const camposOpcionales = ["dni", "historia_clinica"];

      if (!camposOpcionales.includes(campo.id) && !campo.value.trim()) {
        campo.focus();

        mostrarToast(
          "error",
          "Campo obligatorio",
          "Complete todos los campos antes de continuar.",
        );

        return false;
      }
    }

    return true;
  }

  function mostrarPaso(index) {
    pasos.forEach((p, i) => {
      p.classList.toggle("activo", i === index);
    });

    indicadores.forEach((s, i) => {
      s.classList.toggle("activo", i <= index);
    });

    btnPrev.style.display = index === 0 ? "none" : "inline-block";
    btnNext.style.display =
      index === pasos.length - 1 ? "none" : "inline-block";
    btnGuardar.style.display =
      index === pasos.length - 1 ? "inline-block" : "none";

    const modalRegistro = document.getElementById("modalForm");

    if (modalRegistro) {
      modalRegistro.classList.toggle("modal-paso-personal", index === 3);
    }
  }

  btnNext.addEventListener("click", () => {
    if (!validarPasoActual()) return;

    if (pasoActual < pasos.length - 1) {
      pasoActual++;
      mostrarPaso(pasoActual);
    }
  });

  btnPrev.addEventListener("click", () => {
    if (pasoActual > 0) {
      pasoActual--;
      mostrarPaso(pasoActual);
    }
  });

  document.addEventListener("keydown", (e) => {
    if (!modal.classList.contains("activo")) return;

    if (e.key === "ArrowRight" && pasoActual < pasos.length - 1) {
      btnNext.click();
    }

    if (e.key === "ArrowLeft" && pasoActual > 0) {
      btnPrev.click();
    }
  });

  mostrarPaso(0);

  const modalVista = document.getElementById("modalVista");
  const btnCerrarVista = document.getElementById("btnCerrarVista");
  const btnCerrarVista2 = document.getElementById("btnCerrarVista2");
  const contenedorVista = document.getElementById("contenedorVista");
  //EDITAR FORMULARIO
  let modoEdicionVista = false;
  let registroEditandoVista = {};

  const btnEditarVista = document.getElementById("btnEditarVista");
  const btnGuardarVista = document.getElementById("btnGuardarVista");

  btnEditarVista?.addEventListener("click", () => {
    modoEdicionVista = true;

    document
      .querySelectorAll("#contenedorVista [data-campo]")
      .forEach((campo) => {
        campo.disabled = false;
        campo.classList.add("editable");
      });

    btnEditarVista.style.display = "none";
    btnGuardarVista.style.display = "inline-block";
  });

  btnGuardarVista?.addEventListener("click", async () => {
    try {
      const id = modalVista.dataset.id;
      guardarCambiosTemporalesVista();

      const data = { ...registroEditandoVista };

      document
        .querySelectorAll("#contenedorVista [data-campo]")
        .forEach((campo) => {
          const nombreCampo = campo.dataset.campo;
          data[nombreCampo] = campo.value.trim();
        });

      const res = await fetch(`/cirugias/${id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify(data),
      });

      const result = await res.json();
      if (!res.ok || !result.success) {
        mostrarToast(
          "error",
          "Error",
          result.message || "No se pudo actualizar.",
        );

        return;
      }

      mostrarToast(
        "success",
        "Actualizado",
        "El registro fue actualizado correctamente.",
      );

      document
        .querySelectorAll("#contenedorVista [data-campo]")
        .forEach((campo) => {
          campo.disabled = true;
          campo.classList.remove("editable");
        });

      btnGuardarVista.style.display = "none";
      btnEditarVista.style.display = "inline-block";

      cargarCirugias();
    } catch (error) {
      console.error("Error actualizando:", error);

      mostrarToast("error", "Error", "No se pudo conectar con el servidor.");
    }
  });

  btnCerrarVista.addEventListener("click", cerrarModalVista);
  btnCerrarVista2.addEventListener("click", cerrarModalVista);

  function cerrarModalVista() {
    modoEdicionVista = false;
    modalVista.classList.remove("activo");

    document
      .querySelectorAll("#contenedorVista [data-campo]")
      .forEach((campo) => {
        campo.disabled = true;
        campo.classList.remove("editable");
      });

    btnEditarVista.style.display = "inline-block";
    btnGuardarVista.style.display = "none";
  }

  function abrirModalVista(registro) {
    if (!registro) return;

    // LIMPIAR CONTENIDO ANTERIOR
    contenedorVista.innerHTML = "";

    modalVista.dataset.id = registro.id;
    registroEditandoVista = structuredClone(registro);

    const tabsDetalle = document.getElementById("tabsDetalle");

    const secciones = {
      paciente: {
        titulo: "Paciente",
        campos: [
          ["Fecha", formatearFecha(registro.fecha), "", "fecha"],
          ["Hora", formatearHora(registro.hora), "", "hora"],
          [
            "Historia Clínica",
            registro.historia_clinica,
            "",
            "historia_clinica",
          ],
          ["DNI", registro.dni, "", "dni"],
          [
            "Nombres y Apellidos",
            registro.nombres_apellidos,
            "doble",
            "nombres_apellidos",
          ],
          ["Edad", registro.edad, "", "edad"],
          ["Sexo", registro.sexo, "", "sexo"],
        ],
      },
      atencion: {
        titulo: "Atención",
        campos: [
          ["Tipo de Orden", registro.tipo_orden, "", "tipo_orden"],
          ["Especialidad", registro.especialidad, "", "especialidad"],
          ["Tipo de Seguro", registro.tipo_seguro, "", "tipo_seguro"],
          ["Prueba COVID", registro.prueba_covid, "", "prueba_covid"],
          ["Suspensión", registro.suspension, "", "suspension"],
          [
            "Motivo Suspensión",
            registro.motivo_suspension,
            "doble",
            "motivo_suspension",
          ],
        ],
      },
      diagnostico: {
        titulo: "Diagnóstico",
        campos: [
          ["Código CIE 10", registro.codigo_cie10, "", "codigo_cie10"],
          [
            "Diagnóstico Preoperatorio",
            registro.diagnostico_preoperatorio,
            "doble",
            "diagnostico_preoperatorio",
          ],
          [
            "Operación Realizada",
            registro.operacion_realizada,
            "doble",
            "operacion_realizada",
          ],
          ["Comorbilidad", registro.comorbilidad, "", "comorbilidad"],
          ["Reintervención", registro.reintervencion, "", "reintervencion"],
          [
            "RAM Medicamentos",
            registro.ram_medicamentos,
            "",
            "ram_medicamentos",
          ],
          [
            "Discrepancia Diagnóstica",
            registro.discrepancia_diagnostica,
            "",
            "discrepancia_diagnostica",
          ],
        ],
      },
      tiempos: {
        titulo: "Tiempos",
        campos: [
          ["Tiempo Total", registro.tiempo_total, "", "tiempo_total"],
          [
            "Tiempo Anestesia",
            registro.tiempo_anestesia,
            "",
            "tiempo_anestesia",
          ],
          [
            "Tiempo Operación",
            registro.tiempo_operacion,
            "",
            "tiempo_operacion",
          ],
          ["Tiempo URPA", registro.tiempo_urpa, "", "tiempo_urpa"],
          [
            "Complicaciones",
            registro.complicaciones_intraoperatorias,
            "full",
            "complicaciones_intraoperatorias",
          ],
        ],
      },
      personal: {
        titulo: "Personal",
        campos: [
          ["Cirujano 1", registro.cirujano_1, "", "cirujano_1"],
          ["Cirujano 2", registro.cirujano_2, "", "cirujano_2"],
          ["Anestesiólogo", registro.anestesiologo, "", "anestesiologo"],
          [
            "Enfermera Instrumentista",
            registro.enfermera_instrumentista,
            "",
            "enfermera_instrumentista",
          ],
          [
            "Anestesiólogo Recuperación",
            registro.anestesiologo_recuperacion,
            "",
            "anestesiologo_recuperacion",
          ],
          [
            "Enfermera Recuperación",
            registro.enfermera_recuperacion,
            "",
            "enfermera_recuperacion",
          ],
          [
            "Técnico Enfermería 1",
            registro.tecnico_enfermeria_1,
            "",
            "tecnico_enfermeria_1",
          ],
          [
            "Técnico Enfermería 2",
            registro.tecnico_enfermeria_2,
            "",
            "tecnico_enfermeria_2",
          ],
        ],
      },
      anestesia: {
        titulo: "Anestesia",
        campos: [
          ["Tipo Anestesia", registro.tipo_anestesia, "", "tipo_anestesia"],
          ["Cirugía Mayor", registro.cirugia_mayor, "", "cirugia_mayor"],
          ["Cirugía Menor", registro.cirugia_menor, "", "cirugia_menor"],
          ["SOP", registro.sop, "", "sop"],
          ["Destino", registro.destino, "", "destino"],
          ["Observaciones", registro.observaciones, "full", "observaciones"],
        ],
      },
    };

    function mostrarTab(nombre) {
      guardarCambiosTemporalesVista();

      const seccion = secciones[nombre];

      contenedorVista.innerHTML = seccion.campos
        .map(([label, valor, tipo, campo]) =>
          campoVista(label, registroEditandoVista[campo] ?? valor, tipo, campo),
        )
        .join("");

      document.querySelectorAll(".tabs-detalle button").forEach((btn) => {
        btn.classList.toggle("activo", btn.dataset.tab === nombre);
      });

      if (modoEdicionVista) {
        document
          .querySelectorAll("#contenedorVista [data-campo]")
          .forEach((campo) => {
            campo.disabled = false;
            campo.classList.add("editable");
          });
      }

      activarBusquedaCIE10Editar();
      activarBusquedaProcedimientoEditar();
      activarBusquedaPacienteEditar();

      configurarBusquedaPersonalEditar("cirujano_1", "CIRUJANO");

      configurarBusquedaPersonalEditar("cirujano_2", "CIRUJANO");

      configurarBusquedaPersonalEditar("anestesiologo", "ANESTESIOLOGO");

      configurarBusquedaPersonalEditar(
        "anestesiologo_recuperacion",
        "ANESTESIOLOGO",
      );

      configurarBusquedaPersonalEditar(
        "enfermera_instrumentista",
        "LICENCIADA(O) ENFERMERIA",
      );

      configurarBusquedaPersonalEditar(
        "enfermera_recuperacion",
        "LICENCIADA(O) ENFERMERIA",
      );

      configurarBusquedaPersonalEditar(
        "tecnico_enfermeria_1",
        "TECNICO DE ENFERMERIA",
      );

      configurarBusquedaPersonalEditar(
        "tecnico_enfermeria_2",
        "TECNICO DE ENFERMERIA",
      );
    }

    tabsDetalle.innerHTML = Object.entries(secciones)
      .map(
        ([key, sec]) => `
                <button type="button" data-tab="${key}">
                    ${sec.titulo}
                </button>
            `,
      )
      .join("");

    tabsDetalle.querySelectorAll("button").forEach((btn) => {
      btn.addEventListener("click", () => mostrarTab(btn.dataset.tab));
    });

    mostrarTab("paciente");

    btnEditarVista.style.display = "inline-block";
    btnGuardarVista.style.display = "none";

    modalVista.classList.add("activo");
  }

  function guardarCambiosTemporalesVista() {
    document
      .querySelectorAll("#contenedorVista [data-campo]")
      .forEach((campo) => {
        const nombreCampo = campo.dataset.campo;
        registroEditandoVista[nombreCampo] = campo.value.trim();
      });
  }
  //PARA EDITAR REGISTRO- CONVERTIR CALENDARIO A FECHA
  function convertirFechaInput(valor) {
    if (!valor) return "";

    if (String(valor).includes("/")) {
      const [dia, mes, anio] = String(valor).split("/");
      return `${anio}-${mes}-${dia}`;
    }

    if (String(valor).includes("T")) {
      return String(valor).split("T")[0];
    }

    return valor;
  }

  function convertirHoraInput(valor) {
    if (!valor) return "";

    const texto = String(valor);

    if (texto.includes("T")) {
      return texto.split("T")[1].substring(0, 5);
    }

    if (texto.includes(":")) {
      return texto.substring(0, 5);
    }

    return "";
  }

  function campoVista(label, valor, tipo = "", campo = "") {
    let clase = "";

    if (tipo === "doble") clase = "campo-doble";
    if (tipo === "full") clase = "campo-full";
    //FECHA
    if (campo === "fecha") {
      return `
                <div class="campo ${clase}">
                    <label>${label}</label>
                    <input 
                        type="date"
                        disabled
                        data-campo="${campo}"
                        value="${convertirFechaInput(valor)}"
                    >
                </div>
            `;
    }
    //HORA
    if (campo === "hora") {
      return `
                <div class="campo ${clase}">
                    <label>${label}</label>

                    <input
                        type="time"
                        disabled
                        data-campo="${campo}"
                        value="${convertirHoraInput(valor)}"
                    >

                </div>
            `;
    }
    //SEXO
    if (campo === "sexo") {
      return `
                <div class="campo ${clase}">
                    <label>${label}</label>

                    <select
                        disabled
                        data-campo="${campo}"
                    >
                        <option value="">Seleccione</option>

                        <option 
                            value="MASCULINO"
                            ${valor === "MASCULINO" ? "selected" : ""}
                        >
                            MASCULINO
                        </option>

                        <option 
                            value="FEMENINO"
                            ${valor === "FEMENINO" ? "selected" : ""}
                        >
                            FEMENINO
                        </option>

                    </select>

                </div>
            `;
    }
    //TIPO ORDEN
    if (campo === "tipo_orden") {
      return `
                <div class="campo ${clase}">
                    <label>${label}</label>

                    <select
                        disabled
                        data-campo="${campo}"
                    >
                        <option value="">Seleccione</option>

                        <option 
                            value="EMERGENCIA"
                            ${valor === "EMERGENCIA" ? "selected" : ""}
                        >
                            EMERGENCIA
                        </option>

                        <option 
                            value="ELECTIVA"
                            ${valor === "ELECTIVA" ? "selected" : ""}
                        >
                            ELECTIVA
                        </option>

                    </select>

                </div>
            `;
    }
    //SUSPENSION
    if (campo === "suspension") {
      return `
                <div class="campo ${clase}">
                    <label>${label}</label>

                    <select
                        disabled
                        data-campo="${campo}"
                    >
                        <option value="">Seleccione</option>

                        <option 
                            value="SI"
                            ${valor === "SI" ? "selected" : ""}
                        >
                            SI
                        </option>

                        <option 
                            value="NO"
                            ${valor === "NO" ? "selected" : ""}
                        >
                            NO
                        </option>

                    </select>

                </div>
            `;
    }
    //PRUEBA COVID
    if (campo === "prueba_covid") {
      return `
                <div class="campo ${clase}">
                    <label>${label}</label>

                    <select
                        disabled
                        data-campo="${campo}"
                    >
                        <option value="">Seleccione</option>

                        <option 
                            value="NO TIENE"
                            ${valor === "NO TIENE" ? "selected" : ""}
                        >
                            NO TIENE
                        </option>
                        <option 
                            value="SI TIENE"
                            ${valor === "SI TIENE" ? "selected" : ""}
                        >
                            SI TIENE
                        </option>

                    </select>

                </div>
            `;
    }
    //TIPO DE SEGURO
    if (campo === "tipo_seguro") {
      return `
                <div class="campo ${clase}">
                    <label>${label}</label>

                    <select
                        disabled
                        data-campo="${campo}"
                    >
                        <option value="">Seleccione</option>

                        <option 
                            value="SIS"
                            ${valor === "SIS" ? "selected" : ""}
                        >
                            SIS
                        </option>

                        <option 
                            value="ESSALUD"
                            ${valor === "ESSALUD" ? "selected" : ""}
                        >
                            ESSALUD
                        </option>

                        <option 
                            value="SOAT"
                            ${valor === "SOAT" ? "selected" : ""}
                        >
                            SOAT
                        </option>

                        <option 
                            value="PARTICULAR"
                            ${valor === "PARTICULAR" ? "selected" : ""}
                        >
                            PARTICULAR
                        </option>

                    </select>

                </div>
            `;
    }
    //DESTINO
    if (campo === "destino") {
      return `
                <div class="campo ${clase}">
                    <label>${label}</label>

                    <select
                        disabled
                        data-campo="${campo}"
                    >
                        <option value="">Seleccione</option>

                        <option 
                            value="HOSPITALIZACION"
                            ${valor === "HOSPITALIZACION" ? "selected" : ""}
                        >
                            HOSPITALIZACION
                        </option>

                        <option 
                            value="UVI"
                            ${valor === "UVI" ? "selected" : ""}
                        >
                            UVI
                        </option>

                        <option 
                            value="URPA"
                            ${valor === "URPA" ? "selected" : ""}
                        >
                            URPA
                        </option>

                        <option 
                            value="ALTA"
                            ${valor === "ALTA" ? "selected" : ""}
                        >
                            ALTA
                        </option>

                    </select>

                </div>
            `;
    }
    //TIPO ANESTESIA
    if (campo === "tipo_anestesia") {
      return `
                <div class="campo ${clase}">
                    <label>${label}</label>

                    <select
                        disabled
                        data-campo="${campo}"
                    >
                        <option value="">Seleccione</option>

                        <option 
                            value="GENERAL"
                            ${valor === "GENERAL" ? "selected" : ""}
                        >
                            GENERAL
                        </option>

                        <option 
                            value="RAQUIDEA"
                            ${valor === "RAQUIDEA" ? "selected" : ""}
                        >
                            RAQUIDEA
                        </option>

                        <option 
                            value="EPIDURAL"
                            ${valor === "EPIDURAL" ? "selected" : ""}
                        >
                            EPIDURAL
                        </option>

                        <option 
                            value="LOCAL"
                            ${valor === "LOCAL" ? "selected" : ""}
                        >
                            LOCAL
                        </option>

                        <option 
                            value="SEDACION"
                            ${valor === "SEDACION" ? "selected" : ""}
                        >
                            SEDACION
                        </option>

                    </select>

                </div>
            `;
    }
    //TIPO DE CIRUGIA

    if (campo === "cirugia_mayor" || campo === "cirugia_menor") {
      return `
                <div class="campo ${clase}">
                    <label>${label}</label>

                    <select
                        disabled
                        data-campo="${campo}"
                    >
                        <option value="">NO</option>

                        <option 
                            value="X"
                            ${valor === "X" ? "selected" : ""}
                        >
                            SI
                        </option>

                    </select>

                </div>
            `;
    }

    //CIE 10
    if (campo === "codigo_cie10") {
      return `
                <div class="campo ${clase}">
                    <label>${label}</label>

                    <input
                        type="text"
                        disabled
                        autocomplete="off"
                        class="input-cie10-editar"
                        data-campo="${campo}"
                        value="${valor || ""}"
                    >

                    <div class="lista-sugerencias-cie10"></div>

                </div>
            `;
    }

    //OPERACION REALIZADA - PROCEDIMIENTO
    if (campo === "operacion_realizada") {
      return `
                <div class="campo ${clase}">
                    <label>${label}</label>

                    <textarea
                        disabled
                        autocomplete="off"
                        class="input-procedimiento-editar"
                        data-campo="${campo}"
                    >${valor || ""}</textarea>

                    <div class="lista-sugerencias-procedimiento"></div>

                </div>
            `;
    }

    //DNI- HISTORIA CLINICA
    if (campo === "dni" || campo === "historia_clinica") {
      return `
                <div class="campo ${clase}">
                    <label>${label}</label>

                    <input
                        type="text"
                        disabled
                        autocomplete="off"
                        class="input-paciente-editar"
                        data-campo="${campo}"
                        value="${valor || ""}"
                    >

                </div>
            `;
    }

    //ESPECIALIDAD
    if (campo === "especialidad") {
      const opciones = Array.from(
        document.querySelectorAll("#especialidad option"),
      )
        .filter((opt) => opt.value !== "")
        .map(
          (opt) => `
                    <option 
                        value="${opt.value}"
                        ${valor === opt.value ? "selected" : ""}
                    >
                        ${opt.textContent}
                    </option>
                `,
        )
        .join("");

      return `
                <div class="campo ${clase}">
                    <label>${label}</label>

                    <select
                        disabled
                        data-campo="${campo}"
                    >
                        <option value="">Seleccione</option>
                        ${opciones}
                    </select>

                </div>
            `;
    }

    return `
            <div class="campo ${clase}">
                <label>${label}</label>

                <textarea
                    disabled
                    data-campo="${campo}"
                >${valor || ""}</textarea>

            </div>
        `;
  }
  //CAMPO RAM
  function controlarCampoRAM() {
    if (tieneRam.value === "SI") {
      campoRamMedicamento.style.display = "block";
    } else {
      campoRamMedicamento.style.display = "none";
      ramMedicamentos.value = "";
    }
  }
  tieneRam.addEventListener("change", controlarCampoRAM);

  //FUNCION PARA ACTIVAR SUGERENCIAS EN EL FORMULARIO DE EDITAR
  function activarBusquedaCIE10Editar() {
    const input = document.querySelector(".input-cie10-editar");

    if (!input) return;

    const contenedor = input.parentElement.querySelector(
      ".lista-sugerencias-cie10",
    );

    input.addEventListener("input", async () => {
      const texto = input.value.trim();

      if (texto.length < 2) {
        contenedor.innerHTML = "";
        contenedor.style.display = "none";
        return;
      }

      const res = await fetch(
        `/cie10/sugerencias?q=${encodeURIComponent(texto)}`,
        {
          credentials: "include",
        },
      );

      const data = await res.json();

      contenedor.innerHTML = "";

      data.forEach((item) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "opcion-cie";
        btn.innerHTML = `
                    <strong>${item.codigo}</strong>
                    <span>${item.descripcion}</span>
                `;

        btn.addEventListener("click", async () => {
          input.value = item.codigo;

          const diagnostico = document.querySelector(
            '[data-campo="diagnostico_preoperatorio"]',
          );

          if (diagnostico) {
            diagnostico.value = item.descripcion;
          }

          // NUEVO
          try {
            const params = new URLSearchParams({
              cie10: item.codigo,
              diagnostico: item.descripcion,
            });

            const res = await fetch(
              `/sigh/operacion-por-cie10?${params.toString()}`,
              {
                credentials: "include",
              },
            );

            const json = await res.json();

            if (res.ok && json.success && json.data) {
              const info = json.data;
              const tipo = info.tipo_cirugia_sugerido || "";

              // Guardar siempre Mayor/Menor aunque no haya operación autocompletada
              registroEditandoVista.cirugia_mayor = tipo === "MAYOR" ? "X" : "";
              registroEditandoVista.cirugia_menor = tipo === "MENOR" ? "X" : "";

              // Operación solo si es confiable
              registroEditandoVista.operacion_realizada =
                info.operacion_autocompletada && info.operacion_realizada
                  ? info.operacion_realizada
                  : "";

              // Actualizar campos si existen en la pestaña actual
              const operacion = document.querySelector(
                '[data-campo="operacion_realizada"]',
              );
              const campoMayor = document.querySelector(
                '[data-campo="cirugia_mayor"]',
              );
              const campoMenor = document.querySelector(
                '[data-campo="cirugia_menor"]',
              );

              if (operacion) {
                operacion.value = registroEditandoVista.operacion_realizada;
              }

              if (campoMayor) {
                campoMayor.value = registroEditandoVista.cirugia_mayor;
              }

              if (campoMenor) {
                campoMenor.value = registroEditandoVista.cirugia_menor;
              }

              console.log(
                "Sugerencias edición:",
                info.sugerencias_operacion || [],
              );
            }
          } catch (error) {
            console.error("Error autocompletando operación:", error);
          }

          contenedor.innerHTML = "";
          contenedor.style.display = "none";
        });

        contenedor.appendChild(btn);
      });

      contenedor.style.display = data.length ? "block" : "none";
    });
  }
  //PARA BUSCAR PROCEDIMIENTOS EN EDITAR
  function activarBusquedaProcedimientoEditar() {
    const input = document.querySelector(".input-procedimiento-editar");

    if (!input) return;

    const contenedor = input.parentElement.querySelector(
      ".lista-sugerencias-procedimiento",
    );

    input.addEventListener("input", async () => {
      const texto = input.value.trim();

      if (texto.length < 2) {
        contenedor.innerHTML = "";
        contenedor.style.display = "none";
        return;
      }

      const res = await fetch(
        `/sigh/procedimientos/sugerencias?q=${encodeURIComponent(texto)}`,
        {
          credentials: "include",
        },
      );

      const data = await res.json();

      contenedor.innerHTML = "";

      data.forEach((item) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "opcion-procedimiento";

        btn.innerHTML = `
                <strong>${item.codigo || ""}</strong>
                <span>${item.nombre || ""}</span>
                <small>
                    ${item.grupo || ""}
                    ${item.seccion ? " / " + item.seccion : ""}
                    ${item.tipo_cirugia_sugerido ? " — " + item.tipo_cirugia_sugerido : ""}
                </small>
            `;

        btn.addEventListener("click", () => {
          input.value = `${item.codigo || ""} - ${item.nombre || ""}`;

          const tipo = item.tipo_cirugia_sugerido || "";

          const campoMayor = document.querySelector(
            '[data-campo="cirugia_mayor"]',
          );
          const campoMenor = document.querySelector(
            '[data-campo="cirugia_menor"]',
          );

          if (campoMayor) campoMayor.value = tipo === "MAYOR" ? "X" : "";
          if (campoMenor) campoMenor.value = tipo === "MENOR" ? "X" : "";

          contenedor.innerHTML = "";
          contenedor.style.display = "none";
        });

        contenedor.appendChild(btn);
      });

      contenedor.style.display = data.length ? "block" : "none";
    });
  }

  function configurarBusquedaPersonalEditar(campo, profesion) {
    let input = contenedorVista.querySelector(`[data-campo="${campo}"]`);

    if (!input) return;

    // ELIMINAR EVENTOS VIEJOS
    input = limpiarEventos(input);

    const contenedor = document.createElement("div");

    contenedor.className = "sugerencias-personal";

    input.parentElement.style.position = "relative";

    input.parentElement.appendChild(contenedor);

    input.addEventListener("input", async () => {
      const texto = input.value.trim();

      if (texto.length < 2) {
        contenedor.innerHTML = "";
        contenedor.style.display = "none";
        return;
      }

      try {
        const params = new URLSearchParams();

        params.append("busqueda", texto);
        params.append("profesion", profesion);

        const res = await fetch(`/personal-medico?${params.toString()}`, {
          credentials: "include",
        });

        const data = await res.json();

        const activos = data.filter((p) => p.estado === "ACTIVO");

        contenedor.innerHTML = "";

        if (activos.length === 0) {
          contenedor.style.display = "none";
          return;
        }

        activos.slice(0, 5).forEach((item) => {
          const opcion = document.createElement("button");

          opcion.type = "button";

          opcion.className = "opcion-personal";

          opcion.innerHTML = `
                        <strong>${item.apellidos_nombres}</strong>
                        <span>
                            ${item.dni || "Sin DNI"} · 
                            ${item.profesion}
                        </span>
                    `;

          opcion.addEventListener("click", () => {
            input.value = item.apellidos_nombres;

            // GUARDAR EN EL OBJETO GLOBAL
            registroEditandoVista[campo] = item.apellidos_nombres;

            contenedor.innerHTML = "";

            contenedor.style.display = "none";
          });

          contenedor.appendChild(opcion);
        });

        contenedor.style.display = "block";
      } catch (error) {
        console.error("Error buscando personal médico:", error);
      }
    });
  }

  function limpiarEventos(input) {
    if (!input) return null;

    const nuevoInput = input.cloneNode(true);

    input.parentNode.replaceChild(nuevoInput, input);

    return nuevoInput;
  }

  //AUTOCOMPLETADO DEL PACIENTE EN EL FORMULARIO DE EDITAR
  function activarBusquedaPacienteEditar() {
    let inputDni = contenedorVista.querySelector('[data-campo="dni"]');

    let inputHistoria = contenedorVista.querySelector(
      '[data-campo="historia_clinica"]',
    );

    if (!inputDni && !inputHistoria) return;

    if (inputDni) {
      inputDni = limpiarEventos(inputDni);
    }

    if (inputHistoria) {
      inputHistoria = limpiarEventos(inputHistoria);
    }

    let requestActual = 0;

    async function buscarPacienteEditar({ dni = "", historia = "" }) {
      const requestId = ++requestActual;

      const params = new URLSearchParams();

      if (dni) params.append("dni", dni);
      if (historia) params.append("historia", historia);

      const res = await fetch(`/pacientes/buscar?${params.toString()}`, {
        credentials: "include",
      });

      const json = await res.json();

      // IGNORAR RESPUESTAS VIEJAS
      if (requestId !== requestActual) return;

      if (!res.ok || !json.success) return;

      const p = json.data;

      const campoNombre = contenedorVista.querySelector(
        '[data-campo="nombres_apellidos"]',
      );

      const campoEdad = contenedorVista.querySelector('[data-campo="edad"]');

      const campoSexo = contenedorVista.querySelector('[data-campo="sexo"]');

      if (inputDni) inputDni.value = p.dni || "";
      if (inputHistoria) inputHistoria.value = p.historia_clinica || "";

      if (campoNombre) campoNombre.value = p.nombres_apellidos || "";
      if (campoEdad) campoEdad.value = p.edad || "";
      if (campoSexo) campoSexo.value = p.sexo || "";
    }

    inputDni?.addEventListener("blur", () => {
      const dni = inputDni.value.trim();

      if (/^\d{8}$/.test(dni)) {
        buscarPacienteEditar({ dni });
      }
    });

    inputHistoria?.addEventListener("blur", () => {
      const historia = inputHistoria.value.trim();

      if (historia.length >= 1) {
        buscarPacienteEditar({ historia });
      }
    });
  }

  function formatearFecha(fecha) {
    if (!fecha) return "";

    if (typeof fecha === "string") {
      if (fecha.includes("T")) {
        const soloFecha = fecha.split("T")[0];
        const partes = soloFecha.split("-");
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
      }

      if (fecha.includes("-")) {
        const partes = fecha.split("-");
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
      }

      return fecha;
    }

    return "";
  }

  function formatearHora(hora) {
    if (!hora) return "";

    if (typeof hora === "string") {
      if (hora.includes("T")) {
        const parteHora = hora.split("T")[1];
        return parteHora.substring(0, 5);
      }

      return hora.substring(0, 5);
    }

    return "";
  }

  const modal = document.getElementById("modalForm");
  const btnAgregar = document.getElementById("btnAgregar");
  const btnCerrarModal = document.getElementById("btnCerrarModal");
  const btnCerrarModal2 = document.getElementById("btnCerrarModal2");
  const formRegistro = document.getElementById("formRegistro");

  const suspensionSelect = document.getElementById("suspension");
  const campoMotivoSuspension = document.getElementById(
    "campoMotivoSuspension",
  );
  const motivoSuspension = document.getElementById("motivo_suspension");

  function abrirModalRegistro() {
    formRegistro.reset();

    pasoActual = 0;
    mostrarPaso(0);

    campoMotivoSuspension.classList.remove("activo");
    motivoSuspension.value = "NINGUNO";

    controlarCampoUrpa();
    controlarPersonalRecuperacion();
    controlarCampoRAM();

    modal.classList.add("activo");
  }

  btnAgregar.addEventListener("click", abrirModalRegistro);

  function cerrarModalRegistro() {
    modal.classList.remove("activo");

    formRegistro.reset();

    pasoActual = 0;
    mostrarPaso(pasoActual);

    campoMotivoSuspension.classList.remove("activo");
    motivoSuspension.value = "NINGUNO";

    controlarCampoUrpa();
    controlarPersonalRecuperacion();
    controlarCampoRAM();
  }

  if (btnCerrarModal) {
    btnCerrarModal.type = "button";

    btnCerrarModal.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      cerrarModalRegistro();
    });
  }

  if (btnCerrarModal2) {
    btnCerrarModal2.type = "button";

    btnCerrarModal2.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      cerrarModalRegistro();
    });
  }

  suspensionSelect.addEventListener("change", () => {
    if (suspensionSelect.value === "SI") {
      campoMotivoSuspension.classList.add("activo");
      motivoSuspension.value = "";
      motivoSuspension.focus();
    } else {
      campoMotivoSuspension.classList.remove("activo");
      motivoSuspension.value = "NINGUNO";
    }
  });

  const destinoSelect = document.getElementById("destino");
  const campoUrpa = document.getElementById("campoUrpa");
  const tiempoUrpa = document.getElementById("tiempo_urpa");

  const tituloPersonalMedico = document.getElementById("tituloPersonalMedico");
  const gridPersonalMedico = document.querySelector(".grid-personal-dividido");
  const camposRecuperacion = document.querySelectorAll(
    ".personal-recuperacion",
  );

  function controlarPersonalRecuperacion() {
    const esUrpa = destinoSelect.value === "URPA";

    if (gridPersonalMedico) {
      gridPersonalMedico.classList.toggle("modo-urpa", esUrpa);
    }

    const modalForm = document.getElementById("modalForm");

    if (modalForm) {
      modalForm.classList.toggle("modal-modo-urpa", esUrpa);
    }

    camposRecuperacion.forEach((campo) => {
      campo.style.display = esUrpa ? "" : "none";
    });

    if (tituloPersonalMedico) {
      tituloPersonalMedico.textContent = esUrpa
        ? "Personal médico sala y recuperación"
        : "Personal médico sala";
    }

    if (!esUrpa) {
      const anestRec = document.getElementById("anestesiologo_recuperacion");
      const enfRec = document.getElementById("enfermera_recuperacion");
      const tec1 = document.getElementById("tecnico_enfermeria_1");
      const tec2 = document.getElementById("tecnico_enfermeria_2");

      if (anestRec) anestRec.value = "";
      if (enfRec) enfRec.value = "";
      if (tec1) tec1.value = "";
      if (tec2) tec2.value = "";
    }
  }

  function controlarCampoUrpa() {
    if (destinoSelect.value === "URPA") {
      campoUrpa.style.display = "flex";
    } else {
      campoUrpa.style.display = "none";
      tiempoUrpa.value = "";
    }
  }

  destinoSelect.addEventListener("change", () => {
    controlarCampoUrpa();
    controlarPersonalRecuperacion();
  });

  controlarCampoUrpa();
  controlarPersonalRecuperacion();
  controlarCampoRAM();

  formRegistro.addEventListener("submit", async (e) => {
    e.preventDefault();

    const tipoCirugia =
      document.querySelector('input[name="tipo_cirugia"]:checked')?.value || "";

    const data = {
      fecha: document.getElementById("fecha").value,
      hora: document.getElementById("hora").value,
      historia_clinica: document.getElementById("historia_clinica").value,
      dni: document.getElementById("dni").value,
      nombres_apellidos: document.getElementById("nombres_apellidos").value,
      tipo_orden: document.getElementById("tipo_orden").value,
      especialidad: document.getElementById("especialidad").value,
      edad: document.getElementById("edad").value,
      sexo: document.getElementById("sexo").value,
      tipo_seguro: document.getElementById("tipo_seguro").value,
      prueba_covid: document.getElementById("prueba_covid").value,
      suspension: document.getElementById("suspension").value,
      motivo_suspension:
        document.getElementById("motivo_suspension").value || "NINGUNO",
      diagnostico_preoperatorio: document.getElementById(
        "diagnostico_preoperatorio",
      ).value,
      codigo_cie10: document.getElementById("codigo_cie10").value,
      operacion_realizada: document.getElementById("operacion_realizada").value,
      comorbilidad: document.getElementById("comorbilidad").value,
      reintervencion: document.getElementById("reintervencion").value,
      ram_medicamentos: document.getElementById("ram_medicamentos").value,
      discrepancia_diagnostica: document.getElementById(
        "discrepancia_diagnostica",
      ).value,
      tiempo_total: document.getElementById("tiempo_total").value,
      tiempo_anestesia: document.getElementById("tiempo_anestesia").value,
      tiempo_operacion: document.getElementById("tiempo_operacion").value,
      complicaciones_intraoperatorias: document.getElementById(
        "complicaciones_intraoperatorias",
      ).value,
      cirujano_1: document.getElementById("cirujano_1").value,
      cirujano_2: document.getElementById("cirujano_2").value,
      anestesiologo: document.getElementById("anestesiologo").value,
      enfermera_instrumentista: document.getElementById(
        "enfermera_instrumentista",
      ).value,
      anestesiologo_recuperacion: document.getElementById(
        "anestesiologo_recuperacion",
      ).value,
      enfermera_recuperacion: document.getElementById("enfermera_recuperacion")
        .value,
      tecnico_enfermeria_1: document.getElementById("tecnico_enfermeria_1")
        .value,
      tecnico_enfermeria_2: document.getElementById("tecnico_enfermeria_2")
        .value,
      tipo_anestesia: document.getElementById("tipo_anestesia").value,
      cirugia_mayor: tipoCirugia === "MAYOR" ? "X" : "",
      cirugia_menor: tipoCirugia === "MENOR" ? "X" : "",
      sop: document.getElementById("sop").value,
      tiempo_urpa: document.getElementById("tiempo_urpa").value,
      destino: document.getElementById("destino").value,
      observaciones: document.getElementById("observaciones").value,
    };

    try {
      const res = await fetch("/cirugias-manual", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify(data),
      });

      const result = await res.json();

      if (!res.ok || !result.success) {
        mostrarToast(
          "error",
          "Error",
          result.message || "No se pudo guardar el registro.",
        );
        return;
      }

      mostrarToast(
        "success",
        "Registro guardado",
        "La cirugía fue registrada correctamente.",
      );

      cerrarModalRegistro();

      cargarResumen();
      cargarMeses();
      cargarCirugias();
    } catch (error) {
      mostrarToast(
        "error",
        "Error de conexión",
        "No se pudo conectar con el servidor.",
      );
    }
  });

  // ==========================
  // CAMBIO DE VISTAS
  // ==========================
  const menuInicio = document.getElementById("menuInicio");
  const menuAnalisis = document.getElementById("menuAnalisis");
  const menuReportes = document.getElementById("menuReportes");
  const menuGestion = document.getElementById("menuGestion");

  const vistaInicio = document.getElementById("vistaInicio");
  const vistaAnalisis = document.getElementById("vistaAnalisis");
  const vistaReportes = document.getElementById("vistaReportes");
  const vistaGestion = document.getElementById("vistaGestion");

  const selectMesGrafico = document.getElementById("selectMesGrafico");
  const btnCerrarEspecialidades = document.getElementById(
    "btnCerrarEspecialidades",
  );
  const btnVolverEspecialidades = document.getElementById(
    "btnVolverEspecialidades",
  );

  function activarMenu(activo) {
    document.querySelectorAll(".sidebar nav a").forEach((a) => {
      a.classList.remove("active");
    });

    if (activo) {
      activo.classList.add("active");
    }
  }

  function ocultarTodasLasVistas() {
    if (vistaInicio) vistaInicio.style.display = "none";
    if (vistaAnalisis) vistaAnalisis.style.display = "none";
    if (vistaReportes) vistaReportes.style.display = "none";
    if (vistaGestion) vistaGestion.style.display = "none";
  }

  if (menuInicio) {
    menuInicio.addEventListener("click", (e) => {
      e.preventDefault();

      ocultarTodasLasVistas();

      if (vistaInicio) vistaInicio.style.display = "block";

      if (txtBusqueda) txtBusqueda.value = "";

      cargarCirugias();
      activarMenu(menuInicio);
    });
  }

  if (menuAnalisis) {
    menuAnalisis.addEventListener("click", async (e) => {
      e.preventDefault();

      ocultarTodasLasVistas();

      if (vistaAnalisis) vistaAnalisis.style.display = "block";

      activarMenu(menuAnalisis);

      limpiarPanelEspecialidades();
      limpiarResumenOrdenEspecialidad();

      await cargarMesesAnalisis();
    });
  }

  if (menuReportes) {
    menuReportes.addEventListener("click", async (e) => {
      e.preventDefault();

      ocultarTodasLasVistas();

      if (vistaReportes) vistaReportes.style.display = "block";

      activarMenu(menuReportes);

      await cargarMesesReportes();
    });
  }

  if (selectMesGrafico) {
    selectMesGrafico.addEventListener("change", () => {
      const valorMesAnio = selectMesGrafico.value;

      limpiarPanelEspecialidades();
      cargarGraficoPorMes(valorMesAnio);
      limpiarResumenOrdenEspecialidad();
    });
  }

  // MENU GESTION
  if (menuGestion) {
    menuGestion.addEventListener("click", async (e) => {
      e.preventDefault();

      ocultarTodasLasVistas();

      if (vistaGestion) vistaGestion.style.display = "block";

      activarMenu(menuGestion);

      const txtBuscarCIE = document.getElementById("txtBuscarCIE");
      const filtroEstadoCIE = document.getElementById("filtroEstadoCIE");
      const filtroSexoCIE = document.getElementById("filtroSexoCIE");

      const txtBuscarPersonal = document.getElementById("txtBuscarPersonal");
      const filtroProfesionPersonal = document.getElementById(
        "filtroProfesionPersonal",
      );

      const txtBuscarProcedimiento = document.getElementById(
        "txtBuscarProcedimiento",
      );
      const filtroSeccionProcedimiento = document.getElementById(
        "filtroSeccionProcedimiento",
      );

      if (txtBuscarCIE) txtBuscarCIE.value = "";
      if (filtroEstadoCIE) filtroEstadoCIE.value = "";
      if (filtroSexoCIE) filtroSexoCIE.value = "";

      if (txtBuscarPersonal) txtBuscarPersonal.value = "";
      if (filtroProfesionPersonal) filtroProfesionPersonal.value = "";

      if (txtBuscarProcedimiento) txtBuscarProcedimiento.value = "";
      if (filtroSeccionProcedimiento) filtroSeccionProcedimiento.value = "";

      await cargarFiltrosCIE10();

      cargarTablaEspecialidades();
      await buscarCIE10Gestion();

      cargarPersonalMedico();
      cargarSeccionesProcedimientos();
      cargarProcedimientos();
    });
  }
  // ==========================================================
  // ANÁLISIS: BOTÓN PARA AMPLIAR EL GRÁFICO PRINCIPAL
  // ==========================================================
  inicializarModalGraficoOrdenes();
  function limpiarPanelEspecialidades() {
    const panelEspecialidades = document.getElementById("panelEspecialidades");
    const titulo = document.getElementById("tituloGraficoEspecialidades");
    const subtitulo = document.getElementById("subtituloGraficoEspecialidades");

    if (panelEspecialidades) {
      panelEspecialidades.classList.remove("activo");
    }

    if (titulo) {
      titulo.textContent = "Especialidades";
    }

    if (subtitulo) {
      subtitulo.textContent = "Selecciona una barra para ver el detalle.";
    }

    if (graficoEspecialidad) {
      graficoEspecialidad.destroy();
      graficoEspecialidad = null;
    }

    const panelDetalle = document.getElementById("panelDetalleEspecialidad");
    const tablaDetalle = document.getElementById("tablaDetalleEspecialidad");
    const contenedorGraficoEspecialidades = document.getElementById(
      "contenedorGraficoEspecialidades",
    );

    if (panelDetalle) {
      panelDetalle.classList.remove("activo");
    }

    if (contenedorGraficoEspecialidades) {
      contenedorGraficoEspecialidades.classList.remove("oculto");
    }

    if (tablaDetalle) {
      tablaDetalle.innerHTML = `
            <tr>
                <td colspan="12">Selecciona una especialidad.</td>
            </tr>
        `;
    }
  }

async function cargarMesesAnalisis() {
  const select = document.getElementById("selectMesGrafico");

  if (!select) return;

  try {
    select.innerHTML = `<option value="">Cargando meses...</option>`;
    select.value = "";

    const res = await fetch("/api/analisis/meses-disponibles", {
      credentials: "include",
    });

    const json = await res.json();

    if (!res.ok || !json.ok) {
      select.innerHTML = `<option value="">No se pudieron cargar meses</option>`;
      return;
    }

    const meses = Array.isArray(json.data) ? json.data : [];

    if (meses.length === 0) {
      select.innerHTML = `<option value="">Sin datos</option>`;
      return;
    }

    /*
      Orden correcto:
      Enero 2026
      Febrero 2026
      Marzo 2026
      Abril 2026
    */
    const mesesOrdenados = [...meses].sort((a, b) => {
      const anioA = Number(a.anio);
      const anioB = Number(b.anio);
      const mesA = Number(a.mes);
      const mesB = Number(b.mes);

      if (anioA !== anioB) {
        return anioB - anioA;
      }

      return mesA - mesB;
    });

    select.innerHTML = "";

    mesesOrdenados.forEach((item, index) => {
      const mes = Number(item.mes);
      const anio = Number(item.anio);

      const option = document.createElement("option");

      option.value = `${anio}-${String(mes).padStart(2, "0")}`;
      option.textContent = `${obtenerNombreMes(mes)} ${anio}`;

      if (index === 0) {
        option.selected = true;
      }

      select.appendChild(option);
    });

    /*
      Forzamos que siempre inicie en el primer mes:
      Enero 2026.
    */
    select.selectedIndex = 0;

    const valorInicial = select.options[0].value;

    limpiarPanelEspecialidades();

    if (typeof limpiarResumenOrdenEspecialidad === "function") {
      limpiarResumenOrdenEspecialidad();
    }

    cargarGraficoPorMes(valorInicial);
  } catch (error) {
    console.error("Error cargando meses de análisis:", error);
    select.innerHTML = `<option value="">Error al cargar meses</option>`;
  }
}

  if (btnCerrarEspecialidades) {
    btnCerrarEspecialidades.addEventListener("click", () => {
      limpiarPanelEspecialidades();
    });
  }

  if (btnVolverEspecialidades) {
    btnVolverEspecialidades.addEventListener("click", () => {
      const panelDetalle = document.getElementById("panelDetalleEspecialidad");
      const contenedorGraficoEspecialidades = document.getElementById(
        "contenedorGraficoEspecialidades",
      );
      const tabla = document.getElementById("tablaDetalleEspecialidad");
      const titulo = document.getElementById("tituloDetalleEspecialidad");
      const subtitulo = document.getElementById("subtituloDetalleEspecialidad");

      if (panelDetalle) {
        panelDetalle.classList.remove("activo");
      }

      if (contenedorGraficoEspecialidades) {
        contenedorGraficoEspecialidades.classList.remove("oculto");
      }

      if (titulo) {
        titulo.textContent = "Detalle de especialidad";
      }

      if (subtitulo) {
        subtitulo.textContent = "Selecciona una especialidad del gráfico.";
      }

      if (tabla) {
        tabla.innerHTML = `
                <tr>
                    <td colspan="12">Selecciona una especialidad.</td>
                </tr>
            `;
      }

      limpiarResumenOrdenEspecialidad();
    });
  }
  function activarMenu(activo) {
    document.querySelectorAll(".sidebar nav a").forEach((a) => {
      a.classList.remove("active");
    });

    activo.classList.add("active");
  }

  menuInicio.addEventListener("click", (e) => {
    e.preventDefault();

    vistaInicio.style.display = "block";
    vistaAnalisis.style.display = "none";
    vistaGestion.style.display = "none";

    // LIMPIAR BUSQUEDA
    txtBusqueda.value = "";

    cargarCirugias();

    activarMenu(menuInicio);
  });

  menuAnalisis.addEventListener("click", async (e) => {
    e.preventDefault();

    vistaInicio.style.display = "none";
    vistaAnalisis.style.display = "block";
    vistaGestion.style.display = "none";

    activarMenu(menuAnalisis);

    await cargarMesesAnalisis();
  });

  if (menuReportes) {
    menuReportes.addEventListener("click", async (e) => {
      e.preventDefault();

      if (vistaInicio) vistaInicio.style.display = "none";
      if (vistaAnalisis) vistaAnalisis.style.display = "none";
      if (vistaReportes) vistaReportes.style.display = "block";
      if (vistaGestion) vistaGestion.style.display = "none";

      activarMenu(menuReportes);

      await cargarMesesReportes();
    });
  }

  if (selectMesGrafico) {
    selectMesGrafico.addEventListener("change", () => {
      const valorMesAnio = selectMesGrafico.value;

      limpiarPanelEspecialidades();
      cargarGraficoPorMes(valorMesAnio);
      limpiarResumenOrdenEspecialidad();
    });
  }

  // MENU GESTION
  menuGestion.addEventListener("click", async (e) => {
    e.preventDefault();

    vistaInicio.style.display = "none";
    vistaAnalisis.style.display = "none";
    vistaGestion.style.display = "block";

    activarMenu(menuGestion);

    // LIMPIAR CIE10
    document.getElementById("txtBuscarCIE").value = "";

    await cargarFiltrosCIE10();

    document.getElementById("filtroEstadoCIE").value = "";
    document.getElementById("filtroSexoCIE").value = "";

    // LIMPIAR PERSONAL
    document.getElementById("txtBuscarPersonal").value = "";
    document.getElementById("filtroProfesionPersonal").value = "";

    // LIMPIAR PROCEDIMIENTOS
    document.getElementById("txtBuscarProcedimiento").value = "";
    document.getElementById("filtroSeccionProcedimiento").value = "";

    cargarTablaEspecialidades();

    await buscarCIE10Gestion();

    cargarPersonalMedico();
    cargarSeccionesProcedimientos();
    cargarProcedimientos();
  });

  // CERRAR SESIÓN
  document.getElementById("btnCerrarSesion").addEventListener("click", () => {
    // opcional: limpiar datos
    localStorage.clear();
    sessionStorage.clear();

    // redirigir
    fetch("/logout-ls", {
      method: "POST",
      credentials: "include",
    })
      .then(() => {
        window.location.href = (window.APP_BASE || "") + "/cirugias-login";
      })
      .catch(() => {
        window.location.href = (window.APP_BASE || "") + "/cirugias-login";
      });
  });

  async function rellenarOperacionPorCIE10() {
    const inputCIE10 = document.getElementById("codigo_cie10");
    const inputDiagnostico = document.getElementById(
      "diagnostico_preoperatorio",
    );
    const inputOperacion = document.getElementById("operacion_realizada");

    const codigoCIE10 = inputCIE10?.value.trim() || "";
    const diagnostico = inputDiagnostico?.value.trim() || "";

    if (!codigoCIE10 && !diagnostico) {
      if (inputOperacion) inputOperacion.value = "";
      limpiarTipoCirugia();
      pintarSugerenciasOperacionCIE10([]);
      return;
    }

    try {
      const params = new URLSearchParams({
        cie10: codigoCIE10,
        diagnostico: diagnostico,
      });

      const res = await fetch(
        `/sigh/operacion-por-cie10?${params.toString()}`,
        {
          credentials: "include",
        },
      );

      const json = await res.json();

      if (!res.ok || !json.success || !json.data) {
        if (inputOperacion) inputOperacion.value = "";
        limpiarTipoCirugia();
        pintarSugerenciasOperacionCIE10([]);
        return;
      }

      aplicarRespuestaOperacionCIE10(json.data, inputOperacion);
    } catch (error) {
      console.error("Error rellenando operación por CIE-10:", error);
    }
  }

  // BUSCAR CIE FORMULARIO
  const inputCIE10 = document.getElementById("codigo_cie10");
  const diagnosticoPre = document.getElementById("diagnostico_preoperatorio");
  const sugerenciasCIE10 = document.getElementById("sugerenciasCIE10");

  if (inputCIE10 && sugerenciasCIE10 && diagnosticoPre) {
    inputCIE10.addEventListener("input", async () => {
      const texto = inputCIE10.value.trim();

      if (texto.length < 2) {
        sugerenciasCIE10.innerHTML = "";
        sugerenciasCIE10.style.display = "none";
        return;
      }

      try {
        const res = await fetch(
          `/cie10/sugerencias?q=${encodeURIComponent(texto)}`,
          {
            credentials: "include",
          },
        );

        const data = await res.json();

        sugerenciasCIE10.innerHTML = "";

        if (!data.length) {
          sugerenciasCIE10.style.display = "none";
          return;
        }

        data.forEach((item) => {
          const opcion = document.createElement("button");
          opcion.type = "button";
          opcion.className = "opcion-cie";
          opcion.innerHTML = `
                        <strong>${item.codigo}</strong>
                        <span>${item.descripcion}</span>
                    `;

          opcion.addEventListener("click", () => {
            inputCIE10.value = item.codigo;
            diagnosticoPre.value = item.descripcion;

            rellenarOperacionPorCIE10();

            sugerenciasCIE10.innerHTML = "";
            sugerenciasCIE10.style.display = "none";
          });

          sugerenciasCIE10.appendChild(opcion);
        });

        sugerenciasCIE10.style.display = "block";
      } catch (error) {
        console.error("Error buscando CIE10:", error);
      }
    });

    inputCIE10.addEventListener("keydown", async (e) => {
      if (e.key === "Enter") {
        e.preventDefault();

        const primeraOpcion = sugerenciasCIE10.querySelector(".opcion-cie");

        if (primeraOpcion) {
          primeraOpcion.click();
        }
      }
    });
    inputCIE10.addEventListener("blur", () => {
      rellenarOperacionPorCIE10();
    });
  }

  // =====================================================================
  // PROCEDIMIENTOS
  // =====================================================================

  const txtBuscarProcedimiento = document.getElementById(
    "txtBuscarProcedimiento",
  );
  const filtroSeccionProcedimiento = document.getElementById(
    "filtroSeccionProcedimiento",
  );
  const tablaProcedimientos = document.getElementById("tablaProcedimientos");

  // Cargar secciones
  async function cargarSeccionesProcedimientos() {
    if (!filtroSeccionProcedimiento) return;

    try {
      const res = await fetch("/procedimientos/secciones", {
        credentials: "include",
      });

      const respuesta = await res.json();

      const secciones = Array.isArray(respuesta.data)
        ? respuesta.data
        : [];

      filtroSeccionProcedimiento.innerHTML = `
        <option value="">Todas las secciones</option>
      `;

      secciones.forEach((item) => {
        const seccion = item.Seccion || item.seccion || "";

        if (!seccion) return;

        const option = document.createElement("option");
        option.value = seccion;
        option.textContent = seccion;

        filtroSeccionProcedimiento.appendChild(option);
      });
    } catch (error) {
      console.error("Error cargando secciones:", error);
    }
  }

  // Cargar tabla de procedimientos
  async function cargarProcedimientos() {
    if (
      !txtBuscarProcedimiento ||
      !filtroSeccionProcedimiento ||
      !tablaProcedimientos
    ) {
      return;
    }

    try {
      const buscar = txtBuscarProcedimiento.value.trim();
      const seccion = filtroSeccionProcedimiento.value.trim();

      const params = new URLSearchParams();

      if (buscar) {
        params.append("q", buscar);
      }

      if (seccion) {
        params.append("seccion", seccion);
      }

      const url = params.toString()
        ? `/procedimientos?${params.toString()}`
        : "/procedimientos";

      const res = await fetch(url, {
        credentials: "include",
      });

      const resultado = await res.json();

      if (!res.ok || resultado.success === false) {
        console.error("Error del servidor al buscar procedimientos:", resultado);

        tablaProcedimientos.innerHTML = `
          <tr>
            <td colspan="4">
              ${resultado.message || "No se pudo realizar la búsqueda."}
            </td>
          </tr>
        `;

        return;
      }

      const datos = Array.isArray(resultado)
        ? resultado
        : Array.isArray(resultado.data)
          ? resultado.data
          : [];

      tablaProcedimientos.innerHTML = "";

      if (datos.length === 0) {
        tablaProcedimientos.innerHTML = `
          <tr>
            <td colspan="4">No se encontraron procedimientos.</td>
          </tr>
        `;
        return;
      }

      datos.forEach((proc) => {
        tablaProcedimientos.innerHTML += `
          <tr>
            <td>${proc.codigo || ""}</td>

            <td title="${proc.nombre || ""}">
              ${proc.nombre || ""}
            </td>

            <td title="${proc.seccion || ""}">
              ${proc.seccion || ""}
            </td>

            <td title="${proc.subseccion || ""}">
              ${proc.subseccion || ""}
            </td>
          </tr>
        `;
      });
    } catch (error) {
      console.error("Error cargando procedimientos:", error);

      tablaProcedimientos.innerHTML = `
        <tr>
          <td colspan="4">No se pudieron cargar los procedimientos.</td>
        </tr>
      `;
    }
  }

  // Buscar mientras escribe
  txtBuscarProcedimiento?.addEventListener("input", () => {
    cargarProcedimientos();
  });

  // Filtrar por sección
  filtroSeccionProcedimiento?.addEventListener("change", () => {
    cargarProcedimientos();
  });

  const btnLimpiarProcedimientos = document.getElementById(
    "btnLimpiarProcedimientos",
  );
  btnLimpiarProcedimientos?.addEventListener("click", () => {
    document.getElementById("txtBuscarProcedimiento").value = "";
    document.getElementById("filtroSeccionProcedimiento").value = "";
    cargarProcedimientos();
  });

  //NOTIFICACION DE GUARDADO DE REGISTRO DE CIRUGÍA
  function mostrarToast(tipo, titulo, mensaje) {
    const contenedor = document.getElementById("toastContainer");

    const toast = document.createElement("div");
    toast.className = `toast ${tipo}`;

    const icono = tipo === "success" ? "fa-circle-check" : "fa-circle-xmark";

    toast.innerHTML = `
            <i class="fa-solid ${icono}"></i>
            <div>
                <strong>${titulo}</strong>
                <span>${mensaje}</span>
            </div>
        `;

    contenedor.appendChild(toast);

    setTimeout(() => {
      toast.remove();
    }, 3500);
  }
  // ======================================================================
  // SUGERIR OPERACIÓN REALIZADA DESDE CIE-10 + CATÁLOGO SIGH
  // ======================================================================
  async function sugerirOperacionDesdeCIE10(codigoCIE10, diagnostico) {
    try {
      const inputOperacion = document.getElementById("operacion_realizada");

      if (!codigoCIE10 && !diagnostico) {
        if (inputOperacion) inputOperacion.value = "";
        limpiarTipoCirugia();
        pintarSugerenciasOperacionCIE10([]);
        return;
      }

      const params = new URLSearchParams({
        cie10: codigoCIE10 || "",
        diagnostico: diagnostico || "",
      });

      const res = await fetch(
        `/sigh/operacion-por-cie10?${params.toString()}`,
        {
          credentials: "include",
        },
      );

      const json = await res.json();

      if (!res.ok || !json.success || !json.data) {
        if (inputOperacion) inputOperacion.value = "";
        limpiarTipoCirugia();
        pintarSugerenciasOperacionCIE10([]);
        return;
      }

      aplicarRespuestaOperacionCIE10(json.data, inputOperacion);
    } catch (error) {
      console.error("Error sugiriendo operación desde CIE-10:", error);
    }
  }

  // ======================================================================
  // AUTOCOMPLETAR DATOS DEL PACIENTE POR DNI O HISTORIA CLÍNICA
  // ======================================================================
  function iniciarAutocompletadoPaciente() {
    const inputDni = document.getElementById("dni");
    const inputHistoria = document.getElementById("historia_clinica");
    const inputNombre = document.getElementById("nombres_apellidos");
    const inputEdadPaciente = document.getElementById("edad");
    const selectSexoPaciente = document.getElementById("sexo");
    const selectTipoSeguro = document.getElementById("tipo_seguro");
    const inputCodigoCIE10 = document.getElementById("codigo_cie10");
    const inputDiagnosticoPreoperatorio = document.getElementById(
      "diagnostico_preoperatorio",
    );

    if (!inputDni && !inputHistoria) {
      console.warn("No se encontraron los campos DNI o Historia Clínica.");
      return;
    }

    let timerBusquedaPaciente = null;
    let ultimaBusquedaPaciente = "";
    let llenandoAutomaticamente = false;

    function limpiarSoloDatosPaciente() {
      if (inputNombre) inputNombre.value = "";
      if (inputEdadPaciente) inputEdadPaciente.value = "";
      if (selectSexoPaciente) selectSexoPaciente.value = "";
      if (selectTipoSeguro) selectTipoSeguro.value = "";
      if (inputCodigoCIE10) inputCodigoCIE10.value = "";
      if (inputDiagnosticoPreoperatorio)
        inputDiagnosticoPreoperatorio.value = "";
    }

    function llenarPaciente(paciente) {
      if (!paciente) return;

      llenandoAutomaticamente = true;

      if (inputDni) {
        inputDni.value = paciente.dni || "";
      }

      if (inputHistoria) {
        inputHistoria.value = paciente.historia_clinica || "";
      }

      if (inputNombre) {
        inputNombre.value = paciente.nombres_apellidos || "";
      }

      if (inputEdadPaciente) {
        inputEdadPaciente.value = paciente.edad || "";
      }

      if (selectSexoPaciente) {
        selectSexoPaciente.value = paciente.sexo || "";
      }

      if (selectTipoSeguro) {
        selectTipoSeguro.value = paciente.tipo_seguro || "";
      }

      if (inputCodigoCIE10) {
        inputCodigoCIE10.value = paciente.codigo_cie10 || "";
      }

      if (inputDiagnosticoPreoperatorio) {
        inputDiagnosticoPreoperatorio.value =
          paciente.diagnostico_preoperatorio || "";
      }

      rellenarOperacionPorCIE10();

      setTimeout(() => {
        llenandoAutomaticamente = false;
      }, 150);
    }

    async function buscarPaciente({ dni = "", historia = "" }) {
      dni = String(dni || "").trim();
      historia = String(historia || "").trim();

      if (!dni && !historia) return;

      const claveBusqueda = dni ? `dni:${dni}` : `historia:${historia}`;

      if (claveBusqueda === ultimaBusquedaPaciente) {
        return;
      }

      ultimaBusquedaPaciente = claveBusqueda;

      try {
        const params = new URLSearchParams();

        if (dni) {
          params.append("dni", dni);
        }

        if (historia) {
          params.append("historia", historia);
        }

        const res = await fetch(`/pacientes/buscar?${params.toString()}`, {
          method: "GET",
          credentials: "include",
        });

        let json = null;

        try {
          json = await res.json();
        } catch (errorJson) {
          console.error("La respuesta del servidor no fue JSON:", errorJson);
          return;
        }

        if (!res.ok || !json.success) {
          console.warn(json.message || "Paciente no encontrado");
          limpiarSoloDatosPaciente();
          return;
        }

        llenarPaciente(json.data || {});
      } catch (error) {
        console.error(
          "Error buscando paciente por DNI o Historia Clínica:",
          error,
        );
      }
    }

    function programarBusquedaPorDni() {
      if (!inputDni || llenandoAutomaticamente) return;

      const dni = inputDni.value.trim();

      clearTimeout(timerBusquedaPaciente);

      if (dni.length === 0) {
        ultimaBusquedaPaciente = "";
        limpiarSoloDatosPaciente();
        return;
      }

      if (!/^\d{8}$/.test(dni)) {
        return;
      }

      timerBusquedaPaciente = setTimeout(() => {
        buscarPaciente({ dni });
      }, 500);
    }

    function programarBusquedaPorHistoria() {
      if (!inputHistoria || llenandoAutomaticamente) return;

      const historia = inputHistoria.value.trim();

      clearTimeout(timerBusquedaPaciente);

      if (historia.length === 0) {
        ultimaBusquedaPaciente = "";
        limpiarSoloDatosPaciente();
        return;
      }

      if (historia.length < 1) {
        return;
      }

      timerBusquedaPaciente = setTimeout(() => {
        buscarPaciente({ historia });
      }, 500);
    }

    if (inputDni) {
      inputDni.addEventListener("input", programarBusquedaPorDni);

      inputDni.addEventListener("blur", () => {
        if (llenandoAutomaticamente) return;

        const dni = inputDni.value.trim();

        if (/^\d{8}$/.test(dni)) {
          buscarPaciente({ dni });
        }
      });
    }

    if (inputHistoria) {
      inputHistoria.addEventListener("input", programarBusquedaPorHistoria);

      inputHistoria.addEventListener("blur", () => {
        if (llenandoAutomaticamente) return;

        const historia = inputHistoria.value.trim();

        if (historia.length >= 1) {
          buscarPaciente({ historia });
        }
      });
    }
  }

  iniciarAutocompletadoPaciente();
  // ======================================================================
  // BÚSQUEDA DE PACIENTES - dbo.Pacientes
  // ======================================================================

  const txtBusquedaPaciente = document.getElementById("txtBusquedaPaciente");
  const btnBuscarPaciente = document.getElementById("btnBuscarPaciente");
  const btnLimpiarPaciente = document.getElementById("btnLimpiarPaciente");
  const tablaBusquedaPacientes = document.getElementById(
    "tablaBusquedaPacientes",
  );
  const mensajeBusquedaPaciente = document.getElementById(
    "mensajeBusquedaPaciente",
  );

  function limpiarTexto(valor) {
    return String(valor || "").trim();
  }

  function formatearFechaPaciente(fecha) {
    if (!fecha) return "";

    if (typeof fecha === "string") {
      if (fecha.includes("T")) {
        const soloFecha = fecha.split("T")[0];
        const partes = soloFecha.split("-");
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
      }

      if (fecha.includes("-")) {
        const partes = fecha.split("-");
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
      }

      return fecha;
    }

    return "";
  }

  function convertirSexo(idTipoSexo) {
    const id = Number(idTipoSexo);

    if (id === 1) return "MASCULINO";
    if (id === 2) return "FEMENINO";

    return "";
  }

  function calcularEdadPaciente(fechaNacimiento) {
    if (!fechaNacimiento) return "";

    const nacimiento = new Date(fechaNacimiento);

    if (Number.isNaN(nacimiento.getTime())) {
      return "";
    }

    const hoy = new Date();

    let edad = hoy.getFullYear() - nacimiento.getFullYear();

    const mes = hoy.getMonth() - nacimiento.getMonth();

    if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
      edad--;
    }

    return edad >= 0 ? edad : "";
  }

  function pintarPacientes(pacientes, mensajeOk) {
    if (!tablaBusquedaPacientes || !mensajeBusquedaPaciente) return;

    tablaBusquedaPacientes.innerHTML = "";

    if (!pacientes || pacientes.length === 0) {
      mensajeBusquedaPaciente.textContent = "No se encontraron pacientes.";
      mensajeBusquedaPaciente.className = "mensaje info";
      return;
    }

    pacientes.forEach((paciente) => {
      tablaBusquedaPacientes.innerHTML += `
            <tr>
                <td>${limpiarTexto(paciente.NroHistoriaClinica)}</td>
                <td>${limpiarTexto(paciente.IdPaciente)}</td>
                <td>${limpiarTexto(paciente.NroDocumento)}</td>

                <td title="${limpiarTexto(paciente.ApellidoPaterno)}">
                    ${limpiarTexto(paciente.ApellidoPaterno)}
                </td>

                <td title="${limpiarTexto(paciente.ApellidoMaterno)}">
                    ${limpiarTexto(paciente.ApellidoMaterno)}
                </td>

                <td title="${limpiarTexto(paciente.PrimerNombre)}">
                    ${limpiarTexto(paciente.PrimerNombre)}
                </td>

                <td title="${limpiarTexto(paciente.SegundoNombre)}">
                    ${limpiarTexto(paciente.SegundoNombre)}
                </td>

                <td title="${limpiarTexto(paciente.TercerNombre)}">
                    ${limpiarTexto(paciente.TercerNombre)}
                </td>

                <td>${convertirSexo(paciente.IdTipoSexo)}</td>
                <td>${formatearFechaPaciente(paciente.FechaNacimiento)}</td>
                <td>${limpiarTexto(paciente.Telefono)}</td>

                <td title="${limpiarTexto(paciente.DireccionDomicilio)}">
                    ${limpiarTexto(paciente.DireccionDomicilio)}
                </td>

                <td title="${limpiarTexto(paciente.Observacion)}">
                    ${limpiarTexto(paciente.Observacion)}
                </td>
            </tr>
        `;
    });

    mensajeBusquedaPaciente.textContent = mensajeOk;
    mensajeBusquedaPaciente.className = "mensaje success";
  }

  async function cargarPacientesIniciales() {
    if (!tablaBusquedaPacientes || !mensajeBusquedaPaciente) return;

    try {
      mensajeBusquedaPaciente.textContent = "Cargando pacientes...";
      mensajeBusquedaPaciente.className = "mensaje info";

      const res = await fetch("/pacientes?limit=150", {
        method: "GET",
        credentials: "include",
      });

      const json = await res.json();

      if (!res.ok || !json.success) {
        mensajeBusquedaPaciente.textContent =
          json.message || "No se pudieron cargar pacientes.";
        mensajeBusquedaPaciente.className = "mensaje error";
        tablaBusquedaPacientes.innerHTML = "";
        return;
      }

      pintarPacientes(
        json.data || [],
        `Se muestran ${json.data.length} pacientes para visualización.`,
      );
    } catch (error) {
      console.error("Error cargando pacientes:", error);
      mensajeBusquedaPaciente.textContent =
        "Error de conexión con el servidor.";
      mensajeBusquedaPaciente.className = "mensaje error";
    }
  }

  async function buscarPacientes() {
    if (
      !txtBusquedaPaciente ||
      !tablaBusquedaPacientes ||
      !mensajeBusquedaPaciente
    )
      return;

    const busqueda = txtBusquedaPaciente.value.trim();

    if (busqueda.length < 1) {
      mensajeBusquedaPaciente.textContent =
        "Escribe una historia clínica, nombre o apellido para buscar.";
      mensajeBusquedaPaciente.className = "mensaje error";
      tablaBusquedaPacientes.innerHTML = "";
      return;
    }

    try {
      mensajeBusquedaPaciente.textContent = "Buscando pacientes...";
      mensajeBusquedaPaciente.className = "mensaje info";

      const res = await fetch(
        `/pacientes?busqueda=${encodeURIComponent(busqueda)}&limit=150`,
        {
          method: "GET",
          credentials: "include",
        },
      );

      const json = await res.json();

      if (!res.ok || !json.success) {
        mensajeBusquedaPaciente.textContent =
          json.message || "No se pudo buscar pacientes.";
        mensajeBusquedaPaciente.className = "mensaje error";
        tablaBusquedaPacientes.innerHTML = "";
        return;
      }

      pintarPacientes(
        json.data || [],
        `Se encontraron ${json.data.length} paciente(s).`,
      );
    } catch (error) {
      console.error("Error buscando pacientes:", error);
      mensajeBusquedaPaciente.textContent =
        "Error de conexión con el servidor.";
      mensajeBusquedaPaciente.className = "mensaje error";
    }
  }

  function limpiarBusquedaPacientes() {
    if (txtBusquedaPaciente) txtBusquedaPaciente.value = "";
    cargarPacientesIniciales();
  }

  btnBuscarPaciente?.addEventListener("click", buscarPacientes);

  btnLimpiarPaciente?.addEventListener("click", limpiarBusquedaPacientes);

  txtBusquedaPaciente?.addEventListener("keyup", (e) => {
    if (e.key === "Enter") {
      buscarPacientes();
    }
  });
});

function obtenerNombreMes(numeroMes) {
  const meses = [
    "",
    "Enero",
    "Febrero",
    "Marzo",
    "Abril",
    "Mayo",
    "Junio",
    "Julio",
    "Agosto",
    "Septiembre",
    "Octubre",
    "Noviembre",
    "Diciembre",
  ];

  return meses[Number(numeroMes)] || "";
}

//FORMULARIO:PERSONAL BUSQUEDA
function configurarBusquedaPersonal(idInput) {
  const input = document.getElementById(idInput);

  if (!input) return;

  /*
   * Relación estricta entre cada campo y su profesión.
   */
  const profesionesPorCampo = {
    cirujano_1: "CIRUJANO",
    cirujano_2: "CIRUJANO",

    anestesiologo: "ANESTESIOLOGO",
    anestesiologo_recuperacion: "ANESTESIOLOGO",

    enfermera_instrumentista: "LICENCIADA(O) ENFERMERIA",
    enfermera_recuperacion: "LICENCIADA(O) ENFERMERIA",

    tecnico_enfermeria_1: "TECNICO DE ENFERMERIA",
    tecnico_enfermeria_2: "TECNICO DE ENFERMERIA",
  };

  const profesionEsperada = profesionesPorCampo[idInput];

  if (!profesionEsperada) {
    console.warn(
      `No existe una profesión configurada para el campo: ${idInput}`,
    );
    return;
  }

  /*
   * Evita registrar dos veces el mismo campo.
   */
  if (input.dataset.busquedaPersonalConfigurada === "1") {
    return;
  }

  input.dataset.busquedaPersonalConfigurada = "1";
  input.setAttribute("autocomplete", "off");

  const padre = input.parentElement;

  if (!padre) return;

  padre.style.position = "relative";

  /*
   * Elimina listas antiguas que hayan quedado duplicadas.
   */
  padre
    .querySelectorAll(".sugerencias-personal")
    .forEach((elemento) => elemento.remove());

  const contenedor = document.createElement("div");
  contenedor.className = "sugerencias-personal";
  contenedor.style.display = "none";

  padre.appendChild(contenedor);

  let solicitudActual = null;

  const normalizar = (valor) => {
    return String(valor || "")
      .trim()
      .toUpperCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/\s+/g, " ");
  };

  input.addEventListener("input", async () => {
    const texto = input.value.trim();

    /*
     * Al volver a escribir, se invalida la selección anterior.
     */
    delete input.dataset.personalId;
    delete input.dataset.dni;
    delete input.dataset.profesion;

    if (texto.length < 2) {
      contenedor.innerHTML = "";
      contenedor.style.display = "none";
      return;
    }

    /*
     * Cancela la búsqueda anterior cuando el usuario escribe rápido.
     */
    if (solicitudActual) {
      solicitudActual.abort();
    }

    solicitudActual = new AbortController();

    try {
      const params = new URLSearchParams();

      params.set("busqueda", texto);

      /*
       * Se envía el campo, no la profesión.
       * PHP decidirá qué profesión corresponde.
       */
      params.set("campo", idInput);

      const res = await fetch(
        `/personal-medico?${params.toString()}`,
        {
          credentials: "include",
          signal: solicitudActual.signal,
        },
      );

      const respuesta = await res.json();

      if (!res.ok || !respuesta.success) {
        console.error(
          "Error buscando personal:",
          respuesta,
        );

        contenedor.innerHTML = `
          <div class="sin-resultados-personal">
            No se pudo realizar la búsqueda.
          </div>
        `;

        contenedor.style.display = "block";
        return;
      }

      const data = Array.isArray(respuesta.data)
        ? respuesta.data
        : [];

      /*
       * Segunda validación en JavaScript.
       * Aunque el servidor enviara otra profesión,
       * aquí no se mostrará.
       */
      const resultados = data.filter((item) => {
        const profesionCorrecta =
          normalizar(item.profesion) ===
          normalizar(profesionEsperada);

        const estaActivo =
          normalizar(item.estado) === "ACTIVO";

        return profesionCorrecta && estaActivo;
      });

      contenedor.innerHTML = "";

      /*
       * Ocultar las sugerencias de los demás campos.
       */
      document
        .querySelectorAll(".sugerencias-personal")
        .forEach((lista) => {
          if (lista !== contenedor) {
            lista.style.display = "none";
          }
        });

      if (resultados.length === 0) {
        contenedor.innerHTML = `
          <div class="sin-resultados-personal">
            No se encontraron ${profesionEsperada}.
          </div>
        `;

        contenedor.style.display = "block";
        return;
      }

      resultados.slice(0, 8).forEach((item) => {
        const opcion = document.createElement("button");

        opcion.type = "button";
        opcion.className = "opcion-personal";

        opcion.innerHTML = `
          <strong>
            ${item.apellidos_nombres || ""}
          </strong>

          <span>
            DNI: ${item.dni || "Sin DNI"}
          </span>

          <small>
            ${item.profesion || ""}
          </small>
        `;

        opcion.addEventListener("mousedown", (evento) => {
          evento.preventDefault();

          input.value = item.apellidos_nombres || "";

          input.dataset.personalId = item.id || "";
          input.dataset.dni = item.dni || "";
          input.dataset.profesion = item.profesion || "";

          contenedor.innerHTML = "";
          contenedor.style.display = "none";
        });

        contenedor.appendChild(opcion);
      });

      contenedor.style.display = "block";
    } catch (error) {
      if (error.name === "AbortError") {
        return;
      }

      console.error(
        "Error buscando personal:",
        error,
      );

      contenedor.innerHTML = `
        <div class="sin-resultados-personal">
          Error de conexión.
        </div>
      `;

      contenedor.style.display = "block";
    }
  });

  input.addEventListener("focus", () => {
    if (contenedor.children.length > 0) {
      contenedor.style.display = "block";
    }
  });

  input.addEventListener("blur", () => {
    setTimeout(() => {
      contenedor.style.display = "none";
    }, 150);
  });
}

// ==============================================================================================================================
// BLOQUE: ANÁLISIS - HELPERS PARA CARDS, DONA, RESUMEN Y MODAL
// ==============================================================================================================================

function setTextoAnalisis(id, valor) {
  const elemento = document.getElementById(id);

  if (!elemento) return;

  elemento.textContent = valor;
}

function normalizarTipoOrdenAnalisis(valor) {
  return String(valor || "")
    .trim()
    .toUpperCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "");
}

function obtenerTotalPorTipo(data, tipoBuscado) {
  const tipoNormalizado = normalizarTipoOrdenAnalisis(tipoBuscado);

  const encontrado = data.find((item) => {
    return normalizarTipoOrdenAnalisis(item.tipo_orden) === tipoNormalizado;
  });

  return Number(encontrado?.total_pacientes || encontrado?.total || 0);
}

function obtenerTotalGeneralAnalisis(data) {
  return data.reduce((acc, item) => {
    return acc + Number(item.total_pacientes || item.total || 0);
  }, 0);
}

function formatearPeriodoMesAnalisis(mesNumero, anioNumero) {
  const inicio = new Date(anioNumero, mesNumero - 1, 1);
  const fin = new Date(anioNumero, mesNumero, 0);

  const formato = new Intl.DateTimeFormat("es-PE", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });

  return `${formato.format(inicio)} – ${formato.format(fin)}`;
}

function formatearFechaCortaAnalisis(fecha) {
  if (!fecha) return "--";

  const d = new Date(fecha);

  if (Number.isNaN(d.getTime())) return "--";

  return d.toLocaleDateString("es-PE", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

function actualizarDashboardAnalisis(data, mesNumero, anioNumero) {
  const registros = Array.isArray(data) ? data : [];

  const emergencia = obtenerTotalPorTipo(registros, "EMERGENCIA");
  const electiva = obtenerTotalPorTipo(registros, "ELECTIVA");
  const total = obtenerTotalGeneralAnalisis(registros);

  const porcentajeEmergencia =
    total > 0 ? ((emergencia / total) * 100).toFixed(1) : "0";

  const porcentajeElectiva =
    total > 0 ? ((electiva / total) * 100).toFixed(1) : "0";

  const tasaUrgencia =
    total > 0 ? ((emergencia / total) * 100).toFixed(1) : "0";

  const nombreMes = obtenerNombreMes(mesNumero);

  setTextoAnalisis("kpiTotalCirugias", total);
  setTextoAnalisis("kpiEmergencia", emergencia);
  setTextoAnalisis("kpiElectiva", electiva);
  setTextoAnalisis("kpiTasaUrgencia", `${tasaUrgencia}%`);

  setTextoAnalisis(
    "kpiEmergenciaPorcentaje",
    `${porcentajeEmergencia}% del total`,
  );

  setTextoAnalisis("kpiElectivaPorcentaje", `${porcentajeElectiva}% del total`);

  const badgeTotal = document.getElementById("badgeTotalGrafico");

  if (badgeTotal) {
    badgeTotal.innerHTML = `
      <i class="fa-solid fa-circle-info"></i>
      Total: ${total} cirugías
    `;
  }

  setTextoAnalisis("tituloMesGrafico", `${nombreMes} ${anioNumero}`);
  setTextoAnalisis("tituloMesGraficoModal", `${nombreMes} ${anioNumero}`);

  setTextoAnalisis(
    "donaEmergenciaTexto",
    `${porcentajeEmergencia}% (${emergencia})`,
  );

  setTextoAnalisis("donaElectivaTexto", `${porcentajeElectiva}% (${electiva})`);

  setTextoAnalisis(
    "resumenPeriodo",
    formatearPeriodoMesAnalisis(mesNumero, anioNumero),
  );

  const diasMes = new Date(anioNumero, mesNumero, 0).getDate();
  const promedioDiario = total > 0 ? (total / diasMes).toFixed(2) : "0.00";

  setTextoAnalisis("resumenPromedioDiario", `${promedioDiario} cirugías`);

  actualizarGraficoDonaAnalisis(emergencia, electiva);
  cargarResumenPeriodoAnalisis(mesNumero, anioNumero);
}

function actualizarDashboardAnalisisVacio(mesNumero, anioNumero) {
  actualizarDashboardAnalisis([], mesNumero, anioNumero);
  setTextoAnalisis("resumenDiaMayor", "--");
}

function actualizarGraficoDonaAnalisis(emergencia, electiva) {
  const canvas = document.getElementById("graficoDistribucionOrdenes");

  if (!canvas || typeof Chart === "undefined") return;

  const ctx = canvas.getContext("2d");

  if (graficoDistribucionOrdenes) {
    graficoDistribucionOrdenes.destroy();
    graficoDistribucionOrdenes = null;
  }

  graficoDistribucionOrdenes = new Chart(ctx, {
    type: "doughnut",
    data: {
      labels: ["Emergencia", "Electiva"],
      datasets: [
        {
          data: [Number(emergencia || 0), Number(electiva || 0)],
          backgroundColor: [
            "rgba(239, 68, 68, 0.88)",
            "rgba(37, 99, 235, 0.88)",
          ],
          borderColor: "#ffffff",
          borderWidth: 4,
          hoverOffset: 8,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: "62%",
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label(context) {
              const total = Number(emergencia || 0) + Number(electiva || 0);
              const valor = Number(context.raw || 0);
              const porcentaje =
                total > 0 ? ((valor / total) * 100).toFixed(1) : "0.0";

              return `${context.label}: ${valor} (${porcentaje}%)`;
            },
          },
        },
      },
    },
  });
}

async function cargarResumenPeriodoAnalisis(mesNumero, anioNumero) {
  try {
    const res = await fetch(
      `/api/analisis/resumen-periodo?mes=${mesNumero}&anio=${anioNumero}`,
      {
        credentials: "include",
      },
    );

    const json = await res.json();

    if (!res.ok || !json.ok) {
      setTextoAnalisis("resumenDiaMayor", "--");
      return;
    }

    const data = json.data || {};

    if (data.dia_mayor_fecha) {
      setTextoAnalisis(
        "resumenDiaMayor",
        `${formatearFechaCortaAnalisis(data.dia_mayor_fecha)} (${Number(data.dia_mayor_total || 0)} cirugías)`,
      );
    } else {
      setTextoAnalisis("resumenDiaMayor", "--");
    }
  } catch (error) {
    console.warn(
      "No se pudo cargar /api/analisis/resumen-periodo. Agrega esa ruta en server.js para mostrar el día con más cirugías.",
      error,
    );

    setTextoAnalisis("resumenDiaMayor", "--");
  }
}

function obtenerDatosGraficoOrden(data, mesNumero, anioNumero) {
  const registros = Array.isArray(data) ? data : [];

  const labels = registros.map((item) =>
    normalizarTipoOrdenAnalisis(item.tipo_orden || "SIN TIPO"),
  );

  const valores = registros.map((item) =>
    Number(item.total_pacientes || item.total || 0),
  );

  const totalPacientes = valores.reduce((acc, valor) => acc + valor, 0);
  const nombreMes = obtenerNombreMes(mesNumero);

  return {
    labels,
    valores,
    totalPacientes,
    nombreMes,
    mesNumero,
    anioNumero,
  };
}

function crearPluginEtiquetasTipoOrden(totalPacientes) {
  return {
    id: `etiquetasPorcentajeTipoOrden_${Date.now()}`,
    afterDatasetsDraw(chart) {
      const { ctx } = chart;
      const dataset = chart.data.datasets[0];
      const meta = chart.getDatasetMeta(0);

      ctx.save();
      ctx.textAlign = "center";
      ctx.textBaseline = "bottom";
      ctx.fillStyle = document.body.classList.contains("dark-mode")
        ? "#ffffff"
        : "#172033";
      ctx.font = "bold 13px Segoe UI";

      meta.data.forEach((barra, index) => {
        const valor = Number(dataset.data[index] || 0);
        const porcentaje =
          totalPacientes > 0
            ? ((valor / totalPacientes) * 100).toFixed(1)
            : "0.0";

        ctx.fillText(`${valor} (${porcentaje}%)`, barra.x, barra.y - 10);
      });

      ctx.restore();
    },
  };
}

function crearGraficoTipoOrdenEnCanvas({
  canvas,
  datos,
  valorMesAnio,
  permitirDetalle = true,
}) {
  if (!canvas || typeof Chart === "undefined") return null;

  const ctx = canvas.getContext("2d");
  const { labels, valores, totalPacientes, nombreMes, anioNumero } = datos;

  return new Chart(ctx, {
    type: "bar",
    data: {
      labels,
      datasets: [
        {
          label: `Tipo de orden - ${nombreMes.toUpperCase()} ${anioNumero}`,
          data: valores,
          backgroundColor: [
            "rgba(239, 68, 68, 0.45)",
            "rgba(37, 99, 235, 0.45)",
            "rgba(16, 185, 129, 0.45)",
            "rgba(245, 158, 11, 0.45)",
          ],
          borderColor: [
            "rgba(220, 38, 38, 0.85)",
            "rgba(29, 78, 216, 0.85)",
            "rgba(5, 150, 105, 0.85)",
            "rgba(217, 119, 6, 0.85)",
          ],
          borderWidth: 2,
          borderRadius: 14,
          barThickness: 90,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: {
        duration: 1200,
        easing: "easeOutQuart",
      },
      onClick: async (event, elements) => {
        if (!permitirDetalle || !elements.length) return;

        const index = elements[0].index;
        const tipoOrden = labels[index];

        if (tipoOrden === "ELECTIVA") {
          await cargarGraficoMayorMenorElectiva(valorMesAnio);
          return;
        }

        await cargarGraficoEspecialidadesPorTipo(valorMesAnio, tipoOrden);
      },
      onHover: (event, elements) => {
        if (!event?.native?.target) return;

        event.native.target.style.cursor =
          permitirDetalle && elements.length ? "pointer" : "default";
      },
      plugins: {
        legend: {
          display: true,
          labels: {
            color: document.body.classList.contains("dark-mode")
              ? "#ffffff"
              : "#172033",
            font: {
              size: 14,
              weight: "600",
            },
          },
        },
        tooltip: {
          backgroundColor: "#172033",
          titleColor: "#ffffff",
          bodyColor: "#ffffff",
          padding: 14,
          cornerRadius: 12,
          callbacks: {
            label(context) {
              const valor = Number(context.raw || 0);
              const porcentaje =
                totalPacientes > 0
                  ? ((valor / totalPacientes) * 100).toFixed(1)
                  : "0.0";

              return ` ${valor} cirugías (${porcentaje}%)${
                permitirDetalle ? " - Click para ver detalle" : ""
              }`;
            },
          },
        },
      },
      scales: {
        x: {
          grid: {
            display: false,
          },
          ticks: {
            color: document.body.classList.contains("dark-mode")
              ? "#e2e8f0"
              : "#475569",
            font: {
              size: 14,
              weight: "700",
            },
          },
        },
        y: {
          beginAtZero: true,
          suggestedMax: Math.max(...valores, 0) + 10,
          grid: {
            color: "rgba(148, 163, 184, 0.25)",
          },
          ticks: {
            precision: 0,
            color: document.body.classList.contains("dark-mode")
              ? "#cbd5e1"
              : "#64748b",
            font: {
              size: 13,
              weight: "600",
            },
          },
        },
      },
    },
    plugins: [crearPluginEtiquetasTipoOrden(totalPacientes)],
  });
}

function inicializarModalGraficoOrdenes() {
  const modal = document.getElementById("modalGraficoOrdenes");
  const btnAbrir = document.getElementById("btnAmpliarGraficoOrdenes");
  const btnCerrar = document.getElementById("btnCerrarModalGraficoOrdenes");
  const btnMaximizar = document.getElementById("btnMaximizarGraficoOrdenes");
  const backdrop = modal?.querySelector(".modal-grafico-backdrop");

  if (!modal || !btnAbrir) return;

  btnAbrir.addEventListener("click", () => {
    abrirModalGraficoOrdenes();
  });

  btnCerrar?.addEventListener("click", () => {
    cerrarModalGraficoOrdenes();
  });

  backdrop?.addEventListener("click", () => {
    cerrarModalGraficoOrdenes();
  });

  btnMaximizar?.addEventListener("click", () => {
    modal.classList.toggle("maximizado");

    setTimeout(() => {
      if (graficoOrdenModal) {
        graficoOrdenModal.resize();
      }
    }, 120);
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.classList.contains("activo")) {
      cerrarModalGraficoOrdenes();
    }
  });
}

function abrirModalGraficoOrdenes() {
  const modal = document.getElementById("modalGraficoOrdenes");
  const canvas = document.getElementById("graficoOrdenesModal");

  if (!modal || !canvas || !datosGraficoOrdenActual) return;

  modal.classList.add("activo");

  if (graficoOrdenModal) {
    graficoOrdenModal.destroy();
    graficoOrdenModal = null;
  }

  setTimeout(() => {
    graficoOrdenModal = crearGraficoTipoOrdenEnCanvas({
      canvas,
      datos: datosGraficoOrdenActual.datos,
      valorMesAnio: datosGraficoOrdenActual.valorMesAnio,
      permitirDetalle: false,
    });
  }, 80);
}

function cerrarModalGraficoOrdenes() {
  const modal = document.getElementById("modalGraficoOrdenes");

  if (!modal) return;

  modal.classList.remove("activo");
  modal.classList.remove("maximizado");

  if (graficoOrdenModal) {
    graficoOrdenModal.destroy();
    graficoOrdenModal = null;
  }
}

// ==============================
// 🔴 GRÁFICO TIPO ORDEN  (scope global)
// ==============================
async function cargarGraficoPorMes(valorMesAnio) {
  if (!valorMesAnio) return;

  const [anio, mes] = valorMesAnio.split("-");
  const mesNumero = Number(mes);
  const anioNumero = Number(anio);

  if (!mesNumero || !anioNumero) return;

  try {
    const res = await fetch(
      `/api/analisis/tipo-orden?mes=${mesNumero}&anio=${anioNumero}`,
      {
        credentials: "include",
      },
    );

    const json = await res.json();

    if (!res.ok || !json.ok) {
      console.error("Error cargando gráfico tipo de orden:", json);
      actualizarDashboardAnalisisVacio(mesNumero, anioNumero);
      return;
    }

    const data = Array.isArray(json.data) ? json.data : [];

    // Actualiza tarjetas, dona, total, porcentajes y resumen del período
    actualizarDashboardAnalisis(data, mesNumero, anioNumero);

    const canvas = document.getElementById("graficoOrdenes");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");

    if (graficoOrden) {
      graficoOrden.destroy();
      graficoOrden = null;
    }

    if (data.length === 0) {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      datosGraficoOrdenActual = null;
      console.warn("No hay datos para este mes");
      return;
    }

    const datos = obtenerDatosGraficoOrden(data, mesNumero, anioNumero);

    datosGraficoOrdenActual = {
      datos,
      valorMesAnio,
    };

    graficoOrden = crearGraficoTipoOrdenEnCanvas({
      canvas,
      datos,
      valorMesAnio,
      permitirDetalle: true,
    });
  } catch (error) {
    console.error("Error cargando gráfico por mes:", error);
    actualizarDashboardAnalisisVacio(mesNumero, anioNumero);
  }
}

function enfocarGraficoAnalisis() {
  const panel = document.getElementById("panelEspecialidades");
  const contenedorGrafico = document.getElementById(
    "contenedorGraficoEspecialidades",
  );

  if (!panel) return;

  panel.classList.remove("animar-grafico");

  if (contenedorGrafico) {
    contenedorGrafico.classList.remove("animar-canvas-grafico");
  }

  // Reinicia la animación aunque el panel ya esté visible
  void panel.offsetWidth;

  panel.classList.add("activo");
  panel.classList.add("animar-grafico");

  if (contenedorGrafico) {
    contenedorGrafico.classList.add("animar-canvas-grafico");
  }

  setTimeout(() => {
    panel.scrollIntoView({
      behavior: "smooth",
      block: "center",
    });
  }, 180);

  setTimeout(() => {
    panel.classList.remove("animar-grafico");

    if (contenedorGrafico) {
      contenedorGrafico.classList.remove("animar-canvas-grafico");
    }
  }, 900);
}

async function cargarGraficoMayorMenorElectiva(valorMesAnio) {
  if (!valorMesAnio) return;

  const [anio, mes] = valorMesAnio.split("-");
  const mesNumero = Number(mes);
  const anioNumero = Number(anio);

  if (!mesNumero || !anioNumero) return;

  const panel = document.getElementById("panelEspecialidades");
  const titulo = document.getElementById("tituloGraficoEspecialidades");
  const subtitulo = document.getElementById("subtituloGraficoEspecialidades");
  const contenedorGraficoEspecialidades = document.getElementById(
    "contenedorGraficoEspecialidades",
  );
  const panelDetalle = document.getElementById("panelDetalleEspecialidad");

  try {
    const res = await fetch(
      `/api/analisis/mayor-menor-electiva?mes=${mesNumero}&anio=${anioNumero}`,
      {
        credentials: "include",
      },
    );

    const json = await res.json();

    if (!res.ok || !json.ok) {
      console.error("No se pudo cargar mayor/menor electiva");
      return;
    }

    if (panel) {
      panel.classList.add("activo");
    }

    if (contenedorGraficoEspecialidades) {
      contenedorGraficoEspecialidades.classList.remove("oculto");
    }

    if (panelDetalle) {
      panelDetalle.classList.remove("activo");
    }

    const nombreMes = obtenerNombreMes(mesNumero);

    if (titulo) {
      titulo.textContent = "Cirugía mayor y menor - ELECTIVA";
    }

    if (subtitulo) {
      subtitulo.textContent = `${nombreMes.toUpperCase()} ${anioNumero}`;
    }

    const data = Array.isArray(json.data) ? json.data : [];

    const labels = data.map((item) => item.tipo_cirugia);
    const valores = data.map((item) => Number(item.total || 0));
    const total = valores.reduce((acc, valor) => acc + valor, 0);
    const pluginEtiquetasMayorMenor = {
      id: "pluginEtiquetasMayorMenor",
      afterDatasetsDraw(chart) {
        const { ctx } = chart;
        const dataset = chart.data.datasets[0];
        const meta = chart.getDatasetMeta(0);

        ctx.save();
        ctx.textAlign = "center";
        ctx.textBaseline = "bottom";
        ctx.fillStyle = "#1e293b";
        ctx.font = "bold 13px Arial";

        meta.data.forEach((barra, index) => {
          const valor = Number(dataset.data[index] || 0);
          const porcentaje =
            total > 0 ? ((valor / total) * 100).toFixed(1) : "0.0";

          ctx.fillText(`${valor} (${porcentaje}%)`, barra.x, barra.y - 8);
        });

        ctx.restore();
      },
    };

    const canvas = document.getElementById("graficoEspecialidades");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");

    if (graficoEspecialidad) {
      graficoEspecialidad.destroy();
      graficoEspecialidad = null;
    }

    graficoEspecialidad = new Chart(ctx, {
      type: "bar",
      data: {
        labels,
        datasets: [
          {
            label: "Cirugía electiva",
            data: valores,
            backgroundColor: [
              "rgba(249, 115, 22, 0.55)", // MAYOR
              "rgba(16, 185, 129, 0.55)", // MENOR
            ],
            borderColor: ["rgba(234, 88, 12, 1)", "rgba(5, 150, 105, 1)"],
            borderWidth: 2,
            borderRadius: 12,
            barThickness: 80,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,

        onClick: async (event, elements) => {
          if (!elements.length) return;

          const index = elements[0].index;
          const tipoCirugia = labels[index];

          await cargarGraficoEspecialidadesPorTipo(
            valorMesAnio,
            "ELECTIVA",
            tipoCirugia,
          );
        },

        onHover: (event, elements) => {
          event.native.target.style.cursor = elements.length
            ? "pointer"
            : "default";
        },

        plugins: {
          legend: {
            display: true,
            labels: {
              generateLabels(chart) {
                return [
                  {
                    text: "Cirugía electiva",
                    fillStyle: "rgba(37, 99, 235, 0.55)",
                    strokeStyle: "rgba(29, 78, 216, 1)",
                    lineWidth: 2,
                    hidden: false,
                    datasetIndex: 0,
                  },
                ];
              },
            },
          },
          tooltip: {
            callbacks: {
              label(context) {
                const valor = Number(context.raw || 0);
                const porcentaje =
                  total > 0 ? ((valor / total) * 100).toFixed(1) : "0.0";

                return `${valor} pacientes (${porcentaje}%) - Click para ver especialidades`;
              },
            },
          },
        },

        scales: {
          x: {
            grid: {
              display: false,
            },
          },
          y: {
            beginAtZero: true,
            suggestedMax: Math.max(...valores, 0) + 10,
            ticks: {
              precision: 0,
            },
          },
        },
      },
      plugins: [pluginEtiquetasMayorMenor],
    });

    enfocarGraficoAnalisis();
  } catch (error) {
    console.error("Error cargando gráfico mayor/menor electiva:", error);
  }
}

// ==============================
// 🔵 GRÁFICO ESPECIALIDADES  (scope global)
// ==============================
async function cargarGraficoEspecialidadesPorTipo(
  valorMesAnio,
  tipoOrden,
  tipoCirugia = "",
) {
  if (!valorMesAnio || !tipoOrden) return;

  const [anio, mes] = valorMesAnio.split("-");
  const mesNumero = Number(mes);
  const anioNumero = Number(anio);

  if (!mesNumero || !anioNumero) return;

  const panel = document.getElementById("panelEspecialidades");
  const titulo = document.getElementById("tituloGraficoEspecialidades");
  const subtitulo = document.getElementById("subtituloGraficoEspecialidades");

  try {
    const params = new URLSearchParams();

    params.append("mes", mesNumero);
    params.append("anio", anioNumero);
    params.append("tipo_orden", tipoOrden);

    if (tipoCirugia) {
      params.append("tipo_cirugia", tipoCirugia);
    }

    const url = `/api/analisis/especialidades?${params.toString()}`;
    const res = await fetch(url, {
      credentials: "include",
    });

    const data = await res.json();

    if (!res.ok || !data.ok) {
      console.error("Error gráfico especialidades por tipo");
      return;
    }

    if (panel) {
      panel.classList.add("activo");
    }

    const nombreMes = obtenerNombreMes(mesNumero);

    if (titulo) {
      titulo.textContent = tipoCirugia
        ? `Especialidades de ELECTIVA - CIRUGÍA ${tipoCirugia}`
        : `Cirugías de ${tipoOrden}`;
    }

    if (subtitulo) {
      subtitulo.textContent = `${nombreMes.toUpperCase()} ${anioNumero}`;
    }

    if (!data.data || data.data.length === 0) {
      if (graficoEspecialidad) {
        graficoEspecialidad.destroy();
        graficoEspecialidad = null;
      }
      return;
    }

    const labels = data.data.map(
      (item) => item.especialidad || "SIN ESPECIALIDAD",
    );
    const valores = data.data.map((item) => Number(item.total || 0));
    const total = valores.reduce((acc, v) => acc + v, 0);

    const canvas = document.getElementById("graficoEspecialidades");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");

    if (graficoEspecialidad) {
      graficoEspecialidad.destroy();
      graficoEspecialidad = null;
    }

    graficoEspecialidad = new Chart(ctx, {
      type: "bar",
      data: {
        labels,
        datasets: [
          {
            label: `Especialidades - ${tipoOrden}`,
            data: valores,
            backgroundColor: "rgba(16, 185, 129, 0.6)",
            borderColor: "rgba(5, 150, 105, 1)",
            borderWidth: 2,
            borderRadius: 10,
            barThickness: 25,
          },
        ],
      },
      options: {
        indexAxis: "y",
        responsive: true,
        maintainAspectRatio: false,
        onClick: async (event, elements) => {
          if (!elements.length) return;

          const index = elements[0].index;
          const especialidadSeleccionada = labels[index];

          await cargarDetalleEspecialidad(
            valorMesAnio,
            tipoOrden,
            especialidadSeleccionada,
            tipoCirugia,
          );
        },

        onHover: (event, elements) => {
          event.native.target.style.cursor = elements.length
            ? "pointer"
            : "default";
        },

        animation: {
          duration: 900,
          easing: "easeOutQuart",
        },
        layout: {
          padding: {
            right: 70,
          },
        },
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                const v = Number(context.raw || 0);
                const p = total > 0 ? ((v / total) * 100).toFixed(1) : "0.0";
                return `${v} pacientes (${p}%)`;
              },
            },
          },
        },
        scales: {
          x: {
            beginAtZero: true,
            grid: {
              color: "rgba(0,0,0,0.05)",
            },
            ticks: {
              color: document.body.classList.contains("dark-mode")
                ? "#cbd5e1"
                : "#64748b",
              precision: 0,
            },
          },
          y: {
            grid: {
              display: false,
            },
            ticks: {
              color: "#172033",
              font: {
                weight: "bold",
              },
            },
          },
        },
      },
      plugins: [
        {
          id: "labelsEspecialidadesPorTipo",
          afterDatasetsDraw(chart) {
            const { ctx } = chart;

            chart.data.datasets.forEach((dataset, datasetIndex) => {
              const meta = chart.getDatasetMeta(datasetIndex);

              meta.data.forEach((bar, index) => {
                const valor = Number(dataset.data[index] || 0);
                const porcentaje =
                  total > 0 ? ((valor / total) * 100).toFixed(1) : "0.0";

                const texto = `${valor} (${porcentaje}%)`;

                ctx.save();
                ctx.font = "bold 12px Segoe UI";
                ctx.fillStyle = "#172033";
                ctx.textAlign = "left";
                ctx.fillText(texto, bar.x + 15, bar.y + 4);
                ctx.restore();
              });
            });
          },
        },
      ],
    });

    enfocarGraficoAnalisis();
  } catch (error) {
    console.error("Error gráfico especialidades por tipo:", error);
  }
}

function formatearFechaDetalle(fecha) {
  if (!fecha) return "";

  const texto = String(fecha);

  if (texto.includes("T")) {
    const soloFecha = texto.split("T")[0];
    const partes = soloFecha.split("-");
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
  }

  if (texto.includes("-")) {
    const partes = texto.split("-");
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
  }

  return texto;
}

function formatearHoraDetalle(hora) {
  if (!hora) return "";

  const texto = String(hora);

  if (texto.includes("T")) {
    return texto.split("T")[1].substring(0, 5);
  }

  if (texto.includes(":")) {
    return texto.substring(0, 5);
  }

  return texto;
}

function actualizarSubtituloDetalleEspecialidad(cantidad) {
  const subtitulo = document.getElementById("subtituloDetalleEspecialidad");

  if (!subtitulo || !detalleMesAnioActual) return;

  const [anio, mes] = detalleMesAnioActual.split("-");
  const nombreMes = obtenerNombreMes(Number(mes));

  const textoFiltro = detalleTipoCirugiaActual
    ? ` - CIRUGÍA ${detalleTipoCirugiaActual}`
    : "";

  subtitulo.textContent = `${detalleTipoOrdenActual}${textoFiltro} - ${nombreMes.toUpperCase()} ${anio} - ${cantidad} registro(s)`;
}

function renderizarTablaDetalleEspecialidad(registros) {
  const tabla = document.getElementById("tablaDetalleEspecialidad");

  if (!tabla) return;

  if (!Array.isArray(registros) || registros.length === 0) {
    tabla.innerHTML = `
      <tr>
        <td colspan="12">No hay registros para este filtro.</td>
      </tr>
    `;

    actualizarSubtituloDetalleEspecialidad(0);
    return;
  }

  tabla.innerHTML = "";

  registros.forEach((item) => {
    const fila = document.createElement("tr");

    fila.innerHTML = `
      <td>${formatearFechaDetalle(item.fecha)}</td>
      <td>${formatearHoraDetalle(item.hora)}</td>
      <td>${item.historia_clinica || ""}</td>
      <td>${item.dni || ""}</td>
      <td>${item.nombres_apellidos || ""}</td>
      <td>${item.edad || ""}</td>
      <td>${item.sexo || ""}</td>
      <td>${item.diagnostico_preoperatorio || ""}</td>
      <td>${item.operacion_realizada || ""}</td>
      <td>${item.cirujano_1 || ""}</td>
      <td>${item.anestesiologo || ""}</td>
      <td>${item.destino || ""}</td>
    `;

    tabla.appendChild(fila);
  });

  actualizarSubtituloDetalleEspecialidad(registros.length);
}

async function cargarDetalleEspecialidad(
  valorMesAnio,
  tipoOrden,
  especialidad,
  tipoCirugia = "",
) {
  if (!valorMesAnio || !tipoOrden || !especialidad) return;
  detalleMesAnioActual = valorMesAnio;
  detalleTipoOrdenActual = tipoOrden;
  detalleEspecialidadActual = especialidad;
  detalleTipoCirugiaActual = tipoCirugia || "";

  const [anio, mes] = valorMesAnio.split("-");
  const mesNumero = Number(mes);
  const anioNumero = Number(anio);

  const panel = document.getElementById("panelDetalleEspecialidad");
  const titulo = document.getElementById("tituloDetalleEspecialidad");
  const subtitulo = document.getElementById("subtituloDetalleEspecialidad");
  const tabla = document.getElementById("tablaDetalleEspecialidad");
  const contenedorGraficoEspecialidades = document.getElementById(
    "contenedorGraficoEspecialidades",
  );

  if (!panel || !tabla) return;

  try {
    panel.classList.add("activo");
    if (contenedorGraficoEspecialidades) {
      contenedorGraficoEspecialidades.classList.add("oculto");
    }

    tabla.innerHTML = `
            <tr>
                <td colspan="12">Cargando datos...</td>
            </tr>
        `;

    const txtPersonal = document.getElementById("txtBuscarPersonalDetalle");
    const buscarPersonal = txtPersonal ? txtPersonal.value.trim() : "";

    const params = new URLSearchParams();

    params.append("mes", mesNumero);
    params.append("anio", anioNumero);
    params.append("tipo_orden", tipoOrden);
    params.append("especialidad", especialidad);

    if (tipoCirugia) {
      params.append("tipo_cirugia", tipoCirugia);
    }

    if (buscarPersonal) {
      params.append("personal", buscarPersonal);
    }

    const url = `/api/analisis/detalle-especialidad?${params.toString()}`;

    const res = await fetch(url, {
      credentials: "include",
    });

    const json = await res.json();

    if (!res.ok || !json.ok) {
      tabla.innerHTML = `
                <tr>
                    <td colspan="12">No se pudo cargar el detalle.</td>
                </tr>
            `;
      return;
    }

    const registros = Array.isArray(json.data) ? json.data : [];

    const nombreMes = obtenerNombreMes(mesNumero);

    if (titulo) {
      titulo.textContent = `Detalle de ${especialidad}`;
    }

    if (subtitulo) {
      const textoTipoCirugia = tipoCirugia ? ` - CIRUGÍA ${tipoCirugia}` : "";

      subtitulo.textContent = `${tipoOrden}${textoTipoCirugia} - ${nombreMes.toUpperCase()} ${anioNumero} - ${registros.length} registro(s)`;
    }

    if (registros.length === 0) {
      renderizarTablaDetalleEspecialidad([]);
      return;
    }

    renderizarTablaDetalleEspecialidad(registros);
    setTimeout(() => {
      panel.scrollIntoView({
        behavior: "smooth",
        block: "center",
      });
    }, 150);
  } catch (error) {
    console.error("Error cargando detalle especialidad:", error);

    tabla.innerHTML = `
            <tr>
                <td colspan="12">Error de conexión.</td>
            </tr>
        `;
  }
}

const btnBuscarPersonalDetalle = document.getElementById(
  "btnBuscarPersonalDetalle",
);
const btnLimpiarPersonalDetalle = document.getElementById(
  "btnLimpiarPersonalDetalle",
);

btnBuscarPersonalDetalle?.addEventListener("click", () => {
  cargarDetalleEspecialidad(
    detalleMesAnioActual,
    detalleTipoOrdenActual,
    detalleEspecialidadActual,
    detalleTipoCirugiaActual,
  );
});

btnLimpiarPersonalDetalle?.addEventListener("click", () => {
  const txtPersonal = document.getElementById("txtBuscarPersonalDetalle");

  if (txtPersonal) txtPersonal.value = "";

  cargarDetalleEspecialidad(
    detalleMesAnioActual,
    detalleTipoOrdenActual,
    detalleEspecialidadActual,
    detalleTipoCirugiaActual,
  );
});
/* ======================================================
DARK MODE
====================================================== */

const btnModoOscuro = document.getElementById("btnModoOscuro");

function actualizarIcono() {
  const modoOscuroActivo = document.body.classList.contains("dark-mode");

  btnModoOscuro.innerHTML = modoOscuroActivo
    ? '<i class="fa-solid fa-sun"></i>'
    : '<i class="fa-solid fa-moon"></i>';
}

/* Cargar tema guardado */
if (localStorage.getItem("theme") === "dark") {
  document.body.classList.add("dark-mode");
}

/* Mostrar icono correcto al iniciar */
actualizarIcono();

/* Cambiar tema */
btnModoOscuro?.addEventListener("click", () => {
  document.body.classList.toggle("dark-mode");

  const modoOscuroActivo = document.body.classList.contains("dark-mode");

  /* Guardar preferencia */
  localStorage.setItem("theme", modoOscuroActivo ? "dark" : "light");

  /* Actualizar icono */
  actualizarIcono();
});

/* =========================================================
   ZOOM SOLO PARA TABLA DE REPORTE MENSUAL
========================================================= */

document.addEventListener("DOMContentLoaded", () => {
  const areaReporte = document.getElementById("areaReporteMensual");

  if (!areaReporte) return;

  if (!document.getElementById("btnZoomReporteTabla")) {
    const btnZoom = document.createElement("button");
    btnZoom.type = "button";
    btnZoom.id = "btnZoomReporteTabla";
    btnZoom.className = "btn-zoom-reporte-tabla";
    btnZoom.title = "Ampliar tabla";
    btnZoom.innerHTML = `<i class="fa-solid fa-up-right-and-down-left-from-center"></i>`;

    areaReporte.appendChild(btnZoom);
  }

  if (!document.getElementById("modalZoomReporte")) {
    const modal = document.createElement("div");
    modal.id = "modalZoomReporte";
    modal.className = "modal-zoom-reporte";

    modal.innerHTML = `
      <div class="zoom-reporte-panel">
        <div class="zoom-reporte-header">
          <h3>Vista ampliada del reporte mensual</h3>

          <div class="zoom-reporte-controles">
            <button type="button" id="btnZoomMenosReporte" title="Alejar">
              <i class="fa-solid fa-minus"></i>
            </button>

            <span id="txtZoomReporte">100%</span>

            <button type="button" id="btnZoomMasReporte" title="Acercar">
              <i class="fa-solid fa-plus"></i>
            </button>

            <button type="button" id="btnZoomAjustarReporte" title="Ajustar a pantalla">
              <i class="fa-solid fa-compress"></i>
            </button>

            <button type="button" id="btnCerrarZoomReporte" class="btn-cerrar-zoom" title="Cerrar">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        </div>

        <div class="tabla-zoom-stage" id="tablaZoomStage">
          <div class="tabla-zoom-scale" id="tablaZoomScale"></div>
        </div>
      </div>
    `;

    document.body.appendChild(modal);
  }

  let zoomReporteActual = 1;

  const btnZoomReporteTabla = document.getElementById("btnZoomReporteTabla");
  const modalZoomReporte = document.getElementById("modalZoomReporte");
  const tablaZoomStage = document.getElementById("tablaZoomStage");
  const tablaZoomScale = document.getElementById("tablaZoomScale");
  const txtZoomReporte = document.getElementById("txtZoomReporte");

  const btnCerrarZoomReporte = document.getElementById("btnCerrarZoomReporte");
  const btnZoomMasReporte = document.getElementById("btnZoomMasReporte");
  const btnZoomMenosReporte = document.getElementById("btnZoomMenosReporte");
  const btnZoomAjustarReporte = document.getElementById(
    "btnZoomAjustarReporte",
  );

  function quitarIdsClon(elemento) {
    if (!elemento) return;

    elemento.removeAttribute("id");

    elemento.querySelectorAll("[id]").forEach((item) => {
      item.removeAttribute("id");
    });
  }

  function aplicarZoomReporte() {
    const contenido = tablaZoomScale.firstElementChild;

    if (!contenido) return;

    const anchoOriginal = Number(
      tablaZoomScale.dataset.anchoOriginal || contenido.scrollWidth || 1,
    );
    const altoOriginal = Number(
      tablaZoomScale.dataset.altoOriginal || contenido.scrollHeight || 1,
    );

    contenido.style.transformOrigin = "top left";
    contenido.style.transform = `scale(${zoomReporteActual})`;

    tablaZoomScale.style.width = `${anchoOriginal * zoomReporteActual}px`;
    tablaZoomScale.style.height = `${altoOriginal * zoomReporteActual}px`;

    if (txtZoomReporte) {
      txtZoomReporte.textContent = `${Math.round(zoomReporteActual * 100)}%`;
    }
  }

  function ajustarZoomReporteAPantalla() {
    const contenido = tablaZoomScale.firstElementChild;

    if (!contenido || !tablaZoomStage) return;

    contenido.style.transform = "scale(1)";
    tablaZoomScale.style.width = "auto";
    tablaZoomScale.style.height = "auto";

    const anchoOriginal = contenido.scrollWidth;
    const altoOriginal = contenido.scrollHeight;

    tablaZoomScale.dataset.anchoOriginal = anchoOriginal;
    tablaZoomScale.dataset.altoOriginal = altoOriginal;

    const anchoDisponible = tablaZoomStage.clientWidth - 36;
    const altoDisponible = tablaZoomStage.clientHeight - 36;

    const zoomPorAncho = anchoDisponible / anchoOriginal;
    const zoomPorAlto = altoDisponible / altoOriginal;

    zoomReporteActual = Math.min(zoomPorAncho, zoomPorAlto, 1);

    if (zoomReporteActual < 0.25) {
      zoomReporteActual = 0.25;
    }

    aplicarZoomReporte();
  }

  function abrirZoomReporte() {
    const areaOriginal = document.getElementById("areaReporteMensual");

    if (!areaOriginal || !modalZoomReporte || !tablaZoomScale) return;

    tablaZoomScale.innerHTML = "";

    const clon = areaOriginal.cloneNode(true);
    quitarIdsClon(clon);

    tablaZoomScale.appendChild(clon);

    modalZoomReporte.classList.add("activo");
    document.body.classList.add("zoom-reporte-abierto");

    requestAnimationFrame(() => {
      ajustarZoomReporteAPantalla();
    });
  }

  function cerrarZoomReporte() {
    if (!modalZoomReporte || !tablaZoomScale) return;

    modalZoomReporte.classList.remove("activo");
    document.body.classList.remove("zoom-reporte-abierto");

    tablaZoomScale.innerHTML = "";
    zoomReporteActual = 1;
  }

  btnZoomReporteTabla?.addEventListener("click", abrirZoomReporte);

  btnCerrarZoomReporte?.addEventListener("click", cerrarZoomReporte);

  btnZoomMasReporte?.addEventListener("click", () => {
    zoomReporteActual = Math.min(zoomReporteActual + 0.1, 1.8);
    aplicarZoomReporte();
  });

  btnZoomMenosReporte?.addEventListener("click", () => {
    zoomReporteActual = Math.max(zoomReporteActual - 0.1, 0.25);
    aplicarZoomReporte();
  });

  btnZoomAjustarReporte?.addEventListener("click", ajustarZoomReporteAPantalla);

  modalZoomReporte?.addEventListener("click", (e) => {
    if (e.target === modalZoomReporte) {
      cerrarZoomReporte();
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modalZoomReporte?.classList.contains("activo")) {
      cerrarZoomReporte();
    }
  });

  window.addEventListener("resize", () => {
    if (modalZoomReporte?.classList.contains("activo")) {
      ajustarZoomReporteAPantalla();
    }
  });
});
// =========================================================================
// CONTROLADOR DE EVENTOS: HISTORIAL DE IMPORTACIONES DE CIRUGÍAS
// =========================================================================

document.addEventListener("DOMContentLoaded", () => {
  // 1. SELECTORES DE ELEMENTOS (Asegúrate de que los IDs coincidan con tu HTML)
  const menuImportaciones = document.getElementById("menuImportaciones");
  const vistaImportaciones = document.getElementById("vistaImportaciones");
  const tablaHistorialImportaciones = document.getElementById(
    "tablaHistorialImportaciones",
  );
  const txtBuscarImportacion = document.getElementById("txtBuscarImportacion");
  const btnLimpiarImportaciones = document.getElementById(
    "btnLimpiarImportaciones",
  );

  // 2. MANEJO DEL EVENTO CLICK EN EL MENÚ LATERAL
  if (menuImportaciones) {
    menuImportaciones.addEventListener("click", (e) => {
      e.preventDefault();

      // Ocultamos todas las secciones de la app (Función estándar de tu estructura)
      ocultarTodasLasVistas();

      // Mostramos la nueva vista del listado
      if (vistaImportaciones) {
        vistaImportaciones.style.display = "block";
      }

      // Marcamos como activo el botón del menú lateral
      activarMenu(menuImportaciones);

      // Cargamos el listado desde la base de datos / servidor
      cargarHistorialImportaciones();
    });
  }

  // 3. EVENTO PARA EL BUSCADOR EN TIEMPO REAL
  if (txtBuscarImportacion) {
    txtBuscarImportacion.addEventListener("input", () => {
      const termino = txtBuscarImportacion.value.trim();
      cargarHistorialImportaciones(termino);
    });
  }

  // 4. EVENTO PARA EL BOTÓN LIMPIAR BUSCADOR
  if (btnLimpiarImportaciones) {
    btnLimpiarImportaciones.addEventListener("click", () => {
      txtBuscarImportacion.value = "";
      cargarHistorialImportaciones();
    });
  }

  // 5. FUNCIÓN ASÍNCRONA PARA TRAER LAS IMPORTACIONES SUBIDAS
  async function cargarHistorialImportaciones(busqueda = "") {
    if (!tablaHistorialImportaciones) return;

    try {
      // Endpoint que procesa o almacena los metadatos de los Excel subidos
      let url = "/api/importaciones";
      if (busqueda) {
        url += `?q=${encodeURIComponent(busqueda)}`;
      }

      // Renderizar estado de carga en la tabla
      tablaHistorialImportaciones.innerHTML = `
        <tr>
          <td colspan="8" style="text-align: center; padding: 20px; color: #64748b;">
            <i class="fa-solid fa-spinner fa-spin"></i> Cargando historial de importaciones...
          </td>
        </tr>
      `;

      const res = await fetch(url, { credentials: "include" });
      const responseData = await res.json();

      // Validamos la estructura del JSON que retorna tu backend
      const importaciones = Array.isArray(responseData)
        ? responseData
        : responseData.data || [];

      // Limpiamos la tabla para renderizar los datos nuevos
      tablaHistorialImportaciones.innerHTML = "";

      if (importaciones.length === 0) {
        tablaHistorialImportaciones.innerHTML = `
          <tr>
            <td colspan="8" style="text-align: center; color: #64748b; padding: 20px;">
              No se encontraron registros de importación en el sistema.
            </td>
          </tr>
        `;
        return;
      }

      // Inyección dinámica de filas en el tbody
      importaciones.forEach((item) => {
        // Formatear la fecha recibida
        const fechaFormateada = item.fecha_carga
          ? new Date(item.fecha_carga).toLocaleString("es-PE")
          : "--";

        const fila = document.createElement("tr");
        fila.innerHTML = `
          <td><strong>#${item.id || item._id || ""}</strong></td>
          <td>${fechaFormateada}</td>
          <td>
            <i class="fa-solid fa-file-excel" style="color: #10b981; margin-right: 6px;"></i> 
            ${item.nombre_archivo || "Documento.xlsx"}
          </td>
          <td>
            <span style="background: #e2e8f0; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 500;">
              ${item.hoja || "Principal"}
            </span>
          </td>
          <td style="font-weight: 600;">${item.total_registros || 0}</td>
          <td style="color: #10b981; font-weight: 600;">${item.registros_validos || 0}</td>
          <td style="color: #f97316; font-weight: 600;">${item.registros_observados || 0}</td>
          <td>
            <button type="button" class="btn-tabla-importacion" data-id="${item.id || item._id}" title="Ver detalles de esta carga" style="background: #3b82f6; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer;">
              <i class="fa-solid fa-eye"></i>
            </button>
          </td>
        `;
        tablaHistorialImportaciones.appendChild(fila);
      });

      // Registrar los escuchadores para los botones "Ver detalle" generados dinámicamente
      document.querySelectorAll(".btn-tabla-importacion").forEach((btn) => {
        btn.addEventListener("click", () => {
          const importacionId = btn.dataset.id;
          console.log(
            "Filtrando cirugías pertenecientes a la importación ID:",
            importacionId,
          );
          // Aquí puedes añadir lógica para abrir un modal o filtrar tu tabla principal con este ID
        });
      });
    } catch (error) {
      console.error("Error al cargar el historial de importaciones:", error);
      tablaHistorialImportaciones.innerHTML = `
        <tr>
          <td colspan="8" style="text-align: center; color: #ef4444; padding: 20px;">
            <i class="fa-solid fa-circle-exclamation"></i> Error al conectar con el servidor de importaciones.
          </td>
        </tr>
      `;
    }
  }

  // 6. REFRESCADO AUTOMÁTICO AL SUBIR UN NUEVO EXCEL
  // Vinculamos esta acción al botón original de procesar cargas para actualizar la tabla al instante
  const btnImportarOriginal = document.getElementById("btnImportar");
  if (btnImportarOriginal) {
    btnImportarOriginal.addEventListener("click", () => {
      // Se da un breve margen de espera para que culmine el guardado en base de datos antes de refrescar
      setTimeout(() => {
        if (
          vistaImportaciones &&
          vistaImportaciones.style.display === "block"
        ) {
          cargarHistorialImportaciones();
        }
      }, 2000);
    });
  }
});
