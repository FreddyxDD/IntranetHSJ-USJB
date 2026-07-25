<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Error inesperado</title></head>
<body style="margin: 0">
    <x-error-screen title="Algo no salió como esperábamos" message="Registramos el incidente. Intenta nuevamente y, si continúa, comunícate con soporte." error-label="Error interno" :retry-url="url()->current()" :home-url="url('/principal')" />
</body></html>
