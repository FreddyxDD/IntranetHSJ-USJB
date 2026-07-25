const API_URL = window.APP_BASE || "";
const AREAS_URL = window.AREAS_URL || `${API_URL}/areas`;
const pacientesCitaCache = new Map();

const totalRegistrosCard = document.getElementById("totalRegistrosCard");
const registrosHoyCard = document.getElementById("registrosHoyCard");
const registradosCard = document.getElementById("registradosCard");
const atendidosCard = document.getElementById("atendidosCard");
const etiquetaTotalResumen = document.getElementById("etiquetaTotalResumen");
const descripcionTotalResumen = document.getElementById(
  "descripcionTotalResumen",
);
const etiquetaHoyResumen = document.getElementById("etiquetaHoyResumen");
const descripcionHoyResumen = document.getElementById("descripcionHoyResumen");
const etiquetaBusquedaFiltro = document.getElementById(
  "etiquetaBusquedaFiltro",
);
const textoBtnActualizar = document.getElementById("textoBtnActualizar");

const txtBusqueda = document.getElementById("txtBusqueda");
const txtBusquedaRapida = document.getElementById("txtBusquedaRapida");
const filtroEstado = document.getElementById("filtroEstado");
const fechaInicio = document.getElementById("fechaInicio");
const fechaFin = document.getElementById("fechaFin");
let filtroTurno = document.getElementById("filtroTurno");

const btnActualizar = document.getElementById("btnActualizar");
const btnLimpiarFiltros = document.getElementById("btnLimpiarFiltros");
const btnExportar = document.getElementById("btnExportar");

const tituloTablaAdmin = document.getElementById("tituloTablaAdmin");
const textoResultado = document.getElementById("textoResultado");
const tablaHeadAdmin = document.getElementById("tablaHeadAdmin");
const tablaRegistrosAdmin = document.getElementById("tablaRegistrosAdmin");
const tablaPrincipalAdmin = tablaHeadAdmin?.closest("table");
const tablaContenedorAdmin = document.querySelector(".tabla-contenedor");

const cantidadRegistros = document.getElementById("cantidadRegistros");
const textoPaginacion = document.getElementById("textoPaginacion");
const btnPaginaAnterior = document.getElementById("btnPaginaAnterior");
const btnPaginaActual = document.getElementById("btnPaginaActual");
const btnPaginaSiguiente = document.getElementById("btnPaginaSiguiente");

const fondoModal = document.getElementById("fondoModal");
const modalDetalle = document.getElementById("modalDetalle");
const btnCerrarModal = document.getElementById("btnCerrarModal");
const contenidoDetalle = document.getElementById("contenidoDetalle");

const btnMenu = document.getElementById("btnMenu");
const adminLayout = document.querySelector(".admin-layout");

const btnCerrarSesionCitas = document.getElementById("btnCerrarSesionCitas");
const fondoLogout = document.getElementById("fondoLogout");
const modalLogout = document.getElementById("modalLogout");
const btnCancelarLogout = document.getElementById("btnCancelarLogout");
const btnConfirmarLogout = document.getElementById("btnConfirmarLogout");

const seccionesGestionCitas = document.querySelectorAll(".vista-gestion-citas");
const vistaReportes = document.getElementById("vistaReportes");
const reporteMes = document.getElementById("reporteMes");
const btnActualizarReportes = document.getElementById("btnActualizarReportes");
const mensajeReportes = document.getElementById("mensajeReportes");
const graficoConsultoriosSolicitadosCanvas = document.getElementById(
  "graficoConsultoriosSolicitados",
);
const detalleEspecialidadCard = document.getElementById(
  "detalleEspecialidadCard",
);
const tituloDetalleEspecialidad = document.getElementById(
  "tituloDetalleEspecialidad",
);
const subtituloDetalleEspecialidad = document.getElementById(
  "subtituloDetalleEspecialidad",
);
const resumenDetalleEspecialidad = document.getElementById(
  "resumenDetalleEspecialidad",
);
const graficoDetalleEspecialidadCanvas = document.getElementById(
  "graficoDetalleEspecialidad",
);
const btnCerrarDetalleEspecialidad = document.getElementById(
  "btnCerrarDetalleEspecialidad",
);
const listaConsultoriosAnillo = document.getElementById(
  "listaConsultoriosAnillo",
);
const tituloIndicadoresConsultorio = document.getElementById(
  "tituloIndicadoresConsultorio",
);
const subtituloIndicadoresConsultorio = document.getElementById(
  "subtituloIndicadoresConsultorio",
);
const mensajeIndicadoresConsultorio = document.getElementById(
  "mensajeIndicadoresConsultorio",
);
const contenidoIndicadoresConsultorio = document.getElementById(
  "contenidoIndicadoresConsultorio",
);
const indicadorConsultorioTotalCitas = document.getElementById(
  "indicadorConsultorioTotalCitas",
);
const graficoEstadosConsultorioCanvas = document.getElementById(
  "graficoEstadosConsultorio",
);
const mensajeEstadosConsultorio = document.getElementById(
  "mensajeEstadosConsultorio",
);
const indicadorConsultorioPersonal = document.getElementById(
  "indicadorConsultorioPersonal",
);
const cuerpoPersonalConsultorio = document.getElementById(
  "cuerpoPersonalConsultorio",
);
const topbarBusqueda = document.querySelector(".topbar__search");
const tituloPaginaAdmin = document.querySelector(".page-title h2");
const subtituloPaginaAdmin = document.querySelector(".page-title p");

let graficoConsultoriosSolicitados = null;
let graficoDetalleEspecialidad = null;
let graficoEstadosConsultorio = null;
let especialidadesReportesCache = [];
let especialidadDetalleActual = null;
let consultorioDetalleActual = null;

let vistaActual = "citas";
let registrosOriginales = [];
let registrosFiltrados = [];
let paginaActual = 1;
let registrosPorPagina = Number(cantidadRegistros?.value) || 10;

document.addEventListener("DOMContentLoaded", async () => {
  configurarBotonVolver();
  crearFiltroTurnoCitasDiarias();

  if (esVistaDiaria()) {
    configurarRangoHoyCitasDiarias();
  } else {
    limpiarRangoFechasReservados();
  }

  configurarMesReportes();
  actualizarVisibilidadFiltroTurno();
  actualizarVistaPrincipal();
  configurarEventos();
  pintarCabeceraTabla();
  await cargarDatos();
});

function configurarBotonVolver() {
  if (btnCerrarSesionCitas) {
    btnCerrarSesionCitas.innerHTML = `<span>←</span> Volver`;
    btnCerrarSesionCitas.title = "Volver a áreas";
    btnCerrarSesionCitas.setAttribute("aria-label", "Volver a áreas");
  }

  if (modalLogout) {
    modalLogout.classList.add("oculto");
  }

  if (fondoLogout) {
    fondoLogout.classList.add("oculto");
  }
}

function configurarEventos() {
  document.querySelectorAll("[data-menu-toggle]").forEach((boton) => {
    boton.addEventListener("click", (e) => {
      e.preventDefault();

      const dropdownActual = boton.closest(".sidebar-dropdown");

      document.querySelectorAll(".sidebar-dropdown").forEach((dropdown) => {
        if (dropdown !== dropdownActual) {
          dropdown.classList.remove("abierto");
        }
      });

      dropdownActual?.classList.toggle("abierto");
    });
  });

  document.querySelectorAll("[data-vista]").forEach((link) => {
    link.addEventListener("click", async (e) => {
      e.preventDefault();

      const vista = link.dataset.vista || "citas";

      document
        .querySelectorAll(".sidebar-link, .sidebar-sublink")
        .forEach((item) => {
          item.classList.remove("activo");
        });

      link.classList.add("activo");

      const botonCitas = document.querySelector(
        '[data-menu-toggle="citas-admin"]',
      );
      const menuCitas = botonCitas?.closest(".sidebar-dropdown");
      const perteneceACitas = vista === "citas" || vista === "citas-diarias";

      if (perteneceACitas) {
        botonCitas?.classList.add("activo");
        menuCitas?.classList.add("abierto");
      } else {
        botonCitas?.classList.remove("activo");
        menuCitas?.classList.remove("abierto");
      }

      vistaActual = vista;
      actualizarVistaPrincipal();

      if (esVistaReportes()) {
        await cargarReportes();
        return;
      }

      limpiarFiltros(false);
      actualizarVisibilidadFiltroTurno();
      pintarCabeceraTabla();
      await cargarDatos();
    });
  });

  btnActualizar?.addEventListener("click", async () => {
    await cargarDatos();
  });

  btnLimpiarFiltros?.addEventListener("click", async () => {
    if (esVistaDiaria()) {
      limpiarFiltros(false);
      await cargarDatos();
      return;
    }

    limpiarFiltros(true);
  });

  btnExportar?.addEventListener("click", () => {
    exportarCSV();
  });

  btnActualizarReportes?.addEventListener("click", async () => {
    await cargarReportes();
  });

  reporteMes?.addEventListener("change", validarMesReporte);
  btnCerrarDetalleEspecialidad?.addEventListener(
    "click",
    ocultarDetalleEspecialidad,
  );

  txtBusqueda?.addEventListener("input", aplicarFiltros);
  filtroEstado?.addEventListener("change", aplicarFiltros);
  filtroTurno?.addEventListener("change", aplicarFiltros);

  fechaInicio?.addEventListener("change", async () => {
    if (esVistaDiaria()) {
      configurarRangoCitasDiariasSinForzar("inicio");
      await cargarDatos();
      return;
    }

    aplicarFiltros();
  });

  fechaFin?.addEventListener("change", async () => {
    if (esVistaDiaria()) {
      configurarRangoCitasDiariasSinForzar("fin");
      await cargarDatos();
      return;
    }

    aplicarFiltros();
  });

  txtBusquedaRapida?.addEventListener("input", () => {
    if (txtBusqueda) {
      txtBusqueda.value = txtBusquedaRapida.value;
    }

    aplicarFiltros();
  });

  cantidadRegistros?.addEventListener("change", () => {
    registrosPorPagina = Number(cantidadRegistros.value) || 10;
    paginaActual = 1;
    renderizarVistaPaginada();
  });

  btnPaginaAnterior?.addEventListener("click", () => {
    if (paginaActual <= 1) return;

    paginaActual--;
    renderizarVistaPaginada();
  });

  btnPaginaSiguiente?.addEventListener("click", () => {
    const totalPaginas = Math.ceil(
      registrosFiltrados.length / registrosPorPagina,
    );

    if (paginaActual >= totalPaginas) return;

    paginaActual++;
    renderizarVistaPaginada();
  });

  btnCerrarModal?.addEventListener("click", cerrarModal);
  fondoModal?.addEventListener("click", cerrarModal);

  if (btnMenu && adminLayout) {
    btnMenu.addEventListener("click", () => {
      const menuCerrado = adminLayout.classList.toggle("menu-cerrado");

      btnMenu.setAttribute("aria-expanded", String(!menuCerrado));
      btnMenu.setAttribute(
        "aria-label",
        menuCerrado ? "Abrir menú" : "Cerrar menú",
      );
    });
  }

  btnCerrarSesionCitas?.addEventListener("click", volverAreas);
  btnCancelarLogout?.addEventListener("click", cerrarModalLogout);
  fondoLogout?.addEventListener("click", cerrarModalLogout);
  btnConfirmarLogout?.addEventListener("click", volverAreas);
}

function volverAreas() {
  window.location.href = AREAS_URL;
}

function esVistaDiaria() {
  return vistaActual === "citas-diarias";
}

function esVistaReservas() {
  return vistaActual === "citas";
}

function esVistaReportes() {
  return vistaActual === "reportes";
}

function actualizarVistaPrincipal() {
  const mostrarReportes = esVistaReportes();
  const mostrarCitasDiarias = esVistaDiaria();

  document.body.classList.toggle("vista-citas-diarias", mostrarCitasDiarias);
  adminLayout?.classList.toggle("vista-citas-diarias", mostrarCitasDiarias);

  seccionesGestionCitas.forEach((seccion) => {
    seccion.classList.toggle("oculto", mostrarReportes);
  });

  vistaReportes?.classList.toggle("oculto", !mostrarReportes);
  topbarBusqueda?.classList.toggle(
    "oculto",
    mostrarReportes || mostrarCitasDiarias,
  );

  if (tituloPaginaAdmin) {
    tituloPaginaAdmin.textContent = mostrarReportes
      ? "Reportes"
      : "Citas Admin";
  }

  if (subtituloPaginaAdmin) {
    subtituloPaginaAdmin.textContent = mostrarReportes
      ? "Indicadores gráficos de citas diarias"
      : mostrarCitasDiarias
        ? "Administración de citas hospitalarias"
        : "Panel administrativo de citas";
  }

  actualizarTextosSegunVista();
}

