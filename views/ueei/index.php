<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>UEeI | Hospital San José</title>

    <meta
        name="description"
        content="Sistema de acceso a la Unidad de Estadística e Información del Hospital San José"
    />

    <link rel="icon" href="<?= e(url_path('/assets/images/logohsj.png')) ?>" type="image/png" />
    <link rel="shortcut icon" href="<?= e(url_path('/assets/images/logohsj.png')) ?>" type="image/png" />
    <link rel="stylesheet" href="/assets/css/UEeI.css?v=3">
</head>s

<body>
    <header class="topbar">
        <div class="topbar__inner">
            <div class="brand">
                <img
                    class="brand__logo"
                    src="<?= e(url_path('/assets/images/logohsj.png')) ?>"
                    alt="Logo del Hospital San José"
                />

                <div class="brand__text">
                    <div class="brand__name">Hospital San José</div>
                    <div class="brand__sub">
                        Unidad de Estadística e Información
                    </div>
                </div>
            </div>

            <div class="topbar__right">
                <span class="badge">Acceso institucional</span>
            </div>
        </div>
    </header>

    <main class="shell">
        <section class="hero" aria-labelledby="heroTitle">
            <div class="hero__content">
                <p class="hero__eyebrow">
                    Plataforma interna
                </p>

                <h1 id="heroTitle">
                    Bienvenido al sistema UEeI
                </h1>

                <p>
                    Gestiona reportes, egresos y módulos internos del Hospital San José
                    desde una interfaz segura, moderna y preparada para uso institucional.
                </p>

                <div class="hero__meta">
                    <div class="meta__item">
                        <span class="dot"></span>
                        <span>Acceso seguro con credenciales institucionales.</span>
                    </div>

                    <div class="meta__item">
                        <span class="dot"></span>
                        <span>Panel dinámico según permisos del usuario.</span>
                    </div>

                    <div class="meta__item">
                        <span class="dot"></span>
                        <span>Consulta, registro e importación de información.</span>
                    </div>
                </div>

                <div class="hero__status">
                    <span class="status-pill">Sistema operativo</span>
                    <span class="status-pill">Uso interno</span>
                    <span class="status-pill">Acceso seguro</span>
                </div>

                <div class="hero__stats" aria-label="Resumen del sistema">
                    <div class="stat">
                        <strong>24/7</strong>
                        <span>Disponibilidad interna</span>
                    </div>

                    <div class="stat">
                        <strong>100%</strong>
                        <span>Acceso institucional</span>
                    </div>

                    <div class="stat">
                        <strong>UEeI</strong>
                        <span>Gestión hospitalaria</span>
                    </div>
                </div>

                <div class="hero__foot">
                    <small>
                        © <span id="year"></span> Hospital San José
                    </small>
                </div>
            </div>
        </section>

        <section class="auth" aria-label="Autenticación">
            <div class="card">
                <div class="card__header">
                    <h2 id="formTitle">Iniciar sesión</h2>
                    <p id="formSubtitle">
                        Ingresa con tu correo y contraseña
                    </p>
                </div>

                <form class="form" id="authForm" autocomplete="on" novalidate>
                    <label class="field" for="correo">
                        <span class="field__label">
                            Correo electrónico
                        </span>

                        <input
                            class="field__input"
                            type="email"
                            id="correo"
                            name="correo"
                            placeholder="usuario@hospital.gob.pe"
                            autocomplete="email"
                            required
                            aria-describedby="messageBox"
                        />
                    </label>

                    <label class="field" for="password">
                        <span class="field__label">
                            Contraseña
                        </span>

                        <div class="password">
                            <input
                                class="field__input"
                                id="password"
                                type="password"
                                name="password"
                                placeholder="********"
                                autocomplete="current-password"
                                required
                                aria-describedby="messageBox"
                            />

                            <button
                                class="password__toggle"
                                type="button"
                                id="togglePass"
                                aria-label="Mostrar u ocultar contraseña"
                            >
                                Mostrar
                            </button>
                        </div>
                    </label>

                    <label
                        class="field"
                        id="confirmField"
                        for="confirmarPassword"
                        hidden
                    >
                        <span class="field__label">
                            Confirmar contraseña
                        </span>

                        <div class="password">
                            <input
                                class="field__input"
                                id="confirmarPassword"
                                type="password"
                                name="confirmarPassword"
                                placeholder="********"
                                autocomplete="new-password"
                            />

                            <button
                                class="password__toggle"
                                type="button"
                                id="toggleConfirmPass"
                                aria-label="Mostrar u ocultar confirmación"
                            >
                                Mostrar
                            </button>
                        </div>
                    </label>

                    <label class="check" id="rememberWrap">
                        <input type="checkbox" id="remember" />
                        <span>Recordarme</span>
                    </label>

                    <button class="btn" type="submit" id="submitBtn">
                        Ingresar
                    </button>

                    <button
                        class="link switch-mode"
                        type="button"
                        id="switchModeBtn"
                    >
                        ¿No tienes cuenta? Créate una
                    </button>

                    <div
                        class="message"
                        id="messageBox"
                        role="alert"
                        aria-live="polite"
                    ></div>
                </form>
            </div>
        </section>
    </main>

    <script>
        window.APP_BASE = "<?= e(app_base()) ?>";
    </script>

    <script type="module" src="<?= e(url_path('/assets/js/ueei/main.js')) ?>"></script>
</body>
</html>