let graficaProduccion = null;

document.addEventListener("DOMContentLoaded", async () => {
    colocarAnioFooter();
    await verificarSesionYCargar();
});

function colocarAnioFooter() {
    const yearEl = document.getElementById("year");
    if (yearEl) {
        yearEl.textContent = new Date().getFullYear();
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

        await cargarIndicadoresProduccion();
    } catch (error) {
        console.error("Error verificando sesión:", error);
        mostrarErrorEnTabla("No se pudo verificar la sesión.");
        actualizarEstado("Error de sesión");
    }
}

async function cargarIndicadoresProduccion() {
    try {
        const res = await fetch("/indicadores/produccion", {
            method: "GET",
            credentials: "include"
        });

        const resultado = await res.json();

        if (!res.ok || !resultado.ok) {
            throw new Error(resultado.message || "No se pudo obtener la información.");
        }

        const datos = Array.isArray(resultado.data) ? resultado.data : [];

        renderizarTablaProduccion(datos);
        renderizarGraficaProduccion(datos);
        actualizarResumen(datos);
        actualizarEstado("Conectado");
    } catch (error) {
        console.error("Error cargando indicadores:", error);
        mostrarErrorEnTabla("No se pudieron cargar los indicadores desde SQL.");
        actualizarEstado("Error");
    }
}

function renderizarTablaProduccion(datos) {
    const tbody = document.getElementById("tablaProduccionBody");

    if (!tbody) return;

    if (!datos.length) {
        tbody.innerHTML = `
        <tr>
            <td colspan="9">No hay datos registrados.</td>
        </tr>
        `;
        return;
    }

    tbody.innerHTML = datos.map(item => `
        <tr>
            <td>${escapeHTML(item.Orden ?? "")}</td>
            <td>${escapeHTML(item.Nom_Indicador ?? "")}</td>
            <td>
                <div class="variable-box">
                    <span class="variable-item">${escapeHTML(item.Variables ?? "")}</span>
                </div>
            </td>
            <td>${escapeHTML(item.ENE ?? "")}</td>
            <td>${formatearNumero(item.ENE_Valor)}</td>
            <td>${escapeHTML(item.FEB ?? "")}</td>
            <td>${formatearNumero(item.FEB_Valor)}</td>
            <td>${formatearNumero(item.Total_Anual)}</td>
            <td>${formatearNumero(item.Valor_Final)}</td>
        </tr>
    `).join("");
}

function renderizarGraficaProduccion(datos) {
    const canvas = document.getElementById("graficaProduccion");
    if (!canvas) return;

    if (!datos.length) {
        if (graficaProduccion) {
            graficaProduccion.destroy();
            graficaProduccion = null;
        }
        return;
    }

    const agrupados = {};

    datos.forEach(item => {
        const nombre = item.Nom_Indicador || "Sin nombre";

        if (!agrupados[nombre]) {
            agrupados[nombre] = {
                ene: 0,
                feb: 0,
                total: 0
            };
        }

        const ene = obtenerNumeroSeguro(item.ENE_Valor);
        const feb = obtenerNumeroSeguro(item.FEB_Valor);
        const total = obtenerNumeroSeguro(item.Total_Anual);

        agrupados[nombre].ene += ene;
        agrupados[nombre].feb += feb;
        agrupados[nombre].total += total;
    });

    const labels = Object.keys(agrupados);
    const dataEne = labels.map(label => agrupados[label].ene);
    const dataFeb = labels.map(label => agrupados[label].feb);
    const dataTotal = labels.map(label => agrupados[label].total);

    if (graficaProduccion) {
        graficaProduccion.destroy();
    }

    graficaProduccion = new Chart(canvas, {
        type: "bar",
        data: {
            labels,
            datasets: [
                {
                    label: "Enero",
                    data: dataEne,
                    backgroundColor: "rgba(37, 99, 235, 0.75)",
                    borderColor: "rgba(37, 99, 235, 1)",
                    borderWidth: 1,
                    borderRadius: 6
                },
                {
                    label: "Febrero",
                    data: dataFeb,
                    backgroundColor: "rgba(6, 182, 212, 0.75)",
                    borderColor: "rgba(6, 182, 212, 1)",
                    borderWidth: 1,
                    borderRadius: 6
                },
                {
                    label: "Total Anual",
                    data: dataTotal,
                    backgroundColor: "rgba(30, 58, 138, 0.75)",
                    borderColor: "rgba(30, 58, 138, 1)",
                    borderWidth: 1,
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: "index",
                intersect: false
            },
            plugins: {
                legend: {
                    position: "top"
                },
                title: {
                    display: true,
                    text: "Indicadores de Producción y Rendimiento"
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${formatearNumero(context.raw)}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 20
                    }
                },
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function actualizarResumen(datos) {
    const totalIndicadores = document.getElementById("totalIndicadores");
    if (totalIndicadores) {
        totalIndicadores.textContent = datos.length;
    }
}

function actualizarEstado(texto) {
    const estado = document.getElementById("estadoCarga");
    if (estado) {
        estado.textContent = texto;
    }
}

function mostrarErrorEnTabla(mensaje) {
    const tbody = document.getElementById("tablaProduccionBody");
    if (!tbody) return;

    tbody.innerHTML = `
    <tr>
        <td colspan="9">${escapeHTML(mensaje)}</td>
    </tr>
    `;
}

function obtenerNumeroSeguro(valor) {
    if (valor === null || valor === undefined || valor === "") return 0;

    const numero = Number(String(valor).replace(/,/g, "").trim());
    return Number.isNaN(numero) ? 0 : numero;
}

function formatearNumero(valor) {
    if (valor === null || valor === undefined || valor === "") return "";

    const numero = Number(String(valor).replace(/,/g, "").trim());
    if (Number.isNaN(numero)) return escapeHTML(String(valor));

    return numero.toFixed(2);
}

function escapeHTML(valor) {
    return String(valor)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}