<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['cirugias_usuario'])) {
    header('Location: ' . url_path('/cirugias-login'));
    exit;
}

if ((int) ($_SESSION['cirugias_rol'] ?? 1) !== 0) {
    header('Location: ' . url_path('/principal-cirugias'));
    exit;
}

$usuarioAdmin = (string) ($_SESSION['cirugias_usuario'] ?? 'admin');
$nombreAdmin = $usuarioAdmin;
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Admin | Cirugías</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="<?= e(url_path('/assets/css/cirugias-admin.css')) ?>?v=1">
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
  <main class="admin-layout">
    <aside class="admin-sidebar">
      <div class="brand">
        <div class="brand-icon">
          <i data-lucide="scalpel"></i>
        </div>

        <div>
          <h1>Cirugías</h1>
          <p>Panel administrativo</p>
        </div>
      </div>

      <nav class="admin-menu">
        <a href="<?= e(url_path('/cirugias-admin')) ?>" class="active">
          <i data-lucide="users-round"></i>
          Usuarios
        </a>

        <a href="<?= e(url_path('/principal-cirugias')) ?>">
          <i data-lucide="layout-dashboard"></i>
          Ir al sistema
        </a>

        <button type="button" id="btnLogout">
          <i data-lucide="log-out"></i>
          Cerrar sesión
        </button>
      </nav>
    </aside>

    <section class="admin-content">
      <header class="admin-header">
        <div>
          <span class="admin-kicker">Hospital San José</span>
          <h2>Administración de cuentas</h2>
          <p>Solo usuarios con rol administrador pueden acceder a este panel.</p>
        </div>

        <div class="admin-user">
          <strong><?= e($nombreAdmin) ?></strong>
          <small><?= e($usuarioAdmin) ?> · Admin</small>
        </div>
      </header>

      <section class="admin-grid">
        <article class="admin-card form-card">
          <div class="card-title">
            <div>
              <h3>Crear nuevo usuario</h3>
              <p>Registra cuentas para el sistema de cirugías.</p>
            </div>

            <i data-lucide="user-plus"></i>
          </div>

          <form id="formCrearUsuario">
            <div class="form-group">
              <label for="usuario">Usuario</label>
              <input
                type="text"
                id="usuario"
                required
                placeholder="Ejemplo: usuario_cirugias"
              >
            </div>

            <div class="form-group">
              <label for="password">Contraseña</label>
              <input
                type="password"
                id="password"
                required
                placeholder="Mínimo 6 caracteres"
              >
            </div>

            <div class="form-group">
              <label for="rol">Rol</label>
              <select id="rol" required>
                <option value="1">Usuario</option>
                <option value="0">Administrador</option>
              </select>
            </div>

            <p id="mensajeForm" class="mensaje-form"></p>

            <button type="submit" class="btn-primary">
              <i data-lucide="save"></i>
              Crear cuenta
            </button>
          </form>
        </article>

        <article class="admin-card table-card">
          <div class="card-title">
            <div>
              <h3>Usuarios registrados</h3>
              <p>Control de accesos al módulo de cirugías.</p>
            </div>

            <button type="button" id="btnRecargar" class="btn-light">
              <i data-lucide="refresh-cw"></i>
              Actualizar
            </button>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Usuario</th>
                  <th>Rol</th>
                  <th>Estado</th>
                  <th>Acción</th>
                </tr>
              </thead>

              <tbody id="tablaUsuarios">
                <tr>
                  <td colspan="5" class="empty">Cargando usuarios...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
      </section>
    </section>
  </main>

  <script>
    window.CIRUGIAS_ADMIN_LISTAR_USUARIOS_URL = "<?= e(url_path('/api/cirugias/usuarios')) ?>";
    window.CIRUGIAS_ADMIN_CREAR_USUARIO_URL = "<?= e(url_path('/api/cirugias/usuarios')) ?>";
    window.CIRUGIAS_ADMIN_ESTADO_USUARIO_URL = "<?= e(url_path('/api/cirugias/usuarios/estado')) ?>";
    window.LOGOUT_CIRUGIAS_URL = "<?= e(url_path('/logout-cirugias')) ?>";
    window.LOGIN_LS_URL = "<?= e(url_path('/cirugias-login')) ?>";
  </script>

  <script src="<?= e(url_path('/assets/js/cirugias-admin.js')) ?>?v=1"></script>

  <script>
    lucide.createIcons();
  </script>
</body>
</html>