function actualizarTextosSegunVista() {
  const diaria = esVistaDiaria();

  if (etiquetaTotalResumen) {
    etiquetaTotalResumen.textContent = diaria
      ? "Total de citas"
      : "Total registros";
  }

  if (descripcionTotalResumen) {
    descripcionTotalResumen.textContent = diaria
      ? "Programaciones del día seleccionado"
      : "Todos los registros recibidos";
  }

  if (etiquetaHoyResumen) {
    etiquetaHoyResumen.textContent = diaria
      ? "Citas de hoy"
      : "Registros de hoy";
  }

  if (descripcionHoyResumen) {
    descripcionHoyResumen.textContent = diaria
      ? "Programaciones correspondientes a hoy"
      : "Registros del día actual";
  }

  if (etiquetaBusquedaFiltro) {
    etiquetaBusquedaFiltro.textContent = diaria
      ? "Buscar especialidad, consultorio o médico"
      : "Buscar";
  }

  if (txtBusqueda) {
    txtBusqueda.placeholder = diaria
      ? "Buscar especialidad, consultorio, servicio o médico..."
      : "Ejemplo: DNI, historia clínica, paciente, ticket...";
  }

  if (textoBtnActualizar) {
    textoBtnActualizar.textContent = diaria ? "Buscar" : "Actualizar";
  }
}

function limpiarRangoFechasReservados() {
  if (fechaInicio) {
    fechaInicio.value = "";
    fechaInicio.removeAttribute("min");
    fechaInicio.removeAttribute("max");
  }

  if (fechaFin) {
    fechaFin.value = "";
    fechaFin.removeAttribute("min");
    fechaFin.removeAttribute("max");
  }
}

function limpiarFiltros(renderizar = true) {
  if (txtBusqueda) txtBusqueda.value = "";
  if (txtBusquedaRapida) txtBusquedaRapida.value = "";
  if (filtroEstado) filtroEstado.value = "";
  if (filtroTurno) filtroTurno.value = "todos";

  if (esVistaDiaria()) {
    configurarRangoHoyCitasDiarias();
  } else {
    limpiarRangoFechasReservados();
  }

  if (renderizar) {
    aplicarFiltros();
  }
}

async function cargarDatos() {
  if (esVistaReportes()) {
    await cargarReportes();
    return;
  }

  try {
    pacientesCitaCache.clear();
    mostrarCargando();

    const endpoint = esVistaDiaria()
      ? construirEndpointCitasDiarias()
      : `${API_URL}/api/citas-admin/registros`;

    const response = await fetch(endpoint, {
      credentials: "include",
    });

    const result = await response.json();

    if (!result.ok && !result.success) {
      const mensajeError =
        result.error ||
        result.message ||
        "El servicio de citas no está disponible temporalmente.";
      const referencia = result.reference
        ? `<div class="codigo-soporte">Código de soporte: ${escapeHtml(result.reference)}</div>`
        : "";
      console.error(mensajeError);

      registrosOriginales = [];
      registrosFiltrados = [];

      actualizarResumen();

      if (tablaRegistrosAdmin) {
        tablaRegistrosAdmin.innerHTML = `
          <tr>
            <td colspan="${obtenerColspan()}" class="sin-datos">
              ${escapeHtml(mensajeError)}${referencia}
            </td>
          </tr>
        `;
      }

      if (textoResultado) {
        textoResultado.textContent = "0 registros encontrados";
      }

      return;
    }

    registrosOriginales = result.data || result.registros || [];
    registrosFiltrados = [...registrosOriginales];
    paginaActual = 1;

    aplicarFiltros();
  } catch (error) {
    console.error("Error conectando con la API:", error);

    registrosOriginales = [];
    registrosFiltrados = [];

    actualizarResumen();

    if (tablaRegistrosAdmin) {
      tablaRegistrosAdmin.innerHTML = `
        <tr>
          <td colspan="${obtenerColspan()}" class="sin-datos">
            El servicio de citas no está disponible temporalmente. Intenta nuevamente en unos minutos.
          </td>
        </tr>
      `;
    }

    if (textoResultado) {
      textoResultado.textContent = "0 registros encontrados";
    }
  }
}

function construirEndpointCitasDiarias() {
  configurarRangoCitasDiariasSinForzar("inicio");

  const params = new URLSearchParams();

  if (fechaInicio?.value) {
    params.append("fechaInicio", fechaInicio.value);
    params.append("fechaFin", fechaInicio.value);
  } else if (fechaFin?.value) {
    params.append("fechaInicio", fechaFin.value);
    params.append("fechaFin", fechaFin.value);
  }

  const query = params.toString();

  return `${API_URL}/api/citas-admin/citas-diarias${query ? `?${query}` : ""}`;
}

function mesISOLocal(fecha = new Date()) {
  const anio = fecha.getFullYear();
  const mes = String(fecha.getMonth() + 1).padStart(2, "0");
  return `${anio}-${mes}`;
}

function configurarMesReportes() {
  if (reporteMes && !reporteMes.value) {
    reporteMes.value = mesISOLocal();
  }
}

function validarMesReporte() {
  if (!reporteMes) return mesISOLocal();

  const valor = String(reporteMes.value || "").trim();

  if (!/^\d{4}-(0[1-9]|1[0-2])$/.test(valor)) {
    reporteMes.value = mesISOLocal();
  }

  return reporteMes.value;
}

async function cargarReportes() {
  const mesSeleccionado = validarMesReporte();
  mostrarMensajeReportes("Cargando reporte mensual...", "cargando");

  if (btnActualizarReportes) {
    btnActualizarReportes.disabled = true;
    btnActualizarReportes.classList.add("cargando");
  }

  try {
    const params = new URLSearchParams({ mes: mesSeleccionado });

    const response = await fetch(
      `${API_URL}/api/citas-admin/reportes?${params.toString()}`,
      { credentials: "include" },
    );

    /*
       Primero se lee como texto. Si PHP devuelve un warning en HTML,
       evitamos el genérico "Unexpected token <" y mostramos el error real.
    */
    const textoRespuesta = await response.text();
    let result;

    try {
      result = JSON.parse(textoRespuesta);
    } catch (errorJson) {
      console.error(
        "La API de reportes no devolvió JSON válido:",
        textoRespuesta,
      );

      const detalleServidor = textoRespuesta
        .replace(/<br\s*\/?>/gi, " ")
        .replace(/<[^>]*>/g, " ")
        .replace(/&nbsp;/gi, " ")
        .replace(/\s+/g, " ")
        .trim()
        .slice(0, 350);

      throw new Error(
        detalleServidor ||
          `La API devolvió una respuesta inválida (HTTP ${response.status}).`,
      );
    }

    if (!response.ok || (!result.ok && !result.success)) {
      throw new Error(
        result.error ||
          result.message ||
          "No se pudo generar el reporte mensual.",
      );
    }

    if (typeof Chart === "undefined") {
      throw new Error("No se pudo cargar la librería de gráficos Chart.js.");
    }

    const especialidades = Array.isArray(result.especialidadesRanking)
      ? result.especialidadesRanking
      : [];
    especialidadesReportesCache = Array.isArray(result.detalleEspecialidades)
      ? result.detalleEspecialidades
      : especialidades;

    destruirGraficosReportes();
    ocultarDetalleEspecialidad(false);
    pintarGraficoConsultoriosSolicitados(especialidades);

    if (especialidades.length === 0) {
      mostrarMensajeReportes(
        `No existen citas registradas en ${formatearMesReporte(mesSeleccionado)}.`,
        "vacio",
      );
    } else {
      ocultarMensajeReportes();
    }
  } catch (error) {
    console.error("Error cargando reportes:", error);
    especialidadesReportesCache = [];
    destruirGraficosReportes();
    ocultarDetalleEspecialidad(false);
    mostrarMensajeReportes(
      error.message || "Error cargando reportes.",
      "error",
    );
  } finally {
    if (btnActualizarReportes) {
      btnActualizarReportes.disabled = false;
      btnActualizarReportes.classList.remove("cargando");
    }
  }
}

let tooltipReportesElemento = null;

function obtenerTooltipReportesElemento() {
  if (tooltipReportesElemento?.isConnected) {
    return tooltipReportesElemento;
  }

  tooltipReportesElemento = document.createElement("div");
  tooltipReportesElemento.className = "reporte-tooltip-chart";
  tooltipReportesElemento.setAttribute("role", "status");
  tooltipReportesElemento.setAttribute("aria-live", "polite");
  document.body.appendChild(tooltipReportesElemento);

  return tooltipReportesElemento;
}

function colorTooltipReporte(valor) {
  return typeof valor === "string" && valor.trim() ? valor : "#2563eb";
}

function tooltipExternoReportes(context) {
  const { chart, tooltip } = context;
  const elemento = obtenerTooltipReportesElemento();

  if (!tooltip || tooltip.opacity === 0) {
    elemento.classList.remove("reporte-tooltip-chart--visible");
    return;
  }

  const titulo = Array.isArray(tooltip.title) ? tooltip.title.join(" · ") : "";
  const lineasNormales = [];
  const lineasAyuda = [];

  (tooltip.body || []).forEach((bloque, indice) => {
    const color = colorTooltipReporte(
      tooltip.labelColors?.[indice]?.backgroundColor,
    );
    const lineasBloque = [
      ...(bloque.before || []),
      ...(bloque.lines || []),
      ...(bloque.after || []),
    ];

    lineasBloque.forEach((linea) => {
      const texto = String(linea || "").trim();
      if (!texto) return;

      if (
        texto.toLocaleLowerCase("es").startsWith("haz clic") ||
        texto.toLocaleLowerCase("es").startsWith("solo cuenta")
      ) {
        lineasAyuda.push(texto);
        return;
      }

      lineasNormales.push({ texto, color });
    });
  });

  const footer = Array.isArray(tooltip.footer)
    ? tooltip.footer.filter(Boolean)
    : [];

  elemento.innerHTML = `
    <div class="reporte-tooltip-chart__cabecera">
      <span class="reporte-tooltip-chart__etiqueta">Reporte mensual</span>
      <strong>${escapeHtml(titulo || "Detalle")}</strong>
    </div>
    <div class="reporte-tooltip-chart__contenido">
      ${lineasNormales
        .map(
          (item) => `
            <div class="reporte-tooltip-chart__fila">
              <span class="reporte-tooltip-chart__punto" style="background:${escapeHtml(item.color)}"></span>
              <span>${escapeHtml(item.texto)}</span>
            </div>
          `,
        )
        .join("")}
    </div>
    ${footer
      .map(
        (texto) =>
          `<div class="reporte-tooltip-chart__total">${escapeHtml(texto)}</div>`,
      )
      .join("")}
    ${lineasAyuda
      .map(
        (texto) =>
          `<div class="reporte-tooltip-chart__ayuda">☝ ${escapeHtml(texto)}</div>`,
      )
      .join("")}
  `;

  elemento.classList.add("reporte-tooltip-chart--visible");

  const rectCanvas = chart.canvas.getBoundingClientRect();
  const margen = 16;
  const separacion = 22;
  const ancho = elemento.offsetWidth;
  const alto = elemento.offsetHeight;

  let izquierda = rectCanvas.left + tooltip.caretX + separacion;
  let arriba = rectCanvas.top + tooltip.caretY - alto / 2;

  if (izquierda + ancho > window.innerWidth - margen) {
    izquierda = rectCanvas.left + tooltip.caretX - ancho - separacion;
  }

  izquierda = Math.max(
    margen,
    Math.min(izquierda, window.innerWidth - ancho - margen),
  );
  arriba = Math.max(
    margen,
    Math.min(arriba, window.innerHeight - alto - margen),
  );

  elemento.style.left = `${izquierda}px`;
  elemento.style.top = `${arriba}px`;
}

