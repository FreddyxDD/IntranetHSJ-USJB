<?php
$usuario = $_SESSION['cirugias_usuario'] ?? '';
$rol = (int) ($_SESSION['cirugias_rol'] ?? 1);
$esAdmin = $rol === 0;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cirugías</title>

    <link rel="stylesheet" href="<?= e(url_path('/assets/css/principalLS.css')) ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">

    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>

    <!-- =========================================================
         BOTÓN TOGGLE SIDEBAR
         Oculta / muestra el panel lateral izquierdo
    ========================================================== -->
    <button
        type="button"
        id="btnToggleSidebar"
        class="btn-toggle-sidebar"
        aria-label="Ocultar o mostrar menú lateral"
        title="Ocultar o mostrar menú lateral"
    >
        <i class="fa-solid fa-bars-staggered"></i>
    </button>

    <div class="layout">
        <aside class="sidebar">
            <div class="logo">
                <i class="fa-solid fa-notes-medical"></i>
                <span>Historial Clínico</span>
            </div>

            <nav>
                <a href="#" id="menuInicio" class="active">
                    <i class="fa-solid fa-house"></i>
                    Inicio
                </a>

                <a href="#" id="menuAnalisis">
                    <i class="fa-solid fa-chart-line"></i>
                    Análisis
                </a>

                <a href="#" id="menuReportes">
                    <i class="fa-solid fa-file-lines"></i>
                    Reportes
                </a>

                <a href="#" id="menuGestion">
                    <i class="fa-solid fa-gear"></i>
                    Gestión
                </a>

                <a href="#" id="btnCerrarSesion" class="btn-logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Cerrar sesión
                </a>
            </nav>
        </aside>
        <main class="contenedor">

            <div id="vistaInicio">

                <header class="header header-cirugias">
                    <div>
                        <h1>Cirugías</h1>
                        <p>Listado de registros importados desde Excel.</p>
                    </div>

                    <div class="acciones-tabla acciones-header">
                        <input type="file" id="archivoExcel" accept=".xlsx,.xls" hidden>

                        <button id="btnSeleccionar" type="button">Seleccionar Excel</button>
                        <button id="btnImportar" type="button">Subir</button>
                        <button id="btnEliminar" type="button" class="btn-eliminar">Borrar datos</button>
                    </div>

                    <button id="btnModoOscuro" class="btn-dark" aria-hidden="true" tabindex="-1">
                        <i class="fa-solid fa-moon"></i>
                    </button>
                </header>

                <div class="kpis">

                    <div class="kpi-card azul">
                        <div class="kpi-icon">
                            <i class="fa-solid fa-file-medical"></i>
                        </div>

                        <div class="kpi-info">
                            <p>Total de registros</p>
                            <h2 id="totalRegistros">0</h2>
                        </div>
                    </div>

                    <div class="kpi-card verde">
                        <div class="kpi-icon">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>

                        <div class="kpi-info">
                            <p>Registros válidos</p>
                            <h2 id="regValidos">0</h2>
                        </div>
                    </div>

                    <div class="kpi-card naranja">
                        <div class="kpi-icon">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>

                        <div class="kpi-info">
                            <p>Con observaciones</p>
                            <h2 id="regObs">0</h2>
                        </div>
                    </div>

                    <div class="kpi-card morado">
                        <div class="kpi-icon">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>

                        <div class="kpi-info">
                            <p>Última importación</p>
                            <h2 id="ultimaImportacion">--</h2>
                        </div>
                    </div>

                </div>

                <p id="mensajeTabla" class="mensaje"></p>

                <div id="tabsHojas" class="tabs"></div>

                <div class="filtros-tabla">
                    <div class="filtros-left filtros-cirugias">
                        <input type="text" id="txtBusqueda" placeholder="Buscar por DNI o historia clinica">

                        <select id="filtroEspecialidadCirugia">
                            <option value="">Todas las especialidades</option>
                        </select>

                        <select id="filtroTipoOrdenCirugia">
                            <option value="">Todos los tipos</option>
                            <option value="EMERGENCIA">EMERGENCIA</option>
                            <option value="ELECTIVA">ELECTIVA</option>
                        </select>

                        <button id="btnBuscar" type="button">Buscar</button>
                        <button id="btnLimpiarBusqueda" type="button">Limpiar</button>
                    </div>

                    <button id="btnAgregar" type="button">Agregar registro</button>
                </div>

                <div id="tabsMeses" class="tabs"></div>

                <div class="tabla-contenedor tabla-cirugias">
                    <table>
                        <thead>
                            <tr>
                                <th>FECHA</th>
                                <th>HORA</th>
                                <th>HISTORIA CLÍNICA</th>
                                <th>DNI</th>
                                <th>NOMBRES Y APELLIDOS</th>
                                <th>TIPO DE ORDEN</th>
                                <th>ESPECIALIDAD</th>
                                <th>EDAD</th>
                                <th>SEXO</th>
                                <th>TIPO DE SEGURO</th>
                                <th>PRUEBA COVID</th>
                                <th>SUSPENSIÓN</th>
                                <th>MOTIVO DE SUSPENSIÓN</th>
                                <th>DIAGNÓSTICO PREOPERATORIO</th>
                                <th>CÓDIGO CIE 10</th>
                                <th>OPERACIÓN REALIZADA</th>
                                <th>COMORBILIDAD</th>
                                <th>REINTERVENCIÓN</th>
                                <th>RAM MEDICAMENTOS</th>
                                <th>DISCREPANCIA DIAGNÓSTICA</th>
                                <th>TIEMPO TOTAL</th>
                                <th>TIEMPO ANESTESIA</th>
                                <th>TIEMPO OPERACIÓN</th>
                                <th>COMPLICACIONES INTRAOPERATORIAS</th>
                                <th>CIRUJANO 1</th>
                                <th>CIRUJANO 2</th>
                                <th>ANESTESIÓLOGO</th>
                                <th>ENFERMERA INSTRUMENTISTA</th>
                                <th>ANESTESIÓLOGO RECUPERACIÓN</th>
                                <th>ENFERMERA RECUPERACIÓN</th>
                                <th>TÉCNICO DE ENFERMERÍA 1</th>
                                <th>TÉCNICO DE ENFERMERÍA 2</th>
                                <th>TIPO DE ANESTESIA</th>
                                <th>CIRUGÍA MAYOR</th>
                                <th>CIRUGÍA MENOR</th>
                                <th>SOP</th>
                                <th>DESTINO</th>
                                <th id="colTiempoUrpa">TIEMPO URPA</th>
                                <th>OBSERVACIONES</th>
                            </tr>
                        </thead>

                        <tbody id="tablaCirugias"></tbody>
                    </table>
                </div>
            </div>

            <div id="vistaAnalisis" style="display:none;">

                <section class="analisis-dashboard">

                    <div class="analisis-hero">
                        <div>
                            <h1>Análisis de cirugías</h1>
                            <p>Indicadores y comportamiento quirúrgico</p>
                        </div>

                        <div class="analisis-decoracion">
                            <i class="fa-solid fa-heart-pulse"></i>
                            <i class="fa-solid fa-plus"></i>
                        </div>
                    </div>

                    <div class="analisis-toolbar">
                        <div class="filtro-grafico filtro-grafico-moderno">
                            <label for="selectMesGrafico">Seleccionar mes</label>

                            <div class="select-mes-box">
                                <i class="fa-regular fa-calendar"></i>

                                <select id="selectMesGrafico">
                                    <option value="">Cargando meses...</option>
                                </select>
                            </div>
                        </div>

                        <button type="button" id="btnExportarAnalisis" class="btn-exportar-analisis">
                            <i class="fa-solid fa-download"></i>
                            Exportar
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </div>

                    <div class="analisis-kpis">

                        <article class="analisis-card analisis-card-total">
                            <div class="analisis-card-icon analisis-card-icon-total">
                                <img src="<?= e(url_path('/assets/icon/Total_cirugias.png')) ?>" alt="Total cirugías">
                            </div>

                            <div class="analisis-card-info">
                                <p>Total cirugías</p>
                                <h2 id="kpiTotalCirugias">0</h2>
                                <span>100% del total</span>
                            </div>
                        </article>

                        <article class="analisis-card analisis-card-emergencia">
                            <div class="analisis-card-icon analisis-card-icon-emergencia">
                                <img src="<?= e(url_path('/assets/icon/Emergencias.png')) ?>" alt="Emergencia">
                            </div>

                            <div class="analisis-card-info">
                                <p>Emergencia</p>
                                <h2 id="kpiEmergencia">0</h2>
                                <span id="kpiEmergenciaPorcentaje">0% del total</span>
                            </div>
                        </article>

                        <article class="analisis-card analisis-card-electiva">
                            <div class="analisis-card-icon analisis-card-icon-electiva">
                                <img src="<?= e(url_path('/assets/icon/Electiva.png')) ?>" alt="Electiva">
                            </div>

                            <div class="analisis-card-info">
                                <p>Electiva</p>
                                <h2 id="kpiElectiva">0</h2>
                                <span id="kpiElectivaPorcentaje">0% del total</span>
                            </div>
                        </article>

                        <article class="analisis-card analisis-card-urgencia">
                            <div class="analisis-card-icon analisis-card-icon-urgencia">
                                <img src="<?= e(url_path('/assets/icon/Tasa_Urgencia.png')) ?>" alt="Tasa de urgencia">
                            </div>

                            <div class="analisis-card-info">
                                <p>Tasa de urgencia</p>
                                <h2 id="kpiTasaUrgencia">0%</h2>
                                <span>Emergencias / Total</span>
                            </div>
                        </article>

                    </div>

                    <div class="analisis-layout">

                        <section class="grafico-card grafico-card-principal">
                            <div class="grafico-card-header">
                                <div>
                                    <h3>Tipo de orden - <span id="tituloMesGrafico">Mes seleccionado</span></h3>

                                    <div class="leyenda-grafico">
                                        <span>
                                            <i class="leyenda-color emergencia"></i>
                                            Emergencia
                                        </span>

                                        <span>
                                            <i class="leyenda-color electiva"></i>
                                            Electiva
                                        </span>
                                    </div>
                                </div>

                                <div class="grafico-card-acciones">
                                    <span id="badgeTotalGrafico" class="badge-total-grafico">
                                        <i class="fa-solid fa-circle-info"></i>
                                        Total: 0 cirugías
                                    </span>

                                    <button type="button" id="btnAmpliarGraficoOrdenes" class="btn-ampliar-grafico">
                                        <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                                        Ampliar gráfico
                                    </button>
                                </div>
                            </div>

                            <div class="grafico-contenedor grafico-principal">
                                <canvas id="graficoOrdenes"></canvas>
                            </div>
                        </section>

                        <aside class="analisis-panel-derecho">

                            <section class="grafico-card grafico-card-dona">
                                <div class="grafico-card-header simple">
                                    <h3>Distribución de órdenes</h3>
                                </div>

                                <div class="dona-layout">
                                    <div class="dona-canvas">
                                        <canvas id="graficoDistribucionOrdenes"></canvas>
                                    </div>

                                    <div class="dona-leyenda">
                                        <div class="dona-item">
                                            <span class="dona-color emergencia"></span>

                                            <div>
                                                <p>Emergencia</p>
                                                <strong id="donaEmergenciaTexto">0% (0)</strong>
                                            </div>
                                        </div>

                                        <div class="dona-item">
                                            <span class="dona-color electiva"></span>

                                            <div>
                                                <p>Electiva</p>
                                                <strong id="donaElectivaTexto">0% (0)</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="grafico-card resumen-periodo">
                                <div class="grafico-card-header simple">
                                    <h3>Resumen del período</h3>
                                </div>

                                <div class="resumen-lista">
                                    <div class="resumen-item">
                                        <i class="fa-regular fa-calendar"></i>
                                        <span>Período</span>
                                        <strong id="resumenPeriodo">--</strong>
                                    </div>

                                    <div class="resumen-item">
                                        <i class="fa-regular fa-clock"></i>
                                        <span>Promedio diario</span>
                                        <strong id="resumenPromedioDiario">--</strong>
                                    </div>

                                    <div class="resumen-item">
                                        <i class="fa-solid fa-arrow-trend-up"></i>
                                        <span>Día con más cirugías</span>
                                        <strong id="resumenDiaMayor">--</strong>
                                    </div>
                                </div>
                            </section>

                        </aside>

                    </div>

                    <div id="panelEspecialidades" class="grafico-contenedor grafico-detalle">
                        <div class="grafico-detalle-header">
                            <div>
                                <h3 id="tituloGraficoEspecialidades">Especialidades</h3>
                                <p id="subtituloGraficoEspecialidades">
                                    Selecciona una barra para ver el detalle.
                                </p>
                            </div>

                            <button type="button" id="btnCerrarEspecialidades">
                                Cerrar
                            </button>
                        </div>

                        <div id="contenedorGraficoEspecialidades" class="especialidad-grafico-box">
                            <canvas id="graficoEspecialidades"></canvas>
                        </div>

                        <div id="panelDetalleEspecialidad" class="panel-detalle-especialidad">
                            <div class="detalle-header">
                                <div class="detalle-header-info">
                                    <h3 id="tituloDetalleEspecialidad">Detalle de especialidad</h3>
                                    <p id="subtituloDetalleEspecialidad">
                                        Selecciona una especialidad del gráfico.
                                    </p>
                                </div>

                                <div class="detalle-header-derecha">
                                    <div class="detalle-filtros detalle-filtros-header">
                                        <input
                                            type="text"
                                            id="txtBuscarPersonalDetalle"
                                            placeholder="Buscar por cirujano o anestesiólogo"
                                        >

                                        <button type="button" id="btnBuscarPersonalDetalle">Buscar</button>
                                        <button type="button" id="btnLimpiarPersonalDetalle">Limpiar</button>
                                    </div>

                                    <button type="button" id="btnVolverEspecialidades" class="btn-volver-especialidades">
                                        ← Regresar
                                    </button>
                                </div>
                            </div>

                            <div class="tabla-detalle-wrapper">
                                <table class="tabla-detalle-especialidad">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Hora</th>
                                            <th>Historia</th>
                                            <th>DNI</th>
                                            <th>Paciente</th>
                                            <th>Edad</th>
                                            <th>Sexo</th>
                                            <th>Diagnóstico</th>
                                            <th>Operación</th>
                                            <th>Cirujano</th>
                                            <th>Anestesiólogo</th>
                                            <th>Destino</th>
                                        </tr>
                                    </thead>

                                    <tbody id="tablaDetalleEspecialidad">
                                        <tr>
                                            <td colspan="12">Selecciona una especialidad.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </section>

                <div id="modalGraficoOrdenes" class="modal-grafico-analisis">
                    <div class="modal-grafico-backdrop"></div>

                    <div class="modal-grafico-contenido">
                        <div class="modal-grafico-header">
                            <div>
                                <h3>Tipo de orden - <span id="tituloMesGraficoModal">Mes seleccionado</span></h3>

                                <div class="leyenda-grafico">
                                    <span>
                                        <i class="leyenda-color emergencia"></i>
                                        Emergencia
                                    </span>

                                    <span>
                                        <i class="leyenda-color electiva"></i>
                                        Electiva
                                    </span>
                                </div>
                            </div>

                            <div class="modal-grafico-actions">
                                <button type="button" id="btnMaximizarGraficoOrdenes" class="btn-modal-grafico">
                                    <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
                                </button>

                                <button type="button" id="btnCerrarModalGraficoOrdenes" class="btn-modal-grafico">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>

                        <div class="modal-grafico-canvas">
                            <canvas id="graficoOrdenesModal"></canvas>
                        </div>
                    </div>
                </div>

            </div>
