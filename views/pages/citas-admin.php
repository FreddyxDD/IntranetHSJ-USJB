<?php
$usuarioCitasAdmin = $_SESSION['citas_admin_usuario'] ?? '';
?>

<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Citas Admin | Hospital San José</title>

    <link rel="icon" href="../assets/images/logohsj.png" />
    <link rel="stylesheet" href="<?= e(url_path('/assets/css/tailwind.css')) ?>?v=3">
    <link rel="stylesheet" href="<?= e(url_path('/assets/css/citasadmin.css')) ?>?v=1021">
    <style id="estilosReporteInteractivo">
      .reportes-graficos-grid--flujo {
        grid-template-columns: minmax(0, 1fr);
      }

      .reporte-flujo-principal {
        width: 100%;
      }

      .reporte-detalle-flujo {
        display: grid;
        grid-template-columns: minmax(340px, 430px) minmax(0, 1fr);
        gap: 24px;
        align-items: start;
        margin-top: 18px;
      }

      .reporte-anillo-panel,
      .reporte-indicadores-panel {
        border: 1px solid rgba(148, 163, 184, 0.28);
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.86);
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
      }

      .reporte-anillo-panel {
        padding: 20px;
      }

      .reporte-anillo-canvas {
        position: relative;
        width: 100%;
        height: 330px;
      }

      .reporte-consultorios-lista {
        display: grid;
        gap: 10px;
        max-height: 350px;
        margin-top: 16px;
        padding-right: 4px;
        overflow-y: auto;
      }

      .reporte-consultorio-leyenda {
        display: grid;
        grid-template-columns: 12px minmax(0, 1fr) auto;
        gap: 11px;
        align-items: center;
        width: 100%;
        padding: 12px 13px;
        border: 1px solid rgba(148, 163, 184, 0.34);
        border-radius: 13px;
        background: #fff;
        color: #334155;
        font: inherit;
        text-align: left;
        cursor: pointer;
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
      }

      .reporte-consultorio-leyenda:hover {
        transform: translateY(-1px);
        border-color: rgba(37, 99, 235, 0.45);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
      }

      .reporte-consultorio-leyenda.activo {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
      }

      .reporte-consultorio-leyenda__color {
        width: 12px;
        height: 12px;
        border-radius: 999px;
      }

      .reporte-consultorio-leyenda__contenido {
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 0;
      }

      .reporte-consultorio-leyenda__contenido strong {
        color: #0f172a;
        font-size: 13px;
      }

      .reporte-consultorio-leyenda__contenido small {
        overflow: hidden;
        color: #64748b;
        font-size: 11px;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .reporte-consultorio-leyenda__total {
        padding: 5px 8px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
      }

      .reporte-consultorios-vacio {
        padding: 16px;
        border: 1px dashed rgba(100, 116, 139, 0.36);
        border-radius: 12px;
        background: #f8fafc;
        color: #64748b;
        text-align: center;
      }

      .reporte-indicadores-panel {
        overflow: hidden;
      }

      .reporte-indicadores-panel__header {
        padding: 18px 20px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.22);
      }

      .reporte-indicadores-panel__header h4 {
        margin: 0 0 5px;
        color: #0f172a;
        font-size: 17px;
      }

      .reporte-indicadores-panel__header p {
        margin: 0;
        color: #64748b;
        font-size: 13px;
      }

      .reporte-indicadores-mensaje {
        margin: 18px 20px;
        padding: 18px;
        border: 1px dashed rgba(100, 116, 139, 0.36);
        border-radius: 12px;
        background: #f8fafc;
        color: #64748b;
        text-align: center;
      }

      .reporte-indicadores-contenido {
        padding: 18px 20px 20px;
      }

      .reporte-consultorio-indicadores-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
      }

      .reporte-consultorio-indicador {
        min-width: 0;
        padding: 14px;
        border: 1px solid rgba(226, 232, 240, 0.95);
        border-radius: 14px;
        background: linear-gradient(145deg, #ffffff, #f8fafc);
      }

      .reporte-consultorio-indicador span {
        display: block;
        margin-bottom: 6px;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
      }

      .reporte-consultorio-indicador strong {
        color: #0f172a;
        font-size: 23px;
      }

      .reporte-estados-panel {
        margin-top: 18px;
        padding: 16px;
        border: 1px solid rgba(226, 232, 240, 0.95);
        border-radius: 14px;
        background: #ffffff;
      }

      .reporte-estados-panel h5 {
        margin: 0 0 5px;
        color: #0f172a;
        font-size: 15px;
      }

      .reporte-estados-panel p {
        margin: 0 0 12px;
        color: #64748b;
        font-size: 12px;
      }

      .reporte-estados-canvas {
        position: relative;
        width: 100%;
        height: 190px;
      }

      .reporte-estados-mensaje {
        margin-bottom: 12px;
        padding: 13px;
        border: 1px dashed rgba(100, 116, 139, 0.36);
        border-radius: 11px;
        background: #f8fafc;
        color: #64748b;
        text-align: center;
      }

      .reporte-personal-titulo {
        margin: 22px 0 10px;
        color: #0f172a;
        font-size: 15px;
      }

      .reporte-personal-scroll {
        max-height: 340px;
        overflow: auto;
        border: 1px solid rgba(226, 232, 240, 0.95);
        border-radius: 13px;
      }

      .reporte-personal-tabla {
        width: 100%;
        min-width: 700px;
        border-collapse: collapse;
      }

      .reporte-personal-tabla th,
      .reporte-personal-tabla td {
        padding: 11px 12px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.95);
        text-align: left;
        vertical-align: middle;
      }

      .reporte-personal-tabla th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: #f8fafc;
        color: #475569;
        font-size: 11px;
        letter-spacing: 0.02em;
        text-transform: uppercase;
      }

      .reporte-personal-tabla td {
        color: #334155;
        font-size: 12px;
      }

      .reporte-personal-nombre {
        display: inline-flex;
        align-items: center;
        gap: 8px;
      }

      .reporte-personal-nombre__punto {
        width: 9px;
        height: 9px;
        border-radius: 999px;
      }

      .reporte-personal-vacio {
        padding: 18px !important;
        color: #64748b !important;
        text-align: center !important;
      }

      .reporte-detalle-metrica__texto {
        font-size: 16px !important;
      }

      @media (max-width: 1080px) {
        .reporte-detalle-flujo {
          grid-template-columns: 1fr;
        }
      }

      @media (max-width: 760px) {
        .reporte-consultorio-indicadores-grid {
          grid-template-columns: repeat(2, minmax(0, 1fr));
        }
      }

      @media (max-width: 520px) {
        .reporte-anillo-canvas {
          height: 285px;
        }

        .reporte-consultorio-indicadores-grid {
          grid-template-columns: 1fr;
        }

        .reporte-consultorio-leyenda {
          grid-template-columns: 12px minmax(0, 1fr);
        }

        .reporte-consultorio-leyenda__total {
          grid-column: 2;
          justify-self: start;
        }
      }
    </style>
  </head>

  <body class="min-h-screen">
    <div class="admin-layout min-h-screen">
      <!-- =====================================================
           BARRA LATERAL
      ====================================================== -->
      <aside class="sidebar">
        <a href="../pages/Areas.html" class="sidebar-brand">
          <img
            src="../assets/images/logohsj.png"
            alt="Logo Hospital San José"
            class="sidebar-brand__logo"
          />

          <div class="sidebar-brand__text">
            <h1>Hospital San José</h1>
            <p>Panel administrativo</p>
          </div>
        </a>

        <nav class="sidebar-menu" aria-label="Menú principal">
          <a href="<?= e(url_path('/areas')) ?>" class="sidebar-link">
            <span class="sidebar-link__icon">⌂</span>
            <span>Inicio</span>
          </a>

          <!-- Citas Admin desplegable -->
          <div class="sidebar-dropdown">
            <a href="#" class="sidebar-link activo sidebar-dropdown__main" data-menu-toggle="citas-admin">
              <span class="sidebar-link__icon">▣</span>
              <span>Citas Admin</span>
            </a>

            <div class="sidebar-submenu">
              <a href="#" class="sidebar-sublink activo" data-vista="citas">
                Reservados
              </a>

              <a href="#" class="sidebar-sublink" data-vista="citas-diarias">
                Citas diarias
              </a>
            </div>
          </div>

          <a href="#" class="sidebar-link">
            <span class="sidebar-link__icon">♧</span>
            <span>Servicios</span>
          </a>

          <a href="#" class="sidebar-link" data-vista="reportes">
            <span class="sidebar-link__icon">▥</span>
            <span>Reportes</span>
          </a>

          <a href="#" class="sidebar-link">
            <span class="sidebar-link__icon">⚙</span>
            <span>Configuración</span>
          </a>
        </nav>

        <div class="sidebar-security">
          <div class="sidebar-security__icon">✓</div>

          <div>
            <h2>Sistema seguro</h2>
            <p>Tus datos están protegidos con altos estándares de seguridad.</p>
          </div>

          <span class="sidebar-security__arrow">›</span>
        </div>
      </aside>

      <!-- =====================================================
           CONTENIDO PRINCIPAL
      ====================================================== -->
      <div class="main-area">
        <!-- =====================================================
             CABECERA SUPERIOR
        ====================================================== -->
        <header class="topbar">
          <div class="topbar__left">
            <button id="btnMenu" class="menu-btn" type="button" aria-label="Cerrar menú" aria-expanded="true">
              <span></span>
              <span></span>
              <span></span>
            </button>

            <div class="page-title">
              <h2>Citas Admin</h2>
              <p>Panel administrativo de citas</p>
            </div>
          </div>

          <div class="topbar__search">
            <span class="topbar__search-icon">⌕</span>
            <input
              type="text"
              id="txtBusquedaRapida"
              aria-label="Buscar rápido"
              placeholder="Buscar por ticket, paciente, documento..."
            />
          </div>

          <nav class="topbar__actions" aria-label="Acciones de sesión">
            <button class="notification-btn" type="button" aria-label="Notificaciones">
              <span class="notification-btn__icon">♢</span>
              <span class="notification-btn__badge">3</span>
            </button>

            <a href="../pages/Areas.html" class="user-btn">
              <span>←</span>
              Volver a áreas
            </a>

            <button
              id="btnCerrarSesionCitas"
              class="btn-cerrar-sesion"
              type="button"
            >
              <span>↪</span>
              Cerrar sesión
            </button>
          </nav>
        </header>

        <main class="contenedor">
          <!-- =====================================================
               HERO ADMIN
          ====================================================== -->
          <section class="hero-admin vista-gestion-citas">
            <div class="hero-admin__content">
              <p class="hero-admin__eyebrow">
                Bienvenido al panel de administración
                <span></span>
              </p>

              <h2>Gestión inteligente de citas</h2>

              <p class="hero-admin__description">
                Visualiza y administra los registros enviados desde la sala de
                espera virtual.
              </p>

              <div class="hero-features">
                <article class="hero-feature">
                  <div class="hero-feature__icon">🛡</div>
                  <div>
                    <h3>Datos seguros</h3>
                    <p>Protegemos la información de tus pacientes.</p>
                  </div>
                </article>

                <article class="hero-feature">
                  <div class="hero-feature__icon">◷</div>
                  <div>
                    <h3>Información en tiempo real</h3>
                    <p>Actualizaciones al instante para mejores decisiones.</p>
                  </div>
                </article>

                <article class="hero-feature">
                  <div class="hero-feature__icon">▥</div>
                  <div>
                    <h3>Reportes y exportación</h3>
                    <p>Exporta datos y genera reportes fácilmente.</p>
                  </div>
                </article>
              </div>
            </div>

            <div class="hero-admin__graphic" aria-hidden="true">
              <div class="pulse-line"></div>

              <div class="floating-heart">♥</div>

              <div class="medical-board">
                <div class="medical-board__clip"></div>
                <div class="medical-board__cross">✚</div>
                <span></span>
                <span></span>
                <span></span>
              </div>

              <div class="chart-bars">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
              </div>
            </div>
          </section>

          <!-- =====================================================
               TARJETAS RESUMEN
          ====================================================== -->
          <section class="resumen-grid vista-gestion-citas" aria-label="Resumen de registros">
            <article class="resumen-card resumen-card--azul">
              <div class="resumen-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                  <path d="M16 3v4M8 3v4M3 10h18"></path>
                  <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"></path>
                </svg>
              </div>

              <div class="resumen-card__info">
                <span id="etiquetaTotalResumen">Total registros</span>
                <strong id="totalRegistrosCard">0</strong>
                <p id="descripcionTotalResumen">Todos los registros recibidos</p>
              </div>
            </article>

            <article class="resumen-card resumen-card--verde">
              <div class="resumen-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="9"></circle>
                  <path d="M12 7v5l3 2"></path>
                </svg>
              </div>

              <div class="resumen-card__info">
                <span id="etiquetaHoyResumen">Registros de hoy</span>
                <strong id="registrosHoyCard">0</strong>
                <p id="descripcionHoyResumen">Registros del día actual</p>
              </div>
            </article>

            <article class="resumen-card resumen-card--morado">
              <div class="resumen-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                  <path d="M19 8v6M22 11h-6"></path>
                </svg>
              </div>

              <div class="resumen-card__info">
                <span>Registrados</span>
                <strong id="registradosCard">0</strong>
                <p>Pacientes registrados</p>
              </div>
            </article>

            <article class="resumen-card resumen-card--naranja">
              <div class="resumen-card__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="9"></circle>
                  <path d="m8 12 2.6 2.6L16.5 9"></path>
                </svg>
              </div>

              <div class="resumen-card__info">
                <span>Atendidos</span>
                <strong id="atendidosCard">0</strong>
                <p>Pacientes atendidos</p>
              </div>
            </article>
          </section>

          <!-- =====================================================
               FILTROS
          ====================================================== -->
          <section class="panel panel-filtros vista-gestion-citas">
            <div class="panel__header panel__header--filtros">
              <div class="panel-title">
                <span class="panel-title__icon">≡</span>

                <div>
                  <h3>Filtros de búsqueda</h3>
                  <p>
                    Encuentra registros por ticket, historia clínica,
                    documento, paciente, especialidad, servicio o médico.
                  </p>
                </div>
              </div>

              <div class="panel-dots" aria-hidden="true">
                <span></span><span></span><span></span>
                <span></span><span></span><span></span>
                <span></span><span></span><span></span>
              </div>
            </div>

        <div class="filtros-grid">
          <div class="campo campo-busqueda">
            <label id="etiquetaBusquedaFiltro" for="txtBusqueda">Buscar</label>
            <div class="campo-control campo-control--icono">
              <span>⌕</span>
              <input
                type="text"
                id="txtBusqueda"
                placeholder="Ejemplo: DNI, historia clínica, paciente, ticket..."
              />
            </div>
          </div>

          <div class="campo">
            <label for="filtroEstado">Estado</label>
            <select id="filtroEstado">
              <option value="">Todos</option>
              <option value="DISPONIBLE">Disponible</option>
              <option value="SEPARADO">Separado</option>
              <option value="SEPARADO_ADICIONAL">Separado adicional</option>
            </select>
          </div>

          <div class="campo" id="grupoFiltroTurno">
            <label for="filtroTurno">Turno</label>
            <select id="filtroTurno">
              <option value="todos">Todos</option>
              <option value="manana">Mañana</option>
              <option value="tarde">Tarde</option>
            </select>
          </div>

          <div class="campo">
            <label for="fechaInicio">Fecha inicio</label>
            <input type="date" id="fechaInicio" />
          </div>

          <div class="campo">
            <label for="fechaFin">Fecha fin</label>
            <input type="date" id="fechaFin" />
          </div>
        </div>

            <div class="acciones-filtros">
              <button id="btnActualizar" type="button" class="btn-principal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="11" cy="11" r="7"></circle>
                  <path d="m20 20-3.5-3.5"></path>
                </svg>
                <span id="textoBtnActualizar">Actualizar</span>
              </button>

              <button id="btnLimpiarFiltros" type="button" class="btn-secundario">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M4 5h16l-6.2 7.1v5.3l-3.6 1.8v-7.1L4 5Z"></path>
                </svg>
                <span>Limpiar filtros</span>
              </button>

              <button id="btnExportar" type="button" class="btn-outline">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M12 3v12"></path>
                  <path d="m7 10 5 5 5-5"></path>
                  <path d="M5 21h14"></path>
                </svg>
                <span>Exportar CSV</span>
              </button>
            </div>
          </section>

          <!-- =====================================================
                TABLA DE REGISTROS
          ====================================================== -->
          <section class="panel panel-tabla vista-gestion-citas">
            <div class="tabla-header">
              <div class="panel-title">
                <span class="panel-title__icon panel-title__icon--file">▤</span>

                <div>
                  <h3 id="tituloTablaAdmin">Reservados enviados</h3>
                  <p id="textoResultado">0 registros encontrados</p>
                </div>
              </div>

              <div class="tabla-header__right">
                <label for="cantidadRegistros">Mostrar</label>
                <select id="cantidadRegistros" aria-label="Cantidad de registros">
                  <option value="10">10</option>
                  <option value="20">20</option>
                  <option value="30">30</option>
                </select>
                <span>registros</span>
              </div>
            </div>

            <div class="tabla-contenedor">
              <table>
                <thead id="tablaHeadAdmin">
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
                </thead>

                <tbody id="tablaRegistrosAdmin">
                  <tr>
                    <td colspan="12" class="sin-datos">
                      No hay registros cargados.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="tabla-footer">
              <p id="textoPaginacion">
                  Mostrando 0 a 0 de 0 registros
              </p>

              <div class="paginacion">
                  <button
                      type="button"
                      id="btnPaginaAnterior"
                      aria-label="Página anterior"
                  >
                      ‹
                  </button>

                  <button
                      type="button"
                      id="btnPaginaActual"
                      class="activo"
                      disabled
                  >
                      1
                  </button>

                  <button
                      type="button"
                      id="btnPaginaSiguiente"
                      aria-label="Página siguiente"
                  >
                      ›
                  </button>
              </div>
          </div>
          </section>

          <!-- =====================================================
               REPORTES DE CITAS DIARIAS - GRÁFICOS CON DESGLOSE
          ====================================================== -->
          <section id="vistaReportes" class="reportes-citas oculto" aria-label="Reportes de citas diarias">
            <div class="reportes-citas__encabezado">
              <div>
                <p class="reportes-citas__eyebrow">Análisis mensual de citas</p>
                <h2>Reportes por especialidad</h2>
                <p>El ranking principal muestra una sola vez cada especialidad. Haz clic en una barra para ver sus consultorios y el personal responsable.</p>
              </div>

              <div class="reportes-citas__filtros">
                <div class="campo campo--mes-reporte">
                  <label for="reporteMes">Mes del reporte</label>
                  <input type="month" id="reporteMes" aria-describedby="reporteMesAyuda" />
                  <small id="reporteMesAyuda">Todos los gráficos usan el mes completo seleccionado.</small>
                </div>

                <button id="btnActualizarReportes" type="button" class="btn-principal">
                  <span>↻</span>
                  Actualizar mes
                </button>
              </div>
            </div>

            <div id="mensajeReportes" class="mensaje-reportes oculto" role="status"></div>

            <div class="reportes-graficos-grid reportes-graficos-grid--flujo">
              <article class="reporte-grafico-card reporte-grafico-card--ancho reporte-grafico-card--interactivo reporte-flujo-principal">
                <div class="reporte-grafico-card__header reporte-grafico-card__header--separado">
                  <div>
                    <span class="reporte-grafico-card__icon">▥</span>
                    <div>
                      <h3>Total de citas por especialidad</h3>
                      <p>Haz clic en una barra para ver los consultorios, sus estados reales y el personal asignado.</p>
                    </div>
                  </div>
                  <span class="reporte-click-ayuda">Haz clic en una barra</span>
                </div>

              <div
                class="reporte-grafico-scroll"
                tabindex="0"
                aria-label="Listado gráfico de especialidades"
              >
                <div class="reporte-grafico-card__canvas">
                  <canvas id="graficoConsultoriosSolicitados"></canvas>
                </div>
              </div>
              </article>

              <article id="detalleEspecialidadCard" class="reporte-grafico-card reporte-grafico-card--ancho reporte-detalle-especialidad oculto">
                <div class="reporte-grafico-card__header reporte-grafico-card__header--separado">
                  <div>
                    <span class="reporte-grafico-card__icon reporte-grafico-card__icon--morado">↳</span>
                    <div>
                      <p class="reporte-detalle-especialidad__eyebrow">Detalle de la especialidad seleccionada</p>
                      <h3 id="tituloDetalleEspecialidad">Consultorios y personal asignado</h3>
                      <p id="subtituloDetalleEspecialidad">Cada segmento representa un consultorio. Haz clic para ver Anulado, Registrado y Cerrado.</p>
                    </div>
                  </div>

                  <button id="btnCerrarDetalleEspecialidad" class="btn-cerrar-detalle-reporte" type="button">
                    Cerrar detalle
                  </button>
                </div>

                <div id="resumenDetalleEspecialidad" class="reporte-detalle-resumen"></div>

                <div class="reporte-detalle-flujo">
                  <section class="reporte-anillo-panel" aria-label="Distribución de citas por consultorio">
                    <div class="reporte-anillo-canvas">
                      <canvas id="graficoDetalleEspecialidad"></canvas>
                    </div>

                    <div
                      id="listaConsultoriosAnillo"
                      class="reporte-consultorios-lista"
                      aria-label="Consultorios y personal asignado"
                    ></div>
                  </section>

                  <section class="reporte-indicadores-panel" aria-live="polite">
                    <div class="reporte-indicadores-panel__header">
                      <h4 id="tituloIndicadoresConsultorio">Estados del consultorio</h4>
                      <p id="subtituloIndicadoresConsultorio">Selecciona un consultorio para ver los estados reales registrados en SIGH.</p>
                    </div>

                    <div id="mensajeIndicadoresConsultorio" class="reporte-indicadores-mensaje">
                      Haz clic en un segmento del anillo o en un consultorio de la lista.
                    </div>

                    <div id="contenidoIndicadoresConsultorio" class="reporte-indicadores-contenido oculto">
                      <div class="reporte-consultorio-indicadores-grid">
                        <article class="reporte-consultorio-indicador">
                          <span>Citas totales</span>
                          <strong id="indicadorConsultorioTotalCitas">0</strong>
                        </article>

                        <article class="reporte-consultorio-indicador">
                          <span>Personal asignado</span>
                          <strong id="indicadorConsultorioPersonal">0</strong>
                        </article>
                      </div>

                      <section class="reporte-estados-panel" aria-label="Estados reales del consultorio">
                        <h5>Estados reales del consultorio</h5>
                        <p>Solo se muestran los estados que tienen registros en el mes seleccionado.</p>

                        <div id="mensajeEstadosConsultorio" class="reporte-estados-mensaje oculto"></div>

                        <div class="reporte-estados-canvas">
                          <canvas id="graficoEstadosConsultorio"></canvas>
                        </div>
                      </section>

                      <h5 class="reporte-personal-titulo">Personal del consultorio</h5>

                      <div class="reporte-personal-scroll">
                        <table class="reporte-personal-tabla">
                          <thead>
                            <tr>
                              <th>N.º</th>
                              <th>Personal</th>
                              <th>Citas totales</th>
                              <th>Anulado</th>
                              <th>Registrado</th>
                              <th>Cerrado</th>
                            </tr>
                          </thead>
                          <tbody id="cuerpoPersonalConsultorio"></tbody>
                        </table>
                      </div>
                    </div>
                  </section>
                </div>
              </article>
            </div>
          </section>
        </main>
      </div>
    </div>

    <!-- =====================================================
         MODAL DETALLE
    ====================================================== -->
    <div id="fondoModal" class="fondo-modal oculto"></div>

    <section id="modalDetalle" class="modal-detalle oculto">
      <div class="modal-header">
        <h3>Detalle del registro</h3>
        <button id="btnCerrarModal" type="button">×</button>
      </div>

      <div id="contenidoDetalle" class="detalle-grid"></div>
    </section>

    <!-- =====================================================
         MODAL CERRAR SESIÓN
    ====================================================== -->
    <div id="fondoLogout" class="fondo-logout oculto"></div>

    <section id="modalLogout" class="modal-logout oculto">
      <div class="modal-logout-icono">🔒</div>

      <h3>Cerrar sesión</h3>

      <p>¿Estás seguro de que deseas cerrar sesión en Citas Admin?</p>

      <div class="modal-logout-acciones">
        <button
          id="btnCancelarLogout"
          class="btn-cancelar-logout"
          type="button"
        >
          Cancelar
        </button>

        <button
          id="btnConfirmarLogout"
          class="btn-confirmar-logout"
          type="button"
        >
          Sí, cerrar sesión
        </button>
      </div>
    </section>

   <script>
    window.APP_BASE = "<?= e(app_base()) ?>";
    window.CITAS_ADMIN_USUARIO = "<?= e($usuarioCitasAdmin) ?>";
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="<?= e(url_path('/assets/js/citasadmin.js')) ?>?v=1021"></script>
  </body>
</html>
