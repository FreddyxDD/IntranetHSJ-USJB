<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="{{ asset('assets/js/csrf.js') }}" defer></script>
    <title>Información | Hospital San José</title>

    <link rel="icon" href="{{ asset('assets/images/logohsj.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/informacion.css') }}?v=2">
</head>
<body>

<header class="topbar">
    <div class="topbar__inner">

        <a href="{{ url('/principal') }}" class="brand">
            <img
                src="{{ asset('assets/images/logohsj.png') }}"
                alt="Logo Hospital San José"
            >

            <div class="brand__text">
                <h1>Hospital San José</h1>
                <p>Unidad de Estadística e información</p>
            </div>
        </a>

        <nav class="nav">
            <a href="{{ url('/principal') }}">Inicio</a>
            <a href="#paneles">Paneles</a>
            <a href="#tablas">Tablas</a>
            <a href="#resumen">Resumen</a>
        </nav>

    </div>
</header>

<section class="hero-info">
    <div class="hero-info__overlay"></div>

    <div class="hero-info__content">
        <span class="hero-badge">Panel institucional</span>

        <h2>Información y reportes</h2>

        <p>
            Consulta indicadores, tablas resumen y paneles informativos
            del Hospital San José.

            Por ahora esta vista solo trabaja con información local
            dentro del sistema, ya después se podrá conectar a una
            base de datos.
        </p>
    </div>
</section>

<main class="container">

    <section class="summary-cards" id="resumen">

        <div class="summary-card">
            <span><b>Total registros</b></span>
            <h3>1,248</h3>
            <p>Datos consolidados del periodo actual.</p>
        </div>

        <div class="summary-card">
            <span><b>Áreas monitoreadas</b></span>
            <h3>4</h3>
            <p>Egresos, emergencias, usuarios e indicadores.</p>
        </div>

        <div class="summary-card">
            <span><b>Última actualización</b></span>
            <h3>Marzo 2026</h3>
            <p>Información cargada localmente para pruebas.</p>
        </div>

        <div class="summary-card">
            <span><b>Estado</b></span>
            <h3>Activo</h3>
            <p>Módulo listo para futura conexión a datos reales.</p>
        </div>

    </section>

    <section class="panel-section" id="paneles">

        <div class="section-header">
            <span class="section-tag">Vista principal</span>

            <h3>Paneles informativos</h3>

            <p>
                Aquí luego podremos incrustar gráficos de Power BI o
                consumir datos desde la base de datos.

                Por ahora solo se mostrarán bloques visuales simulando
                paneles institucionales.
            </p>
        </div>

        <div class="panel-grid">

            <div class="panel-card panel-card--large animate-bars">

                <div class="panel-card__header">
                    <h4>Indicador general de atenciones</h4>
                    <span>Vista previa</span>
                </div>

                <div class="bars-chart">
                    <div class="bar" style="--bar-height: 65%;"></div>
                    <div class="bar" style="--bar-height: 82%;"></div>
                    <div class="bar" style="--bar-height: 54%;"></div>
                    <div class="bar" style="--bar-height: 90%;"></div>
                    <div class="bar" style="--bar-height: 72%;"></div>
                    <div class="bar" style="--bar-height: 88%;"></div>
                </div>

            </div>

            <div class="panel-card animate-donut">

                <div class="panel-card__header">
                    <h4>Distribución por servicio</h4>
                    <span>Vista previa</span>
                </div>

                <div class="fake-donut" data-percent="68">
                    <div class="donut-center">0%</div>
                </div>

            </div>

            <div class="panel-card animate-line">

                <div class="panel-card__header">
                    <h4>Tendencia mensual</h4>
                    <span>Vista previa</span>
                </div>

                <div class="fake-line">
                    <svg
                        viewBox="0 0 300 220"
                        preserveAspectRatio="none"
                        class="line-svg"
                    >
                        <path
                            class="line-path"
                            d="M20 150 C70 140, 120 130, 170 120 C210 112, 245 105, 280 98"
                        ></path>
                    </svg>
                </div>

            </div>

        </div>

    </section>

    <section class="table-section" id="tablas">

        <div class="section-header">
            <span class="section-tag">Datos locales</span>

            <h3>Tablas informativas</h3>

            <p>
                Estos datos están escritos directamente en el código como
                ejemplo temporal.

                Luego se pueden reemplazar por información traída desde
                SQL Server o cualquier otra fuente.
            </p>
        </div>

        <div class="table-card">

            <div class="table-card__top">
                <h4>Resumen de indicadores institucionales</h4>
            </div>

            <div class="table-wrapper">

                <table>
                    <thead>
                        <tr>
                            <th>Indicador</th>
                            <th>Área</th>
                            <th>Periodo</th>
                            <th>Valor</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>Pacientes atendidos</td>
                            <td>Emergencias</td>
                            <td>Enero 2026</td>
                            <td>320</td>
                            <td><span class="status status--ok">Actualizado</span></td>
                        </tr>

                        <tr>
                            <td>Egresos registrados</td>
                            <td>Hospitalización</td>
                            <td>Enero 2026</td>
                            <td>186</td>
                            <td><span class="status status--ok">Actualizado</span></td>
                        </tr>

                        <tr>
                            <td>Usuarios activos</td>
                            <td>Sistema</td>
                            <td>Enero 2026</td>
                            <td>24</td>
                            <td><span class="status status--warn">Revisión</span></td>
                        </tr>

                        <tr>
                            <td>Reportes emitidos</td>
                            <td>Estadística</td>
                            <td>Enero 2026</td>
                            <td>58</td>
                            <td><span class="status status--ok">Actualizado</span></td>
                        </tr>

                        <tr>
                            <td>Tiempo promedio de atención</td>
                            <td>Emergencias</td>
                            <td>Enero 2026</td>
                            <td>35 min</td>
                            <td><span class="status status--ok">Actualizado</span></td>
                        </tr>
                    </tbody>
                </table>

            </div>

        </div>

    </section>

    <section class="notice-box">

        <h4>Nota importante</h4>

        <p>
            Este módulo está preparado como una primera versión visual.
            Más adelante podrás conectarlo a una base de datos y
            reemplazar estas tablas por reportes reales o incluso
            paneles embebidos de Power BI.
        </p>

    </section>

</main>

<footer class="footer">
    © {{ date('Y') }} Hospital San José - Módulo de información institucional
</footer>

<script src="{{ asset('assets/js/informacion.js') }}?v=1"></script>

</body>
</html>