const etiquetasTotalesBarrasPlugin = {
  id: "etiquetasTotalesBarras",

  afterDatasetsDraw(chart, _args, opcionesPlugin) {
    if (!opcionesPlugin || opcionesPlugin.mostrar === false) return;

    const { ctx, data, chartArea } = chart;
    const totalApilado = Boolean(opcionesPlugin.totalApilado);
    const desplazamiento = Number(opcionesPlugin.desplazamiento ?? 10);
    const formateador =
      typeof opcionesPlugin.formatear === "function"
        ? opcionesPlugin.formatear
        : (valor) => String(valor);

    ctx.save();
    ctx.font =
      "700 12px Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
    ctx.textBaseline = "middle";

    const pintarEtiqueta = (x, y, valor) => {
      const texto = formateador(Number(valor || 0));
      const anchoTexto = ctx.measureText(texto).width;
      const paddingX = 9;
      const alto = 24;
      const ancho = anchoTexto + paddingX * 2;
      const limiteDerecho = chart.width - 6;
      const izquierda = Math.min(x + desplazamiento, limiteDerecho - ancho);
      const arriba = y - alto / 2;

      ctx.fillStyle = "rgba(255, 255, 255, 0.96)";
      ctx.strokeStyle = "rgba(148, 163, 184, 0.55)";
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.roundRect(izquierda, arriba, ancho, alto, 7);
      ctx.fill();
      ctx.stroke();

      ctx.fillStyle = "#0f172a";
      ctx.fillText(texto, izquierda + paddingX, y);
    };

    if (totalApilado) {
      const cantidadFilas = Array.isArray(data.labels) ? data.labels.length : 0;

      for (let indice = 0; indice < cantidadFilas; indice += 1) {
        let total = 0;
        let extremoX = chartArea.left;
        let centroY = null;
        let existeDato = false;

        data.datasets.forEach((_dataset, indiceDataset) => {
          const meta = chart.getDatasetMeta(indiceDataset);
          if (meta.hidden) return;

          const valorCrudo = data.datasets[indiceDataset]?.data?.[indice];
          if (valorCrudo === null || valorCrudo === undefined) return;

          const valor = Number(valorCrudo || 0);
          const barra = meta.data?.[indice];
          if (!barra) return;

          existeDato = true;
          total += valor;
          extremoX = Math.max(extremoX, Number(barra.x || chartArea.left));
          centroY = Number(barra.y || centroY || 0);
        });

        if (existeDato && centroY !== null) {
          pintarEtiqueta(extremoX, centroY, total);
        }
      }
    } else {
      data.datasets.forEach((_dataset, indiceDataset) => {
        const meta = chart.getDatasetMeta(indiceDataset);
        if (meta.hidden) return;

        meta.data.forEach((barra, indice) => {
          const valorCrudo = data.datasets[indiceDataset]?.data?.[indice];
          if (valorCrudo === null || valorCrudo === undefined) return;

          pintarEtiqueta(
            Number(barra.x || chartArea.left),
            Number(barra.y || 0),
            Number(valorCrudo || 0),
          );
        });
      });
    }

    ctx.restore();
  },
};

function formatearCantidadEtiqueta(valor, singular, plural = `${singular}s`) {
  const cantidad = Number(valor || 0);
  return `${cantidad.toLocaleString("es-PE")} ${cantidad === 1 ? singular : plural}`;
}

function formatearPorcentajeEtiqueta(valor) {
  const numero = Number(valor || 0);
  const decimales = Number.isInteger(numero) ? 0 : 1;
  return `${numero.toFixed(decimales)}%`;
}

function opcionesGraficoHorizontal(etiquetaEje, porcentaje = false) {
  return {
    indexAxis: "y",
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      mode: "nearest",
      axis: "y",
      intersect: false,
    },
    animation: {
      duration: 650,
      easing: "easeOutQuart",
    },
    layout: {
      padding: {
        right: 112,
      },
    },
    plugins: {
      etiquetasTotalesBarras: {
        mostrar: true,
        totalApilado: false,
        formatear: porcentaje
          ? formatearPorcentajeEtiqueta
          : (valor) => formatearCantidadEtiqueta(valor, "cita"),
      },
      legend: {
        display: false,
      },
      tooltip: {
        enabled: false,
        external: tooltipExternoReportes,
        callbacks: {
          label(context) {
            const valor = Number(context.raw || 0);
            return porcentaje
              ? `${context.dataset.label}: ${valor.toFixed(2)}%`
              : `${context.dataset.label}: ${valor}`;
          },
        },
      },
    },
    scales: {
      x: {
        beginAtZero: true,
        max: porcentaje ? 100 : undefined,
        title: {
          display: true,
          text: etiquetaEje,
          color: "#475569",
          font: {
            size: 12,
            weight: "700",
          },
        },
        ticks: {
          precision: porcentaje ? undefined : 0,
          color: "#64748b",
          callback(value) {
            return porcentaje ? `${value}%` : value;
          },
        },
        grid: {
          color: "rgba(148, 163, 184, 0.20)",
        },
      },
      y: {
        ticks: {
          autoSkip: false,
          color: "#334155",
          font: {
            size: 11,
            weight: "650",
          },
        },
        grid: {
          display: false,
        },
      },
    },
  };
}

function ajustarAlturaGrafico(canvas, cantidad, minimo = 520, porFila = 44) {
  const contenedor = canvas?.parentElement;

  if (!contenedor) {
    return;
  }

  const totalFilas = Math.max(1, Number(cantidad) || 1);
  const altura = Math.max(minimo, totalFilas * porFila + 120);

  contenedor.style.height = `${altura}px`;
}

function pintarGraficoConsultoriosSolicitados(datos) {
  if (!graficoConsultoriosSolicitadosCanvas) return;

  const filas = datos.length
    ? datos
    : [{ especialidad: "Sin citas registradas", totalCitas: 0 }];

  ajustarAlturaGrafico(graficoConsultoriosSolicitadosCanvas, filas.length);

  const opciones = opcionesGraficoHorizontal("Cantidad de citas");

  opciones.onClick = (_evento, elementos) => {
    if (!elementos.length || !datos.length) return;

    const seleccion = filas[elementos[0].index];
    const detalle = buscarDetalleEspecialidad(seleccion);

    if (detalle) {
      mostrarDetalleEspecialidad(detalle);
    }
  };

  opciones.onHover = (evento, elementos) => {
    const objetivo = evento?.native?.target;
    if (objetivo) {
      objetivo.style.cursor =
        elementos.length && datos.length ? "pointer" : "default";
    }
  };

  opciones.plugins.tooltip.callbacks.label = (context) =>
    `Citas totales del mes: ${Number(context.raw || 0)}`;
  opciones.plugins.tooltip.callbacks.afterLabel = () =>
    datos.length
      ? "Haz clic para ver sus consultorios, estados y personal asignado"
      : "";
  opciones.plugins.etiquetasTotalesBarras.formatear = (valor) =>
    formatearCantidadEtiqueta(valor, "cita");

  graficoConsultoriosSolicitados = new Chart(
    graficoConsultoriosSolicitadosCanvas,
    {
      type: "bar",
      data: {
        labels: filas.map((item) => item.especialidad || "Especialidad"),
        datasets: [
          {
            label: "Citas totales",
            data: filas.map((item) => Number(item.totalCitas || 0)),
            backgroundColor: "rgba(37, 99, 235, 0.78)",
            borderColor: "rgba(37, 99, 235, 1)",
            borderWidth: 1,
            borderRadius: 8,
            borderSkipped: false,
          },
        ],
      },
      options: opciones,
      plugins: [etiquetasTotalesBarrasPlugin],
    },
  );
}

function buscarDetalleEspecialidad(seleccion) {
  const idSeleccionado = Number(seleccion?.idEspecialidad || 0);
  const nombreSeleccionado = normalizarTextoGrafico(seleccion?.especialidad);

  return especialidadesReportesCache.find((item) => {
    const idItem = Number(item?.idEspecialidad || 0);

    if (idSeleccionado > 0 && idItem > 0) {
      return idSeleccionado === idItem;
    }

    return normalizarTextoGrafico(item?.especialidad) === nombreSeleccionado;
  });
}

function normalizarTextoGrafico(valor) {
  return String(valor || "")
    .trim()
    .toLocaleLowerCase("es");
}

function clavePersonalReporte(persona) {
  const id = Number(persona?.idMedico || 0);
  return id > 0
    ? `id:${id}`
    : `nombre:${normalizarTextoGrafico(persona?.medico)}`;
}

function obtenerAnulados(item) {
  return Math.max(0, Number(item?.anulados || 0));
}

function obtenerRegistrados(item) {
  return Math.max(0, Number(item?.registrados || 0));
}

function obtenerCerrados(item) {
  return Math.max(0, Number(item?.cerrados || 0));
}

function obtenerEstadosVisibles(item) {
  return [
    {
      clave: "anulados",
      nombre: "Anulado",
      cantidad: obtenerAnulados(item),
      fondo: "rgba(239, 68, 68, 0.78)",
      borde: "rgba(220, 38, 38, 1)",
    },
    {
      clave: "registrados",
      nombre: "Registrado",
      cantidad: obtenerRegistrados(item),
      fondo: "rgba(59, 130, 246, 0.78)",
      borde: "rgba(37, 99, 235, 1)",
    },
    {
      clave: "cerrados",
      nombre: "Cerrado",
      cantidad: obtenerCerrados(item),
      fondo: "rgba(16, 185, 129, 0.78)",
      borde: "rgba(5, 150, 105, 1)",
    },
  ].filter((estado) => estado.cantidad > 0);
}

const PALETA_CONSULTORIOS = [
  "#2563eb",
  "#7c3aed",
  "#0891b2",
  "#059669",
  "#d97706",
  "#dc2626",
  "#db2777",
  "#4f46e5",
  "#0f766e",
  "#65a30d",
  "#c2410c",
  "#9333ea",
];

function obtenerPersonalConsultorio(consultorio) {
  return Array.isArray(consultorio?.personal)
    ? consultorio.personal.filter((persona) =>
        String(persona?.medico || "").trim(),
      )
    : [];
}

function nombresPersonalConsultorio(consultorio, limite = 3) {
  const nombres = obtenerPersonalConsultorio(consultorio).map(
    (persona) => persona.medico || "Personal no identificado",
  );

  if (!nombres.length) {
    return "Personal no identificado";
  }

  const visibles = nombres.slice(0, limite);
  const restantes = nombres.length - visibles.length;

  return restantes > 0
    ? `${visibles.join(" · ")} · +${restantes} más`
    : visibles.join(" · ");
}

function totalPersonalEspecialidad(consultorios) {
  const unicos = new Set();

  consultorios.forEach((consultorio) => {
    obtenerPersonalConsultorio(consultorio).forEach((persona) => {
      unicos.add(clavePersonalReporte(persona));
    });
  });

  return unicos.size;
}

const textoCentroAnilloPlugin = {
  id: "textoCentroAnillo",

  afterDraw(chart) {
    if (chart.config.type !== "doughnut") return;

    const { ctx, chartArea } = chart;
    if (!chartArea) return;

    const total = Number(chart.$totalCentro || 0);
    const etiqueta = String(chart.$textoCentroSuperior || "Total de citas");
    const centroX = (chartArea.left + chartArea.right) / 2;
    const centroY = (chartArea.top + chartArea.bottom) / 2;

    ctx.save();
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";

    ctx.fillStyle = "#64748b";
    ctx.font =
      "700 12px Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
    ctx.fillText(etiqueta, centroX, centroY - 14);

    ctx.fillStyle = "#0f172a";
    ctx.font =
      "800 28px Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
    ctx.fillText(total.toLocaleString("es-PE"), centroX, centroY + 15);

    ctx.restore();
  },
};

