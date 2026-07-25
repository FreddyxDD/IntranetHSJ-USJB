<?php
$usuario = $_SESSION['cirugias_usuario'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistema del Área de Cirugías</title>

    <link rel="stylesheet" href="<?= e(url_path('/assets/css/LoginLS.css')) ?>?v=2">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
    <main class="login-page">
        <section class="login-stage">
            <div class="login-glass-shell">

                <section class="login-hero">
                    <div class="login-brand-row">
                        <div class="brand-hospital">
                            <img src="<?= e(url_path('/assets/images/fondo.png')) ?>?v=2" alt="Hospital San José Chincha">

                            <div>
                                <span>HOSPITAL</span>
                                <strong>SAN JOSÉ</strong>
                                <small>Comprometidos con la vida</small>
                            </div>
                        </div>

                        <div class="brand-separator"></div>

                        <div class="brand-ueei">
                            <strong>UEeI</strong>
                            <small>Unidad de Estadística<br>e Informática</small>
                        </div>
                    </div>

                    <div class="login-operation-visual">
                        <div class="operation-orbit operation-orbit-one"></div>
                        <div class="operation-orbit operation-orbit-two"></div>
                        <div class="operation-pulse-line"></div>

                        <div class="operation-image-frame">
                            <img
                                class="operation-image"
                                src="<?= e(url_path('/assets/images/Cirugias.png')) ?>?v=2"
                                alt="Sala de operaciones"
                            >
                        </div>

                        <div class="operation-lock-badge">
                            <i data-lucide="lock-keyhole"></i>
                        </div>
                    </div>

                    <div class="login-benefits">
                        <article class="benefit-card">
                            <div class="benefit-icon">
                                <i data-lucide="scalpel"></i>
                            </div>
                            <h4>Gestión Quirúrgica</h4>
                            <p>Agenda y controla procedimientos con precisión.</p>
                            <span></span>
                        </article>

                        <article class="benefit-card">
                            <div class="benefit-icon">
                                <i data-lucide="heart-pulse"></i>
                            </div>
                            <h4>Monitoreo en Tiempo Real</h4>
                            <p>Seguimiento integral del paciente y del proceso.</p>
                            <span></span>
                        </article>

                        <article class="benefit-card">
                            <div class="benefit-icon">
                                <i data-lucide="calendar-days"></i>
                            </div>
                            <h4>Agenda Inteligente</h4>
                            <p>Programación eficiente de salas, equipos y especialistas.</p>
                            <span></span>
                        </article>

                        <article class="benefit-card">
                            <div class="benefit-icon">
                                <i data-lucide="shield-check"></i>
                            </div>
                            <h4>Acceso Seguro</h4>
                            <p>Información protegida bajo estándares institucionales.</p>
                            <span></span>
                        </article>
                    </div>
                </section>

                <section class="login-form-panel">
                    <div class="login-status">
                        <span></span>
                        Sistema en línea
                    </div>

                    <div class="login-pulse-decor"></div>

                    <div class="login-heading">
                        <div class="login-heading-icon">
                            <i data-lucide="scalpel"></i>
                        </div>

                        <div>
                            <h1>Acceso <span>Quirúrgico</span></h1>
                            <p>Sistema del Área de Cirugías</p>
                            <i class="heading-line"></i>
                        </div>
                    </div>

                    <form class="login-card login-form" id="formLogin">
                        <div class="form-group">
                            <label for="usuario">Usuario</label>

                            <div class="input-group">
                                <span class="input-icon">
                                    <i data-lucide="user-round"></i>
                                </span>

                                <input
                                    type="text"
                                    id="usuario"
                                    name="usuario"
                                    placeholder="Usuario"
                                    autocomplete="username"
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Contraseña</label>

                            <div class="input-group">
                                <span class="input-icon">
                                    <i data-lucide="lock-keyhole"></i>
                                </span>

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    placeholder="Contraseña"
                                    autocomplete="current-password"
                                    required
                                >

                                <button
                                    type="button"
                                    class="toggle-password"
                                    id="togglePassword"
                                    aria-label="Mostrar contraseña"
                                >
                                    <i data-lucide="eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="login-options">
                            <label class="remember-user">
                                <input type="checkbox" id="recordarUsuario" checked>
                                <span></span>
                                Recordar usuario
                            </label>

                            <a href="#" class="forgot-password-inline">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>

                        <p id="error-msg" class="error-msg"></p>

                        <button type="submit" class="btn-login">
                            <i data-lucide="log-in"></i>
                            Ingresar al Sistema
                        </button>

                        <div class="login-divider">
                            <span></span>
                            <small>o consulta</small>
                            <span></span>
                        </div>

                        <a href="<?= e(url_path('/manual-cirugias')) ?>" class="manual-usuario">
                            <i data-lucide="book-open"></i>
                            Ver Manual del Sistema
                        </a>
                    </form>

                    <div class="login-warning">
                        <i data-lucide="shield-check"></i>

                        <p>
                            Este sistema es de uso exclusivo del personal autorizado
                            del Hospital San José. Todas las actividades son registradas.
                        </p>
                    </div>
                </section>
            </div>

            <a href="<?= e(url_path('/areas')) ?>" class="btn-volver">
                <i data-lucide="home"></i>
                Volver al inicio
            </a>
        </section>
    </main>

    <script>
        lucide.createIcons();
    </script>

    <script>
    window.APP_BASE = "<?= e(app_base()) ?>";
    window.LOGIN_LS_URL = "<?= e(url_path('/login-ls')) ?>";
    window.PRINCIPAL_CIRUGIAS_URL = "<?= e(url_path('/principal-cirugias')) ?>";
    window.CIRUGIAS_ADMIN_URL = "<?= e(url_path('/cirugias-admin')) ?>";
    </script>

<script src="<?= e(url_path('/assets/js/LoginLS.js')) ?>?v=21"></script>
</body>
</html>
