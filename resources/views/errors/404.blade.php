<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Página no encontrada</title></head>
<body style="margin: 0">
    <x-error-screen title="No encontramos esa página" message="La dirección puede haber cambiado o el recurso ya no está disponible." error-label="Ruta 404" :retry-url="url()->current()" :home-url="url('/principal')" />
</body></html>