function mostrarDetalleEspecialidad(especialidad) {
  if (!detalleEspecialidadCard || !graficoDetalleEspecialidadCanvas) return;

  especialidadDetalleActual = especialidad;
  consultorioDetalleActual = null;

  const consultorios = Array.isArray(especialidad?.consultorios)
    ? especialidad.consultorios
        .filter((consultorio) => Number(consultorio?.totalCitas || 0) > 0)
        .sort(
          (a, b) =>
            Number(b?.totalCitas || 0) - Number(a?.totalCitas || 0) ||
            String(a?.servicio || "").localeCompare(
              String(b?.servicio || ""),
              "es",
            ),
        )
    : [];

  const totalCitas = Math.max(0, Number(especialidad?.totalCitas || 0));
  const colores = consultorios.map(
    (_consultorio, indice) =>
      PALETA_CONSULTORIOS[indice % PALETA_CONSULTORIOS.length],
  );

  graficoDetalleEspecialidad?.destroy();
  graficoDetalleEspecialidad = null;

  detalleEspecialidadCard.classList.remove("oculto");

  if (tituloDetalleEspecialidad) {
    tituloDetalleEspecialidad.textContent =
      especialidad?.especialidad || "Especialidad";
  }

  if (subtituloDetalleEspecialidad) {
    subtituloDetalleEspecialidad.textContent =
      `Consultorios de ${formatearMesReporte(validarMesReporte())}. ` +
      "Cada segmento representa el total de citas de un consultorio; haz clic para ver sus estados reales y personal.";
  }

  pintarResumenDetalleEspecialidad(especialidad, consultorios);
  pintarListaConsultoriosAnillo(consultorios, colores);
  ocultarIndicadoresConsultorio();

  const hayConsultorios = consultorios.length > 0;
  const filasGrafico = hayConsultorios
    ? consultorios
    : [{ servicio: "Sin consultorios con citas", totalCitas: 1, personal: [] }];
  const coloresGrafico = hayConsultorios ? colores : ["#cbd5e1"];

  graficoDetalleEspecialidad = new Chart(graficoDetalleEspecialidadCanvas, {
    type: "doughnut",
    data: {
      labels: filasGrafico.map(
        (consultorio) => consultorio?.servicio || "Consultorio",
      ),
      datasets: [
        {
          label: "Citas totales",
          data: filasGrafico.map((consultorio) =>
            Number(consultorio?.totalCitas || 0),
          ),
          backgroundColor: coloresGrafico,
          borderColor: "#ffffff",
          borderWidth: 3,
          hoverOffset: 12,
          spacing: 3,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: "65%",
      interaction: {
        mode: "nearest",
        intersect: true,
      },
      onClick(_evento, elementos) {
        if (!hayConsultorios || !elementos.length) return;

        const indice = elementos[0].index;
        mostrarIndicadoresConsultorio(
          consultorios[indice],
          indice,
          colores[indice],
        );
      },
      onHover(evento, elementos) {
        const objetivo = evento?.native?.target;
        if (objetivo) {
          objetivo.style.cursor =
            hayConsultorios && elementos.length ? "pointer" : "default";
        }
      },
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          callbacks: {
            title(contextos) {
              return contextos?.[0]?.label || "Consultorio";
            },
            label(context) {
              const consultorio = filasGrafico[context.dataIndex] || {};
              const total = Number(consultorio?.totalCitas || 0);
              const porcentaje =
                totalCitas > 0 ? (total / totalCitas) * 100 : 0;

              return `Citas totales: ${total.toLocaleString("es-PE")} (${porcentaje.toFixed(1)}%)`;
            },
            afterLabel(context) {
              if (!hayConsultorios) return "";

              const consultorio = filasGrafico[context.dataIndex] || {};
              return [
                `Personal: ${nombresPersonalConsultorio(consultorio, 2)}`,
                "Haz clic para ver los indicadores",
              ];
            },
          },
        },
      },
    },
    plugins: [textoCentroAnilloPlugin],
  });

  graficoDetalleEspecialidad.$textoCentroSuperior = "Total de citas";
  graficoDetalleEspecialidad.$totalCentro = totalCitas;
  graficoDetalleEspecialidad.draw();

  requestAnimationFrame(() => {
    detalleEspecialidadCard.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
  });
}

function pintarListaConsultoriosAnillo(consultorios, colores) {
  if (!listaConsultoriosAnillo) return;

  if (!consultorios.length) {
    listaConsultoriosAnillo.innerHTML = `
      <div class="reporte-consultorios-vacio">
        No existen consultorios con citas en esta especialidad.
      </div>
    `;
    return;
  }

  listaConsultoriosAnillo.innerHTML = consultorios
    .map((consultorio, indice) => {
      const total = Math.max(0, Number(consultorio?.totalCitas || 0));
      const personal = nombresPersonalConsultorio(consultorio, 2);

      return `
        <button
          type="button"
          class="reporte-consultorio-leyenda"
          data-consultorio-indice="${indice}"
          aria-label="Ver indicadores de ${escapeHtml(consultorio?.servicio || "Consultorio")}" 
        >
          <span
            class="reporte-consultorio-leyenda__color"
            style="background:${escapeHtml(colores[indice])}"
          ></span>
          <span class="reporte-consultorio-leyenda__contenido">
            <strong>${escapeHtml(consultorio?.servicio || "Consultorio sin nombre")}</strong>
            <small>${escapeHtml(personal)}</small>
          </span>
          <span class="reporte-consultorio-leyenda__total">
            ${total.toLocaleString("es-PE")} citas
          </span>
        </button>
      `;
    })
    .join("");

  listaConsultoriosAnillo
    .querySelectorAll("[data-consultorio-indice]")
    .forEach((boton) => {
      boton.addEventListener("click", () => {
        const indice = Number(boton.dataset.consultorioIndice);
        const consultorio = consultorios[indice];

        if (!consultorio) return;

        mostrarIndicadoresConsultorio(consultorio, indice, colores[indice]);
      });
    });
}

function ocultarIndicadoresConsultorio() {
  consultorioDetalleActual = null;

  graficoEstadosConsultorio?.destroy();
  graficoEstadosConsultorio = null;

  listaConsultoriosAnillo
    ?.querySelectorAll(".reporte-consultorio-leyenda")
    .forEach((boton) => boton.classList.remove("activo"));

  if (tituloIndicadoresConsultorio) {
    tituloIndicadoresConsultorio.textContent = "Estados del consultorio";
  }

  if (subtituloIndicadoresConsultorio) {
    subtituloIndicadoresConsultorio.textContent =
      "Selecciona un consultorio para ver los estados reales registrados en SIGH.";
  }

  if (mensajeIndicadoresConsultorio) {
    mensajeIndicadoresConsultorio.textContent =
      "Haz clic en un segmento del anillo o en un consultorio de la lista.";
    mensajeIndicadoresConsultorio.classList.remove("oculto");
  }

  contenidoIndicadoresConsultorio?.classList.add("oculto");

  if (mensajeEstadosConsultorio) {
    mensajeEstadosConsultorio.textContent = "";
    mensajeEstadosConsultorio.classList.add("oculto");
  }

  if (cuerpoPersonalConsultorio) {
    cuerpoPersonalConsultorio.innerHTML = "";
  }

  if (graficoDetalleEspecialidad && especialidadDetalleActual) {
    graficoDetalleEspecialidad.setActiveElements([]);
    graficoDetalleEspecialidad.$textoCentroSuperior = "Total de citas";
    graficoDetalleEspecialidad.$totalCentro = Math.max(
      0,
      Number(especialidadDetalleActual?.totalCitas || 0),
    );
    graficoDetalleEspecialidad.update("none");
  }
}

function mostrarIndicadoresConsultorio(consultorio, indice, color) {
  if (!consultorio) return;

  consultorioDetalleActual = consultorio;

  const totalCitas = Math.max(0, Number(consultorio?.totalCitas || 0));
  const personal = obtenerPersonalConsultorio(consultorio);
  const estados = obtenerEstadosVisibles(consultorio);

  listaConsultoriosAnillo
    ?.querySelectorAll(".reporte-consultorio-leyenda")
    .forEach((boton) => {
      boton.classList.toggle(
        "activo",
        Number(boton.dataset.consultorioIndice) === indice,
      );
    });

  if (graficoDetalleEspecialidad) {
    graficoDetalleEspecialidad.setActiveElements([
      { datasetIndex: 0, index: indice },
    ]);
    graficoDetalleEspecialidad.$textoCentroSuperior = "Citas consultorio";
    graficoDetalleEspecialidad.$totalCentro = totalCitas;
    graficoDetalleEspecialidad.update("none");
  }

  if (tituloIndicadoresConsultorio) {
    tituloIndicadoresConsultorio.textContent =
      consultorio?.servicio || "Consultorio";
  }

  if (subtituloIndicadoresConsultorio) {
    subtituloIndicadoresConsultorio.textContent =
      `${especialidadDetalleActual?.especialidad || "Especialidad"} · ` +
      `${personal.length.toLocaleString("es-PE")} personal asignado`;
  }

  mensajeIndicadoresConsultorio?.classList.add("oculto");
  contenidoIndicadoresConsultorio?.classList.remove("oculto");

  if (indicadorConsultorioTotalCitas) {
    indicadorConsultorioTotalCitas.textContent =
      totalCitas.toLocaleString("es-PE");
  }

  if (indicadorConsultorioPersonal) {
    indicadorConsultorioPersonal.textContent =
      personal.length.toLocaleString("es-PE");
  }

  graficoEstadosConsultorio?.destroy();
  graficoEstadosConsultorio = null;

  if (estados.length === 0) {
    if (mensajeEstadosConsultorio) {
      mensajeEstadosConsultorio.textContent =
        "Este consultorio no tiene estados 0, 1 o 2 registrados en el mes seleccionado.";
      mensajeEstadosConsultorio.classList.remove("oculto");
    }

    if (graficoEstadosConsultorioCanvas) {
      graficoEstadosConsultorioCanvas.classList.add("oculto");
    }
  } else {
    mensajeEstadosConsultorio?.classList.add("oculto");
    graficoEstadosConsultorioCanvas?.classList.remove("oculto");

    if (graficoEstadosConsultorioCanvas) {
      const opcionesEstados = opcionesGraficoHorizontal("Cantidad de citas");
      opcionesEstados.layout.padding.right = 80;
      opcionesEstados.plugins.tooltip.callbacks.label = (context) =>
        `${context.label}: ${Number(context.raw || 0).toLocaleString("es-PE")}`;
      opcionesEstados.plugins.etiquetasTotalesBarras.formatear = (valor) =>
        Number(valor || 0).toLocaleString("es-PE");

      graficoEstadosConsultorio = new Chart(graficoEstadosConsultorioCanvas, {
        type: "bar",
        data: {
          labels: estados.map((estado) => estado.nombre),
          datasets: [
            {
              label: "Citas por estado",
              data: estados.map((estado) => estado.cantidad),
              backgroundColor: estados.map((estado) => estado.fondo),
              borderColor: estados.map((estado) => estado.borde),
              borderWidth: 1,
              borderRadius: 8,
              borderSkipped: false,
            },
          ],
        },
        options: opcionesEstados,
        plugins: [etiquetasTotalesBarrasPlugin],
      });
    }
  }

  if (cuerpoPersonalConsultorio) {
    if (!personal.length) {
      cuerpoPersonalConsultorio.innerHTML = `
        <tr>
          <td colspan="6" class="reporte-personal-vacio">
            No se encontró personal asignado a este consultorio.
          </td>
        </tr>
      `;
      return;
    }

    cuerpoPersonalConsultorio.innerHTML = personal
      .map((persona, posicion) => {
        const totalPersona = Math.max(0, Number(persona?.totalCitas || 0));
        const anuladosPersona = obtenerAnulados(persona);
        const registradosPersona = obtenerRegistrados(persona);
        const cerradosPersona = obtenerCerrados(persona);

        return `
          <tr>
            <td>${posicion + 1}</td>
            <td>
              <span class="reporte-personal-nombre">
                <span
                  class="reporte-personal-nombre__punto"
                  style="background:${escapeHtml(color || "#2563eb")}"
                ></span>
                <strong>${escapeHtml(persona?.medico || "Personal no identificado")}</strong>
              </span>
            </td>
            <td>${totalPersona.toLocaleString("es-PE")}</td>
            <td>${anuladosPersona.toLocaleString("es-PE")}</td>
            <td>${registradosPersona.toLocaleString("es-PE")}</td>
            <td>${cerradosPersona.toLocaleString("es-PE")}</td>
          </tr>
        `;
      })
      .join("");
  }
}

