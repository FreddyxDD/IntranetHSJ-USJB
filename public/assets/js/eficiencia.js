document.addEventListener("DOMContentLoaded", async () => {
    colocarAnioFooter();
    crearEscalaTermometro();
    configurarSelectorIndicador();
    configurarPantallaCompletaGrafico();
    configurarPantallaCompletaTabla();
    configurarExportaciones();
    await verificarSesionYCargar();
});

let datosGlobales = [];

const INDICADORES_CONFIG = {
    "rendimiento-sala": {
        orden: 21,
        titulo: "Indicador 21 - Rendimiento de Sala de Operaciones",
        descripcion: "Visualización de cumplimiento mensual respecto a la meta esperada.",
        meta: 85,
        maxEscala: 100,
        formatoMeta: "numero",
        formatoValor: "numero",
        leyendas: {
            rojo: "Bajo 85",
            verde: "Meta 85",
            naranja: "Alto 90"
        },
        evaluar(valor) {
            const n = Number(valor);

            if (Number.isNaN(n)) {
                return { estado: "rojo", texto: "Estado: Sin dato" };
            }

            if (n >= 85 && n < 90) {
                return { estado: "verde", texto: "Estado: Dentro del rango permitido" };
            }

            if (n >= 90) {
                return { estado: "naranja", texto: "Estado: Sobre la meta permitida" };
            }

            return { estado: "rojo", texto: "Estado: Menor que la meta" };
        }
    },

    "cirugias-suspendidas": {
        orden: 22,
        titulo: "Indicador 22 - Porcentaje de Cirugías Suspendidas",
        descripcion: "Control mensual del porcentaje de cirugías suspendidas respecto a la meta esperada.",
        meta: 5,
        maxEscala: 20,
        formatoMeta: "porcentaje",
        formatoValor: "porcentaje",
        leyendas: {
            rojo: "Bajo meta",
            verde: "Meta 5%",
            naranja: "Sobre meta"
        },
        evaluar(valor) {
            const n = Number(valor);

            if (Number.isNaN(n)) {
                return { estado: "rojo", texto: "Estado: Sin dato" };
            }

            if (n >= 5 && n < 6) {
                return { estado: "verde", texto: "Estado: Dentro del rango permitido" };
            }

            if (n >= 6) {
                return { estado: "naranja", texto: "Estado: Sobre el límite permitido" };
            }

            return { estado: "rojo", texto: "Estado: Por debajo de la meta" };
        }
    },

    "ocupacion-cama": {
        orden: 23,
        titulo: "Indicador 23 - Porcentaje de ocupación cama",
        descripcion: "Seguimiento del nivel de ocupación de camas respecto al logro esperado.",
        meta: 80,
        maxEscala: 100,
        formatoMeta: "porcentaje",
        formatoValor: "porcentaje",
        leyendas: {
            rojo: "Bajo meta",
            verde: "Meta 80%",
            naranja: "Alto 90%"
        },
        evaluar(valor) {
            const n = Number(valor);

            if (Number.isNaN(n)) {
                return { estado: "rojo", texto: "Estado: Sin dato" };
            }

            if (n >= 80 && n < 90) {
                return { estado: "verde", texto: "Estado: Cumple la meta de hospitalización" };
            }

            if (n >= 90) {
                return { estado: "naranja", texto: "Estado: Sobre el rango esperado" };
            }

            return { estado: "rojo", texto: "Estado: Menor que la meta esperada" };
        }
    },

    "intervalo-sustitucion": {
        orden: 24,
        titulo: "Indicador 24 - Intervalo de Sustitución de camas",
        descripcion: "Control del tiempo promedio en días que una cama permanece desocupada entre egresos e ingresos.",
        meta: 2,
        maxEscala: 10,
        formatoMeta: "dias",
        formatoValor: "dias",
        leyendas: {
            rojo: "Fuera de rango",
            verde: "Meta 2 días",
            naranja: "Muy alto"
        },
        evaluar(valor) {
            const n = Number(valor);

            if (Number.isNaN(n)) {
                return { estado: "rojo", texto: "Estado: Sin dato" };
            }

            if (n >= 0 && n <= 2) {
                return { estado: "verde", texto: "Estado: Dentro del rango esperado" };
            }

            return { estado: "rojo", texto: "Estado: Mayor al rango esperado" };
        }
    }
};

function colocarAnioFooter() {
    const anioActual = new Date().getFullYear();

    const yearEl = document.getElementById("year");
    if (yearEl) yearEl.textContent = anioActual;

    const anioEl = document.getElementById("anio");
    if (anioEl) anioEl.textContent = anioActual;
}

function crearEscalaTermometro(maxEscala = 100) {
    const escala = document.getElementById("termometroEscala");
    if (!escala) return;

    let html = "";
    const paso = calcularPasoEscala(maxEscala);

    for (let i = maxEscala; i >= 0; i -= paso) {
        html += `<div class="escala-item">${i}</div>`;
    }

    escala.innerHTML = html;
}

function calcularPasoEscala(maxEscala) {
    if (maxEscala <= 10) return 1;
    if (maxEscala <= 20) return 2;
    if (maxEscala <= 50) return 5;
    return 10;
}

function configurarSelectorIndicador() {
    const selector = document.getElementById("selectorIndicador");
    if (!selector) return;

    selector.addEventListener("change", () => {
        if (!datosGlobales.length) return;

        try {
            renderizarGraficoSegunSelector(datosGlobales);
        } catch (error) {
            console.error("Error al cambiar indicador:", error);
            mostrarErrorEnGrafico("No se pudo cambiar el gráfico.");
        }
    });
}

