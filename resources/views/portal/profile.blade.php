<?php

$esc = function ($value): string {
    return e((string) $value);
};

$url = function (string $path): string {
    return url($path);
};

$usuario = $usuario ?? [
    'id' => 0,
    'correo' => 'Correo no disponible',
    'rol' => 'trabajador',
    'rol_texto' => 'Personal autorizado',
    'estado' => 0,
    'estado_texto' => 'Desconocido',
    'fecha_creacion' => null,
    'fecha_actualizacion' => null,
];

$formatearFecha = static function (?string $fecha): string {
    if (!$fecha) {
        return 'No disponible';
    }

    try {
        return (new DateTime($fecha))->format('d/m/Y H:i');
    } catch (Throwable $error) {
        return 'No disponible';
    }
};

$fotoPerfil = $url('/assets/images/logohsj.png');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="{{ asset('assets/js/csrf.js') }}" defer></script>
    <title>Perfil del usuario | Hospital San José</title>

    <link rel="icon" href="<?= $esc($url('/assets/images/logohsj.png')) ?>" type="image/png">
    <link rel="stylesheet" href="<?= $esc($url('/assets/css/perfil.css')) ?>?v=1">
</head>

<body
    data-login-url="<?= $esc($url('/ueei-login')) ?>"
    data-home-url="<?= $esc($url('/principal')) ?>"
>
    <header class="topbar">
        <div class="topbar__inner">
            <a class="brand" href="<?= $esc($url('/principal')) ?>">
                <img class="brand__logo" src="<?= $esc($url('/assets/images/logohsj.png')) ?>" alt="Logo Hospital San José">
                <div class="brand__text">
                    <h1>Hospital San José</h1>
                    <p>Perfil institucional del personal autorizado</p>
                </div>
            </a>

            <div class="topbar__actions">
                <a class="topbar__btn" href="<?= $esc($url('/principal')) ?>">Inicio</a>
                <button class="topbar__btn topbar__btn--danger" id="logoutBtn" type="button">Cerrar sesión</button>
            </div>
        </div>
    </header>

    <main class="page">
        <section class="profile-header">
            <div class="cover">
                <div class="cover__badge">Acceso interno del sistema UEeI</div>
            </div>

            <div class="profile-main">
                <div class="profile-top">
                    <div class="profile-identity">
                        <div class="avatar">
                            <img src="<?= $esc($fotoPerfil) ?>" alt="Foto de perfil" id="fotoPerfil">
                        </div>

                        <div class="profile-info">
                            <h2>Cuenta institucional</h2>

                            <p id="correoUsuario">Correo:
                                <?= $esc($usuario['correo']) ?>
                            </p>

                            <p id="sede">
                                Sede: Hospital San José de Chincha
                            </p>

                            <span class="profile-role" id="rolUsuario">
                                <?= $esc($usuario['rol_texto']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <nav class="profile-nav" aria-label="Navegación del perfil">
                    <a href="#informacion" class="active">Información</a>
                    <a href="#actividad">Actividad</a>
                    <a href="#accesos">Accesos</a>
                    <a href="#estado">Estado</a>
                </nav>
            </div>
        </section>

        <section class="content">
            <aside>
                <div class="card" id="informacion">
                    <h3>Información del personal</h3>

                    <div class="info-list">

                        <div class="info-item">
                            <strong>Correo institucional</strong>
                            <span id="infoCorreo">
                                <?= $esc($usuario['correo']) ?>
                            </span>
                        </div>

                        <div class="info-item">
                            <strong>Rol de acceso</strong>
                            <span id="infoRol">
                                <?= $esc($usuario['rol_texto']) ?>
                            </span>
                        </div>

                        <div class="info-item">
                            <strong>Estado de la cuenta</strong>
                            <span id="infoEstado">
                                <?= $esc($usuario['estado_texto']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card" id="accesos">
                    <h3>Accesos rápidos</h3>

                    <div class="quick-links">
                        <a class="quick-link" href="<?= $esc($url('/principal')) ?>">
                            <span>Volver al panel principal</span>
                            <strong>→</strong>
                        </a>

                        <a class="quick-link" href="<?= $esc($url('/informacion')) ?>">
                            <span>Módulo de información</span>
                            <strong>→</strong>
                        </a>

                        <a class="quick-link" href="<?= $esc($url('/eficiencia')) ?>">
                            <span>Módulo de eficiencia</span>
                            <strong>→</strong>
                        </a>

                        <a class="quick-link" href="<?= $esc($url('/produccion')) ?>">
                            <span>Módulo de producción</span>
                            <strong>→</strong>
                        </a>
                    </div>
                </div>
            </aside>

            <div class="content-main">
                <div class="card" id="actividad">
                    <h3>Actividad reciente</h3>

                    <div class="post">
                        <div class="post__head">
                            <div class="post__title">Último acceso al sistema</div>
                            <div class="post__date" id="ultimoAcceso">Hoy</div>
                        </div>
                        <div class="post__body">
                            El usuario ingresó correctamente al sistema institucional UEeI y realizó validación de sesión.
                        </div>
                    </div>

                    <div class="post">
                        <div class="post__head">
                            <div class="post__title">Módulos utilizados recientemente</div>
                            <div class="post__date">Actualizando automáticamente</div>
                        </div>
                        <div class="post__body">
                            Egresos hospitalarios, generación de constancias y administración de registros internos.
                        </div>
                    </div>

                    <div class="post">
                        <div class="post__head">
                            <div class="post__title">Observación institucional</div>
                            <div class="post__date">Sistema interno</div>
                        </div>
                        <div class="post__body">
                            Este perfil pertenece a una cuenta de uso interno autorizada para operaciones administrativas y consulta de información hospitalaria.
                        </div>
                    </div>
                </div>

                <div class="card" id="estado">
                    <h3>Estado del usuario</h3>

                    <div class="status-box">
                        <div class="status-row">
                            <span>Sesión actual</span>
                            <span class="status-badge status-ok">Activa</span>
                        </div>

                        <div class="status-row">
                            <span>Permiso de acceso</span>
                            <span class="status-badge status-ok">Autorizado</span>
                        </div>

                        <div class="status-row">
                            <span>Estado de credenciales</span>
                            <span class="status-badge status-warn">Verificadas</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <footer>
            © <span id="year"></span> Hospital San José - Perfil de personal autorizado
        </footer>
    </main>

    <script src="<?= $esc($url('/assets/js/ueei/perfil.js')) ?>?v=1"></script>
</body>
</html>