<!-- =========================================================
     VISTA REPORTES MENSUALES
========================================================== -->
<div id="vistaReportes" style="display:none;">
    <section class="reportes-dashboard">

        <div class="reportes-hero">
            <div>
                <h1>Reportes de cirugías</h1>
                <p>Reporte mensual por servicio, tipo de orden y tipo de cirugía.</p>
            </div>

            <div class="reportes-hero-icon">
                <i class="fa-solid fa-file-medical-alt"></i>
            </div>
        </div>

        <div class="reportes-toolbar">
            <div class="filtro-reporte-mes">
                <label for="selectMesReporte">Seleccionar mes</label>

                <div class="select-mes-box select-mes-reporte-box">
                    <i class="fa-regular fa-calendar"></i>

                    <select id="selectMesReporte">
                        <option value="">Cargando meses...</option>
                    </select>
                </div>
            </div>

            <div class="reportes-acciones">
                <button type="button" id="btnActualizarReporte" class="btn-reporte-accion">
                    <i class="fa-solid fa-rotate-right"></i>
                    Actualizar
                </button>

                <button type="button" id="btnImprimirReporte" class="btn-reporte-accion secundario">
                    <i class="fa-solid fa-print"></i>
                    Imprimir
                </button>
            </div>
        </div>

        <div class="reportes-kpis">
            <article class="reporte-kpi">
                <span>Total cirugías</span>
                <strong id="reporteTotalCirugias">0</strong>
            </article>

            <article class="reporte-kpi electiva">
                <span>Electivas</span>
                <strong id="reporteTotalElectivas">0</strong>
            </article>

            <article class="reporte-kpi emergencia">
                <span>Emergencias</span>
                <strong id="reporteTotalEmergencias">0</strong>
            </article>

            <article class="reporte-kpi horas">
                <span>Total horas</span>
                <strong id="reporteTotalHoras">0</strong>
            </article>
        </div>

        <section class="reporte-tabla-card" id="areaReporteMensual">
            <div class="reporte-encabezado-documento">
                <h2>CIRUGÍAS REALIZADAS EN EL CENTRO QUIRÚRGICO DEL HOSPITAL SAN JOSÉ DE CHINCHA</h2>
                <p>NÚMERO DE SALAS OPERATIVAS 02 (UNA PARA ELECTIVAS Y UNA PARA EMERGENCIAS)</p>
                <h3 id="tituloReporteMensual">MES SELECCIONADO</h3>
            </div>

            <div class="tabla-reporte-scroll">
                <table class="tabla-reporte-mensual">
                    <thead>
                        <tr>
                            <th rowspan="3" class="col-servicio-reporte">SERVICIO</th>
                            <th colspan="8">CIRUGÍAS ELECTIVAS</th>
                            <th colspan="8">CIRUGÍAS DE EMERGENCIAS</th>
                        </tr>

                        <tr>
                            <th colspan="4">CIRUGÍA MAYOR</th>
                            <th colspan="4">CIRUGÍA MENOR</th>
                            <th colspan="4">CIRUGÍA MAYOR</th>
                            <th colspan="4">CIRUGÍA MENOR</th>
                        </tr>

                        <tr>
                            <th>N° CIRUGÍA</th>
                            <th>T. OPERATORIO</th>
                            <th>T. ANESTÉSICO</th>
                            <th>T. TOTAL DE HRS</th>

                            <th>N° CIRUGÍA</th>
                            <th>T. OPERATORIO</th>
                            <th>T. ANESTÉSICO</th>
                            <th>T. TOTAL DE HRS</th>

                            <th>N° CIRUGÍA</th>
                            <th>T. OPERATORIO</th>
                            <th>T. ANESTÉSICO</th>
                            <th>T. TOTAL DE HRS</th>

                            <th>N° CIRUGÍA</th>
                            <th>T. OPERATORIO</th>
                            <th>T. ANESTÉSICO</th>
                            <th>T. TOTAL DE HRS</th>
                        </tr>
                    </thead>

                    <tbody id="tablaReporteMensual">
                        <tr>
                            <td colspan="17">Selecciona un mes para cargar el reporte.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="fuente-reporte">FUENTE: LIBRO DE ANESTESIA Y FICHAS DEL CENTRO QUIRÚRGICO</p>
        </section>

        <div class="reportes-resumenes reportes-resumenes-compacto">

    <section class="reporte-mini-tabla reporte-mini-tabla-compacta">
        <h3 id="tituloElectivasReporte">CIRUGÍAS ELECTIVAS REALIZADAS</h3>

        <table class="tabla-resumen-compacta">
            <tbody>
                <tr>
                    <th rowspan="2" class="titulo-lateral-resumen">
                        CIRUGÍAS ELECTIVAS
                    </th>

                    <th>CIRUGÍA<br>MAYOR</th>
                    <th>CIRUGÍA<br>MENOR</th>
                    <th>TOTAL</th>
                </tr>

                <tr>
                    <td id="reporteElectivaMayor">0</td>
                    <td id="reporteElectivaMenor">0</td>
                    <td id="reporteElectivaTotal">0</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="reporte-mini-tabla reporte-mini-tabla-compacta">
        <h3 id="tituloEmergenciasReporte">CIRUGÍAS EMERGENCIAS REALIZADAS</h3>

        <table class="tabla-resumen-compacta">
            <tbody>
                <tr>
                    <th rowspan="2" class="titulo-lateral-resumen">
                        CIRUGÍAS EMERGENCIAS
                    </th>

                    <th>CIRUGÍA<br>MAYOR</th>
                    <th>CIRUGÍA<br>MENOR</th>
                    <th>TOTAL</th>
                </tr>

                <tr>
                    <td id="reporteEmergenciaMayor">0</td>
                    <td id="reporteEmergenciaMenor">0</td>
                    <td id="reporteEmergenciaTotal">0</td>
                </tr>
            </tbody>
        </table>
    </section>

        </div>

    </section>
