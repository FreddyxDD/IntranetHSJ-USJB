<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Servicio no disponible</title></head>
<body style="margin: 0">
    <x-error-screen title="Servicio temporalmente no disponible" message="Estamos teniendo dificultades para responder. Intenta nuevamente en unos minutos." error-label="Servicio 503" :reference="$reference ?? null" :retry-url="url()->current()" :home-url="url('/principal')" />
</body></html>
