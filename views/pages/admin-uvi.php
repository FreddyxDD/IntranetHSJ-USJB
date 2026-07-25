<?php
$correo = $_SESSION['ueei_correo'] ?? '';
$rol = $_SESSION['ueei_rol'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Administrador UVI | Hospital San José</title>

    <link rel="icon" href="<?= e(url_path('/assets/images/logohsj.png')) ?>" type="image/png" />
    <link rel="stylesheet" href="<?= e(url_path('/assets/css/AdminUvi.css')) ?>?v=1" />
</head>

<body>
    <header class="topbar">
        <div class="topbar__inner">
            <div class="brand">
                <img
                    class="brand__logo"
                    src="<?= e(url_path('/assets/images/logohsj.png')) ?>"
                    alt="Logo Hospital San José"
                />

                <div class="brand__text">
                    <div class="brand__name">Hospital San José</div>
                    <div class="brand__sub">Panel Administrativo UVI</div>
                </div>
            </div>

            <a href="<?= e(url_path('/principal')) ?>" class="back-btn">
                Ir al sistema
            </a>
        </div>
    </header>

    <main class="page">
        <section class="admin-shell">
            <div class="admin-left">
                <span class="visual__badge">Administrador UVI</span>

                <h1>Creación de usuarios</h1>

                <p class="left__text">
                    Desde este panel puedes registrar personal autorizado para el acceso
                    al sistema de la Unidad de Vigilancia Intensiva.
                </p>

                <div class="visual__features">
                    <div class="feature">
                        <div class="feature__icon">+</div>

                        <div>
                            <strong>Registrar personal</strong>
                            <span>Creación segura de nuevas cuentas autorizadas.</span>
                        </div>
                    </div>

                    <div class="feature">
                        <div class="feature__icon">✓</div>

                        <div>
                            <strong>Gestión por roles</strong>
                            <span>Asignación de permisos como administrador o usuario.</span>
                        </div>
                    </div>

                    <div class="feature">
                        <div class="feature__icon">♥</div>

                        <div>
                            <strong>Entorno controlado</strong>
                            <span>Acceso exclusivo para administración institucional.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-right">
                <div class="admin-box">
                    <div class="admin-box__header">
                        <span class="mini-badge">UVI</span>

                        <h2>Nuevo usuario</h2>

                        <p>Registra cuentas para el personal autorizado.</p>
                    </div>

                    <form id="crearUsuarioForm" class="form" autocomplete="off">
                        <label class="field">
                            <span class="field__label">Usuario</span>

                            <input
                                class="field__input"
                                type="text"
                                id="nuevoUsuario"
                                placeholder="Ingresa el usuario"
                                required
                            />
                        </label>

                        <label class="field">
                            <span class="field__label">Contraseña</span>

                            <div class="password-wrapper">
                                <input
                                    class="field__input"
                                    type="password"
                                    id="nuevoPassword"
                                    placeholder="********"
                                    required
                                />

                                <button type="button" id="togglePassword" class="eye-btn">
                                    <i data-lucide="eye"></i>
                                </button>
                            </div>
                        </label>

                        <label class="field">
                            <span class="field__label">Rol</span>

                            <select class="field__input" id="nuevoRol" required>
                                <option value="0">Usuario</option>
                                <option value="1">Administrador</option>
                            </select>
                        </label>

                        <button type="submit" class="btn-primary">
                            Crear cuenta
                        </button>

                        <div id="mensajeAdmin" class="message-box">
                            Aquí podrás registrar usuarios y asignar su rol dentro del sistema.
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <button id="btnUsuarios" class="floating-btn">
        <i data-lucide="users"></i>
    </button>

    <div id="modalUsuarios" class="modal-usuarios">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Usuarios registrados</h3>
                <button id="cerrarModal">✕</button>
            </div>

            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>

                <tbody id="tablaUsuarios"></tbody>
            </table>
        </div>
    </div>

    <div id="modalEditar" class="modal-usuarios">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h3>Editar usuario</h3>
                <button id="cerrarEditar">✕</button>
            </div>

            <form id="formEditarUsuario" class="form">
                <input type="hidden" id="editId">

                <label class="field">
                    <span class="field__label">Usuario</span>
                    <input
                        class="field__input"
                        type="text"
                        id="editUsuario"
                        required
                    >
                </label>

                <label class="field">
                    <span class="field__label">Rol</span>

                    <select class="field__input" id="editRol">
                        <option value="0">Usuario</option>
                        <option value="1">Administrador</option>
                    </select>
                </label>

                <button class="btn-primary">
                    Guardar cambios
                </button>
            </form>
        </div>
    </div>

    <div id="modalEliminar" class="modal-usuarios">
        <div class="modal-content modal-small modal-confirm">
            <div class="confirm-icon">
                <i data-lucide="alert-triangle"></i>
            </div>

            <h3 class="confirm-title">Eliminar usuario</h3>

            <p class="texto-confirmacion">
                Esta acción eliminará el usuario del sistema.
                <br>
                <strong>¿Deseas continuar?</strong>
            </p>

            <div class="acciones-confirmacion">
                <button id="cancelarEliminar" class="btn-cancel">
                    Cancelar
                </button>

                <button id="confirmarEliminar" class="btn-danger">
                    Eliminar
                </button>
            </div>
        </div>
    </div>

    <script>
        window.APP_BASE = "<?= e(app_base()) ?>";

        window.UEEI_USER = {
            correo: <?= json_encode($correo, JSON_UNESCAPED_UNICODE) ?>,
            rol: <?= json_encode($rol, JSON_UNESCAPED_UNICODE) ?>
        };
    </script>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="<?= e(url_path('/assets/js/AdminUVI.js')) ?>?v=1"></script>
</body>
</html>