function pintarResumenDetalleEspecialidad(especialidad, consultorios) {
  if (!resumenDetalleEspecialidad) return;

  const totalCitas = Math.max(0, Number(especialidad?.totalCitas || 0));
  const totalPersonal =
    Number(especialidad?.totalPersonal) > 0
      ? Number(especialidad.totalPersonal)
      : totalPersonalEspecialidad(consultorios);

  resumenDetalleEspecialidad.innerHTML = `
    <div class="reporte-detalle-metrica">
      <span>Citas totales</span>
      <strong>${totalCitas.toLocaleString("es-PE")}</strong>
    </div>
    <div class="reporte-detalle-metrica">
      <span>Consultorios</span>
      <strong>${consultorios.length.toLocaleString("es-PE")}</strong>
    </div>
    <div class="reporte-detalle-metrica">
      <span>Personal asignado</span>
      <strong>${totalPersonal.toLocaleString("es-PE")}</strong>
    </div>
    <div class="reporte-detalle-metrica">
      <span>Mes del reporte</span>
      <strong class="reporte-detalle-metrica__texto">${escapeHtml(formatearMesReporte(validarMesReporte()))}</strong>
    </div>
  `;
}

function formatearMesReporte(valor) {
  if (!/^\d{4}-(0[1-9]|1[0-2])$/.test(String(valor || ""))) {
    return "el mes seleccionado";
  }

  const [anio, mes] = valor.split("-").map(Number);
  const fecha = new Date(anio, mes - 1, 1);
  const texto = new Intl.DateTimeFormat("es-PE", {
    month: "long",
    year: "numeric",
  }).format(fecha);

  return texto.charAt(0).toUpperCase() + texto.slice(1);
}

function ocultarDetalleEspecialidad(conDesplazamiento = false) {
  graficoDetalleEspecialidad?.destroy();
  graficoDetalleEspecialidad = null;
  especialidadDetalleActual = null;
  consultorioDetalleActual = null;

  detalleEspecialidadCard?.classList.add("oculto");

  if (resumenDetalleEspecialidad) {
    resumenDetalleEspecialidad.innerHTML = "";
  }

  if (listaConsultoriosAnillo) {
    listaConsultoriosAnillo.innerHTML = "";
  }

  ocultarIndicadoresConsultorio();

  if (conDesplazamiento && graficoConsultoriosSolicitadosCanvas) {
    graficoConsultoriosSolicitadosCanvas.scrollIntoView({
      behavior: "smooth",
      block: "center",
    });
  }
}

function destruirGraficosReportes() {
  tooltipReportesElemento?.classList.remove("reporte-tooltip-chart--visible");

  graficoConsultoriosSolicitados?.destroy();
  graficoDetalleEspecialidad?.destroy();
  graficoEstadosConsultorio?.destroy();

  graficoConsultoriosSolicitados = null;
  graficoDetalleEspecialidad = null;
  graficoEstadosConsultorio = null;
}

function mostrarMensajeReportes(texto, tipo = "cargando") {
  if (!mensajeReportes) return;

  mensajeReportes.textContent = texto;
  mensajeReportes.className = `mensaje-reportes mensaje-reportes--${tipo}`;
}

function ocultarMensajeReportes() {
  if (!mensajeReportes) return;

  mensajeReportes.textContent = "";
  mensajeReportes.className = "mensaje-reportes oculto";
}

function fechaISOLocal(fecha) {
  return [
    fecha.getFullYear(),
    String(fecha.getMonth() + 1).padStart(2, "0"),
    String(fecha.getDate()).padStart(2, "0"),
  ].join("-");
}

function crearFiltroTurnoCitasDiarias() {
  if (filtroTurno) return;

  const grupoEstado =
    filtroEstado?.closest(".form-group") ||
    filtroEstado?.closest(".filter-group") ||
    filtroEstado?.closest(".campo-filtro") ||
    filtroEstado?.parentElement;

  const grupoFechaInicio =
    fechaInicio?.closest(".form-group") ||
    fechaInicio?.closest(".filter-group") ||
    fechaInicio?.closest(".campo-filtro") ||
    fechaInicio?.parentElement;

  const referencia = grupoEstado || grupoFechaInicio;
  const contenedor = referencia?.parentElement;

  if (!referencia || !contenedor) return;

  const grupoTurno = document.createElement("div");
  grupoTurno.className = referencia.className || "form-group";
  grupoTurno.id = "grupoFiltroTurno";
  grupoTurno.innerHTML = `
    <label for="filtroTurno">Turno</label>
    <select id="filtroTurno">
      <option value="todos">Todos</option>
      <option value="manana">Mañana</option>
      <option value="tarde">Tarde</option>
    </select>
  `;

  referencia.insertAdjacentElement(
    grupoEstado ? "afterend" : "beforebegin",
    grupoTurno,
  );
  filtroTurno = document.getElementById("filtroTurno");
  filtroTurno?.addEventListener("change", aplicarFiltros);
  actualizarVisibilidadFiltroTurno();
}

function actualizarVisibilidadFiltroTurno() {
  const grupoTurno =
    document.getElementById("grupoFiltroTurno") || filtroTurno?.parentElement;
  if (!grupoTurno) return;

  grupoTurno.style.display = esVistaDiaria() ? "" : "none";
}

function configurarRangoHoyCitasDiarias() {
  /*
    Citas diarias debe consultar SOLO el día seleccionado.
    No se limita a hoy: se permiten fechas pasadas y futuras.
    Si no hay fecha, se coloca la fecha local actual como valor inicial.
  */
  const hoyTexto = fechaISOLocal(new Date());

  if (fechaInicio) {
    fechaInicio.removeAttribute("min");
    fechaInicio.removeAttribute("max");

    if (!fechaInicio.value) {
      fechaInicio.value = hoyTexto;
    }
  }

  if (fechaFin) {
    fechaFin.removeAttribute("min");
    fechaFin.removeAttribute("max");
    fechaFin.value = fechaInicio?.value || hoyTexto;
  }
}

function configurarRangoCitasDiariasSinForzar(origen = "inicio") {
  /*
    Sin restricciones de calendario:
    - Se puede elegir cualquier fecha pasada o futura.
    - La tabla de citas diarias solo consulta un día.
    - Fecha fin siempre queda igual a la fecha inicio.
  */
  fechaInicio?.removeAttribute("min");
  fechaInicio?.removeAttribute("max");
  fechaFin?.removeAttribute("min");
  fechaFin?.removeAttribute("max");

  const hoyTexto = fechaISOLocal(new Date());

  if (!fechaInicio?.value && !fechaFin?.value) {
    if (fechaInicio) fechaInicio.value = hoyTexto;
    if (fechaFin) fechaFin.value = hoyTexto;
    return;
  }

  if (origen === "fin" && fechaFin?.value) {
    if (fechaInicio) fechaInicio.value = fechaFin.value;
    return;
  }

  if (fechaInicio?.value) {
    if (fechaFin) fechaFin.value = fechaInicio.value;
    return;
  }

  if (fechaFin?.value) {
    if (fechaInicio) fechaInicio.value = fechaFin.value;
  }
}

function mostrarCargando() {
  const colspan = obtenerColspan();

  if (tablaRegistrosAdmin) {
    tablaRegistrosAdmin.innerHTML = `
      <tr>
        <td colspan="${colspan}" class="sin-datos">Cargando datos...</td>
      </tr>
    `;
  }
}

function aplicarFiltros() {
  const texto = normalizar(txtBusqueda?.value || "");
  const estado = normalizar(filtroEstado?.value || "");
  const turnoFiltro = normalizar(filtroTurno?.value || "todos");

  const inicio = fechaInicio?.value
    ? parseFechaLocal(fechaInicio.value, false)
    : null;

  const fin = fechaFin?.value ? parseFechaLocal(fechaFin.value, true) : null;

  registrosFiltrados = registrosOriginales.filter((item) => {
    const contenido = normalizar(JSON.stringify(item));

    const cumpleTexto = texto === "" || contenido.includes(texto);

    const estadoItem = normalizar(item.estado || "");

    const cumpleEstado =
      estado === "" || estado === "todos" || estadoItem === estado;

    const cumpleTurno =
      !esVistaDiaria() ||
      turnoFiltro === "" ||
      turnoFiltro === "todos" ||
      coincideTurnoCitaDiaria(item, turnoFiltro);

    const fechaItemTexto = esVistaDiaria() ? item.fecha : item.fechaRegistro;

    let cumpleFecha = true;

    if (fechaItemTexto && (inicio || fin)) {
      const fechaItem = parseFechaLocal(fechaItemTexto, false);

      if (fechaItem) {
        if (inicio && fechaItem < inicio) {
          cumpleFecha = false;
        }

        if (fin && fechaItem > fin) {
          cumpleFecha = false;
        }
      }
    }

    return cumpleTexto && cumpleEstado && cumpleTurno && cumpleFecha;
  });

  paginaActual = 1;
  actualizarResumen();
  renderizarVistaPaginada();
}

function parseFechaLocal(valor, finDelDia = false) {
  if (!valor) return null;

  const texto = String(valor).trim().slice(0, 10);

  let anio;
  let mes;
  let dia;

  if (/^\d{4}-\d{2}-\d{2}$/.test(texto)) {
    const partes = texto.split("-");
    anio = Number(partes[0]);
    mes = Number(partes[1]);
    dia = Number(partes[2]);
  } else if (/^\d{2}\/\d{2}\/\d{4}$/.test(texto)) {
    const partes = texto.split("/");
    dia = Number(partes[0]);
    mes = Number(partes[1]);
    anio = Number(partes[2]);
  } else {
    const fecha = new Date(valor);
    return Number.isNaN(fecha.getTime()) ? null : fecha;
  }

  if (!anio || !mes || !dia) return null;

  return new Date(
    anio,
    mes - 1,
    dia,
    finDelDia ? 23 : 0,
    finDelDia ? 59 : 0,
    finDelDia ? 59 : 0,
    finDelDia ? 999 : 0,
  );
}

function coincideTurnoCitaDiaria(item, turnoFiltro) {
  const turno = normalizar(item.turno || "");
  const horaInicio = String(item.horaInicio || "").slice(0, 5);
  const hora = Number(horaInicio.slice(0, 2));

  if (turnoFiltro === "manana") {
    return turno.includes("manana") || (!Number.isNaN(hora) && hora < 12);
  }

  if (turnoFiltro === "tarde") {
    return turno.includes("tarde") || (!Number.isNaN(hora) && hora >= 12);
  }

  return true;
}

function pintarCabeceraTabla() {
  if (!tablaHeadAdmin) return;

  const esDiaria = vistaActual === "citas-diarias";
  tablaPrincipalAdmin?.classList.toggle(
    "tabla-citas-diarias-compacta",
    esDiaria,
  );
  tablaContenedorAdmin?.classList.toggle(
    "tabla-contenedor--compacto",
    esDiaria,
  );

  if (vistaActual === "citas") {
    tituloTablaAdmin.textContent = "Reservados enviados";

    tablaHeadAdmin.innerHTML = `
      <tr>
        <th>Ticket</th>
        <th>Historia clínica</th>
        <th>Documento</th>
        <th>Paciente</th>
        <th>Sexo</th>
        <th>Teléfono</th>
        <th>Especialidad</th>
        <th>Servicio</th>
        <th>Médico</th>
        <th>Fecha registro</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    `;
    return;
  }

  if (vistaActual === "citas-diarias") {
    tituloTablaAdmin.textContent = "Citas diarias por especialidad";

    tablaHeadAdmin.innerHTML = `
      <tr>
        <th>Fecha / consultorio</th>
        <th>Médico(a)</th>
        <th>Horario</th>
        <th>Cupos y citas</th>
        <th>Estado / acciones</th>
      </tr>
    `;
  }
}

function renderizarVistaPaginada() {
  const total = registrosFiltrados.length;
  const totalPaginas = Math.max(1, Math.ceil(total / registrosPorPagina));
  const inicio = (paginaActual - 1) * registrosPorPagina;
  const fin = inicio + registrosPorPagina;
  const pagina = registrosFiltrados.slice(inicio, fin);

  pintarTabla(pagina);

  if (textoResultado) {
    textoResultado.textContent = `${total} registros encontrados`;
  }

  if (textoPaginacion) {
    if (total === 0) {
      textoPaginacion.textContent = "Mostrando 0 registros";
    } else {
      textoPaginacion.textContent = `Mostrando ${inicio + 1} a ${Math.min(fin, total)} de ${total} registros`;
    }
  }

  if (btnPaginaActual) {
    btnPaginaActual.textContent = String(paginaActual);
  }

  if (btnPaginaAnterior) {
    btnPaginaAnterior.disabled = paginaActual <= 1;
  }

  if (btnPaginaSiguiente) {
    btnPaginaSiguiente.disabled = paginaActual >= totalPaginas;
  }
}

