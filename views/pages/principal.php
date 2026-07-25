<?php
$correo = $_SESSION['ueei_correo'] ?? '';
$rol = $_SESSION['ueei_rol'] ?? '';
$areaId = isset($_SESSION['ueei_area_id']) ? (int) $_SESSION['ueei_area_id'] : null;
$modulosAutorizados = function_exists('modulos_autorizados')
    ? modulos_autorizados($areaId, $rol)
    : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal | Hospital San José</title>

    <link rel="icon" href="/assets/images/logohsj.png?v=1" type="image/png">
    <link rel="stylesheet" href="/assets/css/principal.css?v=8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>

<body>
    <header class="topbar">
        <div class="topbar__inner">
            <a href="/principal" class="brand">
                <img src="/assets/images/logohsj.png?v=1" alt="Logo Hospital San José">

                <div class="brand__text">
                    <h1>Hospital San José</h1>
                    <p>Unidad de Estadística e información</p>
                </div>
            </a>

            <nav class="nav" id="mainNav">
                <a href="#accesos" class="activo">Accesos</a>
                <a href="#nosotros">Nosotros</a>
                <a href="#informacion">Información</a>
                <a href="#contacto">Contacto</a>
            </nav>

            <div class="user-menu">
                <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú de usuario">
                    ☰
                </button>

                <div class="dropdown-menu" id="dropdownMenu">
                    <a href="/areas" class="dropdown-item">Ver Áreas</a>
                    <a href="/perfil" class="dropdown-item">Ver mi perfil</a>

                    <button type="button" class="dropdown-item dropdown-item--danger" id="logoutBtn">
                        Cerrar sesión
                    </button>
                </div>
            </div>
        </div>
    </header>

    <section class="hero" id="hero">
        <div class="hero__overlay"></div>
        <div class="hero__particles"></div>
        <div class="hero__glow hero__glow--1"></div>
        <div class="hero__glow hero__glow--2"></div>
        <div class="hero__glow hero__glow--3"></div>
        <div class="hero__grid"></div>

        <div class="hero__content">
            <span class="hero__badge">Bienvenido a la plataforma UEeI</span>

            <div class="hero__box reveal">
                <h2 id="heroTitle">Hospital San José</h2>
                <p id="heroDesc">
                    Accede a estadísticas, módulos internos, reportes y servicios digitales desde un solo lugar.
                </p>
            </div>

            <div class="search-box reveal">
                <div class="search-icon">
                    <img src="/assets/icon/lupaIcon.png?v=1" alt="Buscar">
                </div>

                <input
                    type="text"
                    id="searchInput"
                    placeholder="Busca lo que necesites. Ejemplo: Egresos, reportes, información..."
                    autocomplete="off"
                >

                <button class="search-btn" id="searchBtn" type="button" aria-label="Buscar">
                    <img src="/assets/icon/lupaIcon.png?v=1" alt="">
                    <span>Buscar</span>
                </button>
            </div>

            <div class="search-suggestions" id="searchSuggestions"></div>

            <div class="caption" id="captionText">
                Sistema de gestión hospitalaria
            </div>
        </div>
    </section>

    <section class="section section-alt" id="accesos">
        <div class="section-header reveal">
            <h3>Accesos</h3>

            <p class="section-desc">
                Conoce más sobre la labor del Hospital San José de Chincha y el compromiso que mantiene
                con la salud, la atención oportuna y el servicio a la comunidad.
            </p>

            <div class="nosotros-botones">
                <?php if (empty($modulosAutorizados)): ?>
                    <span class="nosotros-btn">
                        No tienes módulos asignados. Solicita acceso al administrador.
                    </span>
                <?php else: ?>
                    <?php foreach ($modulosAutorizados as $modulo): ?>
                        <a href="<?= e(url_path($modulo['ruta'])) ?>" class="nosotros-btn">
                            <?= e($modulo['nombre']) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="section nosotros-section" id="nosotros">
        <div class="nosotros-layout">

            <figure class="nosotros-imagen reveal">
                <img
                    src="/assets/images/imaguen2.jpg?v=1"
                    alt="Fachada del Hospital San José de Chincha"
                >
            </figure>

            <div class="nosotros-contenido">
                <div class="nosotros-etiqueta reveal">
                    <i class="bi bi-plus-lg"></i>
                    <span>Sobre nosotros</span>
                </div>

                <h3 class="reveal">
                    Hospital San José de Chincha
                </h3>

                <p class="nosotros-subtitulo reveal">
                    Entidad adscrita a la Dirección Regional de Salud de Ica.
                </p>

                <p class="nosotros-descripcion reveal">
                    Somos una institución de salud orientada a brindar atención
                    médica integral, humana y oportuna a la población,
                    fortaleciendo nuestros servicios y procesos para contribuir
                    al bienestar de nuestra comunidad.
                </p>

                <div class="nosotros-cards">

                    <article class="nosotros-card reveal">
                        <div class="nosotros-card__encabezado">
                            <span class="nosotros-card__icono nosotros-card__icono--azul">
                                <i class="bi bi-bullseye"></i>
                            </span>

                            <h4>Misión</h4>
                        </div>

                        <p>
                            Brindar servicios de salud integrales con calidad,
                            calidez y responsabilidad, priorizando la atención
                            del paciente y el compromiso con la comunidad.
                        </p>
                    </article>

                    <article class="nosotros-card reveal">
                        <div class="nosotros-card__encabezado">
                            <span class="nosotros-card__icono nosotros-card__icono--verde">
                                <i class="bi bi-eye-fill"></i>
                            </span>

                            <h4>Visión</h4>
                        </div>

                        <p>
                            Ser una institución de salud referente en la región
                            por su compromiso, innovación y excelencia en la
                            gestión hospitalaria.
                        </p>
                    </article>

                </div>
            </div>

        </div>
    </section>

    <section class="section compromiso-section">
        <div class="compromiso-banner reveal">

            <div class="compromiso-banner__contenido">
                <div class="compromiso-banner__titulo">
                    <i class="bi bi-plus-lg"></i>
                    <span>Nuestro compromiso</span>
                </div>

                <p>
                    Fortalecemos la transformación digital y la organización
                    interna para facilitar el acceso a información y mejorar
                    la experiencia de usuarios y personal.
                </p>
            </div>

            <div class="compromiso-banner__grafico" aria-hidden="true">
                <i class="bi bi-display"></i>
                <i class="bi bi-bar-chart-line-fill"></i>
            </div>

        </div>
    </section>

    <section class="section valores-section">
        <div class="valores-titulo reveal">
            <i class="bi bi-plus-lg valores-titulo__icono"></i>
            <h3>Valores institucionales</h3>
        </div>

        <div class="valores-grid">

            <article class="valor-card reveal">
                <div class="valor-card__icono valor-card__icono--morado">
                    <i class="bi bi-people-fill"></i>
                </div>

                <h4>Compromiso</h4>

                <p>
                    Cumplimos con responsabilidad cada uno de nuestros roles.
                </p>
            </article>

            <article class="valor-card reveal">
                <div class="valor-card__icono valor-card__icono--azul">
                    <i class="bi bi-heart-fill"></i>
                </div>

                <h4>Respeto</h4>

                <p>
                    Valoramos a las personas y promovemos un trato digno.
                </p>
            </article>

            <article class="valor-card reveal">
                <div class="valor-card__icono valor-card__icono--verde">
                    <i class="bi bi-shield-check"></i>
                </div>

                <h4>Responsabilidad</h4>

                <p>
                    Actuamos con compromiso en cada proceso para brindar un mejor servicio.
                </p>
            </article>

            <article class="valor-card reveal">
                <div class="valor-card__icono valor-card__icono--rosado">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>

                <h4>Transparencia</h4>

                <p>
                    Actuamos con claridad y honestidad en todos nuestros procesos.
                </p>
            </article>

            <article class="valor-card reveal">
                <div class="valor-card__icono valor-card__icono--naranja">
                    <i class="bi bi-star-fill"></i>
                </div>

                <h4>Vocación de servicio</h4>

                <p>
                    Trabajamos con entrega y dedicación por el bienestar de la población.
                </p>
            </article>

        </div>
    </section>

    <section class="section" id="informacion">
        <div class="section-header reveal">
            <h3>Información de las opciones del sistema</h3>
            <p class="section-desc">
                Cada módulo cumple una función específica dentro de la plataforma institucional,
                permitiendo organizar la información y agilizar los procesos principales.
            </p>
        </div>

        <div class="info-grid">
            <div class="info-card reveal">
                <h4>Descubre todas las funcionalidades del sistema</h4>
                <p>
                    Usa el buscador o la barra de navegación para acceder a los módulos disponibles.
                </p>
            </div>
        </div>
    </section>

    <footer class="footer" id="contacto">
        <div class="footer__contenido">

            <!-- Hospital -->
            <div class="footer__columna footer__institucion">
                <div class="footer-brand">
                    <img
                        src="/assets/images/logohsj.png?v=1"
                        alt="Logo Hospital San José"
                    >

                    <div>
                        <strong>Hospital San José</strong>
                        <p>Unidad de Estadística e Información</p>
                    </div>
                </div>

                <div class="footer__redes">
                    <a
                        href="https://www.facebook.com/p/Hospital-San-Jose-de-Chincha-100064742256936/?locale=es_LA"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Facebook del Hospital San José de Chincha"
                        title="Facebook del Hospital San José de Chincha"
                    >
                        f
                    </a>
                </div>
            </div>

            <!-- Enlaces -->
            <div class="footer__columna">
                <h4>Enlaces</h4>

                <a href="#accesos">Accesos</a>
                <a href="#nosotros">Nosotros</a>
                <a href="#informacion">Información</a>
                <a href="#contacto">Contacto</a>
            </div>

            <!-- Contacto -->
            <div class="footer__columna">
                <h4>Información de contacto</h4>

                <p>
                    <span class="footer__icono">☎</span>
                    +51 960 537 615
                </p>

                <p>
                    <span class="footer__icono">●</span>
                    Chincha, Ica - Perú
                </p>
            </div>

        </div>

        <div class="footer__inferior">
            © <span id="year"></span> Hospital San José -
            Unidad de Estadística e Información.
            Todos los derechos reservados.
        </div>
    </footer>

    <button id="scrollTopBtn" class="scroll-top-btn" aria-label="Ir arriba">↑</button>

    <script>
        window.APP_BASE = "";
        window.UEEI_USER = {
            correo: <?= json_encode($correo, JSON_UNESCAPED_UNICODE) ?>,
            rol: <?= json_encode($rol, JSON_UNESCAPED_UNICODE) ?>,
            area_id: <?= json_encode($areaId, JSON_UNESCAPED_UNICODE) ?>
        };
        window.UEEI_MODULOS = <?= json_encode($modulosAutorizados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>

    <script src="/assets/js/principal.js?v=2"></script>
</body>
</html>
