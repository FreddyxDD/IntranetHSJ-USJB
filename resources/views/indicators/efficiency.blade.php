<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicador de Eficiencia | Hospital San José</title>
    <link rel="icon" href="{{ asset('assets/images/logohsj.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/eficiencia.css') }}?v=2">
</head>
<body>
    <header class="topbar">
        <div class="topbar__inner">
            <a href="{{ url('/principal') }}" class="brand">
                <img src="{{ asset('assets/images/logohsj.png') }}" alt="Logo Hospital San José">
                <div class="brand__text">
                    <h1>Hospital San José</h1>
                    <p>Unidad de Estadística e Información</p>
                </div>
            </a>
            
            <a href="{{ url('/principal') }}" class="back-btn">Volver al inicio</a>
        </div>
    </header>
    
    <section class="hero-eficiencia">
        <div class="hero-eficiencia__overlay"></div>
        <div class="hero-eficiencia__content">
            <span class="hero-badge">Indicadores Hospitalarios</span>
            <h2>Indicador de Eficiencia</h2>
            <p>
                Visualiza los indicadores de eficiencia hospitalaria con una tabla de resultados
                y un panel gráfico de seguimiento.
            </p>
        </div>
    </section>
    
    <main class="contenedor">
        
        <section class="panel-info">
            <div class="panel-info__item">
                <span class="label">Establecimiento</span>
                <h3 id="establecimiento">Hospital San José de Chincha</h3>
            </div>
            
            <div class="panel-info__item">
                <span class="label">Año</span>
                <h3 id="anio">2024</h3>
            </div>
            
            <div class="panel-info__item">
                <span class="label">Estado de carga</span>
                <h3 id="estadoCarga">Cargando...</h3>
            </div>
            
            <div class="panel-info__item">
                <span class="label">Total indicadores</span>
                <h3 id="totalIndicadores">0</h3>
            </div>
        </section>
        
        <section class="seccion-titulo">
            <span class="section-tag">Sección B</span>
            <h3>Indicadores de Eficiencia</h3>
            <p>
                Esta sección muestra los indicadores de desempeño hospitalario relacionados con la
                eficiencia del servicio médico.
            </p>
        </section>
        
        <section class="tabla-panel" id="tablaFullscreenCard">
            <div class="tabla-header tabla-header-flex">
                <div>
                    <h4>Listado de Indicadores</h4>
                    <p>Datos cargados directamente desde SQL Server.</p>
                </div>
                
                <div class="tabla-acciones">
                    <button
                        type="button"
                        class="action-btn export-btn"
                        id="btnExportarPDF"
                        title="Generar PDF"
                        aria-label="Generar PDF"
                    >
                        PDF
                    </button>
                    
                    <button
                        type="button"
                        class="action-btn export-btn"
                        id="btnExportarExcel"
                        title="Generar Excel"
                        aria-label="Generar Excel"
                    >
                        Excel
                    </button>
                    
                    <button
                        type="button"
                        class="fullscreen-btn"
                        id="btnFullscreenTabla"
                        title="Pantalla completa tabla"
                        aria-label="Pantalla completa tabla"
                    >
                        <span class="fullscreen-icon-open">⛶</span>
                        <span class="fullscreen-icon-close">✕</span>
                    </button>
                </div>
            </div>
            
            <div class="tabla-wrapper">
                <table class="tabla-indicadores">
                    <thead>
                        <tr>
                            <th>Ord</th>
                            <th>Nombre del Indicador</th>
                            <th>Variables</th>
                            <th>ENE</th>
                            <th>Valor ENE</th>
                            <th>FEB</th>
                            <th>Valor FEB</th>
                            <th>MAR</th>
                            <th>Valor MAR</th>
                            <th>Total Anual</th>
                            <th>Valor Final</th>
                        </tr>
                    </thead>
                    <tbody id="tablaEficienciaBody">
                        <tr>
                            <td colspan="11">Cargando datos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        
        <section class="grafico-panel" id="panelGraficoRendimiento">
            <div class="grafico-header-flex">
                <div class="grafico-header-texto">
                    <h4 id="tituloIndicadorGrafico">
                        Indicador 21 - Rendimiento de Sala de Operaciones
                    </h4>
                    <p id="descripcionIndicadorGrafico">
                        Visualización de cumplimiento mensual respecto a la meta esperada.
                    </p>
                </div>
                
                <div class="selector-indicador-header">
                    <label for="selectorIndicador">Seleccionar indicador</label>
                    <select id="selectorIndicador">
                        <option value="rendimiento-sala" selected>Rendimiento de Sala de Operaciones</option>
                        <option value="cirugias-suspendidas">Porcentaje de Cirugías Suspendidas</option>
                        <option value="ocupacion-cama">Porcentaje de ocupación cama</option>
                        <option value="intervalo-sustitucion">Intervalo de Sustitución de camas</option>
                    </select>
                </div>
            </div>
            
            <div class="grafico-layout">
                <div class="grafico-barras-card" id="graficoFullscreenCard">
                    <div class="card-title-row">
                        <h5>Valores del indicador</h5>
                        
                        <div class="card-actions">
                            <span class="card-note">Comparación mensual y valor final</span>
                            
                            <button
                                type="button"
                                class="fullscreen-btn"
                                id="btnFullscreenGrafico"
                                title="Pantalla completa gráfico"
                                aria-label="Pantalla completa gráfico"
                            >
                                <span class="fullscreen-icon-open">⛶</span>
                                <span class="fullscreen-icon-close">✕</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="leyenda-umbrales" id="leyendaUmbrales">
                        <span class="leyenda-item">
                            <span class="leyenda-linea roja"></span>
                            Bajo
                        </span>
                        <span class="leyenda-item">
                            <span class="leyenda-linea verde"></span>
                            Meta
                        </span>
                        <span class="leyenda-item">
                            <span class="leyenda-linea naranja"></span>
                            Alto
                        </span>
                    </div>
                    
                    <div class="grafico-ejes-wrap">
                        <div class="eje-y-custom" id="ejeYCustom">
                            <span>100</span>
                            <span>90</span>
                            <span>80</span>
                            <span>70</span>
                            <span>60</span>
                            <span>50</span>
                            <span>40</span>
                            <span>30</span>
                            <span>20</span>
                            <span>10</span>
                            <span>0</span>
                        </div>
                        
                        <div class="grafico-principal">
                            <div class="grafico-barras" id="graficoBarrasRendimiento">
                                <div class="sin-datos">Cargando gráfico...</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="grafico-meta-card">
                    <div class="card-title-row">
                        <h5>Control de meta</h5>
                        <span class="card-note">Seguimiento del valor actual</span>
                    </div>
                    
                    <div class="meta-info">
                        <div class="meta-badge">
                            <span>Meta esperada</span>
                            <strong id="metaEsperadaTexto">85</strong>
                        </div>
                        
                        <div class="meta-badge">
                            <span>Valor actual</span>
                            <strong id="valorActualTexto">--</strong>
                        </div>
                        
                        <div class="meta-badge" id="estadoMetaTexto">
                            Estado: --
                        </div>
                    </div>
                    
                    <div class="termometro-wrap">
                        <div class="termometro-escala" id="termometroEscala"></div>
                        
                        <div class="termometro-box">
                            <div class="termometro">
                                <div class="termometro-linea-meta" id="lineaMeta"></div>
                                <div class="termometro-barra" id="termometroBarra"></div>
                                <div class="termometro-valor" id="termometroValor">--</div>
                            </div>
                            <span class="termometro-label">Valor actual</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
    </main>
    
    <footer class="footer">
        © <span id="year"></span> Hospital San José - Indicadores de Eficiencia
    </footer>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="{{ asset('assets/js/eficiencia.js') }}?v=1"></script>
</body>
</html>
