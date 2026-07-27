<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Áreas | Hospital San José</title>

    <link rel="icon" href="<?= e(asset('assets/images/logohsj.png')) ?>?v=1" type="image/png" />
    <link rel="stylesheet" href="<?= e(asset('assets/css/Areas.css')) ?>?v=3" />
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    />
</head>

<body>
    <header class="topbar">
        <div class="topbar__inner">
            <a href="<?= e(url('/principal')) ?>" class="brand">
                <img
                    src="<?= e(asset('assets/images/logohsj.png')) ?>?v=1"
                    alt="Logo Hospital San José"
                />

                <div class="brand__text">
                    <h1>Hospital San José</h1>
                    <p>Unidad de Estadística e Información</p>
                </div>
            </a>

            <button
                type="button"
                class="navbar-toggle hs-collapse-toggle"
                id="hsj-navbar-toggle"
                data-hs-collapse="#hsj-navbar-collapse"
                aria-controls="hsj-navbar-collapse"
                aria-label="Abrir menú de navegación"
                aria-expanded="false"
            >
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            <div id="hsj-navbar-collapse" class="navbar-collapse hidden">
                <nav class="navbar-links" aria-label="Navegación principal">
                    <a href="<?= e(url('/principal')) ?>">
                        <i class="bi bi-house-door" aria-hidden="true"></i>
                        <span>Inicio</span>
                    </a>
                    <a href="<?= e(url('/areas')) ?>" class="active" aria-current="page">
                        <i class="bi bi-grid" aria-hidden="true"></i>
                        <span>Áreas</span>
                    </a>
                    <?php if ($isAdmin): ?>
                        <a href="<?= e(url('/admin-ueei')) ?>">
                            <i class="bi bi-people" aria-hidden="true"></i>
                            <span>Perfiles y accesos (CRUD)</span>
                        </a>
                    <?php endif; ?>
                </nav>

                <div class="hs-dropdown profile-dropdown">
                    <button
                        id="hsj-profile-menu"
                        type="button"
                        class="hs-dropdown-toggle profile-trigger"
                        aria-haspopup="menu"
                        aria-expanded="false"
                    >
                        <span class="profile-avatar" aria-hidden="true"><?= e($profileInitial) ?></span>
                        <span class="profile-summary">
                            <strong><?= e($profileLabel) ?></strong>
                            <small><?= e($rol ?: 'Usuario') ?></small>
                        </span>
                        <i class="bi bi-chevron-down profile-chevron" aria-hidden="true"></i>
                    </button>

                    <div
                        class="hs-dropdown-menu profile-menu hidden"
                        role="menu"
                        aria-orientation="vertical"
                        aria-labelledby="hsj-profile-menu"
                    >
                        <div class="profile-menu__identity">
                            <strong><?= e($correo) ?></strong>
                            <span><?= e($rol ?: 'Usuario autorizado') ?></span>
                        </div>

                        <a href="<?= e(url('/perfil')) ?>" role="menuitem">
                            <i class="bi bi-person-circle" aria-hidden="true"></i>
                            Mi perfil
                        </a>

                        <?php if ($isAdmin): ?>
                            <a href="<?= e(url('/admin-ueei')) ?>" role="menuitem">
                                <i class="bi bi-person-gear" aria-hidden="true"></i>
                                Gestionar perfiles
                            </a>
                        <?php endif; ?>

                        <form method="post" action="<?= e(url('/logout-ueei')) ?>">
                            <button type="submit" role="menuitem">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="contenedor">
        <section class="hero">
            <div class="hero__content">
                <p class="hero__welcome">¡Bienvenido!</p>

                <h2>Áreas <span>del Sistema</span></h2>

                <p>
                    Selecciona un área para gestionar y visualizar la información
                    estadística del hospital.
                </p>
            </div>

            <div class="hero__image"></div>
        </section>

        <?php if (!empty($quickAccess)): ?>
            <section class="quick-access">
                <div class="quick-access__info">
                    <div class="quick-access__icon">
                        <i class="bi bi-lightning-charge-fill" aria-hidden="true"></i>
                    </div>

                    <div>
                        <h3>Acceso rápido</h3>
                        <p>Navega fácilmente entre las áreas habilitadas para tu usuario</p>
                    </div>
                </div>

                <div class="quick-access__buttons">
                    <?php foreach ($quickAccess as $modulo): ?>
                        <a href="<?= e(url($modulo['ruta'])) ?>">
                            <?= $modulo['icon_html'] ?>
                            <span><?= e($modulo['nombre'] ?? 'Módulo') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="areas-section">
            <div class="areas-section__header">
                <h3>Módulos autorizados</h3>
                <p>
                    Aquí solo aparecen los módulos permitidos para tu área o rol.
                </p>
            </div>

            <?php if (empty($modulos)): ?>
                <div class="cards-grid">
                    <article class="card">
                        <div class="card__icon card__icon--emoji">!</div>

                        <div class="card__content">
                            <h4>Sin módulos asignados</h4>
                            <p>
                                Tu cuenta todavía no tiene módulos habilitados. Solicita acceso al administrador.
                            </p>
                        </div>
                    </article>
                </div>
            <?php else: ?>
                <div class="cards-grid">
                    <?php foreach ($modulos as $modulo): ?>
                        <?php
                            $codigo = (string) ($modulo['codigo'] ?? 'modulo');
                            $nombre = (string) ($modulo['nombre'] ?? 'Módulo');
                            $descripcion = (string) ($modulo['descripcion'] ?? 'Acceso al módulo del sistema.');
                            $ruta = (string) ($modulo['ruta'] ?? '#');
                            $icono = (string) ($modulo['icono'] ?? '');
                        ?>

                        <a href="<?= e(url($ruta)) ?>" class="<?= e($modulo['card_class']) ?>">
                            <div class="card__icon">
                                <?= $modulo['icon_html'] ?>
                            </div>

                            <div class="card__content">
                                <h4><?= e($nombre) ?></h4>
                                <p><?= e($descripcion) ?></p>
                                <span class="card__arrow" aria-hidden="true">→</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <div class="site-footer__inner">
            <a href="<?= e(url('/principal')) ?>" class="site-footer__brand">
                <img src="<?= e(asset('assets/images/logohsj.png')) ?>" alt="" />
                <span>
                    <strong>Hospital San José</strong>
                    <small>Unidad de Estadística e Información</small>
                </span>
            </a>

            <nav class="site-footer__links" aria-label="Enlaces del pie de página">
                <a href="<?= e(url('/principal')) ?>">Inicio</a>
                <a href="<?= e(url('/areas')) ?>">Áreas</a>
                <a href="<?= e(url('/perfil')) ?>">Mi perfil</a>
            </nav>

            <p>© <?= date('Y') ?> Hospital San José. Uso institucional.</p>
        </div>
    </footer>

    <script src="<?= e(asset('assets/vendor/preline/preline.js')) ?>?v=4.2.0"></script>
</body>
</html>
