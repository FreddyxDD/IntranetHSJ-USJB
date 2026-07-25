<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Constancia {{ str_pad((string) $constancia->numero, 4, '0', STR_PAD_LEFT) }}-{{ $constancia->anio }}</title>
    <style>
        body{font-family:Arial,sans-serif;color:#172033;margin:0;background:#eef2f7}.sheet{box-sizing:border-box;width:210mm;min-height:297mm;margin:12px auto;padding:22mm;background:#fff;box-shadow:0 8px 30px #0002}.header{display:flex;align-items:center;gap:18px;border-bottom:3px solid #0b4a8b;padding-bottom:14px}.header img{width:72px}.header h1{font-size:19px;margin:0;color:#0b4a8b}.title{text-align:center;margin:42px 0 30px}.title h2{font-size:22px;text-decoration:underline}.body{font-size:15px;line-height:1.8;text-align:justify}.data{margin:24px 0;border-collapse:collapse;width:100%}.data td{padding:7px;border-bottom:1px solid #d8dee8}.footer{margin-top:60px;text-align:center}.cancelled{border:3px solid #b91c1c;color:#b91c1c;font-size:24px;font-weight:bold;margin:24px 0;padding:12px;text-align:center}.actions{position:fixed;right:20px;top:20px}.actions button{border:0;border-radius:10px;background:#0b63ce;color:#fff;padding:12px 18px;font-weight:bold;cursor:pointer}@media print{body{background:#fff}.sheet{margin:0;box-shadow:none}.actions{display:none}}@media(max-width:850px){.sheet{width:100%;min-height:100vh;margin:0;padding:24px}.actions{position:static;padding:12px;background:#fff;text-align:right}}
    </style>
</head>
<body>
    <div class="actions"><button onclick="window.print()">Imprimir / guardar PDF</button></div>
    <article class="sheet">
        <header class="header">
            <img src="{{ asset('assets/images/logohsj.png') }}" alt="Hospital San José">
            <div><h1>HOSPITAL SAN JOSÉ DE CHINCHA</h1><div>Unidad de Estadística e Informática</div></div>
        </header>
        <div class="title"><h2>CONSTANCIA DE EGRESO HOSPITALARIO</h2><strong>N.° {{ str_pad((string) $constancia->numero, 4, '0', STR_PAD_LEFT) }}-{{ $constancia->anio }}</strong></div>
        @if ($constancia->estado === 'anulada')
            <div class="cancelled">CONSTANCIA ANULADA<br><small>{{ $constancia->motivo_anulacion }}</small></div>
        @endif
        <section class="body">
            <p>Se deja constancia que la persona cuyos datos se indican a continuación registra atención de hospitalización en esta institución:</p>
            <table class="data">
                <tr><td><strong>Paciente</strong></td><td>{{ $constancia->paciente }}</td></tr>
                <tr><td><strong>Documento</strong></td><td>{{ $constancia->doc_iden ?: 'No consignado' }}</td></tr>
                <tr><td><strong>Historia clínica</strong></td><td>{{ $constancia->numhc }}</td></tr>
                <tr><td><strong>Fecha de ingreso</strong></td><td>{{ $constancia->fecing?->format('d/m/Y') }}</td></tr>
                <tr><td><strong>Fecha de egreso</strong></td><td>{{ $constancia->fecegr?->format('d/m/Y') }}</td></tr>
                <tr><td><strong>Servicio / UPS</strong></td><td>{{ $constancia->servicio ?: $constancia->ups }}</td></tr>
                <tr><td><strong>Diagnóstico principal</strong></td><td>{{ $constancia->coddiag1 }} — {{ $constancia->descdiag1 }}</td></tr>
            </table>
            <p>Se expide la presente constancia a solicitud del interesado para los fines que estime convenientes.</p>
            @if ($constancia->observacion)<p><strong>Observación:</strong> {{ $constancia->observacion }}</p>@endif
            <p>Chincha, {{ now()->translatedFormat('d \\d\\e F \\d\\e Y') }}.</p>
        </section>
        <footer class="footer"><div>______________________________________</div><strong>{{ $constancia->issuer_display_name ?: 'Responsable autorizado' }}</strong><div>Hospital San José de Chincha</div></footer>
    </article>
</body>
</html>
