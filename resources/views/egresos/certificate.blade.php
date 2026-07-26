@php($canPrint = ($allowPrint ?? false) && $constancia->canBePrinted())
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $document['correlative'] }} - Constancia de Hospitalización</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            background: #e5e7eb;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
        }
        .print-actions {
            position: fixed;
            right: 18px;
            top: 18px;
            z-index: 20;
        }
        .print-actions button {
            border: 0;
            border-radius: 9px;
            background: #0b63ce;
            color: #fff;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            padding: 12px 18px;
        }
        .legal-notice {
            background: #fff;
            border: 1px solid #fecaca;
            border-radius: 9px;
            color: #991b1b;
            font-size: 13px;
            font-weight: 700;
            left: 18px;
            max-width: 520px;
            padding: 11px 14px;
            position: fixed;
            top: 18px;
            z-index: 20;
        }
        .print-revoked { display: none; }
        .sheet {
            background: #fff;
            box-shadow: 0 8px 30px #0002;
            height: 297mm;
            margin: 12px auto;
            overflow: hidden;
            position: relative;
            width: 210mm;
        }
        .sheet.sheet-multiple {
            height: auto;
            min-height: 297mm;
            overflow: visible;
        }
        .sheet-multiple .content {
            left: auto;
            min-height: 297mm;
            padding: 88mm 25mm 15mm;
            position: relative;
            top: auto;
            width: auto;
        }
        .minsa-logo {
            height: 16mm;
            left: 35mm;
            object-fit: contain;
            position: absolute;
            top: 12mm;
            width: 22mm;
        }
        .institution {
            font-size: 9pt;
            left: 20mm;
            line-height: 5mm;
            position: absolute;
            top: 34mm;
        }
        .institution strong {
            display: block;
            font-size: 10pt;
        }
        .correlative {
            align-items: center;
            border: .8mm solid #000;
            display: flex;
            font-size: 11pt;
            font-weight: 700;
            height: 15mm;
            justify-content: center;
            left: 135mm;
            position: absolute;
            top: 12mm;
            width: 60mm;
        }
        .watermark {
            height: 170mm;
            left: 20mm;
            object-fit: contain;
            opacity: .075;
            position: absolute;
            top: 63mm;
            width: 170mm;
            z-index: 0;
        }
        .document-title {
            font-size: 16pt;
            font-weight: 700;
            left: 20mm;
            position: absolute;
            text-align: center;
            top: 61mm;
            width: 170mm;
        }
        .content {
            font-size: 12pt;
            left: 25mm;
            line-height: 7mm;
            position: absolute;
            text-align: justify;
            top: 88mm;
            width: 160mm;
            z-index: 1;
        }
        .content p { margin: 0; }
        .statement-title {
            font-weight: 700;
            margin-top: 9mm !important;
        }
        .statement {
            margin-top: 5mm !important;
            text-indent: 10mm;
        }
        .diagnoses {
            margin-top: 6mm;
        }
        .diagnoses-title {
            font-weight: 700;
            margin-bottom: 2mm;
        }
        .diagnosis {
            font-size: 11pt;
            line-height: 6mm;
            margin-left: 10mm;
            padding-left: 7mm;
            position: relative;
            text-align: left;
        }
        .diagnosis-number {
            font-weight: 700;
            left: 0;
            position: absolute;
        }
        .episode-list {
            margin-top: 5mm;
        }
        .episode {
            border-left: .8mm solid #1d4ed8;
            break-inside: avoid;
            margin-bottom: 4mm;
            page-break-inside: avoid;
            padding: 2mm 0 2mm 4mm;
        }
        .episode-title {
            font-size: 10.5pt;
            font-weight: 700;
            line-height: 5.5mm;
        }
        .episode-detail {
            font-size: 10pt;
            line-height: 5.5mm;
        }
        .episode .diagnosis {
            font-size: 9.5pt;
            line-height: 5mm;
            margin-left: 4mm;
        }
        .closing {
            margin-top: 10mm !important;
            text-indent: 0;
        }
        .issue-date {
            margin-top: 7mm !important;
        }
        .signature-area {
            height: 47mm;
            margin-top: 7mm;
            position: relative;
        }
        .signature-line {
            border-top: .2mm solid #000;
            position: absolute;
            right: 5mm;
            text-align: center;
            top: 25mm;
            width: 62mm;
        }
        .signature-line strong,
        .signature-line span {
            display: block;
            font-size: 9pt;
            line-height: 5mm;
        }
        .initials {
            bottom: 0;
            font-size: 10pt;
            font-weight: 700;
            left: 0;
            line-height: 5mm;
            position: absolute;
        }
        .cancelled {
            border: 1.2mm solid #b91c1c;
            color: #b91c1c;
            font-size: 22pt;
            font-weight: 700;
            left: 42mm;
            opacity: .75;
            padding: 4mm 8mm;
            position: absolute;
            text-align: center;
            top: 133mm;
            transform: rotate(-20deg);
            width: 126mm;
            z-index: 10;
        }
        @media print {
            body { background: #fff; }
            .print-actions, .legal-notice { display: none; }
            .sheet { display: none !important; }
            .print-revoked {
                color: #991b1b;
                display: block;
                font-family: Arial, Helvetica, sans-serif;
                font-size: 18pt;
                font-weight: 700;
                margin: 35mm auto;
                max-width: 170mm;
                text-align: center;
            }
            body.print-authorized .sheet { box-shadow: none; display: block !important; margin: 0; }
            body.print-authorized .print-revoked { display: none; }
        }
        @media screen and (max-width: 850px) {
            .sheet {
                margin: 0;
                transform-origin: top left;
            }
            .print-actions { position: fixed; }
        }
    </style>
</head>
<body>
    @if ($canPrint)
        <div class="print-actions">
            <button id="authorize-print" type="button" data-url="{{ $printAuthorizationUrl }}">Autorizar e imprimir</button>
        </div>
    @else
        <div class="legal-notice">
            @if ($constancia->estado === 'anulada')
                Documento anulado: disponible únicamente para consulta histórica. Su reimpresión está bloqueada.
            @else
                Vista de consulta sin autorización de impresión.
            @endif
        </div>
    @endif

    <div class="print-revoked">
        IMPRESIÓN NO AUTORIZADA<br>
        Esta constancia se encuentra anulada o fue abierta únicamente en modo consulta.
    </div>

    <article class="sheet @if ($document['episode_count'] > 1) sheet-multiple @endif">
        <img class="watermark" src="/assets/images/fondo.png" alt="" aria-hidden="true">
        <img class="minsa-logo" src="/assets/images/logo.jpeg" alt="Ministerio de Salud">

        <div class="institution">
            <strong>DIRECCION REGIONAL DE SALUD</strong>
            <div>Hospital San José: Av. Alva Maurtua N°600</div>
            <div>056-261232 Telefax: 056-261421 Chincha Alta</div>
        </div>

        <div class="correlative">{{ $document['correlative'] }}</div>
        <h1 class="document-title">CONSTANCIA DE HOSPITALIZACION</h1>

        @if ($constancia->estado === 'anulada')
            <div class="cancelled">
                CONSTANCIA ANULADA
                @if ($constancia->motivo_anulacion)
                    <div style="font-size: 10pt; margin-top: 2mm;">{{ $constancia->motivo_anulacion }}</div>
                @endif
            </div>
        @endif

        <section class="content">
            <p>El que suscribe Director Ejecutivo del Hospital “San José” de Chincha, a través de la<br>Jefatura de la Oficina de Estadística e Informática:</p>

            <p class="statement-title">HACE CONSTAR:</p>

            @if ($document['episode_count'] === 1)
                <p class="statement">
                    Que, la paciente <strong>{{ $document['patient'] }}</strong>, identificada con
                    {{ $document['document_type'] }} N° <strong>{{ $document['document'] }}</strong>,
                    registra ingreso al servicio de hospitalización de
                    <strong>{{ $document['service'] }}</strong> desde el
                    <strong>{{ $document['admission_date'] }}</strong> hasta
                    <strong>{{ $document['discharge_date'] }}</strong>. Alta por Indicación Médica,
                    con condición de Alta Mejorado y pronóstico Bueno; según se registra en la hoja
                    automatizada de epicrisis de la Historia Clínica N°
                    <strong>{{ $document['history'] }}</strong>.
                </p>

                <div class="diagnoses">
                    <div class="diagnoses-title">Diagnóstico</div>
                    @forelse ($document['diagnoses'] as $diagnosis)
                        <div class="diagnosis">
                            <span class="diagnosis-number">{{ $loop->iteration }}.-</span>
                            <strong>{{ $diagnosis['code'] }}:</strong>
                            {{ $diagnosis['description'] }}
                        </div>
                    @empty
                        <div class="diagnosis">CIE-10: NO REGISTRADO</div>
                    @endforelse
                </div>
            @else
                <p class="statement">
                    Que, la paciente <strong>{{ $document['patient'] }}</strong>, identificada con
                    {{ $document['document_type'] }} N° <strong>{{ $document['document'] }}</strong>,
                    registra los siguientes <strong>{{ $document['episode_count'] }} episodios de
                    hospitalización</strong>, según las hojas automatizadas de epicrisis de la
                    Historia Clínica N° <strong>{{ $document['history'] }}</strong>:
                </p>

                <div class="episode-list">
                    @foreach ($document['episodes'] as $episode)
                        <div class="episode">
                            <div class="episode-title">
                                Episodio {{ $loop->iteration }}: {{ $episode['service'] }}
                            </div>
                            <div class="episode-detail">
                                Desde <strong>{{ $episode['admission_date'] }}</strong> hasta
                                <strong>{{ $episode['discharge_date'] }}</strong>.
                                Condición de alta: <strong>{{ $episode['condition'] }}</strong>.
                            </div>
                            @forelse ($episode['diagnoses'] as $diagnosis)
                                <div class="diagnosis">
                                    <span class="diagnosis-number">{{ $loop->iteration }}.-</span>
                                    <strong>{{ $diagnosis['code'] }}:</strong>
                                    {{ $diagnosis['description'] }}
                                </div>
                            @empty
                                <div class="diagnosis">CIE-10: NO REGISTRADO</div>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="closing">Se extiende la presente Constancia para los fines que estime conveniente, según lo solicitado por el recurrente.</p>

            <p class="issue-date">Chincha Alta, {{ $document['issue_date'] }}</p>

            <div class="signature-area">
                <div class="signature-line">
                    <strong>{{ $document['director_title'] }}</strong>
                    @if ($document['director_name'])
                        <span>{{ $document['director_name'] }}</span>
                    @endif
                    <span>Hospital San José - Chincha</span>
                </div>
                <div class="initials">
                    <div>{{ $document['director_initials'] }}/DE-HSJCH.</div>
                    <div>{{ $document['ccp_initials'] }}/{{ $document['lower_code'] }}</div>
                </div>
            </div>
        </section>
    </article>
    @if ($canPrint)
        <script>
            (() => {
                const button = document.getElementById('authorize-print');
                window.addEventListener('afterprint', () => document.body.classList.remove('print-authorized'));
                button?.addEventListener('click', async () => {
                    button.disabled = true;
                    button.textContent = 'Validando estado…';
                    try {
                        const response = await fetch(button.dataset.url, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                        });
                        const payload = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            const validation = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                            throw new Error(validation || payload.message || 'La impresión no fue autorizada.');
                        }
                        document.body.classList.add('print-authorized');
                        window.print();
                    } catch (error) {
                        document.body.classList.remove('print-authorized');
                        window.alert(error.message);
                    } finally {
                        button.disabled = false;
                        button.textContent = 'Autorizar e imprimir';
                    }
                });
            })();
        </script>
    @endif
</body>
</html>
