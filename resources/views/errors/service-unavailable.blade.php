<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Servicio temporalmente no disponible</title>
</head>
<body style="margin: 0">
    <x-error-screen
        title="No pudimos comunicarnos con el servidor de citas"
        :message="$message"
        error-label="Conexión interrumpida"
        :reference="$reference"
        :retry-url="url()->current()"
        :home-url="url('/principal')"
    />
</body>
</html>