function pintarTabla(registros) {
  if (!tablaRegistrosAdmin) return;

  if (!registros.length) {
    tablaRegistrosAdmin.innerHTML = `
      <tr>
        <td colspan="${obtenerColspan()}" class="sin-datos">
          No se encontraron registros para mostrar.
        </td>
      </tr>
    `;
    return;
  }

  if (vistaActual === "citas") {
    tablaRegistrosAdmin.innerHTML = registros.map(renderFilaReservado).join("");
    return;
  }

  if (vistaActual === "citas-diarias") {
    tablaRegistrosAdmin.innerHTML = registros
      .map(renderFilaCitaDiaria)
      .join("");
  }
}

function renderFilaReservado(item) {
  const paciente = `${item.nombre || ""} ${item.apellido || ""}`.trim();

  return `
    <tr>
      <td>${escapeHtml(item.ticket || "")}</td>
      <td>${escapeHtml(item.historiaClinica || "")}</td>
      <td>${escapeHtml(item.docIden || "")}</td>
      <td>${escapeHtml(paciente || "Sin nombre")}</td>
      <td>${escapeHtml(item.sexo || "")}</td>
      <td>${escapeHtml(item.telefono || "")}</td>
      <td>${escapeHtml(item.especialidad || "")}</td>
      <td>${escapeHtml(item.servicio || "")}</td>
      <td>${escapeHtml(item.medico || "")}</td>
      <td>${formatearFechaHora(item.fechaRegistro)}</td>
      <td>${badgeEstado(item.estado || "REGISTRADO")}</td>
      <td>${accionesReserva(item)}</td>
    </tr>
  `;
}

function renderFilaPacienteReservado(item) {
  const paciente = `${item.nombre || ""} ${item.apellido || ""}`.trim();

  return `
    <tr>
      <td>${escapeHtml(paciente || "Sin nombre")}</td>
      <td>${escapeHtml(item.docIden || "")}</td>
      <td>${escapeHtml(item.historiaClinica || "")}</td>
      <td>${escapeHtml(item.sexo || "")}</td>
      <td>${escapeHtml(item.telefono || "")}</td>
      <td>${escapeHtml(item.especialidad || "")}</td>
      <td>${escapeHtml(item.servicio || "")}</td>
      <td>${escapeHtml(item.medico || "")}</td>
      <td>${formatearFechaHora(item.fechaRegistro)}</td>
      <td>${badgeEstado(item.estado || "REGISTRADO")}</td>
      <td>${accionesReserva(item)}</td>
    </tr>
  `;
}

function renderFilaCitaDiaria(item) {
  const idProgramacion = Number(item.idProgramacion || 0);
  const filaId = `pacientes-cita-${idProgramacion}`;
  const itemJson = JSON.stringify(item).replace(/'/g, "&apos;");

  const horaInicio = item.horaInicio || "";
  const horaFin = item.horaFin || "";
  const horario = `${horaInicio} - ${horaFin}`.trim();
  const turnoTexto = item.turno || obtenerTurnoPorHora(horaInicio);

  const cuposProgramados = Number(item.cuposProgramados ?? 0);
  const citasOtorgadas = Number(item.citasOtorgadas ?? 0);
  const citasAtendidas = Number(item.citasAtendidas ?? item.atendidos ?? 0);
  const citasAdicionales = Number(item.citasAdicionales ?? 0);
  const cuposDisponibles = Number(item.cuposDisponibles ?? 0);

  const area = obtenerAreaProgramacion(item);
  const consultorio = obtenerConsultorioProgramacion(item);

  return `
    <tr class="fila-cita-diaria fila-cita-click fila-cita-diaria-final"
        onclick='togglePacientesCitaDiaria(${itemJson})'>
      <td class="celda-consultorio celda-consultorio-final">
        <div class="consultorio-fecha-linea">
          <span class="icono-mini">▣</span>
          <strong>${formatearFecha(item.fecha)}</strong>
        </div>
        <span class="area-chip">${escapeHtml(area)}</span>
        <strong class="consultorio-titulo">${escapeHtml(item.servicio || item.especialidad || "")}</strong>
        <span class="consultorio-ubicacion">⌖ ${escapeHtml(consultorio)}</span>
      </td>

      <td class="celda-medico celda-medico-final">
        <div class="medico-linea">
          <span class="icono-mini icono-mini--doctor">♟</span>
          <strong>${escapeHtml(item.medico || "")}</strong>
        </div>
        <span>${escapeHtml(item.departamento || "Estrategias y Programas")}</span>
        <small>· ${escapeHtml(area)}</small>
      </td>

      <td class="celda-horario celda-horario-final">
        <span class="horario-chip">◷ ${escapeHtml(horario)}</span>
        <span class="turno-chip">${escapeHtml(turnoTexto)}</span>
      </td>

      <td class="celda-cupos celda-cupos-final">
        <div class="metricas-cupos-final">
          <div class="metrica-cupo">
            <strong>${escapeHtml(cuposProgramados)}</strong>
            <span>Cupos<br>programados</span>
          </div>
          <div class="metrica-cupo metrica-cupo--verde">
            <strong>${escapeHtml(citasOtorgadas)}</strong>
            <span>Citas<br>otorgadas</span>
          </div>
          <div class="metrica-cupo metrica-cupo--morado">
            <strong>${escapeHtml(citasAtendidas)}</strong>
            <span>Atendido</span>
          </div>
          <div class="metrica-cupo metrica-cupo--naranja">
            <strong>${escapeHtml(citasAdicionales)}</strong>
            <span>Citas<br>adicionales</span>
          </div>
          <div class="metrica-cupo metrica-cupo--gris">
            <strong>${escapeHtml(cuposDisponibles)}</strong>
            <span>Disponibles</span>
          </div>
        </div>
      </td>

      <td class="celda-acciones celda-acciones-final" onclick="event.stopPropagation()">
        <div class="acciones-cita-final">
          ${badgeEstado(item.estado || "PROGRAMADO")}
          <button type="button" class="btn-expandir-final" aria-label="Mostrar detalle"
            onclick='togglePacientesCitaDiaria(${itemJson})'>⌄</button>
        </div>
      </td>
    </tr>

    <tr id="${filaId}" class="fila-pacientes-desplegable oculto">
      <td colspan="5">
        <div class="agenda-pacientes-contenedor">
          Cargando pacientes...
        </div>
      </td>
    </tr>
  `;
}

function obtenerAreaProgramacion(item) {
  const especialidad = String(item.especialidad || "").trim();
  const servicio = String(item.servicio || "").trim();
  const texto = `${especialidad} ${servicio}`.toUpperCase();

  if (texto.includes("CRED")) return "CRED";
  if (texto.includes("INMUN")) return "CRED";
  if (especialidad)
    return especialidad.split(/[\/\-]/)[0].trim() || especialidad;
  return "Servicio";
}

function obtenerConsultorioProgramacion(item) {
  const servicio = String(item.servicio || "").trim();
  const match = servicio.match(/\b([IVX]+|\d+)\b$/i);

  if (item.consultorio) return item.consultorio;
  if (match) return `Consultorio ${match[1]}`;
  if (item.idServicio) return `Consultorio ${item.idServicio}`;
  return "Consultorio";
}

function obtenerTurnoPorHora(horaInicio) {
  const hora = Number(String(horaInicio || "").slice(0, 2));

  if (!Number.isNaN(hora) && hora >= 12) return "Tarde";
  return "Mañana";
}

function renderFilaPacienteDiario(item) {
  return renderFilaCitaDiaria(item);
}

function accionesReserva(item) {
  const estado = String(item.estado || "REGISTRADO").toUpperCase();

  return `
    <div class="acciones-registro">
      <select class="select-estado-registro" onchange="cambiarEstadoReserva(${Number(item.idRegistro || 0)}, this.value)">
        <option value="REGISTRADO" ${estado === "REGISTRADO" ? "selected" : ""}>Registrado</option>
        <option value="ATENDIDO" ${estado === "ATENDIDO" ? "selected" : ""}>Atendido</option>
        <option value="ANULADO" ${estado === "ANULADO" ? "selected" : ""}>Anulado</option>
      </select>

      <button type="button" class="btn-detalle" onclick='abrirDetalle(${JSON.stringify(item).replace(/'/g, "&apos;")})'>
        Ver
      </button>
    </div>
  `;
}

function accionesCitaDiaria(item) {
  const estado = String(item.estado || "PROGRAMADO").toUpperCase();

  return `
    <div class="acciones-registro">
      <select class="select-estado-registro" onchange="cambiarEstadoCitaDiaria(${Number(item.idProgramacion || 0)}, this.value, '${escapeHtml(item.fecha || "")}')">
        <option value="PROGRAMADO" ${estado === "PROGRAMADO" ? "selected" : ""}>Programado</option>
        <option value="ATENDIDO" ${estado === "ATENDIDO" ? "selected" : ""}>Atendido</option>
        <option value="ANULADO" ${estado === "ANULADO" ? "selected" : ""}>Anulado</option>
        <option value="CITA_ADICIONAL" ${estado === "CITA_ADICIONAL" ? "selected" : ""}>Cita adicional</option>
      </select>
    </div>
  `;
}

window.cambiarEstadoReserva = async function cambiarEstadoReserva(id, estado) {
  if (!id) return;

  try {
    const response = await fetch(
      `${API_URL}/api/citas-admin/registros/${id}/estado`,
      {
        method: "PUT",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ estado }),
      },
    );

    const result = await response.json();

    if (!result.ok && !result.success) {
      alert(result.error || "No se pudo actualizar el estado.");
      return;
    }

    await cargarDatos();
  } catch (error) {
    console.error(error);
    alert("Error actualizando estado.");
  }
};

window.cambiarEstadoCitaDiaria = async function cambiarEstadoCitaDiaria(
  idProgramacion,
  estado,
  fecha,
) {
  if (!idProgramacion) return;

  try {
    const response = await fetch(
      `${API_URL}/api/citas-admin/citas-diarias/${idProgramacion}/estado`,
      {
        method: "PUT",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          estado,
          fecha,
        }),
      },
    );

    const result = await response.json();

    if (!result.ok && !result.success) {
      alert(
        result.error || "No se pudo actualizar el estado de la cita diaria.",
      );
      return;
    }

    await cargarDatos();
  } catch (error) {
    console.error(error);
    alert("Error actualizando estado de cita diaria.");
  }
};

window.abrirDetalle = function abrirDetalle(item) {
  if (!contenidoDetalle || !modalDetalle || !fondoModal) return;

  contenidoDetalle.innerHTML = Object.entries(item)
    .map(
      ([key, value]) => `
      <div class="detalle-item">
        <strong>${escapeHtml(key)}</strong>
        <span>${escapeHtml(value ?? "")}</span>
      </div>
    `,
    )
    .join("");

  fondoModal.classList.remove("oculto");
  modalDetalle.classList.remove("oculto");
};

function cerrarModal() {
  fondoModal?.classList.add("oculto");
  modalDetalle?.classList.add("oculto");
}

function actualizarResumen() {
  const total = registrosFiltrados.length;
  const hoy = new Date().toISOString().slice(0, 10);

  const registrosHoy = registrosFiltrados.filter((item) => {
    const fecha = esVistaDiaria() ? item.fecha : item.fechaRegistro;
    return String(fecha || "").slice(0, 10) === hoy;
  }).length;

  const registrados = registrosFiltrados.filter((item) => {
    const estado = String(item.estado || "").toUpperCase();
    return (
      estado === "REGISTRADO" ||
      estado === "PROGRAMADO" ||
      estado === "CITA_ADICIONAL"
    );
  }).length;

  const atendidos = registrosFiltrados.filter((item) => {
    return String(item.estado || "").toUpperCase() === "ATENDIDO";
  }).length;

  if (totalRegistrosCard) totalRegistrosCard.textContent = total;
  if (registrosHoyCard) registrosHoyCard.textContent = registrosHoy;
  if (registradosCard) registradosCard.textContent = registrados;
  if (atendidosCard) atendidosCard.textContent = atendidos;
}

function exportarCSV() {
  if (!registrosFiltrados.length) {
    alert("No hay datos para exportar.");
    return;
  }

  const columnas = Object.keys(registrosFiltrados[0]);

  const filas = registrosFiltrados.map((row) => {
    return columnas
      .map((col) => {
        const valor = row[col] ?? "";
        return `"${String(valor).replace(/"/g, '""')}"`;
      })
      .join(",");
  });

  const csv = [columnas.join(","), ...filas].join("\n");

  const blob = new Blob([csv], {
    type: "text/csv;charset=utf-8;",
  });

  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");

  link.href = url;
  link.download = `${vistaActual}_${new Date().toISOString().slice(0, 10)}.csv`;
  link.click();

  URL.revokeObjectURL(url);
}