</div>
            <div id="vistaBusqueda" style="display:none;">

                <header class="header">
                    <div>
                        <h1>Búsqueda de pacientes</h1>
                        <p>Busca pacientes por historia clínica, nombres o apellidos.</p>
                    </div>
                </header>

                <div class="busqueda-pacientes-card">

                    <div class="busqueda-pacientes-header">
                        <div>
                            <h3>Consultar paciente</h3>
                            <p>Los datos se extraen desde la tabla dbo.Pacientes.</p>
                        </div>
                    </div>

                    <div class="filtros-left busqueda-pacientes">
                        <input type="text" id="txtBusquedaPaciente"
                            placeholder="Buscar por historia clínica, nombres o apellidos">

                        <button id="btnBuscarPaciente" type="button">
                            Buscar
                        </button>

                        <button id="btnLimpiarPaciente" type="button">
                            Limpiar
                        </button>
                    </div>

                    <p id="mensajeBusquedaPaciente" class="mensaje"></p>

                    <div class="tabla-contenedor tabla-pacientes">
                        <table class="tabla-busqueda-pacientes">
                            <thead>
                                <tr>
                                    <th>Historia clínica</th>
                                    <th>ID Paciente</th>
                                    <th>DNI</th>
                                    <th>Apellido paterno</th>
                                    <th>Apellido materno</th>
                                    <th>Primer nombre</th>
                                    <th>Segundo nombre</th>
                                    <th>Tercer nombre</th>
                                    <th>Sexo</th>
                                    <th>Fecha nacimiento</th>
                                    <th>Teléfono</th>
                                    <th>Dirección</th>
                                    <th>Observación</th>
                                </tr>
                            </thead>

                            <tbody id="tablaBusquedaPacientes"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div id="vistaImportaciones" style="display:none;">
                <header class="header">
                    <div>
                        <h1>Historial de Importaciones</h1>
                        <p>Listado detallado de todas las importaciones cargadas en el sistema.</p>
                    </div>
                </header>

                <div class="filtros-tabla">
                    <div class="filtros-left">
                        <input type="text" id="txtBuscarImportacion" placeholder="Buscar por archivo o usuario...">
                        <button id="btnLimpiarImportaciones" type="button" class="btn-cancelar" style="padding: 9px 15px; border-radius: var(--radius-md, 8px);">
                            Limpiar
                        </button>
                    </div>
                </div>

                <div class="tabla-contenedor">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>FECHA DE CARGA</th>
                                <th>NOMBRE DEL ARCHIVO</th>
                                <th>HOJA / PESTAÑA</th>
                                <th>TOTAL REGISTROS</th>
                                <th>REG. VÁLIDOS</th>
                                <th>CON OBSERVACIONES</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="tablaHistorialImportaciones">
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 20px; color: #64748b;">
                                    No hay importaciones registradas o cargando...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="vistaGestion" style="display:none;">
                <header class="header">
                    <div>
                        <h1>Gestión</h1>
                        <p>Administración de tablas</p>
                    </div>
                </header>

                <div class="gestion-tabs">
                    <button class="gestion-tab activo" data-tab="especialidades">Especialidades</button>
                    <button class="gestion-tab" data-tab="cie10">CIE 10</button>
                    <button class="gestion-tab" data-tab="personal">Personal médico</button>
                    <button class="gestion-tab" data-tab="procedimientos">Procedimientos</button>
                </div>
                <div class="gestion-grid">

                    <section class="gestion-card gestion-panel activo" data-panel="especialidades">

                        <div class="gestion-card-header">
                            <div>
                                <h3>Especialidades</h3>
                            </div>
                        </div>

                        <div class="filtros-tabla gestion-form">
                            <div class="filtros-left">
                                <input type="text" id="txtNuevaEspecialidad" placeholder="Nueva especialidad">
                                <button id="btnGuardarEspecialidad" type="button">Guardar</button>
                            </div>
                        </div>

                        <div class="tabla-contenedor tabla-gestion tabla-especialidades">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Especialidad</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaEspecialidades"></tbody>
                            </table>
                        </div>
                    </section>

                    <section class="gestion-card gestion-panel" data-panel="cie10">

                        <div class="gestion-card-header">
                            <div>
                                <h3>Código CIE 10</h3>
                            </div>
                        </div>

                        <div class="filtros-tabla gestion-form">
                            <div class="filtros-left filtros-cie10">
                                <input type="text" id="txtBuscarCIE" placeholder="Buscar CIE 10 o diagnóstico">

                                <select id="filtroEstadoCIE"></select>

                                <select id="filtroSexoCIE"></select>

                                <button id="btnLimpiarCIE10" class="btn-limpiar-filtro" type="button">
                                    <i class="fa-solid fa-filter-circle-xmark"></i>
                                </button>
                            </div>
                        </div>

                        <div class="tabla-contenedor tabla-gestion tabla-cie10-contenedor">
                            <table class="tabla-cie10">
                                <colgroup>
                                    <col class="col-codigo">
                                    <col class="col-descripcion">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Descripción</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaCIE10"></tbody>
                            </table>
                        </div>

                    </section>

                    <section class="gestion-card gestion-card-personal gestion-panel" data-panel="personal">

                        <div class="gestion-card-header">
                            <div>
                                <h3>Personal médico</h3>
                            </div>
                        </div>

                        <div class="filtros-tabla gestion-form filtros-personal">
                            <div class="filtros-left filtros-personal-left">
                                <input type="text" id="txtBuscarPersonal"
                                    placeholder="Buscar por DNI o apellidos y nombres">

                                <select id="filtroProfesionPersonal">
                                    <option value="">Todas las profesiones</option>
                                </select>

                                <button id="btnLimpiarPersonal" class="btn-limpiar-filtro" type="button">
                                    <i class="fa-solid fa-filter-circle-xmark"></i>
                                </button>
                            </div>


                            <button type="button" id="btnAgregarPersonal">
                                <i class="fa-solid fa-plus"></i>
                                Agregar
                            </button>
                        </div>

                        <div class="tabla-contenedor tabla-gestion tabla-personal">
                            <table>
                                <thead>
                                    <tr>
                                        <th>DNI</th>
                                        <th>Apellidos y nombres</th>
                                        <th>Profesión</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaPersonalMedico"></tbody>
                            </table>
                        </div>

                    </section>

                    <section class="gestion-card gestion-card-procedimientos gestion-panel" data-panel="procedimientos">

                        <div class="gestion-card-header">
                            <div>
                                <h3>Procedimientos</h3>
                            </div>
                        </div>

                        <div class="filtros-tabla gestion-form filtros-procedimientos">
                            <div class="filtros-left filtros-procedimientos-left">
                                <input type="text" id="txtBuscarProcedimiento"
                                    placeholder="Buscar por código o procedimiento">

                                <select id="filtroSeccionProcedimiento">
                                    <option value="">Todas las secciones</option>
                                </select>

                                <button id="btnLimpiarProcedimientos" class="btn-limpiar-filtro" type="button">
                                    <i class="fa-solid fa-filter-circle-xmark"></i>
                                </button>
                            </div>
                        </div>

                        <div class="tabla-contenedor tabla-gestion tabla-procedimientos">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Procedimiento</th>
                                        <th>Sección</th>
                                        <th>Subsección</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaProcedimientos"></tbody>
                            </table>
                        </div>

                    </section>
                </div>
            </div>

            <div id="modalForm" class="modal">
                <div class="modal-contenido modal-grande">

                    <div class="modal-header">
                        <div>
                            <h2>Nuevo registro de cirugía</h2>
                            <p>Completa los datos por pasos.</p>
                        </div>
                        <button type="button" id="btnCerrarModal" class="btn-cerrar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="steps">
                        <div class="step activo">1</div>
                        <div class="step">2</div>
                        <div class="step">3</div>
                        <div class="step">4</div>
                        <div class="step">5</div>
                        <div class="step">6</div>
                    </div>

                    <form id="formRegistro">
                        <div class="form-step activo">

                            <h3>Datos del paciente</h3>
                            <div class="grid-form grid-paciente">
                                <div class="campo campo-fecha">
                                    <label>Fecha</label>
                                    <input type="date" id="fecha" required>
                                </div>

                                <div class="campo campo-hora">
                                    <label>Hora</label>
                                    <input type="time" id="hora">
                                </div>

                                <div class="campo campo-hc">
                                    <label>Historia Clínica</label>
                                    <input type="text" id="historia_clinica">
                                </div>

                                <div class="campo campo-dni">
                                    <label>DNI</label>
                                    <input type="text" id="dni" maxlength="8">
                                </div>

                                <div class="campo campo-nombre">
                                    <label>Nombres y Apellidos</label>
                                    <input type="text" id="nombres_apellidos">
                                </div>

                                <div class="campo campo-edad">
                                    <label>Edad</label>
                                    <input type="number" id="edad" min="0">
                                </div>

                                <div class="campo campo-sexo">
                                    <label>Sexo</label>
                                    <select id="sexo">
                                        <option value="">Seleccione</option>
                                        <option value="FEMENINO">FEMENINO</option>
                                        <option value="MASCULINO">MASCULINO</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-step">
                            <h3>Datos de atención</h3>
                            <div class="grid-form grid-atencion">
                                <div class="campo campo-orden">
                                    <label>Tipo de Orden</label>
                                    <select id="tipo_orden">
                                        <option value="">Seleccione</option>
                                        <option value="EMERGENCIA">EMERGENCIA</option>
                                        <option value="ELECTIVA">ELECTIVA</option>
                                    </select>
                                </div>

                                <div class="campo campo-especialidad">
                                    <label>Especialidad</label>
                                    <select id="especialidad">
                                        <option value="">Seleccione</option>
                                    </select>
                                </div>

                                <div class="campo campo-seguro">
                                    <label>Tipo de Seguro</label>
                                    <select id="tipo_seguro">
                                        <option value="">Ninguno</option>
                                        <option value="SIS">SIS</option>
                                        <option value="ESSALUD">ESSALUD</option>
                                        <option value="PARTICULAR">PARTICULAR</option>
                                        <option value="SOAT">SOAT</option>
                                        <option value="FOSPOLI">FOSPOLI</option>
                                    </select>
                                </div>

                                <div class="campo campo-covid">
                                    <label>Prueba COVID</label>
                                    <select id="prueba_covid">
                                        <option value="NO TIENE">NO TIENE</option>
                                        <option value="SI TIENE">SI TIENE</option>
                                    </select>
                                </div>

                                <div class="campo campo-suspension">
                                    <label>Suspensión</label>
                                    <select id="suspension">
                                        <option value="NO">NO</option>
                                        <option value="SI">SI</option>
                                    </select>
                                </div>

                                <div class="campo campo-motivo" id="campoMotivoSuspension">
                                    <label>Motivo de Suspensión</label>
                                    <textarea id="motivo_suspension" placeholder="Escriba el motivo"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-step">
                            <h3>Procedimiento</h3>
                            <div class="grid-form grid-diagnostico">

                                <div class="campo campo-cie">
                                    <label>Código CIE 10</label>
                                    <input type="text" id="codigo_cie10" placeholder="Ej: K35">
                                    <div id="sugerenciasCIE10" class="sugerencias-cie"></div>
                                </div>

                                <div class="campo campo-diagnostico">
                                    <label>Diagnóstico Preoperatorio</label>
                                    <textarea id="diagnostico_preoperatorio"></textarea>
                                </div>

                                <div class="campo campo-operacion">
                                    <label>Operación Realizada</label>
                                    <textarea id="operacion_realizada"
                                        placeholder="Buscar por código o procedimiento"></textarea>

                                    <div id="sugerenciasProcedimientos" class="sugerencias-procedimiento"></div>
                                </div>

                                <div class="campo campo-cirugia">
                                    <label>Tipo de Cirugía</label>
                                    <div class="radio-group">
                                        <label><input type="radio" name="tipo_cirugia" value="MAYOR"> Mayor</label>
                                        <label><input type="radio" name="tipo_cirugia" value="MENOR"> Menor</label>
                                    </div>
                                </div>

                                <div class="campo campo-sop">
                                    <label>SOP</label>
                                    <select id="sop">
                                        <option value="">Seleccione</option>
                                        <option value="SOP 1">SOP 1</option>
                                        <option value="SOP 2">SOP 2</option>
                                        <option value="SOP 3">SOP 3</option>
                                    </select>
                                </div>

                                <div class="campo campo-destino">
                                    <label>Destino</label>
                                    <select id="destino">
                                        <option value="">Seleccione</option>
                                        <option value="URPA">URPA</option>
                                        <option value="AMBULATORIO">AMBULATORIO</option>
                                        <option value="TRAUMA SHOCK">TRAUMA SHOCK</option>
                                        <option value="UVI">UVI</option>
                                    </select>
                                </div>

                                <div class="campo campo-urpa" id="campoUrpa">
                                    <label>Tiempo URPA</label>
                                    <input type="text" id="tiempo_urpa">
                                </div>

                            </div>
                        </div>

                <div class="form-step">
                    <h3 id="tituloPersonalMedico">Personal médico sala</h3>

                    <div class="grid-form grid-personal grid-personal-dividido">
                        <!-- LADO IZQUIERDO -->
                        <div class="personal-columna personal-columna-sala">
                            <h4>Personal Médico de Sala</h4>

                            <div class="campo campo-cirujano1">
                                <label>Cirujano 1</label>
                                <input type="text" id="cirujano_1">
                            </div>

                            <div class="campo campo-cirujano2">
                                <label>Cirujano 2</label>
                                <input type="text" id="cirujano_2">
                            </div>

                            <div class="campo campo-anestesiologo">
                                <label>Anestesiólogo</label>
                                <input type="text" id="anestesiologo">
                            </div>

                            <div class="campo campo-instrumentista">
                                <label>Enfermera Instrumentista</label>
                                <input type="text" id="enfermera_instrumentista">
                            </div>
                        </div>

                        <!-- BARRA DEL MEDIO -->
                        <div class="separador-personal personal-recuperacion"></div>

                        <!-- LADO DERECHO: SOLO URPA -->
                        <div class="personal-columna personal-columna-recuperacion personal-recuperacion">
                            <h4>Personal Médico de Recuperación</h4>

                            <div class="campo campo-anest-rec">
                                <label>Anestesiólogo Recuperación</label>
                                <input type="text" id="anestesiologo_recuperacion">
                            </div>

                            <div class="campo campo-enf-rec">
                                <label>Enfermera Recuperación</label>
                                <input type="text" id="enfermera_recuperacion">
                            </div>

                            <div class="campo campo-tecnico1">
                                <label>Técnico de Enfermería 1</label>
                                <input type="text" id="tecnico_enfermeria_1">
                            </div>

                            <div class="campo campo-tecnico2">
                                <label>Técnico de Enfermería 2</label>
                                <input type="text" id="tecnico_enfermeria_2">
                            </div>
                        </div>
                    </div>
                </div>      
                        <div class="form-step">
                            <h3>Tiempos y eventos</h3>
                            <div class="grid-form grid-tiempo">

                                <div class="campo campo-total">
                                    <label>Tiempo Total</label>
                                    <input type="text" id="tiempo_total">
                                </div>

                                <div class="campo campo-anestesia">
                                    <label>Tiempo Anestesia</label>
                                    <input type="text" id="tiempo_anestesia">
                                </div>

                                <div class="campo campo-operacion">
                                    <label>Tiempo Operación</label>
                                    <input type="text" id="tiempo_operacion">
                                </div>

                                <div class="campo campo-comorbilidad">
                                    <label>Comorbilidad</label>
                                    <input type="text" id="comorbilidad" value="NINGUNA">
                                </div>

                                <div class="campo campo-reintervencion">
                                    <label>Reintervención</label>
                                    <select id="reintervencion">
                                        <option value="NO">NO</option>
                                        <option value="SI">SI</option>
                                    </select>
                                </div>

                                <div class="campo campo-ram">
                                    <label>RAM Medicamentos</label>
                                    <select id="tiene_ram">
                                        <option value="NO">NO</option>
                                        <option value="SI">SI</option>
                                    </select>
                                </div>

                                <div class="campo campo-ram-detalle" id="campoRamMedicamento" style="display:none;">
                                    <label>Medicamento</label>
                                    <input type="text" id="ram_medicamentos" placeholder="Ingrese el medicamento">
                                </div>

                                <div class="campo campo-tipo-anestesia">
                                    <label>Tipo de Anestesia</label>
                                    <select id="tipo_anestesia">
                                        <option value="">Seleccione</option>
                                        <option value="AGII">AGII</option>
                                        <option value="GEV">GEV</option>
                                        <option value="RAQUIDEA">RAQUIDEA</option>
                                        <option value="GEV + LOCAL">GEV + LOCAL</option>
                                        <option value="LOCAL">LOCAL</option>
                                        <option value="BALANCEADO">BALANCEADO</option>
                                        <option value="TIVA">TIVA</option>
                                    </select>
                                </div>

                                <div class="campo campo-discrepancia">
                                    <label>Discrepancia Diagnóstica</label>
                                    <select id="discrepancia_diagnostica">
                                        <option value="NO">NO</option>
                                        <option value="SI">SI</option>
                                    </select>
                                </div>

                            </div>
                        </div>

                        <div class="form-step">
                            <h3>Cierre quirúrgico</h3>
                            <div class="grid-form grid-anestesia">

                                <div class="campo campo-complicaciones">
                                    <label>Complicaciones Intraoperatorias</label>
                                    <textarea id="complicaciones_intraoperatorias">NINGUNA</textarea>
                                </div>

                                <div class="campo campo-observaciones">
                                    <label>Observaciones</label>
                                    <textarea id="observaciones"></textarea>
                                </div>

                            </div>
                        </div>

                        <div class="acciones-modal">
                            <button type="button" id="btnCerrarModal2" class="btn-cancelar">Cancelar</button>
                            <button type="button" id="btnPrev">Anterior</button>
                            <button type="button" id="btnNext">Siguiente</button>
                            <button type="submit" id="btnGuardar" class="btn-guardar">Guardar</button>
                        </div>
                    </form>

                </div>
            </div>

            <div id="modalVista" class="modal">
                <div class="modal-contenido modal-detalle">

                    <div class="modal-header">
                        <div>
                            <h2>Detalle del registro</h2>
                            <p>Información completa de la cirugía seleccionada.</p>
                        </div>

                        <button type="button" id="btnCerrarVista" class="btn-cerrar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                    </div>

                    <form id="formVista">
                        <div class="tabs-detalle" id="tabsDetalle"></div>

                        <div class="detalle-contenido" id="contenedorVista"></div>

                        <div class="acciones-modal">

                            <button type="button" id="btnEditarVista" class="btn-secundario">
                                Editar
                            </button>

                            <button type="button" id="btnGuardarVista" class="btn-guardar" style="display:none;">
                                Guardar cambios
                            </button>

                            <button type="button" id="btnCerrarVista2" class="btn-cancelar">
                                Cerrar
                            </button>

                        </div>
                    </form>

                </div>
            </div>

            <div id="modalPersonalMedico" class="modal">
                <div class="modal-contenido modal-personal">

                    <div class="modal-header">
                        <div>
                            <h2 id="tituloModalPersonal">Editar personal médico</h2>
                            <p id="subtituloModalPersonal">
                                Actualiza la información registrada.
                            </p>
                        </div>

                        <button type="button" id="btnCerrarModalPersonal" class="btn-cerrar">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form id="formPersonalMedico">
                        <input type="hidden" id="personal_id">

                        <div class="grid-form grid-personal-modal">

                            <div class="campo campo-dni">
                                <label>DNI</label>
                                <input type="text" id="personal_dni" maxlength="15">
                            </div>

                            <div class="campo campo-nombre">
                                <label>Apellidos y nombres</label>
                                <input type="text" id="personal_apellidos_nombres" required>
                            </div>

                            <div class="campo campo-profesion">
                                <label>Profesión</label>
                                <input type="text" id="personal_profesion" list="listaProfesionesPersonal"
                                    placeholder="Seleccione o escriba una profesión">

                                <datalist id="listaProfesionesPersonal"></datalist>
                            </div>

                            <div class="campo campo-modalidad">
                                <label>Modalidad de contrato</label>
                                <input type="text" id="personal_modalidad_contrato">
                            </div>

                            <div class="campo campo-colegio">
                                <label>Colegio profesional</label>
                                <input type="text" id="personal_colegio_profesional">
                            </div>

                            <div class="campo campo-colegiatura">
                                <label>Número de colegiatura</label>
                                <input type="text" id="personal_numero_colegiatura">
                            </div>

                            <div class="campo campo-registro">
                                <label>Registro especialidad</label>
                                <input type="text" id="personal_registro_especialidad">
                            </div>

                            <div class="campo campo-estado">
                                <label>Estado</label>
                                <select id="personal_estado">
                                    <option value="ACTIVO">ACTIVO</option>
                                    <option value="INACTIVO">INACTIVO</option>
                                </select>
                            </div>

                        </div>

                        <div class="acciones-modal">
                            <button type="button" id="btnCancelarPersonal" class="btn-cancelar">
                                Cancelar
                            </button>

                            <button type="submit" class="btn-guardar" id="btnGuardarPersonal">
                                Guardar cambios
                            </button>
                        </div>
                    </form>

                </div>
            </div>
            
            <div id="toastContainer" class="toast-container"></div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    window.APP_BASE = "<?= e(app_base()) ?>";
    window.CIRUGIAS_USUARIO = "<?= e($usuario) ?>";
    window.CIRUGIAS_ROL = <?= (int) $rol ?>;
    window.CIRUGIAS_ES_ADMIN = <?= $esAdmin ? 'true' : 'false' ?>;

    const fetchOriginalCirugias = window.fetch.bind(window);

    window.fetch = function (input, options) {
        if (typeof input === "string" && input.startsWith("/")) {
            return fetchOriginalCirugias(window.APP_BASE + input, options);
        }

        return fetchOriginalCirugias(input, options);
    };
</script>

<script src="<?= e(url_path('/assets/js/principalLS.js')) ?>"></script>

</body>

</html>