<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Lista de Pacientes por Consultorio</title>
        <style>
            @page {
                size: A4 portrait;
                margin: 8mm;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                color: #111827;
                font-family: Arial, Helvetica, sans-serif;
                font-size: 10px;
            }

            .toolbar {
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                padding: 12px;
                background: #f4f4f5;
                border-bottom: 1px solid #d4d4d8;
            }

            .toolbar button {
                border: 1px solid #0f766e;
                border-radius: 6px;
                background: #0f766e;
                color: white;
                cursor: pointer;
                font-weight: 700;
                padding: 8px 12px;
            }

            .sheet {
                min-height: 281mm;
                page-break-after: always;
                position: relative;
            }

            .sheet:last-child {
                page-break-after: auto;
            }

            .report-header {
                align-items: center;
                border: 1px solid #111827;
                display: grid;
                gap: 8px;
                grid-template-columns: 64px 1fr 72px;
                margin-bottom: 7px;
                padding: 6px 8px;
            }

            .logo-main,
            .logo-oite {
                display: block;
                height: 58px;
                max-width: 68px;
                object-fit: contain;
            }

            .logo-oite {
                justify-self: end;
            }

            .institution {
                text-align: center;
                line-height: 1.15;
            }

            .institution .name {
                font-size: 12px;
                font-weight: 700;
                text-transform: uppercase;
            }

            .institution .office {
                font-size: 10px;
                font-weight: 700;
                margin-top: 2px;
                text-transform: uppercase;
            }

            .title {
                font-size: 16px;
                font-weight: 800;
                letter-spacing: .02em;
                margin-top: 4px;
                text-transform: uppercase;
            }

            .meta {
                display: grid;
                grid-template-columns: 1fr auto;
                gap: 8px;
                border: 1px solid #111827;
                padding: 6px 8px;
                margin-bottom: 6px;
            }

            .consultorio {
                font-size: 12px;
                font-weight: 800;
                margin-bottom: 2px;
                text-transform: uppercase;
            }

            .subline {
                margin-top: 2px;
            }

            .summary {
                text-align: right;
                white-space: nowrap;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                border: 1px solid #111827;
                padding: 3px 4px;
                vertical-align: top;
            }

            th {
                background: #e5e7eb;
                font-size: 9px;
                text-align: left;
                text-transform: uppercase;
            }

            .num {
                text-align: right;
                width: 22px;
            }

            .time {
                width: 56px;
                white-space: nowrap;
            }

            .hc {
                width: 78px;
                font-size: 12px;
                font-weight: 800;
                white-space: nowrap;
            }

            .hc-title {
                line-height: 1.1;
            }

            .doc {
                width: 76px;
                white-space: nowrap;
            }

            .financing {
                width: 82px;
                font-weight: 700;
                white-space: nowrap;
            }

            .patient {
                font-weight: 700;
            }

            .muted {
                color: #52525b;
                font-size: 9px;
            }

            .blank-row td {
                height: 24px;
                color: #71717a;
            }

            .signature {
                border: 1px solid #111827;
                display: grid;
                gap: 12px;
                grid-template-columns: 1fr 1fr;
                margin-top: 8px;
                padding: 8px;
            }

            .signature-title {
                font-weight: 800;
                grid-column: 1 / -1;
                text-transform: uppercase;
            }

            .signature-box {
                min-height: 54px;
                padding-top: 28px;
                text-align: center;
            }

            .signature-line {
                border-top: 1px solid #111827;
                padding-top: 4px;
            }

            .report-footer {
                align-items: center;
                color: #52525b;
                display: flex;
                font-size: 9px;
                justify-content: space-between;
                margin-top: 7px;
            }

            .footer-logo {
                height: 24px;
                object-fit: contain;
                width: 30px;
            }

            @media print {
                .toolbar {
                    display: none;
                }

                body {
                    print-color-adjust: exact;
                    -webkit-print-color-adjust: exact;
                }
            }
        </style>
    </head>
    <body>
        <div class="toolbar">
            <button type="button" onclick="window.print()">Imprimir</button>
            <button type="button" onclick="window.close()">Cerrar</button>
        </div>

        @forelse ($groups as $group)
            @php($blankRows = $group['blank_rows'] ?? max(0, $group['capacity'] - $group['total']))
            <section class="sheet">
                <header class="report-header">
                    <img class="logo-main" src="{{ asset('images/logo/logo.png') }}" alt="Hospital San Jose">
                    <div class="institution">
                        <div class="name">Hospital San Jose de Chincha</div>
                        <div>Modulo de Citas y Reportes Operativos</div>
                        <div class="office">Lista de Pacientes por Consultorio</div>
                        <div class="title">Lista de Pacientes</div>
                    </div>
                    <img class="logo-oite" src="{{ asset('images/reportes/logo-oite.png') }}" alt="OITE">
                </header>

                <div class="meta">
                    <div>
                        <div class="consultorio">{{ $group['servicio'] }} ({{ $group['servicio_id'] }})</div>
                        <div class="subline">Medico: <strong>{{ $group['medico'] }}</strong></div>
                        <div class="subline">Especialidad: {{ $group['especialidad'] }}</div>
                    </div>
                    <div class="summary">
                        <div>Fecha: <strong>{{ \Illuminate\Support\Carbon::parse($filters['fecha'])->format('d/m/Y') }}</strong></div>
                        <div>Turno: {{ ucfirst($filters['turno']) }}</div>
                        <div>Orden: {{ $filters['orden'] === 'hc' ? 'Terminal de historia' : 'Hora de cita' }}</div>
                        <div>Registradas: <strong>{{ $group['total'] }}</strong> / Cupos: <strong>{{ $group['capacity'] }}</strong></div>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th class="num">N.</th>
                            <th class="time">Fecha / Hora</th>
                            <th class="hc hc-title">N&deg; de HC</th>
                            <th class="financing">Financ.</th>
                            <th>Paciente</th>
                            <th class="doc">DNI / Doc.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($group['rows'] as $row)
                            @php($cita = $row['cita'])
                            @if (! $cita)
                                <tr class="blank-row">
                                    <td class="num">{{ $row['number'] }}</td>
                                    <td class="time">
                                        @if ($row['slot_start'])
                                            {{ \Illuminate\Support\Carbon::parse($filters['fecha'])->format('d/m/Y') }}<br>
                                            <strong>{{ $row['slot_start'] }}</strong>
                                        @else
                                            &nbsp;
                                        @endif
                                    </td>
                                    <td class="hc"></td>
                                    <td class="financing">&nbsp;</td>
                                    <td>Paciente:</td>
                                    <td class="doc">&nbsp;</td>
                                </tr>
                                @continue
                            @endif
                            @php($markers = collect([
                                $cita->EsCitaAdicional ? 'Cita adicional' : null,
                                \App\Support\AppointmentTurn::isExtended($cita) ? 'Turno extendido' : null,
                                $row['is_overflow'] ? 'Fuera de cupo programado' : null,
                            ])->filter()->implode(' / '))
                            <tr>
                                <td class="num">{{ $row['number'] }}</td>
                                <td class="time">
                                    {{ \Illuminate\Support\Carbon::parse($filters['fecha'])->format('d/m/Y') }}<br>
                                    <strong>{{ trim($cita->HoraInicio) }}</strong>
                                </td>
                                <td class="hc">{{ \App\Support\ClinicalHistoryNumber::format($cita->paciente?->NroHistoriaClinica) }}</td>
                                <td class="financing">{{ \App\Http\Controllers\Sigh\PatientListPrintController::financingLabel($cita) }}</td>
                                <td>
                                    <div class="patient">{{ $cita->paciente?->nombre_completo ?: '-' }}</div>
                                    @if ($markers !== '')
                                        <div class="muted">{{ $markers }}</div>
                                    @endif
                                </td>
                                <td class="doc">{{ $cita->paciente?->NroDocumento ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="signature">
                    <div class="signature-title">Conformidad de recepcion de historias clinicas</div>
                    <div class="signature-box">
                        <div class="signature-line">Entrega Archivo / Admision</div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-line">Recibe Consultorio</div>
                    </div>
                    <div>Fecha y hora de entrega: __________________________</div>
                    <div>Observaciones: ____________________________________</div>
                </div>

                <footer class="report-footer">
                    <span>Generado: {{ $printedAt->format('d/m/Y H:i') }}</span>
                    <span>OITE - Oficina de Informatica, Telecomunicaciones y Estadistica</span>
                    <img class="footer-logo" src="{{ asset('images/reportes/logo-oite.png') }}" alt="OITE">
                </footer>
            </section>
        @empty
            <section class="sheet">
                <header class="report-header">
                    <img class="logo-main" src="{{ asset('images/logo/logo.png') }}" alt="Hospital San Jose">
                    <div class="institution">
                        <div class="name">Hospital San Jose de Chincha</div>
                        <div class="title">Lista de Pacientes</div>
                    </div>
                    <img class="logo-oite" src="{{ asset('images/reportes/logo-oite.png') }}" alt="OITE">
                </header>
                <p>No hay consultorios seleccionados o no existen citas para los filtros aplicados.</p>
            </section>
        @endforelse

        <script>
            window.addEventListener('load', () => {
                window.setTimeout(() => window.print(), 350);
            });
        </script>
    </body>
</html>
