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
</head>

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
                        Ingresa con tu DNI, correo o usuario
                    </p>
                </div>

                <form class="form" id="authForm" autocomplete="on" novalidate>
                    <label class="field" id="loginIdentifierField" for="correo">
                        <span class="field__label">
                            DNI, correo o usuario
                        </span>

                        <input
                            class="field__input"
                            type="text"
                            id="correo"
                            name="correo"
                            placeholder="Ingresa tu DNI o correo"
                            autocomplete="username"
                            required
                            aria-describedby="messageBox"
                        />
                    </label>

                    <label class="field" id="loginPasswordField" for="password">
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

                    <div class="registration-panel" id="registrationPanel" hidden>
                        <div class="registration-notice">
                            <strong>Registro con identidad institucional</strong>
                            <span>Solo podrás crear la cuenta si tu DNI se encuentra activo en HSJ_Identity.</span>
                        </div>

                        <label class="field" for="registrationDni">
                            <span class="field__label">Número de DNI</span>
                            <input
                                class="field__input"
                                id="registrationDni"
                                name="registrationDni"
                                type="text"
                                inputmode="numeric"
                                maxlength="8"
                                autocomplete="off"
                                placeholder="8 dígitos"
                            />
                        </label>

                        <button class="btn btn--secondary" type="button" id="validateDniBtn">
                            Validar DNI
                        </button>

                        <div class="identity-result" id="identityResult" hidden aria-live="polite">
                            <span class="identity-result__icon" aria-hidden="true">✓</span>
                            <div>
                                <strong>Identidad validada</strong>
                                <span id="validatedPersonName"></span>
                            </div>
                        </div>

                        <div class="credential-rule">
                            <strong>Tu acceso se generará automáticamente</strong>
                            <span><b>Usuario:</b> tu número de DNI.</span>
                            <span><b>Contraseña inicial:</b> fecha de nacimiento (DDMMAAAA) + últimos 4 dígitos del DNI.</span>
                            <span><b>Perfil inicial:</b> consulta. Los accesos adicionales se solicitan al administrador.</span>
                        </div>
                    </div>

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

    <dialog class="activation-dialog" id="activationDialog" aria-labelledby="activationTitle">
        <div class="activation-dialog__content">
            <div class="activation-dialog__badge">Cuenta activada</div>
            <h2 id="activationTitle">Guarda tus datos de acceso</h2>
            <p>Tu sesión ya fue iniciada. Antes de navegar, revisa y confirma estas instrucciones.</p>

            <dl class="activation-credentials">
                <div>
                    <dt>Usuario para iniciar sesión</dt>
                    <dd id="activationUsername"></dd>
                </div>
                <div>
                    <dt>Contraseña inicial</dt>
                    <dd id="activationPassword"></dd>
                </div>
                <div>
                    <dt>Nivel de acceso</dt>
                    <dd>Consulta</dd>
                </div>
            </dl>

            <div class="activation-dialog__warning">
                Si necesitas ingresar a otros módulos o realizar modificaciones, deberás solicitar el acceso al administrador de la plataforma.
            </div>

            <label class="activation-confirmation">
                <input type="checkbox" id="activationAcknowledgement" />
                <span>Confirmo que leí y guardé mis datos de acceso.</span>
            </label>

            <button class="btn" type="button" id="confirmActivationBtn" disabled>
                Entendido, ingresar al portal
            </button>
            <div class="message" id="activationMessage" role="alert" aria-live="polite"></div>
        </div>
    </dialog>

    <script>
        window.APP_BASE = "<?= e(app_base()) ?>";
    </script>

    <script type="module" src="<?= e(url_path('/assets/js/ueei/main.js')) ?>"></script>
</body>
</html>
