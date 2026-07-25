<?php
$correo = $_SESSION['ueei_correo'] ?? '';
$rol = $_SESSION['ueei_rol'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicador de Calidad | Hospital San José</title>

    <link rel="icon" href="<?= e(url_path('/assets/images/logohsj.png')) ?>">
    <link rel="stylesheet" href="<?= e(url_path('/assets/css/calidad.css')) ?>?v=2">
</head>

<body>
    <header class="topbar">
        <div class="topbar__inner">
            <a href="<?= e(url_path('/principal')) ?>" class="brand">
                <img src="<?= e(url_path('/assets/images/logohsj.png')) ?>" alt="Logo Hospital San José">

                <div class="brand__text">
                    <h1>Hospital San José</h1>
                    <p>Unidad de Estadística e Información</p>
                </div>
            </a>

            <a href="<?= e(url_path('/principal')) ?>" class="back-btn">
                Volver al inicio
            </a>
        </div>
    </header>

    <section class="hero-calidad">
        <div class="hero-calidad__overlay"></div>

        <div class="hero-calidad__content">
            <span class="hero-badge">Indicadores Hospitalarios</span>

            <h2>Indicador de Calidad</h2>

            <p>
                Visualiza los indicadores principales del área de calidad.
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
        </section>

        <section class="seccion-titulo">
            <span class="section-tag">Sección C</span>

            <h3>Indicadores de Calidad</h3>

            <p>
                Esta sección muestra los indicadores de desempeño hospitalario relacionados con
                calidad del servicio médico.
            </p>
        </section>

        <section class="tabla-panel">
            <div class="tabla-header">
                <h4>Listado de Indicadores</h4>
                <p>Datos cargados directamente desde la base de datos.</p>
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
                            <th>Total Anual</th>
                            <th>Valor Final</th>
                        </tr>
                    </thead>

                    <tbody id="tablaCalidadBody">
                        <tr>
                            <td colspan="9">Cargando datos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="cards-resumen">
            <div class="resumen-card">
                <h4>Total de registros</h4>
                <p id="totalIndicadores">0</p>
            </div>

            <div class="resumen-card">
                <h4>Estado</h4>
                <p id="estadoCarga">Cargando...</p>
            </div>

            <div class="resumen-card">
                <h4>Origen de datos</h4>
                <p>MySQL</p>
            </div>
        </section>
    </main>

    <footer class="footer">
        © <span id="year"></span> Hospital San José - Calidad
    </footer>

    <script>
        window.APP_BASE = "<?= e(app_base()) ?>";
        window.UEEI_USER = {
            correo: <?= json_encode($correo, JSON_UNESCAPED_UNICODE) ?>,
            rol: <?= json_encode($rol, JSON_UNESCAPED_UNICODE) ?>
        };
    </script>

    <script src="<?= e(url_path('/assets/js/calidad.js')) ?>?v=1"></script>
</body>
</html>