function abrirModalLogout() {
  volverAreas();
}

function cerrarModalLogout() {
  fondoLogout?.classList.add("oculto");
  modalLogout?.classList.add("oculto");
}

async function cerrarSesionCitasAdmin() {
  volverAreas();
}

function obtenerColspan() {
  return esVistaDiaria() ? 5 : 12;
}

function badgeEstado(estado) {
  const estadoTexto = String(estado || "").toUpperCase();
  const clase = estadoTexto.toLowerCase().replace(/\s+/g, "_");

  return `<span class="badge ${clase}">${escapeHtml(estadoTexto.replace("_", " "))}</span>`;
}

function formatearFecha(fecha) {
  if (!fecha) return "";

  const partes = String(fecha).slice(0, 10).split("-");

  if (partes.length !== 3) {
    return escapeHtml(fecha);
  }

  return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function formatearFechaHora(valor) {
  if (!valor) return "";

  const texto = String(valor);
  const fecha = formatearFecha(texto.slice(0, 10));
  const hora = texto.match(/(\d{2}:\d{2})/);

  return hora ? `${fecha} ${hora[1]}` : fecha;
}

function esFechaHoy(fechaValor) {
  if (!fechaValor) return false;

  const fechaTexto = String(fechaValor).slice(0, 10);

  const ahora = new Date();
  const anio = ahora.getFullYear();
  const mes = String(ahora.getMonth() + 1).padStart(2, "0");
  const dia = String(ahora.getDate()).padStart(2, "0");

  const hoy = `${anio}-${mes}-${dia}`;

  return fechaTexto === hoy;
}

function normalizar(texto) {
  return String(texto || "")
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "");
}

function escapeHtml(valor) {
  return String(valor ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

window.togglePacientesCitaDiaria = async function togglePacientesCitaDiaria(
  item,
) {
  const idProgramacion = Number(item.idProgramacion || 0);

  if (!idProgramacion) {
    alert("No se encontró la programación médica.");
    return;
  }

  const fila = document.getElementById(`pacientes-cita-${idProgramacion}`);

  if (!fila) return;

  const contenedor = fila.querySelector(".agenda-pacientes-contenedor");

  if (!fila.classList.contains("oculto")) {
    fila.classList.add("oculto");
    return;
  }

  document
    .querySelectorAll(".fila-pacientes-desplegable")
    .forEach((otraFila) => {
      if (otraFila !== fila) {
        otraFila.classList.add("oculto");
      }
    });

  fila.classList.remove("oculto");

  if (pacientesCitaCache.has(idProgramacion)) {
    contenedor.innerHTML = renderAgendaPacientes(
      pacientesCitaCache.get(idProgramacion),
      item,
    );
    return;
  }

  contenedor.innerHTML = `<p class="sin-datos">Cargando pacientes...</p>`;

  try {
    const response = await fetch(
      `${API_URL}/api/citas-admin/citas-diarias/${idProgramacion}/pacientes`,
      {
        method: "GET",
        credentials: "include",
      },
    );

    const result = await response.json();

    if (!result.ok && !result.success) {
      contenedor.innerHTML = `
        <p class="sin-datos">
          ${escapeHtml(result.error || "No se pudieron cargar los pacientes.")}
        </p>
      `;
      return;
    }

    const pacientes = result.data || [];

    pacientesCitaCache.set(idProgramacion, pacientes);
    contenedor.innerHTML = renderAgendaPacientes(pacientes, item);
  } catch (error) {
    console.error(error);

    contenedor.innerHTML = `
      <p class="sin-datos">Error conectando con la API de pacientes.</p>
    `;
  }
};

function renderAgendaPacientes(pacientes, programacion) {
  const cuposProgramados = Math.max(
    Number(programacion.cuposProgramados || 0),
    pacientes.length,
  );

  const totalSlots = cuposProgramados > 0 ? cuposProgramados : pacientes.length;

  if (!totalSlots) {
    return `
      <div class="agenda-resumen">
        <strong>${escapeHtml(programacion.especialidad || "")}</strong>
        <span>No hay cupos ni pacientes para esta programación.</span>
      </div>
    `;
  }

  const bloques = [];

  for (let i = 0; i < totalSlots; i++) {
    const paciente = pacientes[i] || null;

    if (paciente) {
      bloques.push(renderBloquePaciente(paciente, i));
    } else {
      bloques.push(renderBloqueDisponible(programacion, i));
    }
  }

  return `
    <div class="agenda-resumen">
      <div>
        <strong>${escapeHtml(programacion.especialidad || "")}</strong>
        <span>${escapeHtml(programacion.servicio || "")}</span>
      </div>

      <div>
        <strong>${escapeHtml(programacion.medico || "")}</strong>
        <span>${formatearFecha(programacion.fecha)} | ${escapeHtml(programacion.horaInicio || "")} - ${escapeHtml(programacion.horaFin || "")}</span>
      </div>

      <div>
        <strong>${pacientes.length}</strong>
        <span>pacientes registrados</span>
      </div>

      <div>
        <strong>${Number(programacion.cuposDisponibles || 0)}</strong>
        <span>cupos disponibles</span>
      </div>
    </div>

    <div class="agenda-pacientes-lista">
      ${bloques.join("")}
    </div>
  `;
}

function renderBloquePaciente(paciente, index) {
  const claseColor = obtenerClaseColorCita(paciente);
  const numero = String(index + 1).padStart(2, "0");

  return `
    <article class="agenda-bloque ${claseColor}">
      <div class="agenda-bloque-numero">${numero}</div>

      <div class="agenda-bloque-contenido">
        <div class="agenda-bloque-top">
          <strong>${escapeHtml(paciente.estadoCita || "SEPARADO")}</strong>
          <span>${escapeHtml(paciente.tipoSeguro || "SIS")}</span>
        </div>

        <div class="agenda-bloque-hora">
          ${escapeHtml(paciente.horaInicio || "")} - ${escapeHtml(paciente.horaFin || "")}
        </div>

        <div class="agenda-bloque-paciente">
          HC: ${escapeHtml(paciente.historiaClinica || "")}
          (${escapeHtml(paciente.paciente || "PACIENTE SIN NOMBRE")})
        </div>
      </div>
    </article>
  `;
}

function renderBloqueDisponible(programacion, index) {
  const numero = String(index + 1).padStart(2, "0");
  const horario = calcularHorarioDisponible(programacion, index);

  return `
    <article class="agenda-bloque cita-disponible">
      <div class="agenda-bloque-numero">${numero}</div>

      <div class="agenda-bloque-contenido">
        <div class="agenda-bloque-top">
          <strong>Disponible</strong>
          <span></span>
        </div>

        <div class="agenda-bloque-hora">
          ${escapeHtml(horario)}
        </div>
      </div>
    </article>
  `;
}

function obtenerClaseColorCita(paciente) {
  if (paciente.colorCita === "anterior") {
    return "cita-anterior";
  }

  if (paciente.colorCita === "hoy") {
    return "cita-hoy";
  }

  const fechaCita = String(paciente.fechaCita || "").slice(0, 10);

  const ahora = new Date();
  const hoy = [
    ahora.getFullYear(),
    String(ahora.getMonth() + 1).padStart(2, "0"),
    String(ahora.getDate()).padStart(2, "0"),
  ].join("-");

  if (fechaCita < hoy) return "cita-anterior";
  if (fechaCita === hoy) return "cita-hoy";

  return "cita-anterior";
}

function calcularHorarioDisponible(programacion, index) {
  const horaInicio = programacion.horaInicio || "";
  const promedio = Number(programacion.tiempoPromedioAtencion || 0);

  if (!horaInicio || !promedio) {
    return "";
  }

  const inicio = sumarMinutos(horaInicio, promedio * index);
  const fin = sumarMinutos(horaInicio, promedio * (index + 1));

  return `${inicio} - ${fin}`;
}

function sumarMinutos(hora, minutosAgregar) {
  const partes = String(hora).split(":");

  if (partes.length < 2) {
    return "";
  }

  const fecha = new Date();

  fecha.setHours(Number(partes[0]), Number(partes[1]), 0, 0);
  fecha.setMinutes(fecha.getMinutes() + minutosAgregar);

  const hh = String(fecha.getHours()).padStart(2, "0");
  const mm = String(fecha.getMinutes()).padStart(2, "0");

  return `${hh}:${mm}`;
}

/* =========================================================
   DESPLEGABLE CITAS DIARIAS COMO TABLA
   Morado: citas anteriores
   Naranja: citas de hoy
   Verde: disponible
========================================================= */

window.verPacientesCitaDiaria = function () {
  console.warn("Modal desactivado. Ahora se usa tabla desplegable por fila.");
  return false;
};

const cachePacientesCitasTabla =
  typeof pacientesCitaCache !== "undefined" ? pacientesCitaCache : new Map();

window.togglePacientesCitaDiaria = async function togglePacientesCitaDiaria(
  item,
) {
  const idProgramacion = Number(item.idProgramacion || 0);

  if (!idProgramacion) {
    alert("No se encontró la programación médica.");
    return;
  }

  const fila = document.getElementById(`pacientes-cita-${idProgramacion}`);
  if (!fila) return;

  const contenedor = fila.querySelector(".agenda-pacientes-contenedor");
  if (!contenedor) return;

  if (!fila.classList.contains("oculto")) {
    fila.classList.add("oculto");
    return;
  }

  document
    .querySelectorAll(".fila-pacientes-desplegable")
    .forEach((otraFila) => {
      if (otraFila !== fila) otraFila.classList.add("oculto");
    });

  fila.classList.remove("oculto");

  if (cachePacientesCitasTabla.has(idProgramacion)) {
    contenedor.innerHTML = renderTablaAgendaPacientes(
      cachePacientesCitasTabla.get(idProgramacion),
      item,
    );
    return;
  }

  contenedor.innerHTML = `<p class="sin-datos">Cargando pacientes...</p>`;

  try {
    const response = await fetch(
      `${API_URL}/api/citas-admin/citas-diarias/${idProgramacion}/pacientes`,
      {
        method: "GET",
        credentials: "include",
      },
    );

    const result = await response.json();

    if (!result.ok && !result.success) {
      contenedor.innerHTML = `
        <p class="sin-datos">
          ${escapeHtml(result.error || "No se pudieron cargar los pacientes.")}
        </p>
      `;
      return;
    }

    const pacientes = Array.isArray(result.data) ? result.data : [];
    cachePacientesCitasTabla.set(idProgramacion, pacientes);

    contenedor.innerHTML = renderTablaAgendaPacientes(pacientes, item);
  } catch (error) {
    console.error(error);
    contenedor.innerHTML = `<p class="sin-datos">Error conectando con la API de pacientes.</p>`;
  }
};

function renderTablaAgendaPacientes(pacientes, programacion) {
  const pacientesOrdenados = [...pacientes].sort((a, b) => {
    const ordenA = Number(a.numeroOrden || a.numeroCupo || a.orden || 0);
    const ordenB = Number(b.numeroOrden || b.numeroCupo || b.orden || 0);

    if (ordenA && ordenB && ordenA !== ordenB) return ordenA - ordenB;

    return String(a.horaInicio || "").localeCompare(String(b.horaInicio || ""));
  });

  const citasNormales = pacientesOrdenados.filter(
    (paciente) => !esCitaAdicionalPaciente(paciente),
  );
  const citasAtendidas = pacientesOrdenados.filter(esPacienteAtendido);
  const citasNormalesPendientes = citasNormales.filter(
    (paciente) => !esPacienteAtendido(paciente),
  );
  const citasAdicionales = pacientesOrdenados.filter(
    (paciente) =>
      esCitaAdicionalPaciente(paciente) && !esPacienteAtendido(paciente),
  );

  const cuposProgramados = Math.max(
    Number(programacion.cuposProgramados || 0),
    citasNormales.length,
  );

  const filas = [];

  for (let i = 0; i < cuposProgramados; i++) {
    const paciente = citasNormales[i] || null;

    if (paciente) {
      filas.push(renderFilaTablaPaciente(paciente, i));
    } else {
      filas.push(renderFilaTablaDisponible(programacion, i));
    }
  }

  citasAdicionales.forEach((paciente, index) => {
    filas.push(renderFilaTablaPaciente(paciente, cuposProgramados + index));
  });

  return `
    <div class="agenda-tabla-resumen agenda-tabla-resumen--final">
      <div>
        <strong>${escapeHtml(programacion.especialidad || "")}</strong>
        <span>${escapeHtml(programacion.servicio || "")}</span>
      </div>

      <div>
        <strong>${escapeHtml(programacion.medico || "")}</strong>
        <span>${formatearFecha(programacion.fecha)} | ${escapeHtml(programacion.horaInicio || "")} - ${escapeHtml(programacion.horaFin || "")}</span>
      </div>

      <div>
        <strong>${citasNormalesPendientes.length}</strong>
        <span>citas otorgadas</span>
      </div>

      <div>
        <strong>${citasAtendidas.length}</strong>
        <span>atendidos</span>
      </div>

      <div>
        <strong>${citasAdicionales.length}</strong>
        <span>citas adicionales</span>
      </div>
    </div>

    <div class="agenda-leyenda-estados" aria-label="Leyenda de estados de citas">
      <span><i class="leyenda-punto leyenda-disponible"></i>Disponible</span>
      <span><i class="leyenda-punto leyenda-pendiente"></i>Pendiente de atención</span>
      <span><i class="leyenda-punto leyenda-atendido"></i>Atendido</span>
      <span><i class="leyenda-punto leyenda-adicional"></i>Adicional <small>(Programado por el personal)</small></span>
    </div>

    <div class="agenda-tabla-scroll agenda-tabla-scroll--final">
      <table class="agenda-tabla-pacientes agenda-tabla-pacientes--final">
        <thead>
          <tr>
            <th>Turno / estado</th>
            <th>Horario</th>
            <th>Paciente / HC</th>
            <th>Seguro</th>
            <th>Acciones</th>
          </tr>
        </thead>

        <tbody>
          ${filas.join("")}
        </tbody>
      </table>
    </div>
  `;
}

function renderFilaTablaPaciente(paciente, index) {
  const numero = String(index + 1).padStart(2, "0");
  const estadoVisual = obtenerEstadoVisualPaciente(paciente);
  const idPaciente = obtenerIdPacienteCita(paciente);
  const horario =
    `${paciente.horaInicio || ""} - ${paciente.horaFin || ""}`.trim();
  const seguro = paciente.tipoSeguro || "SIS";
  const historiaClinica = paciente.historiaClinica || "";
  const nombrePaciente = paciente.paciente || "PACIENTE SIN NOMBRE";

  return `
    <tr class="${estadoVisual.claseFila}">
      <td data-label="Turno / estado">
        <div class="estado-cita-detalle ${estadoVisual.claseEstado}">
          <span class="numero-turno">${numero}</span>
          <span class="estado-dot"></span>
          <div>
            <strong>${escapeHtml(estadoVisual.texto)}</strong>
            ${estadoVisual.detalle ? `<small>${escapeHtml(estadoVisual.detalle)}</small>` : ""}
          </div>
        </div>
      </td>

      <td data-label="Horario">
        <span class="horario-agenda-final">${escapeHtml(horario)}</span>
      </td>

      <td data-label="Paciente / HC">
        <div class="paciente-hc-final">
          <strong>${escapeHtml(nombrePaciente)}</strong>
          <span>HC: ${escapeHtml(historiaClinica || "—")}</span>
        </div>
      </td>

      <td data-label="Seguro">
        ${escapeHtml(seguro)}
      </td>

      <td data-label="Acciones">
        <div class="agenda-acciones">
          <button type="button" class="btn-agenda btn-atendido"
            onclick="event.stopPropagation(); cambiarEstadoPacienteCita('${escapeHtml(idPaciente)}', 'ATENDIDO')">
            Atendido
          </button>

          <button type="button" class="btn-agenda btn-anulado"
            onclick="event.stopPropagation(); cambiarEstadoPacienteCita('${escapeHtml(idPaciente)}', 'ANULADO')">
            Anulado
          </button>
        </div>
      </td>
    </tr>
  `;
}

function renderFilaTablaDisponible(programacion, index) {
  const numero = String(index + 1).padStart(2, "0");
  const horario = calcularHorarioDisponible(programacion, index);

  return `
    <tr class="cita-disponible-tabla">
      <td data-label="Turno / estado">
        <div class="estado-cita-detalle estado-disponible">
          <span class="numero-turno">${numero}</span>
          <span class="estado-dot"></span>
          <div>
            <strong>Disponible</strong>
          </div>
        </div>
      </td>

      <td data-label="Horario">
        <span class="horario-agenda-final">${escapeHtml(horario)}</span>
      </td>

      <td data-label="Paciente / HC">
        <div class="paciente-hc-final">
          <strong>Disponible</strong>
          <span>HC: —</span>
        </div>
      </td>

      <td data-label="Seguro">—</td>

      <td data-label="Acciones">
        <span class="agenda-sin-accion">Sin acción</span>
      </td>
    </tr>
  `;
}

function obtenerEstadoVisualPaciente(paciente) {
  if (esPacienteAtendido(paciente)) {
    return {
      texto: "Atendido",
      detalle: `Atención: ${obtenerHoraAtencionPaciente(paciente)}`,
      claseFila: "cita-atendida-tabla",
      claseEstado: "estado-atendido",
    };
  }

  if (esCitaAdicionalPaciente(paciente) || paciente.colorCita === "adicional") {
    return {
      texto: "Adicional",
      detalle: "Cita adicional",
      claseFila: "cita-adicional-tabla",
      claseEstado: "estado-adicional",
    };
  }

  return {
    texto: "Pendiente de atención",
    detalle: `Registro: ${obtenerHoraRegistroPaciente(paciente)}`,
    claseFila: "cita-pendiente-tabla",
    claseEstado: "estado-pendiente",
  };
}

function esPacienteAtendido(paciente) {
  const estado = String(
    paciente.estadoCita || paciente.estado || "",
  ).toUpperCase();
  const idEstado = String(paciente.idEstadoCita || "");

  return estado === "ATENDIDO" || idEstado === "2";
}

function obtenerHoraAtencionPaciente(paciente) {
  const candidatos = [
    paciente.horaAtencion,
    paciente.fechaAtencion,
    paciente.fechaHoraAtencion,
    paciente.atendidoEn,
    paciente.horaFin,
  ];

  return extraerHoraDeCampos(candidatos) || "--:--";
}

function obtenerHoraRegistroPaciente(paciente) {
  const candidatos = [
    paciente.horaRegistro,
    paciente.fechaHoraRegistro,
    paciente.creadoEn,
    paciente.fechaRegistro,
    paciente.horaInicio,
  ];

  const hora = extraerHoraDeCampos(candidatos);
  return hora && hora !== "00:00" ? hora : paciente.horaInicio || "--:--";
}

function extraerHoraDeCampos(candidatos) {
  for (const valor of candidatos) {
    if (!valor) continue;

    const texto = String(valor);
    const match = texto.match(/(\d{2}:\d{2})/);

    if (match) {
      return match[1];
    }
  }

  return "";
}

function obtenerClaseColorCitaTabla(paciente) {
  return obtenerEstadoVisualPaciente(paciente).claseFila;
}

function esCitaAdicionalPaciente(paciente) {
  return (
    paciente.esCitaAdicional === true ||
    paciente.esCitaAdicional === 1 ||
    paciente.esCitaAdicional === "1" ||
    String(paciente.tipoCita || "").toUpperCase() === "ADICIONAL"
  );
}

function obtenerEstadoTextoTablaPaciente(paciente) {
  return obtenerEstadoVisualPaciente(paciente).texto;
}

function obtenerIdPacienteCita(paciente) {
  return (
    paciente.idCita ||
    paciente.idCuentaAtencion ||
    paciente.idAtencion ||
    paciente.numeroCuenta ||
    paciente.historiaClinica ||
    ""
  );
}

window.cambiarEstadoPacienteCita = async function cambiarEstadoPacienteCita(
  idPaciente,
  estado,
) {
  if (!idPaciente) {
    alert("No se encontró el identificador del paciente.");
    return;
  }

  const confirmar = confirm(`¿Deseas marcar esta cita como ${estado}?`);
  if (!confirmar) return;

  try {
    const response = await fetch(
      `${API_URL}/api/citas-admin/citas-diarias/pacientes/${encodeURIComponent(idPaciente)}/estado`,
      {
        method: "POST",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ estado }),
      },
    );

    const result = await response.json();

    if (!result.ok && !result.success) {
      alert(result.error || "No se pudo actualizar el estado.");
      return;
    }

    cachePacientesCitasTabla.clear();

    if (typeof cargarDatos === "function") {
      cargarDatos();
    } else {
      location.reload();
    }
  } catch (error) {
    console.error(error);
    alert("Error conectando con la API para actualizar el estado.");
  }
};

