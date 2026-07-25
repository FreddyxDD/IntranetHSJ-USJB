<?php
$correo = $_SESSION['ueei_correo'] ?? '';
$rol = $_SESSION['ueei_rol'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login UVI | Hospital San José</title>

    <link
        rel="icon"
        href="<?= e(url_path('/assets/images/logohsj.png')) ?>"
        type="image/png"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link
        rel="stylesheet"
        href="<?= e(url_path('/assets/css/LoginUVI.css')) ?>?v=3"
    >
</head>

<body>
<header class="topbar">
    <div class="topbar__inner">
        <a href="<?= e(url_path('/principal')) ?>" class="brand">
            <img
                src="<?= e(url_path('/assets/images/logohsj.png')) ?>"
                alt="Logo Hospital San José"
                class="brand__logo"
            >

            <div>
                <strong class="brand__name">Hospital San José</strong>
                <span class="brand__sub">Unidad de Vigilancia Intensiva</span>
            </div>
        </a>

        <a href="<?= e(url_path('/principal')) ?>" class="back-btn">
            <i class="bi bi-house-door"></i>
            <span>Volver al inicio</span>
        </a>
    </div>
</header>

<main class="page">
    <section class="login-card">

        <div class="visual-panel">
            <div class="visual-panel__content">
                <span class="pill">Acceso institucional</span>

                <h1>Sistema UVI</h1>

                <p>
                    Gestión y monitoreo inteligente para una atención crítica segura.
                </p>
            </div>

            <div class="benefits">
                <article class="benefit">
                    <i class="bi bi-activity"></i>
                    <div>
                        <strong>Monitoreo clínico</strong>
                        <span>Seguimiento continuo de información.</span>
                    </div>
                </article>

                <article class="benefit">
                    <i class="bi bi-shield-check"></i>
                    <div>
                        <strong>Acceso institucional</strong>
                        <span>Credenciales y control de acceso.</span>
                    </div>
                </article>

                <article class="benefit">
                    <i class="bi bi-hospital"></i>
                    <div>
                        <strong>Entorno hospitalario</strong>
                        <span>Uso claro, rápido y profesional.</span>
                    </div>
                </article>
            </div>
        </div>

        <div class="form-panel">
            <div class="login-box">
                <div class="login-box__header">
                    <span class="uvi-badge">UVI</span>
                    <h2>Iniciar sesión</h2>
                    <p>Ingresa con tu usuario personal y contraseña.</p>
                </div>

                <form
                    class="form"
                    id="loginForm"
                    method="post"
                    autocomplete="on"
                >
                    <div class="field">
                        <label for="usuario">Usuario</label>

                        <div class="input-group">
                            <i class="bi bi-person"></i>

                            <input
                                type="text"
                                id="usuario"
                                name="usuario"
                                placeholder="Ingresa tu usuario"
                                autocomplete="username"
                                maxlength="100"
                                required
                            >
                        </div>

                        <small class="field__error" id="usuarioError"></small>
                    </div>

                    <div class="field">
                        <label for="password">Contraseña</label>

                        <div class="input-group">
                            <i class="bi bi-lock"></i>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Ingresa tu contraseña"
                                autocomplete="current-password"
                                maxlength="255"
                                required
                            >

                            <button
                                type="button"
                                id="togglePassword"
                                class="password-toggle"
                                aria-label="Mostrar contraseña"
                                aria-pressed="false"
                            >
                                Mostrar
                            </button>
                        </div>

                        <small class="field__error" id="passwordError"></small>
                    </div>

                    <label class="remember" for="recordarme">
                        <input
                            type="checkbox"
                            id="recordarme"
                            name="recordarme"
                            value="1"
                        >
                        <span>Recordarme</span>
                    </label>

                    <button
                        type="submit"
                        class="login-btn"
                        id="loginButton"
                    >
                        <span class="btn-primary__text">Ingresar</span>
                        <span class="btn-primary__loader" aria-hidden="true"></span>
                    </button>

                    <div
                        class="message-box"
                        id="messageBox"
                        role="status"
                        aria-live="polite"
                    >
                        <i class="bi bi-shield-check"></i>
                        <span>
                            Acceso exclusivo para personal autorizado del
                            Hospital San José.
                        </span>
                    </div>
                </form>
            </div>
        </div>

    </section>
</main>

<script>
    window.APP_BASE = "<?= e(app_base()) ?>";

    window.UEEI_USER = {
        correo: <?= json_encode($correo, JSON_UNESCAPED_UNICODE) ?>,
        rol: <?= json_encode($rol, JSON_UNESCAPED_UNICODE) ?>
    };
</script>

<script src="<?= e(url_path('/assets/js/UVILogin.js')) ?>?v=3"></script>
</body>
</html>
