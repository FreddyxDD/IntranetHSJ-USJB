<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Acceso denegado</title></head>
<body style="margin: 0">
    <x-error-screen title="Acceso denegado" message="No tienes permiso para ingresar a este módulo. Solicita la habilitación al administrador del sistema." error-label="Acceso 403" :show-retry="false" :home-url="url('/principal')" />
</body></html>