function configurarPantallaCompletaGrafico() {
    const btn = document.getElementById("btnFullscreenGrafico");
    const card = document.getElementById("graficoFullscreenCard");

    if (!btn || !card) return;

    btn.addEventListener("click", async () => {
        try {
            const activo =
                document.fullscreenElement === card ||
                document.webkitFullscreenElement === card;

            if (!activo) {
                if (card.requestFullscreen) {
                    await card.requestFullscreen();
                } else if (card.webkitRequestFullscreen) {
                    card.webkitRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    await document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
            }
        } catch (error) {
            console.error("No se pudo cambiar fullscreen gráfico:", error);
        }
    });

    document.addEventListener("fullscreenchange", actualizarEstadoBotonesFullscreen);
    document.addEventListener("webkitfullscreenchange", actualizarEstadoBotonesFullscreen);
}

function configurarPantallaCompletaTabla() {
    const btn = document.getElementById("btnFullscreenTabla");
    const card = document.getElementById("tablaFullscreenCard");

    if (!btn || !card) return;

    btn.addEventListener("click", async () => {
        try {
            const activo =
                document.fullscreenElement === card ||
                document.webkitFullscreenElement === card;

            if (!activo) {
                if (card.requestFullscreen) {
                    await card.requestFullscreen();
                } else if (card.webkitRequestFullscreen) {
                    card.webkitRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    await document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
            }
        } catch (error) {
            console.error("No se pudo cambiar fullscreen tabla:", error);
        }
    });

    document.addEventListener("fullscreenchange", actualizarEstadoBotonesFullscreen);
    document.addEventListener("webkitfullscreenchange", actualizarEstadoBotonesFullscreen);
}

function actualizarEstadoBotonesFullscreen() {
    actualizarEstadoBotonFullscreenGrafico();
    actualizarEstadoBotonFullscreenTabla();
}

function actualizarEstadoBotonFullscreenGrafico() {
    const btn = document.getElementById("btnFullscreenGrafico");
    const card = document.getElementById("graficoFullscreenCard");

    if (!btn || !card) return;

    const activo =
        document.fullscreenElement === card ||
        document.webkitFullscreenElement === card;

    btn.setAttribute(
        "title",
        activo ? "Salir de pantalla completa" : "Pantalla completa gráfico"
    );

    btn.setAttribute(
        "aria-label",
        activo ? "Salir de pantalla completa" : "Pantalla completa gráfico"
    );
}

function actualizarEstadoBotonFullscreenTabla() {
    const btn = document.getElementById("btnFullscreenTabla");
    const card = document.getElementById("tablaFullscreenCard");

    if (!btn || !card) return;

    const activo =
        document.fullscreenElement === card ||
        document.webkitFullscreenElement === card;

    btn.setAttribute(
        "title",
        activo ? "Salir de pantalla completa" : "Pantalla completa tabla"
    );

    btn.setAttribute(
        "aria-label",
        activo ? "Salir de pantalla completa" : "Pantalla completa tabla"
    );
}

function configurarExportaciones() {
    const btnPDF = document.getElementById("btnExportarPDF");
    const btnExcel = document.getElementById("btnExportarExcel");

    if (btnPDF) {
        btnPDF.addEventListener("click", exportarReportePDFBonito);
    }

    if (btnExcel) {
        btnExcel.addEventListener("click", exportarReporteExcelBonito);
    }
}

async function verificarSesionYCargar() {
    try {
        const resSesion = await fetch("/me-ueei", {
            method: "GET",
            credentials: "include"
        });

        if (!resSesion.ok) {
            window.location.href = "/";
            return;
        }

        await cargarIndicadoresEficiencia();
    } catch (error) {
        console.error("Error verificando sesión:", error);
        mostrarErrorEnTabla("No se pudo verificar la sesión.");
        actualizarEstado("Error de sesión");
    }
}

async function cargarIndicadoresEficiencia() {
    try {
        const res = await fetch("/indicadores/eficiencia", {
            method: "GET",
            credentials: "include"
        });

        const resultado = await res.json();

        if (!res.ok || !resultado.ok) {
            throw new Error(resultado.message || "No se pudo obtener la información.");
        }

        const datos = Array.isArray(resultado.data) ? resultado.data : [];
        datosGlobales = datos;

        renderizarTablaEficiencia(datos);

        try {
            renderizarGraficoSegunSelector(datos);
        } catch (errorGrafico) {
            console.error("Error renderizando gráfico:", errorGrafico);
            mostrarErrorEnGrafico("No se pudo cargar el gráfico.");
        }

        actualizarResumen(datos);
        actualizarEstado("Conectado");
    } catch (error) {
        console.error("Error cargando indicadores:", error);
        mostrarErrorEnTabla("No se pudieron cargar los indicadores desde SQL.");
        actualizarEstado("Error");
        mostrarErrorEnGrafico("No se pudo cargar el gráfico.");
    }
}

function renderizarTablaEficiencia(datos) {
    const tbody = document.getElementById("tablaEficienciaBody");
    if (!tbody) return;

    if (!datos.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="11">No hay datos registrados.</td>
            </tr>
        `;
        return;
    }

    const grupos = agruparPorIndicador(datos);
    let html = "";

    grupos.forEach(grupo => {
        const rowspan = grupo.rowspan;

        const bloqueEne = construirBloqueFraccion(grupo.filas, "Ene");
        const bloqueFeb = construirBloqueFraccion(grupo.filas, "Feb");
        const bloqueMar = construirBloqueFraccion(grupo.filas, "Mar");
        const bloqueTotal = construirBloqueFraccion(grupo.filas, "Total_Anual");

        const valorEne = obtenerPrimerValorValido(grupo.filas, "Ene_Valor");
        const valorFeb = obtenerPrimerValorValido(grupo.filas, "Feb_Valor");
        const valorMar = obtenerPrimerValorValido(grupo.filas, "Mar_Valor");
        const valorFinal = obtenerPrimerValorValido(grupo.filas, "Valor_Final");

        grupo.filas.forEach((item, index) => {
            html += "<tr>";

            if (index === 0) {
                html += `
                    <td class="celda-orden" rowspan="${rowspan}">
                        ${escapeHTML(grupo.Orden)}
                    </td>

                    <td class="celda-indicador" rowspan="${rowspan}">
                        ${escapeHTML(grupo.Nombre_Indicador)}
                    </td>
                `;
            }

            html += `
                <td class="celda-variable">
                    <div class="variable-box">
                        <span class="variable-item">${escapeHTML(item.Variable ?? "")}</span>
                    </div>
                </td>
            `;

            if (index === 0) {
                html += `
                    <td class="celda-agrupada" rowspan="${rowspan}">
                        ${bloqueEne}
                    </td>

                    <td class="celda-valor" rowspan="${rowspan}">
                        ${formatearDecimal(valorEne)}
                    </td>

                    <td class="celda-agrupada" rowspan="${rowspan}">
                        ${bloqueFeb}
                    </td>

                    <td class="celda-valor" rowspan="${rowspan}">
                        ${formatearDecimal(valorFeb)}
                    </td>

                    <td class="celda-agrupada" rowspan="${rowspan}">
                        ${bloqueMar}
                    </td>

                    <td class="celda-valor" rowspan="${rowspan}">
                        ${formatearDecimal(valorMar)}
                    </td>

                    <td class="celda-agrupada" rowspan="${rowspan}">
                        ${bloqueTotal}
                    </td>

                    <td class="celda-valor" rowspan="${rowspan}">
                        ${formatearDecimal(valorFinal)}
                    </td>
                `;
            }

            html += "</tr>";
        });
    });

    tbody.innerHTML = html;
}

function renderizarGraficoSegunSelector(datos) {
    const selector = document.getElementById("selectorIndicador");
    const claveSeleccionada = selector ? selector.value : "rendimiento-sala";
    const config = INDICADORES_CONFIG[claveSeleccionada] || INDICADORES_CONFIG["rendimiento-sala"];

    actualizarTextosGrafico(config);
    crearEscalaTermometro(config.maxEscala);
    actualizarLeyendaDinamica(config);
    renderizarGraficoIndicador(datos, config);
}

function actualizarTextosGrafico(config) {
    const titulo = document.getElementById("tituloIndicadorGrafico");
    const descripcion = document.getElementById("descripcionIndicadorGrafico");

    if (titulo) titulo.textContent = config.titulo;
    if (descripcion) descripcion.textContent = config.descripcion;
}

function actualizarLeyendaDinamica(config) {
    const card = document.getElementById("graficoFullscreenCard");
    if (!card) return;

    let leyenda = card.querySelector(".leyenda-umbrales");

    if (!leyenda) {
        leyenda = document.createElement("div");
        leyenda.className = "leyenda-umbrales";

        const grafico = card.querySelector(".grafico-ejes-wrap");
        if (grafico) {
            card.insertBefore(leyenda, grafico);
        } else {
            card.appendChild(leyenda);
        }
    }

    leyenda.innerHTML = `
        <span class="leyenda-item">
            <span class="leyenda-linea roja"></span>
            ${escapeHTML(config.leyendas.rojo)}
        </span>
        <span class="leyenda-item">
            <span class="leyenda-linea verde"></span>
            ${escapeHTML(config.leyendas.verde)}
        </span>
        <span class="leyenda-item">
            <span class="leyenda-linea naranja"></span>
            ${escapeHTML(config.leyendas.naranja)}
        </span>
    `;
}

function renderizarGraficoIndicador(datos, config) {
    const contenedorBarras = document.getElementById("graficoBarrasRendimiento");
    const termometroBarra = document.getElementById("termometroBarra");
    const termometroValor = document.getElementById("termometroValor");
    const lineaMeta = document.getElementById("lineaMeta");
    const valorActualTexto = document.getElementById("valorActualTexto");
    const estadoMetaTexto = document.getElementById("estadoMetaTexto");
    const metaEsperadaTexto = document.getElementById("metaEsperadaTexto");

    if (
        !contenedorBarras ||
        !termometroBarra ||
        !termometroValor ||
        !lineaMeta ||
        !valorActualTexto ||
        !estadoMetaTexto ||
        !metaEsperadaTexto
    ) {
        console.warn("Faltan elementos del gráfico en el HTML.");
        return;
    }

    const filasIndicador = datos.filter(item => Number(item.Orden) === config.orden);

    if (!filasIndicador.length) {
        contenedorBarras.innerHTML = '<div class="sin-datos">No hay datos para este indicador.</div>';
        valorActualTexto.textContent = "--";
        estadoMetaTexto.textContent = "Estado: --";
        termometroValor.textContent = "--";
        metaEsperadaTexto.textContent = "--";
        lineaMeta.style.bottom = "0%";
        termometroBarra.style.height = "0%";
        termometroBarra.style.background = "linear-gradient(180deg, #d1d5db 0%, #9ca3af 100%)";
        estadoMetaTexto.classList.remove("estado-verde", "estado-rojo", "estado-naranja");
        return;
    }

    const valorEne = toNumberSafe(obtenerPrimerValorValido(filasIndicador, "Ene_Valor"));
    const valorFeb = toNumberSafe(obtenerPrimerValorValido(filasIndicador, "Feb_Valor"));
    const valorMar = toNumberSafe(obtenerPrimerValorValido(filasIndicador, "Mar_Valor"));
    const valorFinal = toNumberSafe(obtenerPrimerValorValido(filasIndicador, "Valor_Final"));

    const barras = [
        { label: "Valor ENE", valor: valorEne },
        { label: "Valor FEB", valor: valorFeb },
        { label: "Valor MAR", valor: valorMar },
        { label: "Valor FINAL", valor: valorFinal }
    ];

    contenedorBarras.innerHTML = construirSVGGrafico(barras, config);

    metaEsperadaTexto.textContent = formatearSegunTipo(config.meta, config.formatoMeta);

    const valorEvaluado = valorFinal;
    const porcentajeAltura = calcularPorcentaje(valorEvaluado, 0, config.maxEscala);
    const evaluacionFinal = config.evaluar(valorEvaluado);

    termometroBarra.style.height = `${porcentajeAltura}%`;
    termometroBarra.style.background = obtenerColorBarra(evaluacionFinal.estado);
    termometroValor.textContent = formatearSegunTipo(valorEvaluado, config.formatoValor);
    valorActualTexto.textContent = formatearSegunTipo(valorEvaluado, config.formatoValor);

    const posicionMeta = calcularPorcentaje(config.meta, 0, config.maxEscala);
    lineaMeta.style.bottom = `${posicionMeta}%`;

    estadoMetaTexto.classList.remove("estado-verde", "estado-rojo", "estado-naranja");
    estadoMetaTexto.textContent = evaluacionFinal.texto;

    if (evaluacionFinal.estado === "verde") {
        estadoMetaTexto.classList.add("estado-verde");
    } else if (evaluacionFinal.estado === "naranja") {
        estadoMetaTexto.classList.add("estado-naranja");
    } else {
        estadoMetaTexto.classList.add("estado-rojo");
    }
}

function construirSVGGrafico(barras, config) {
    const width = 1100;
    const height = 560;

    const margin = {
        top: 24,
        right: 28,
        bottom: 100,
        left: 78
    };

    const plotX = margin.left;
    const plotY = margin.top;
    const plotWidth = width - margin.left - margin.right;
    const plotHeight = height - margin.top - margin.bottom;
    const plotBottom = plotY + plotHeight;

    const paso = calcularPasoEscala(config.maxEscala);
    const slotWidth = plotWidth / barras.length;
    const barWidth = Math.min(86, slotWidth * 0.42);

    const lineasGrid = [];
    const etiquetasY = [];
    const etiquetasX = [];
    const barrasSVG = [];
    const burbujas = [];

    for (let v = 0; v <= config.maxEscala; v += paso) {
        const y = valorAY(v, config.maxEscala, plotY, plotHeight);

        lineasGrid.push(`
            <line class="svg-grid-line" x1="${plotX}" y1="${y}" x2="${plotX + plotWidth}" y2="${y}" />
        `);

        etiquetasY.push(`
            <text class="svg-y-label" x="${plotX - 18}" y="${y + 7}" text-anchor="end">${v}</text>
        `);
    }

    const metaY = valorAY(config.meta, config.maxEscala, plotY, plotHeight);

    barras.forEach((barra, index) => {
        const centroX = plotX + (slotWidth * index) + (slotWidth / 2);
        const barX = centroX - (barWidth / 2);
        const barY = valorAY(barra.valor, config.maxEscala, plotY, plotHeight);
        const barHeight = Math.max(12, plotBottom - barY);

        const evaluacion = config.evaluar(barra.valor);

        let barGradientClass = "url(#barRedGradient)";

        if (evaluacion.estado === "verde") {
            barGradientClass = "url(#barGreenGradient)";
        } else if (evaluacion.estado === "naranja") {
            barGradientClass = "url(#barOrangeGradient)";
        }

        barrasSVG.push(`
            <g class="svg-bar-group svg-bar-shadow">
                <rect
                    class="svg-bar-shape"
                    x="${barX}"
                    y="${barY}"
                    width="${barWidth}"
                    height="${barHeight}"
                    rx="22"
                    ry="22"
                    fill="${barGradientClass}"
                />
            </g>
        `);

        const bubbleWidth = 124;
        const bubbleHeight = 54;
        const bubbleX = centroX - (bubbleWidth / 2);
        const bubbleY = Math.max(6, barY - 68);

        burbujas.push(`
            <rect class="svg-bubble" x="${bubbleX}" y="${bubbleY}" width="${bubbleWidth}" height="${bubbleHeight}" rx="24" ry="24"></rect>
            <text class="svg-bubble-text" x="${centroX}" y="${bubbleY + bubbleHeight / 2 + 1}">
                ${escapeXML(formatearSegunTipo(barra.valor, config.formatoValor))}
            </text>
        `);

        etiquetasX.push(`
            <text class="svg-x-label" x="${centroX}" y="${height - 28}">
                ${escapeXML(barra.label)}
            </text>
        `);
    });

    return `
        <svg class="grafico-svg" viewBox="0 0 ${width} ${height}" preserveAspectRatio="xMidYMid meet">
            <defs>
                <linearGradient id="barRedGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#ff5757"></stop>
                    <stop offset="55%" stop-color="#ff2f38"></stop>
                    <stop offset="100%" stop-color="#ef1c1c"></stop>
                </linearGradient>

                <linearGradient id="barGreenGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#53dd78"></stop>
                    <stop offset="55%" stop-color="#22c55e"></stop>
                    <stop offset="100%" stop-color="#169e48"></stop>
                </linearGradient>

                <linearGradient id="barOrangeGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#ffbc66"></stop>
                    <stop offset="55%" stop-color="#fb923c"></stop>
                    <stop offset="100%" stop-color="#f97316"></stop>
                </linearGradient>
            </defs>

            ${lineasGrid.join("")}
            <line class="svg-meta-line" x1="${plotX}" y1="${metaY}" x2="${plotX + plotWidth}" y2="${metaY}" />
            <line class="svg-axis" x1="${plotX}" y1="${plotY}" x2="${plotX}" y2="${plotBottom}" />
            <line class="svg-axis" x1="${plotX}" y1="${plotBottom}" x2="${plotX + plotWidth}" y2="${plotBottom}" />
            ${barrasSVG.join("")}
            ${burbujas.join("")}
            ${etiquetasY.join("")}
            ${etiquetasX.join("")}
        </svg>
    `;
}

function valorAY(valor, maxEscala, plotY, plotHeight) {
    const porcentaje = calcularPorcentaje(valor, 0, maxEscala);
    return plotY + plotHeight - (plotHeight * porcentaje / 100);
}

function toNumberSafe(valor) {
    const n = Number(valor);
    return Number.isNaN(n) ? 0 : n;
}

function formatearSegunTipo(valor, tipo) {
    const numero = Number(valor);

    if (Number.isNaN(numero)) return "--";

    if (tipo === "porcentaje") {
        return `${numero.toFixed(2)}%`;
    }

    if (tipo === "dias") {
        return `${numero.toFixed(2)} días`;
    }

    return numero.toFixed(2);
}

function obtenerColorBarra(estado) {
    if (estado === "verde") {
        return "linear-gradient(180deg, #3ad46a 0%, #1faa4b 100%)";
    }

    if (estado === "naranja") {
        return "linear-gradient(180deg, #ffae52 0%, #f97316 100%)";
    }

    return "linear-gradient(180deg, #ff4b4b 0%, #e52222 100%)";
}

function calcularPorcentaje(valor, minimo, maximo) {
    const numero = Number(valor);

    if (Number.isNaN(numero)) return 0;
    if (maximo <= minimo) return 0;

    const porcentaje = ((numero - minimo) / (maximo - minimo)) * 100;
    return Math.max(0, Math.min(porcentaje, 100));
}

function mostrarErrorEnGrafico(mensaje) {
    const contenedor = document.getElementById("graficoBarrasRendimiento");
    if (contenedor) {
        contenedor.innerHTML = `<div class="sin-datos">${escapeHTML(mensaje)}</div>`;
    }

    const valorActualTexto = document.getElementById("valorActualTexto");
    const estadoMetaTexto = document.getElementById("estadoMetaTexto");
    const termometroValor = document.getElementById("termometroValor");
    const termometroBarra = document.getElementById("termometroBarra");
    const metaEsperadaTexto = document.getElementById("metaEsperadaTexto");
    const lineaMeta = document.getElementById("lineaMeta");

    if (valorActualTexto) valorActualTexto.textContent = "--";

    if (estadoMetaTexto) {
        estadoMetaTexto.textContent = "Estado: --";
        estadoMetaTexto.classList.remove("estado-verde", "estado-rojo", "estado-naranja");
    }

    if (termometroValor) termometroValor.textContent = "--";
    if (metaEsperadaTexto) metaEsperadaTexto.textContent = "--";
    if (lineaMeta) lineaMeta.style.bottom = "0%";

    if (termometroBarra) {
        termometroBarra.style.height = "0%";
        termometroBarra.style.background = "linear-gradient(180deg, #d1d5db 0%, #9ca3af 100%)";
    }
}

function agruparPorIndicador(datos) {
    const mapa = new Map();

    for (const item of datos) {
        const orden = item.Orden ?? "";
        const nombre = item.Nombre_Indicador ?? "";
        const clave = `${orden}|||${nombre}`;

        if (!mapa.has(clave)) {
            mapa.set(clave, {
                Orden: Number(orden),
                Nombre_Indicador: nombre,
                filas: []
            });
        }

        mapa.get(clave).filas.push(item);
    }

    return Array.from(mapa.values()).map(grupo => {
        const filasOrdenadas = ordenarFilasPorOrden(grupo.Orden, grupo.filas);

        return {
            ...grupo,
            filas: filasOrdenadas,
            rowspan: filasOrdenadas.length
        };
    });
}

function ordenarFilasPorOrden(orden, filas) {
    const filasClonadas = [...filas];

    return filasClonadas.sort((a, b) => {
        const prioridadA = obtenerPrioridadPorOrden(orden, a.Variable);
        const prioridadB = obtenerPrioridadPorOrden(orden, b.Variable);
        return prioridadA - prioridadB;
    });
}

function obtenerPrioridadPorOrden(orden, variable) {
    const v = normalizarTexto(variable);

    if (orden === 21) {
        if (v.includes("cirugias y procedimientos ejecutadas")) return 1;
        if (v.includes("salas de operaciones utilizadas")) return 2;
        return 999;
    }

    if (orden === 22) {
        if (v.includes("suspendidas")) return 1;
        if (v.includes("programadas")) return 2;
        return 999;
    }

    if (orden === 23) {
        if (v.includes("pacientes dia")) return 1;
        if (v.includes("camas operativas")) return 2;
        return 999;
    }

    if (orden === 24) {
        if (v.includes("dias cama disponibles")) return 1;
        if (v.includes("egresos hospitalarios")) return 2;
        return 999;
    }

    return 999;
}

function normalizarTexto(texto) {
    return String(texto || "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/°/g, "")
        .replace(/º/g, "")
        .replace(/[()]/g, " ")
        .replace(/[.-]/g, " ")
        .replace(/\//g, " ")
        .replace(/\s+/g, " ")
        .trim()
        .toLowerCase();
}

function construirBloqueFraccion(filas, campo) {
    const valores = filas
        .map(f => f[campo])
        .filter(v => v !== null && v !== undefined && v !== "");

    if (!valores.length) {
        return '<span class="celda-vacia"></span>';
    }

    return `
        <div class="fraccion-columna">
            ${valores.map((valor, index) => `
                <div class="fraccion-item ${index < valores.length - 1 ? "con-linea" : ""}">
                    ${formatearEntero(valor)}
                </div>
            `).join("")}
        </div>
    `;
}

function obtenerPrimerValorValido(filas, campo) {
    for (const fila of filas) {
        const valor = fila[campo];
        if (valor !== null && valor !== undefined && valor !== "") {
            return valor;
        }
    }
    return "";
}

function actualizarResumen(datos) {
    const totalIndicadores = document.getElementById("totalIndicadores");
    if (totalIndicadores) {
        const grupos = agruparPorIndicador(datos);
        totalIndicadores.textContent = grupos.length;
    }
}

function actualizarEstado(texto) {
    const estado = document.getElementById("estadoCarga");
    if (estado) {
        estado.textContent = texto;
    }
}

function mostrarErrorEnTabla(mensaje) {
    const tbody = document.getElementById("tablaEficienciaBody");
    if (!tbody) return;

    tbody.innerHTML = `
        <tr>
            <td colspan="11">${escapeHTML(mensaje)}</td>
        </tr>
    `;
}

function formatearDecimal(valor) {
    if (valor === null || valor === undefined || valor === "") return "";

    const numero = Number(valor);
    if (Number.isNaN(numero)) return escapeHTML(String(valor));

    return numero.toFixed(2);
}

function formatearEntero(valor) {
    if (valor === null || valor === undefined || valor === "") return "";

    const numero = Number(valor);
    if (Number.isNaN(numero)) return escapeHTML(String(valor));

    return String(numero);
}

function escapeHTML(valor) {
    return String(valor)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function escapeXML(valor) {
    return String(valor)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&apos;");
}

/* =========================
   EXPORTACIONES
========================= */

function setBotonCargando(btn, textoCargando) {
    if (!btn) return;
    btn.dataset.originalText = btn.textContent;
    btn.textContent = textoCargando;
    btn.disabled = true;
    btn.classList.add("loading");
}

function resetBoton(btn) {
    if (!btn) return;
    btn.textContent = btn.dataset.originalText || btn.textContent;
    btn.disabled = false;
    btn.classList.remove("loading");
}

function formatearFechaHoraActual() {
    const ahora = new Date();
    return ahora.toLocaleString("es-PE", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit"
    });
}

function obtenerNombreIndicadorSeleccionado() {
    const selector = document.getElementById("selectorIndicador");
    if (!selector) return "Indicador seleccionado";
    return selector.options[selector.selectedIndex]?.text || "Indicador seleccionado";
}

function obtenerResumenExportacion() {
    return {
        establecimiento: document.getElementById("establecimiento")?.textContent?.trim() || "Hospital San José de Chincha",
        anio: document.getElementById("anio")?.textContent?.trim() || String(new Date().getFullYear()),
        totalIndicadores: document.getElementById("totalIndicadores")?.textContent?.trim() || "0",
        estado: document.getElementById("estadoCarga")?.textContent?.trim() || "--",
        origen: "SQL Server",
        indicadorSeleccionado: obtenerNombreIndicadorSeleccionado(),
        fechaExportacion: formatearFechaHoraActual()
    };
}

function obtenerTablaExportacionDesdeDatos() {
    const headers = [
        "Ord",
        "Nombre del Indicador",
        "Variable",
        "ENE",
        "Valor ENE",
        "FEB",
        "Valor FEB",
        "MAR",
        "Valor MAR",
        "Total Anual",
        "Valor Final"
    ];

    if (!Array.isArray(datosGlobales) || !datosGlobales.length) {
        return { headers, rows: [] };
    }

    const grupos = agruparPorIndicador(datosGlobales);
    const rows = [];

    grupos.forEach(grupo => {
        const valorEne = formatearDecimal(obtenerPrimerValorValido(grupo.filas, "Ene_Valor"));
        const valorFeb = formatearDecimal(obtenerPrimerValorValido(grupo.filas, "Feb_Valor"));
        const valorMar = formatearDecimal(obtenerPrimerValorValido(grupo.filas, "Mar_Valor"));
        const valorFinal = formatearDecimal(obtenerPrimerValorValido(grupo.filas, "Valor_Final"));

        grupo.filas.forEach((fila, index) => {
            rows.push([
                index === 0 ? String(grupo.Orden ?? "") : "",
                index === 0 ? String(grupo.Nombre_Indicador ?? "") : "",
                String(fila.Variable ?? ""),
                formatearEntero(fila.Ene),
                index === 0 ? valorEne : "",
                formatearEntero(fila.Feb),
                index === 0 ? valorFeb : "",
                formatearEntero(fila.Mar),
                index === 0 ? valorMar : "",
                formatearEntero(fila.Total_Anual),
                index === 0 ? valorFinal : ""
            ]);
        });
    });

    return { headers, rows };
}

async function capturarElementoComoImagen(elemento, scale = 2) {
    if (!elemento) return null;

    const canvas = await html2canvas(elemento, {
        scale,
        backgroundColor: "#ffffff",
        useCORS: true,
        logging: false,
        scrollX: 0,
        scrollY: -window.scrollY,
        windowWidth: document.documentElement.scrollWidth,
        windowHeight: document.documentElement.scrollHeight
    });

    return {
        dataUrl: canvas.toDataURL("image/png", 1.0),
        width: canvas.width,
        height: canvas.height
    };
}

function agregarPiePDF(doc) {
    const totalPages = doc.internal.getNumberOfPages();

    for (let i = 1; i <= totalPages; i++) {
        doc.setPage(i);
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();

        doc.setDrawColor(220, 228, 236);
        doc.line(12, pageHeight - 10, pageWidth - 12, pageHeight - 10);

        doc.setFontSize(8);
        doc.setTextColor(100, 116, 139);
        doc.text("Hospital San José de Chincha - Unidad de Estadística e Información", 14, pageHeight - 5);
        doc.text(`Página ${i} de ${totalPages}`, pageWidth - 28, pageHeight - 5);
    }
}

function dibujarCabeceraPDF(doc, resumen, pageWidth, soloTitulo = false) {
    const headerHeight = soloTitulo ? 18 : 24;

    doc.setFillColor(37, 99, 235);
    doc.rect(0, 0, pageWidth, headerHeight, "F");

    doc.setTextColor(255, 255, 255);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(soloTitulo ? 13 : 18);
    doc.text("Reporte de Indicadores de Eficiencia", 14, soloTitulo ? 11 : 14);

    if (!soloTitulo) {
        doc.setFont("helvetica", "normal");
        doc.setFontSize(9);
        doc.text(`Fecha de exportación: ${resumen.fechaExportacion}`, pageWidth - 78, 14);

        doc.setTextColor(31, 41, 55);
        doc.setFontSize(10);
        doc.text(`Establecimiento: ${resumen.establecimiento}`, 14, 32);
        doc.text(`Año: ${resumen.anio}`, 14, 38);
        doc.text(`Estado de carga: ${resumen.estado}`, 60, 38);
        doc.text(`Indicador gráfico actual: ${resumen.indicadorSeleccionado}`, 110, 38);
        doc.text(`Total de indicadores: ${resumen.totalIndicadores}`, 14, 44);
        doc.text(`Origen de datos: ${resumen.origen}`, 60, 44);
    }
}

async function exportarReportePDFBonito() {
    const btn = document.getElementById("btnExportarPDF");
    setBotonCargando(btn, "Generando PDF...");

    try {
        const graficoPanel = document.getElementById("panelGraficoRendimiento");
        const resumen = obtenerResumenExportacion();
        const { headers, rows } = obtenerTablaExportacionDesdeDatos();

        if (!rows.length) {
            alert("No hay datos para exportar.");
            return;
        }

        if (!window.jspdf || !window.jspdf.jsPDF) {
            throw new Error("jsPDF no está cargado.");
        }

        if (typeof window.jspdf.jsPDF.API.autoTable !== "function") {
            throw new Error("jspdf-autotable no está cargado.");
        }

        let grafico = null;
        if (graficoPanel) {
            try {
                grafico = await capturarElementoComoImagen(graficoPanel, 2.2);
            } catch (e) {
                console.warn("No se pudo capturar el gráfico:", e);
            }
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF("l", "mm", "a4");
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();

        dibujarCabeceraPDF(doc, resumen, pageWidth, false);

        // =========================
        // TABLA PRIMERO
        // =========================
        doc.autoTable({
            head: [headers],
            body: rows,
            startY: 54,
            theme: "grid",
            margin: { left: 10, right: 10, top: 20, bottom: 16 },
            styles: {
                fontSize: 7,
                cellPadding: 2,
                overflow: "linebreak",
                valign: "middle",
                textColor: [31, 41, 55],
                lineColor: [226, 232, 240],
                lineWidth: 0.25
            },
            headStyles: {
                fillColor: [37, 99, 235],
                textColor: [255, 255, 255],
                fontStyle: "bold",
                halign: "center",
                valign: "middle"
            },
            bodyStyles: {
                halign: "center",
                valign: "middle"
            },
            alternateRowStyles: {
                fillColor: [248, 250, 252]
            },
            columnStyles: {
                0: { cellWidth: 10 },
                1: { cellWidth: 38, halign: "left" },
                2: { cellWidth: 40, halign: "left" },
                3: { cellWidth: 14 },
                4: { cellWidth: 16 },
                5: { cellWidth: 14 },
                6: { cellWidth: 16 },
                7: { cellWidth: 14 },
                8: { cellWidth: 16 },
                9: { cellWidth: 18 },
                10: { cellWidth: 18 }
            },
            didParseCell(data) {
                if (data.section === "body") {
                    if (data.column.index === 1 || data.column.index === 2) {
                        data.cell.styles.halign = "left";
                    }

                    const row = rows[data.row.index];
                    const esFilaPrincipal = !!(row && (row[0] || row[1]));
                    if (esFilaPrincipal) {
                        data.cell.styles.fillColor = [243, 247, 255];
                    }
                }
            },
            didDrawPage(data) {
                if (data.pageNumber > 1) {
                    dibujarCabeceraPDF(doc, resumen, pageWidth, true);
                }
            }
        });

        // =========================
        // GRAFICO DESPUÉS
        // =========================
        let yGrafico = doc.lastAutoTable.finalY + 10;

        if (yGrafico + 95 > pageHeight - 18) {
            doc.addPage("a4", "l");
            dibujarCabeceraPDF(doc, resumen, pageWidth, true);
            yGrafico = 28;
        }

        doc.setTextColor(31, 41, 55);
        doc.setFontSize(12);
        doc.setFont("helvetica", "bold");
        doc.text("Gráfico de seguimiento", 14, yGrafico);

        if (grafico && grafico.dataUrl) {
            const boxX = 12;
            const boxY = yGrafico + 4;
            const boxW = pageWidth - 24;
            const boxH = 120; 

            doc.setDrawColor(220, 228, 236);
            doc.roundedRect(boxX, boxY, boxW, boxH, 6, 6);

            const maxImgW = boxW - 8;
            const maxImgH = boxH - 8;

            const ratio = grafico.width / grafico.height;

            // usar casi todo el ancho
            let imgW = boxW - 4;
            let imgH = imgW / ratio;

            // si se pasa de alto, ajustar
            if (imgH > boxH - 4) {
                imgH = boxH - 4;
                imgW = imgH * ratio;
            }

            const imgX = boxX + ((boxW - imgW) / 2);
            const imgY = boxY + ((boxH - imgH) / 2);

            doc.addImage(
                grafico.dataUrl,
                "PNG",
                imgX,
                imgY,
                imgW,
                imgH,
                undefined,
                "FAST"
            );
        } else {
            doc.setFont("helvetica", "normal");
            doc.setFontSize(10);
            doc.setTextColor(100, 116, 139);
            doc.text("No se pudo capturar el gráfico.", 14, yGrafico + 10);
        }

        agregarPiePDF(doc);
        doc.save(`Reporte_Eficiencia_${resumen.anio}.pdf`);
    } catch (error) {
        console.error("Error exportando PDF bonito:", error);
        alert(`No se pudo generar el PDF.\n${error.message || error}`);
    } finally {
        resetBoton(btn);
    }
}

function aplicarEstiloCeldaExcel(cell, opts = {}) {
    cell.alignment = {
        horizontal: opts.horizontal || "center",
        vertical: "middle",
        wrapText: true
    };
    cell.border = bordeSuaveExcel();

    if (opts.fill) {
        cell.fill = {
            type: "pattern",
            pattern: "solid",
            fgColor: { argb: opts.fill }
        };
    }

    if (opts.font) {
        cell.font = opts.font;
    }
}

function bordeSuaveExcel() {
    return {
        top: { style: "thin", color: { argb: "FFE2E8F0" } },
        left: { style: "thin", color: { argb: "FFE2E8F0" } },
        bottom: { style: "thin", color: { argb: "FFE2E8F0" } },
        right: { style: "thin", color: { argb: "FFE2E8F0" } }
    };
}

function bordeFuerteExcel() {
    return {
        top: { style: "thin", color: { argb: "FFD1D5DB" } },
        left: { style: "thin", color: { argb: "FFD1D5DB" } },
        bottom: { style: "thin", color: { argb: "FFD1D5DB" } },
        right: { style: "thin", color: { argb: "FFD1D5DB" } }
    };
}

async function exportarReporteExcelBonito() {
    const btn = document.getElementById("btnExportarExcel");
    setBotonCargando(btn, "Generando Excel...");

    try {
        const resumen = obtenerResumenExportacion();
        const { headers, rows } = obtenerTablaExportacionDesdeDatos();

        if (!rows.length) {
            alert("No hay datos para exportar.");
            return;
        }

        const workbook = new ExcelJS.Workbook();
        workbook.creator = "Hospital San José";
        workbook.created = new Date();
        workbook.modified = new Date();

        const ws = workbook.addWorksheet("Indicadores", {
            views: [{ state: "frozen", ySplit: 2 }]
        });

        ws.properties.defaultRowHeight = 22;
        ws.autoFilter = "A2:K2";

        ws.mergeCells("A1:K1");
        ws.getCell("A1").value = "TABLA DE INDICADORES DE EFICIENCIA";
        aplicarEstiloCeldaExcel(ws.getCell("A1"), {
            horizontal: "center",
            fill: "FF2563EB",
            font: { bold: true, size: 14, color: { argb: "FFFFFFFF" } }
        });
        ws.getRow(1).height = 24;

        headers.forEach((header, i) => {
            const cell = ws.getRow(2).getCell(i + 1);
            cell.value = header;

            aplicarEstiloCeldaExcel(cell, {
                horizontal: "center",
                fill: "FF1D4ED8",
                font: { bold: true, color: { argb: "FFFFFFFF" } }
            });

            cell.border = bordeFuerteExcel();
        });

        ws.getRow(2).height = 24;

        rows.forEach((row, rowIndex) => {
            const excelRow = ws.getRow(rowIndex + 3);
            const esFilaPrincipal = !!(row[0] || row[1]);
            const fillColor = esFilaPrincipal ? "FFF8FAFC" : "FFFFFFFF";

            row.forEach((value, colIndex) => {
                const cell = excelRow.getCell(colIndex + 1);
                cell.value = value;

                aplicarEstiloCeldaExcel(cell, {
                    horizontal: (colIndex === 1 || colIndex === 2) ? "left" : "center",
                    fill: fillColor
                });

                if ((colIndex === 0 || colIndex === 1) && value) {
                    cell.font = { bold: true, color: { argb: "FF111827" } };
                }
            });

            excelRow.height = 24;
        });

        ws.columns = [
            { width: 8 },
            { width: 30 },
            { width: 45 },
            { width: 10 },
            { width: 12 },
            { width: 10 },
            { width: 12 },
            { width: 10 },
            { width: 12 },
            { width: 14 },
            { width: 14 }
        ];

        const buffer = await workbook.xlsx.writeBuffer();

        saveAs(
            new Blob([buffer], {
                type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            }),
            `Tabla_Eficiencia_${resumen.anio}.xlsx`
        );
    } catch (error) {
        console.error("Error exportando Excel:", error);
        alert("No se pudo generar el archivo Excel.");
    } finally {
        resetBoton(btn);
    }
}