function insertarEstilosTablaAgendaCitas() {
  if (document.getElementById("estilos-tabla-agenda-citas")) return;

  const style = document.createElement("style");
  style.id = "estilos-tabla-agenda-citas";

  style.textContent = `
    .fila-cita-click {
      cursor: pointer;
    }

    .fila-cita-click:hover {
      background: #f0fbff;
    }

    .fila-pacientes-desplegable.oculto {
      display: none;
    }

    .fila-pacientes-desplegable > td {
      padding: 0 !important;
      background: #f8fbff;
      border-top: 0;
    }

    .agenda-pacientes-contenedor {
      padding: 14px 18px 18px;
    }

    .agenda-tabla-resumen {
      display: grid;
      grid-template-columns: 1.4fr 1.4fr 120px 120px;
      gap: 12px;
      margin-bottom: 12px;
      padding: 12px;
      border: 1px solid #cdeefa;
      border-radius: 10px;
      background: #eefcff;
      color: #082447;
    }

    .agenda-tabla-resumen div {
      display: flex;
      flex-direction: column;
      gap: 3px;
    }

    .agenda-tabla-resumen strong {
      font-size: 13px;
      font-weight: 900;
      text-transform: uppercase;
    }

    .agenda-tabla-resumen span {
      font-size: 12px;
      color: #37546f;
      font-weight: 700;
    }

    .agenda-tabla-scroll {
      width: 100%;
      overflow-x: auto;
      border: 1px solid #0b3552;
      border-radius: 8px;
    }

    .agenda-tabla-pacientes {
      width: 100%;
      min-width: 1100px;
      border-collapse: collapse;
      font-size: 12px;
      color: #061d3d;
    }

    .agenda-tabla-pacientes thead th {
      background: linear-gradient(90deg, #06456a, #12b8aa);
      color: #ffffff;
      padding: 10px 8px;
      text-align: left;
      font-size: 11px;
      text-transform: uppercase;
      border: 1px solid #07364f;
      white-space: nowrap;
    }

    .agenda-tabla-pacientes tbody td {
      padding: 9px 8px;
      border: 1px solid #17233a;
      font-weight: 800;
      vertical-align: middle;
    }

    .cita-separado-tabla td {
      background: #ffe2c2;
      color: #3b2208;
    }

    .cita-adicional-tabla td {
      background: #ffd6d6;
      color: #4a1111;
    }

    .cita-disponible-tabla td {
      background: #dcfce7;
      color: #0f2f1c;
    }

    .agenda-acciones {
      display: flex;
      gap: 6px;
      align-items: center;
      justify-content: center;
      flex-wrap: wrap;
    }

    .btn-agenda {
      border: 0;
      border-radius: 8px;
      padding: 7px 10px;
      font-size: 11px;
      font-weight: 900;
      cursor: pointer;
      color: #ffffff;
      box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
    }

    .btn-atendido {
      background: #10b981;
    }

    .btn-anulado {
      background: #ef4444;
    }

    .btn-atendido:hover {
      background: #059669;
    }

    .btn-anulado:hover {
      background: #dc2626;
    }

    .agenda-sin-accion {
      display: inline-flex;
      padding: 6px 9px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.55);
      color: #37546f;
      font-size: 11px;
      font-weight: 900;
    }

    @media (max-width: 1100px) {
      .agenda-tabla-resumen {
        grid-template-columns: 1fr 1fr;
      }
    }
  `;

  document.head.appendChild(style);
}

// Estilos finales ahora viven en citasadmin.css para evitar bordes antiguos.
// insertarEstilosTablaAgendaCitas();
