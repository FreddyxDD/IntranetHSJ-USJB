<?php
$base = app_base();
?>

<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Login Citas Admin | Hospital San José</title>

    <link rel="icon" href="../assets/images/logohsj.png" />
    <link rel="stylesheet" href="<?= e(url_path('/assets/css/citasadminLO.css')) ?>?v=1">
  </head>

  <body>
    <main class="login-page">
      <section class="login-card">
        <div class="login-brand">
          <img
            src="../assets/images/logohsj.png"
            alt="Logo Hospital San José"
          />

          <div>
            <h1>Hospital San José</h1>
            <p>Panel administrativo de citas</p>
          </div>
        </div>

        <div class="login-header">
          <div class="login-icon">📅</div>

          <h2>Citas Admin</h2>

          <p>
            Ingrese sus credenciales para gestionar los registros enviados desde
            la sala de espera virtual.
          </p>
        </div>

        <form id="formCitasAdminLogin" class="login-form">
          <div class="campo">
            <label for="usuario">Usuario</label>
            <input
              type="text"
              id="usuario"
              placeholder="Ingrese usuario"
              autocomplete="username"
              required
            />
          </div>

          <div class="campo">
            <label for="password">Contraseña</label>
            <input
              type="password"
              id="password"
              placeholder="Ingrese contraseña"
              autocomplete="current-password"
              required
            />
          </div>

          <p id="mensajeLogin" class="mensaje-login"></p>

          <button id="btnIngresar" type="submit">Ingresar</button>
          <button id="btnAbrirRegistro" class="btn-registro" type="button">
            Crear cuenta admin
          </button>
        </form>

        <a href="../pages/Areas.html" class="volver"> ⬅ Volver a áreas </a>
      </section>
    </main>

    <div id="fondoRegistro" class="fondo-registro oculto"></div>

    <section id="modalRegistro" class="modal-registro oculto">
      <div class="modal-registro-header">
        <div>
          <h3>Crear cuenta admin</h3>
          <p>Registra un usuario para acceder al panel de Citas Admin.</p>
        </div>

        <button id="btnCerrarRegistro" type="button">×</button>
      </div>

      <form id="formCitasAdminRegistro" class="login-form">
        <div class="campo">
          <label for="usuarioRegistro">Usuario</label>
          <input
            type="text"
            id="usuarioRegistro"
            placeholder="Cree un usuario"
            autocomplete="username"
            required
          />
        </div>

        <div class="campo">
          <label for="passwordRegistro">Contraseña</label>
          <input
            type="password"
            id="passwordRegistro"
            placeholder="Cree una contraseña"
            autocomplete="new-password"
            required
          />
        </div>

        <div class="campo">
          <label for="passwordRegistro2">Confirmar contraseña</label>
          <input
            type="password"
            id="passwordRegistro2"
            placeholder="Repita la contraseña"
            autocomplete="new-password"
            required
          />
        </div>

        <p id="mensajeRegistro" class="mensaje-login"></p>

        <button id="btnRegistrar" type="submit">Registrar cuenta</button>
      </form>
    </section>

    <script>
    window.APP_BASE = "<?= e(app_base()) ?>";
    </script>

    <script src="<?= e(url_path('/assets/js/citasadminLO.js')) ?>?v=1"></script>
  </body>
</html>
