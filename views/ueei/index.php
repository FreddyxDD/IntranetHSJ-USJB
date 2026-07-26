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
                            <span>Si tu DNI existe en HSJ_Identity, la cuenta se activará automáticamente. Si aún no existe, podrás enviar tus datos para aprobación.</span>
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

                        <div class="manual-identity-form" id="manualIdentityForm" hidden>
                            <div class="manual-identity-form__header">
                                <strong>Completa tus datos personales</strong>
                                <span>La información será revisada por un administrador antes de habilitar la cuenta.</span>
                            </div>

                            <label class="field" for="registrationNames">
                                <span class="field__label">Nombres</span>
                                <input class="field__input" id="registrationNames" name="names" type="text" maxlength="180" autocomplete="given-name">
                            </label>

                            <div class="registration-fields-grid">
                                <label class="field" for="registrationPaternalName">
                                    <span class="field__label">Apellido paterno</span>
                                    <input class="field__input" id="registrationPaternalName" name="paternal_last_name" type="text" maxlength="80" autocomplete="family-name">
                                </label>

                                <label class="field" for="registrationMaternalName">
                                    <span class="field__label">Apellido materno</span>
                                    <input class="field__input" id="registrationMaternalName" name="maternal_last_name" type="text" maxlength="80">
                                </label>
                            </div>

                            <label class="field" for="registrationBirthDate">
                                <span class="field__label">Fecha de nacimiento</span>
                                <input class="field__input" id="registrationBirthDate" name="birth_date" type="date" autocomplete="bday">
                            </label>

                            <label class="field" for="registrationEmail">
                                <span class="field__label">Correo electrónico</span>
                                <input class="field__input" id="registrationEmail" name="email" type="email" maxlength="255" autocomplete="email">
                            </label>

                            <label class="field" for="registrationPhone">
                                <span class="field__label">Teléfono</span>
                                <input class="field__input" id="registrationPhone" name="phone" type="tel" maxlength="30" autocomplete="tel" placeholder="Ej. 987654321">
                            </label>
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

    <dialog class="activation-dialog" id="pendingDialog" aria-labelledby="pendingTitle">
        <div class="activation-dialog__content">
            <div class="activation-dialog__badge activation-dialog__badge--pending">Solicitud registrada</div>
            <h2 id="pendingTitle">Pendiente de aprobación</h2>
            <p>La cuenta fue creada, pero todavía no tiene acceso a las áreas del Intranet.</p>

            <dl class="activation-credentials">
                <div>
                    <dt>Usuario asignado</dt>
                    <dd id="pendingUsername"></dd>
                </div>
                <div>
                    <dt>Contraseña inicial</dt>
                    <dd id="pendingPassword"></dd>
                </div>
                <div>
                    <dt>Estado</dt>
                    <dd>Pendiente</dd>
                </div>
            </dl>

            <div class="activation-dialog__warning">
                Un administrador revisará tus datos. Podrás iniciar sesión con estas credenciales después de que la solicitud sea aprobada.
            </div>

            <label class="activation-confirmation">
                <input type="checkbox" id="pendingAcknowledgement" />
                <span>Confirmo que guardé mis datos de acceso y comprendí que debo esperar la aprobación.</span>
            </label>

            <button class="btn" type="button" id="closePendingBtn" disabled>
                Entendido, volver al inicio
            </button>
        </div>
    </dialog>

    <script>
        window.APP_BASE = "<?= e(app_base()) ?>";
    </script>

    <script type="module" src="<?= e(url_path('/assets/js/ueei/main.js')) ?>"></script>
</body>
</html>
