<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Egresos - Intranet HSJ</title>
    <link rel="icon" href="/assets/images/logohsj.png">
    <link rel="stylesheet" href="/assets/css/tailwind.css">
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
        <div class="mx-auto flex max-w-screen-2xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <a href="/areas" class="grid size-11 shrink-0 place-items-center rounded-xl bg-blue-50 ring-1 ring-blue-100">
                    <img src="/assets/images/logohsj.png" alt="Hospital San José" class="size-9 object-contain">
                </a>
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-blue-950 sm:text-base">Hospital San José de Chincha</p>
                    <p class="truncate text-xs text-slate-500">Módulo central de Egresos</p>
                </div>
            </div>
            <div class="hs-dropdown relative inline-flex">
                <button type="button" class="hs-dropdown-toggle inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50">
                    <span class="grid size-8 place-items-center rounded-lg bg-blue-600 text-xs text-white">{{ mb_strtoupper(mb_substr($centralUser['name'], 0, 1)) }}</span>
                    <span class="hidden max-w-40 truncate sm:block">{{ $centralUser['name'] }}</span>
                    <span aria-hidden="true">⌄</span>
                </button>
                <div class="hs-dropdown-menu mt-2 hidden min-w-64 rounded-xl border border-slate-200 bg-white p-2 opacity-0 shadow-xl transition-[opacity,margin] hs-dropdown-open:opacity-100">
                    <div class="border-b border-slate-100 px-3 py-2">
                        <p class="truncate text-sm font-semibold">{{ $centralUser['name'] }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $centralUser['email'] }}</p>
                    </div>
                    <a href="/areas" class="mt-1 block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">Volver a módulos</a>
                    <a href="/perfil" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">Mi perfil</a>
                    <form method="post" action="/logout-ueei">
                        @csrf
                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-rose-700 hover:bg-rose-50">Cerrar sesión</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-blue-950 via-blue-800 to-cyan-600 p-6 text-white shadow-xl sm:p-8">
            <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold ring-1 ring-white/25">Datos consolidados en Intranet_HSJ</span>
                    <h1 class="mt-4 text-2xl font-black sm:text-4xl">Consulta de egresos hospitalarios</h1>
                    <p class="mt-2 max-w-2xl text-sm text-blue-100 sm:text-base">Localiza al paciente por historia clínica, documento, nombres o apellidos y genera su constancia con trazabilidad central.</p>
                </div>
                <a href="/areas" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-3 text-sm font-bold text-blue-900 shadow hover:bg-blue-50">← Panel principal</a>
            </div>
        </section>

        <section id="metrics" class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach (['totalEgresos' => 'Egresos registrados', 'egresosMes' => 'Egresos del mes', 'reportesGenerados' => 'Constancias emitidas', 'constanciasActivas' => 'Constancias activas'] as $key => $label)
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-black text-blue-900" data-metric="{{ $key }}">—</p>
                </article>
            @endforeach
        </section>

        <div class="mt-6 flex gap-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-sm" role="tablist">
            <button class="eg-tab whitespace-nowrap rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white" data-panel="records">Consultar egresos</button>
            @if ($abilities['manageImports'])
                <button class="eg-tab whitespace-nowrap rounded-xl px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100" data-panel="imports">Importar</button>
            @endif
            @if ($abilities['viewReports'])
                <button class="eg-tab whitespace-nowrap rounded-xl px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100" data-panel="reports">Reportes</button>
            @endif
            @if ($abilities['viewHistory'])
                <button class="eg-tab whitespace-nowrap rounded-xl px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100" data-panel="history">Historial de constancias</button>
            @endif
            @if ($abilities['viewAudit'] ?? false)
                <button class="eg-tab whitespace-nowrap rounded-xl px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100" data-panel="audit">Auditoría</button>
            @endif
            @if ($abilities['manageConfiguration'])
                <button class="eg-tab whitespace-nowrap rounded-xl px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100" data-panel="configuration">Configuración</button>
            @endif
        </div>

        <section id="panel-records" class="eg-panel mt-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <form id="search-form" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_auto_auto_auto_auto]">
                    <label class="sr-only" for="search-query">Buscar paciente</label>
                    <input id="search-query" type="search" maxlength="150" placeholder="Historia clínica, DNI, nombres o apellidos" class="min-h-12 flex-1 rounded-xl border border-slate-300 px-4 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    <input id="search-from" type="date" aria-label="Fecha desde" class="min-h-12 rounded-xl border border-slate-300 px-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    <input id="search-to" type="date" aria-label="Fecha hasta" class="min-h-12 rounded-xl border border-slate-300 px-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    <button class="min-h-12 rounded-xl bg-blue-600 px-6 font-bold text-white shadow-sm hover:bg-blue-700" type="submit">Buscar</button>
                    @if ($abilities['createRecords'])
                        <button id="new-record" class="min-h-12 rounded-xl bg-emerald-600 px-5 font-bold text-white hover:bg-emerald-700" type="button">Registrar excepción</button>
                    @endif
                </form>
                <p id="records-status" class="mt-3 text-sm text-slate-500" aria-live="polite">Cargando registros recientes…</p>
            </div>

            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr><th class="px-4 py-3">HC / Documento</th><th class="px-4 py-3">Paciente</th><th class="px-4 py-3">Ingreso / Egreso</th><th class="px-4 py-3">UPS</th><th class="px-4 py-3">Diagnóstico</th><th class="px-4 py-3 text-right">Acciones</th></tr>
                        </thead>
                        <tbody id="records-body" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
                <div id="records-pagination" class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm"></div>
            </div>
        </section>

        @if ($abilities['manageImports'])
            <section id="panel-imports" class="eg-panel mt-4 hidden">
                <form id="import-form" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-lg font-black text-blue-950">Importación masiva controlada</h2>
                    <p class="mt-1 text-sm text-slate-500">Formatos admitidos: CSV, XLSX y DBF. Primero se analizará cada fila; ningún dato se insertará hasta que revise el resultado y confirme la carga.</p>
                    <label class="mt-5 block rounded-2xl border-2 border-dashed border-blue-200 bg-blue-50/60 p-6 text-center">
                        <span class="block text-sm font-bold text-blue-950">Seleccione el archivo de egresos</span>
                        <input name="archivo" type="file" required accept=".csv,.xlsx,.dbf" class="mt-4 block w-full text-sm">
                    </label>
                    <div class="mt-5 flex items-center justify-end gap-3">
                        <span id="import-status" class="mr-auto text-sm text-slate-600" aria-live="polite"></span>
                        <button type="submit" class="rounded-xl bg-blue-600 px-5 py-3 font-bold text-white hover:bg-blue-700">Analizar archivo</button>
                    </div>
                </form>
                <div id="import-result" class="mt-4 hidden rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow-sm sm:p-6"></div>
                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-lg font-black text-blue-950">Importaciones recientes</h2>
                    <div id="imports-list" class="mt-4 space-y-3"></div>
                </div>
            </section>
        @endif

        @if ($abilities['viewReports'])
            <section id="panel-reports" class="eg-panel mt-4 hidden">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <form id="report-form" class="grid flex-1 gap-3 sm:grid-cols-3">
                            <label><span class="mb-1 block text-xs font-bold uppercase text-slate-500">Desde</span><input name="date_from" type="date" class="min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                            <label><span class="mb-1 block text-xs font-bold uppercase text-slate-500">Hasta</span><input name="date_to" type="date" class="min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                            <button class="min-h-11 self-end rounded-xl bg-blue-600 px-5 font-bold text-white">Actualizar reporte</button>
                        </form>
                        <div class="flex gap-2">
                            <a id="export-csv" href="#" class="rounded-xl border border-emerald-200 px-4 py-3 text-sm font-bold text-emerald-700 hover:bg-emerald-50">Exportar CSV</a>
                            <a id="export-xlsx" href="#" class="rounded-xl border border-blue-200 px-4 py-3 text-sm font-bold text-blue-700 hover:bg-blue-50">Exportar XLSX</a>
                        </div>
                    </div>
                    <div class="mt-6 grid gap-5 lg:grid-cols-2">
                        <div><h3 class="font-bold text-blue-950">Egresos por mes</h3><div id="monthly-report" class="mt-3 space-y-2"></div></div>
                        <div><h3 class="font-bold text-blue-950">Egresos por UPS</h3><div id="services-report" class="mt-3 max-h-96 space-y-2 overflow-y-auto"></div></div>
                    </div>
                </div>
            </section>
        @endif

        @if ($abilities['viewHistory'])
            <section id="panel-history" class="eg-panel mt-4 hidden">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <form id="history-form" class="flex flex-col gap-3 sm:flex-row">
                        <input id="history-query" type="search" maxlength="150" placeholder="Buscar constancia por HC, documento o paciente" class="min-h-12 flex-1 rounded-xl border border-slate-300 px-4 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        <button class="rounded-xl bg-slate-800 px-6 py-3 font-bold text-white hover:bg-slate-900">Buscar</button>
                    </form>
                </div>
                <div id="history-list" class="mt-4 grid gap-3"></div>
            </section>
        @endif

        @if ($abilities['manageConfiguration'])
            <section id="panel-configuration" class="eg-panel mt-4 hidden">
                <form id="configuration-form" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-black text-blue-950">Registrar configuración institucional</h2>
                        <p class="mt-1 text-sm text-slate-500">El formulario inicia vacío. Al guardar se crea un registro histórico y sus valores quedan activos únicamente para las nuevas constancias.</p>
                    </div>
                    <div class="grid items-start gap-6 lg:grid-cols-[minmax(300px,.78fr)_minmax(480px,1.22fr)]">
                        <div class="space-y-4">
                            @foreach ([
                                ['nombre_director', 'Nombre del director', 180, true, 'Aparece debajo del cargo, en el bloque de firma de la vista preliminar.'],
                                ['cargo_director', 'Cargo del director', 180, true, 'Define el título principal mostrado sobre la firma del documento.'],
                                ['nombre_jefe', 'Nombre del jefe de Estadística', 180, true, 'Identifica al responsable de la jefatura y queda registrado en la trazabilidad institucional.'],
                                ['cargo_jefe', 'Cargo del jefe de Estadística', 180, true, 'Registra el cargo formal del responsable que interviene en la emisión.'],
                                ['iniciales_director', 'Iniciales del director', 20, false, 'Se usan como respaldo de las iniciales superiores del pie si no se consignan las del jefe.'],
                                ['iniciales_jefe', 'Iniciales del jefe', 20, true, 'Aparecen en la primera línea del bloque de iniciales, en la parte inferior izquierda.'],
                                ['iniciales_ccp', 'Iniciales de elaboración / CCP', 20, true, 'Aparecen en la segunda línea del bloque de iniciales del documento.'],
                            ] as [$name, $label, $max, $required, $help])
                                <label class="block">
                                    <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-slate-700">
                                        {{ $label }}
                                        <span class="hs-tooltip relative inline-block [--placement:top]">
                                            <button type="button" class="hs-tooltip-toggle grid size-5 place-items-center rounded-full bg-blue-50 text-xs font-black text-blue-700" aria-label="Ayuda sobre {{ $label }}">?</button>
                                            <span class="hs-tooltip-content invisible absolute z-30 inline-block w-64 rounded-lg bg-slate-900 px-3 py-2 text-xs font-medium leading-5 text-white opacity-0 shadow-lg transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">{{ $help }}</span>
                                        </span>
                                    </span>
                                    <input name="{{ $name }}" maxlength="{{ $max }}" autocomplete="off" @required($required) class="min-h-11 w-full rounded-xl border border-slate-300 px-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                                </label>
                            @endforeach
                            <label class="block">
                                <span class="mb-1.5 flex items-center gap-2 text-sm font-bold text-slate-700">
                                    Observación institucional
                                    <span class="hs-tooltip relative inline-block [--placement:top]">
                                        <button type="button" class="hs-tooltip-toggle grid size-5 place-items-center rounded-full bg-blue-50 text-xs font-black text-blue-700" aria-label="Ayuda sobre la observación institucional">?</button>
                                        <span class="hs-tooltip-content invisible absolute z-30 inline-block w-64 rounded-lg bg-slate-900 px-3 py-2 text-xs font-medium leading-5 text-white opacity-0 shadow-lg transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">Justifica o describe el alcance de esta configuración. Se conserva en la constancia y en auditoría, pero no se imprime como parte del cuerpo legal.</span>
                                    </span>
                                </span>
                                <textarea name="observacion" maxlength="2000" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"></textarea>
                            </label>
                            <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center">
                                <span id="configuration-status" class="mr-auto text-sm text-slate-500" aria-live="polite">Complete los campos para crear un nuevo registro.</span>
                                <button type="submit" class="rounded-xl bg-blue-600 px-5 py-3 font-bold text-white hover:bg-blue-700">Registrar y activar</button>
                            </div>
                        </div>
                        <aside class="min-w-0 lg:sticky lg:top-4">
                            <div class="mb-4">
                                <h3 class="font-black text-blue-950">Vista preliminar del documento</h3>
                                <p class="mt-1 text-sm text-slate-500">Los cambios se reflejan en tiempo real y solo se activan después de registrar.</p>
                            </div>
                            <div class="overflow-x-auto rounded-2xl bg-slate-100 p-3 sm:p-5">
                                <article id="certificate-preview" class="relative mx-auto aspect-[210/297] min-w-[420px] max-w-[610px] overflow-hidden bg-white p-[7%] text-[7px] leading-relaxed text-black shadow-xl sm:text-[9px]">
                                    <img src="/assets/images/fondo.png" alt="" class="pointer-events-none absolute left-1/2 top-1/2 w-[78%] -translate-x-1/2 -translate-y-1/2 opacity-[0.07]">
                                    <div class="relative z-10">
                                        <div class="flex items-start justify-between">
                                            <div class="w-32 text-center"><img src="/assets/images/logo.jpeg" alt="Ministerio de Salud" class="mx-auto w-16"></div>
                                            <div class="border-2 border-black px-5 py-3 text-center font-black">N° 0001-{{ now()->year }}-HSJ-GIN</div>
                                        </div>
                                        <div class="mt-5 font-black">DIRECCIÓN REGIONAL DE SALUD</div>
                                        <div>Hospital San José: Av. Alva Maurtua N°600</div>
                                        <div>056-261232 Telefax: 056-261421 Chincha Alta</div>
                                        <h4 class="mt-10 text-center text-base font-black">CONSTANCIA DE HOSPITALIZACIÓN</h4>
                                        <p class="mt-8">El que suscribe Director Ejecutivo del Hospital “San José” de Chincha, a través de la Jefatura de la Oficina de Estadística e Informática:</p>
                                        <p class="mt-5 font-black">HACE CONSTAR:</p>
                                        <p class="mt-4 text-justify">Que, la paciente <strong>PACIENTE DE REFERENCIA</strong>, identificada con DNI N° <strong>00000000</strong>, registra ingreso al servicio de hospitalización de <strong>GINECOLOGÍA</strong> desde el <strong>01/01/{{ now()->year }}</strong> hasta <strong>02/01/{{ now()->year }}</strong>. Alta por Indicación Médica, con condición de Alta Mejorado y pronóstico Bueno; según se registra en la hoja automatizada de epicrisis de la Historia Clínica N° <strong>000000</strong>.</p>
                                        <p class="mt-4 font-black">Diagnóstico</p>
                                        <p class="ml-5"><strong>1.- Z00.0:</strong> Diagnóstico de referencia</p>
                                        <p class="mt-6">Se extiende la presente Constancia para los fines que estime conveniente, según lo solicitado por el recurrente.</p>
                                        <p class="mt-5">Chincha Alta, {{ now()->translatedFormat('d \\d\\e F \\d\\e Y') }}</p>
                                        <div class="mt-10 flex items-end justify-between">
                                            <div class="font-black">
                                                <div><span data-preview="iniciales_jefe">MASG</span>/DE-HSJCH.</div>
                                                <div><span data-preview="iniciales_ccp">KRJ</span>/J-GIN</div>
                                            </div>
                                            <div class="w-44 border-t border-black pt-1 text-center">
                                                <strong data-preview="cargo_director">DIRECCIÓN EJECUTIVA</strong>
                                                <span data-preview="nombre_director" class="block"></span>
                                                <span class="block">Hospital San José - Chincha</span>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </aside>
                    </div>
                </form>
                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h3 class="font-black text-blue-950">Configuraciones registradas</h3>
                            <p class="mt-1 text-sm text-slate-500">Historial de versiones activadas, responsable y fecha de registro.</p>
                        </div>
                        <div id="configuration-active" class="text-xs font-bold text-emerald-700"></div>
                    </div>
                    <div id="configuration-history" class="mt-4 grid gap-3"></div>
                </div>
            </section>
        @endif

        @if ($abilities['viewAudit'] ?? false)
            <section id="panel-audit" class="eg-panel mt-4 hidden">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                    <div>
                        <h2 class="text-lg font-black text-blue-950">Auditoría del módulo de Egresos</h2>
                        <p class="mt-1 text-sm text-slate-500">Generaciones, modificaciones, anulaciones, configuraciones, importaciones y mantenimientos registrados con usuario, fecha, IP y valores modificados.</p>
                    </div>
                    <form id="audit-form" class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_220px_auto_auto_auto]">
                        <input id="audit-query" type="search" maxlength="150" placeholder="Usuario, evento o identificador" class="min-h-11 rounded-xl border border-slate-300 px-3">
                        <select id="audit-type" class="min-h-11 rounded-xl border border-slate-300 px-3">
                            <option value="">Todos los eventos</option>
                            <option value="certificate.generar">Constancias generadas</option>
                            <option value="certificate.editar">Constancias editadas</option>
                            <option value="certificate.anular">Constancias anuladas</option>
                            <option value="certificate.imprimir">Impresiones habilitadas</option>
                            <option value="certificate_configuration.registered">Configuraciones registradas</option>
                            <option value="record.create">Egresos registrados</option>
                            <option value="record.update">Egresos corregidos</option>
                            <option value="import.previewed">Archivos analizados</option>
                            <option value="import.completed">Importaciones</option>
                        </select>
                        <input id="audit-from" type="date" aria-label="Auditoría desde" class="min-h-11 rounded-xl border border-slate-300 px-3">
                        <input id="audit-to" type="date" aria-label="Auditoría hasta" class="min-h-11 rounded-xl border border-slate-300 px-3">
                        <button class="rounded-xl bg-slate-800 px-5 font-bold text-white">Filtrar</button>
                    </form>
                </div>
                <div id="audit-list" class="mt-4 space-y-3"></div>
                <div id="audit-pagination" class="mt-4 flex items-center justify-between text-sm"></div>
            </section>
        @endif
    </main>

    <div id="detail-modal" class="hs-overlay fixed inset-0 z-[80] hidden size-full overflow-y-auto bg-slate-950/50" role="dialog" tabindex="-1">
        <div class="m-3 flex min-h-[calc(100%-1.5rem)] items-center justify-center">
            <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4"><h2 class="font-bold text-blue-950">Detalle del egreso</h2><button data-hs-overlay="#detail-modal" class="rounded-lg p-2 hover:bg-slate-100" aria-label="Cerrar">✕</button></div>
                <div id="detail-content" class="p-5"></div>
                <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 px-5 py-4">
                    <button data-hs-overlay="#detail-modal" class="rounded-xl border border-slate-300 px-4 py-2 font-semibold">Cerrar</button>
                    @if ($abilities['createCertificates'])
                        <button id="create-certificate" class="rounded-xl bg-emerald-600 px-4 py-2 font-bold text-white hover:bg-emerald-700">Generar constancia</button>
                    @endif
                    @if ($abilities['updateRecords'])
                        <button id="edit-record" class="rounded-xl bg-amber-500 px-4 py-2 font-bold text-white hover:bg-amber-600">Corregir egreso</button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div id="record-modal" class="hs-overlay fixed inset-0 z-[90] hidden size-full overflow-y-auto bg-slate-950/50" role="dialog" tabindex="-1">
        <div class="m-3 flex min-h-[calc(100%-1.5rem)] items-center justify-center py-5">
            <form id="record-form" class="w-full max-w-4xl rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4"><div><h2 id="record-modal-title" class="font-bold text-blue-950">Registrar egreso excepcional</h2><p class="text-xs text-slate-500">Los cambios quedarán asociados a su cuenta central.</p></div><button type="button" data-hs-overlay="#record-modal" class="rounded-lg p-2 hover:bg-slate-100">✕</button></div>
                <div class="border-b border-blue-100 bg-blue-50 px-5 py-3">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <p class="mr-auto text-sm text-blue-900">Ingrese la HC o documento y consulte la fuente maestra de pacientes.</p>
                        <span id="patient-search-status" class="text-xs font-semibold text-blue-700" aria-live="polite"></span>
                        <button id="lookup-patient" type="button" class="rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-bold text-blue-700 hover:bg-blue-100">Buscar en SIGH local</button>
                    </div>
                </div>
                <div class="grid max-h-[70vh] gap-4 overflow-y-auto p-5 sm:grid-cols-2 lg:grid-cols-3">
                    <label><span class="mb-1.5 block text-sm font-bold text-slate-700">Tipo de documento</span><select name="doc_tipo_id" class="min-h-11 w-full rounded-xl border border-slate-300 px-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"><option value="">No especificado</option><option value="1">DNI</option><option value="2">Carnet de extranjería</option><option value="3">Pasaporte</option><option value="4">Documento extranjero</option><option value="5">Código de recién nacido</option><option value="8">Documento de madre + hijo</option></select></label>
                    @foreach ([
                        ['numhc', 'Historia clínica', 'text', false],
                        ['doc_iden', 'Documento', 'text', false],
                        ['nomb', 'Nombres', 'text', true],
                        ['apell', 'Apellidos', 'text', true],
                        ['sexo', 'Sexo', 'text', false],
                        ['edad', 'Edad', 'text', false],
                        ['fecing', 'Fecha de ingreso', 'date', true],
                        ['fecegr', 'Fecha de egreso', 'date', true],
                        ['ups', 'UPS / Servicio', 'text', true],
                        ['condicion', 'Condición de egreso', 'text', false],
                        ['financia', 'Financiamiento', 'text', false],
                        ['coddiag1', 'Diagnóstico principal CIE-10', 'text', true],
                        ['coddiag2', 'Diagnóstico 2', 'text', false],
                        ['coddiag3', 'Diagnóstico 3', 'text', false],
                        ['coddiag4', 'Diagnóstico 4', 'text', false],
                    ] as [$name, $label, $type, $required])
                        <label><span class="mb-1.5 block text-sm font-bold text-slate-700">{{ $label }}</span><input name="{{ $name }}" type="{{ $type }}" @required($required) maxlength="150" class="min-h-11 w-full rounded-xl border border-slate-300 px-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"></label>
                    @endforeach
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-5 py-4"><span id="record-status" class="mr-auto text-sm text-rose-700"></span><button type="button" data-hs-overlay="#record-modal" class="rounded-xl border border-slate-300 px-4 py-2 font-semibold">Cancelar</button><button type="submit" class="rounded-xl bg-blue-600 px-5 py-2 font-bold text-white">Guardar con auditoría</button></div>
            </form>
        </div>
    </div>

    <div id="edit-certificate-modal" class="hs-overlay fixed inset-0 z-[90] hidden size-full overflow-y-auto bg-slate-950/50" role="dialog" tabindex="-1">
        <div class="m-3 flex min-h-[calc(100%-1.5rem)] items-center justify-center py-5">
            <form id="edit-certificate-form" class="w-full max-w-4xl rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4"><h2 class="font-bold text-blue-950">Editar constancia</h2><button type="button" data-hs-overlay="#edit-certificate-modal" class="rounded-lg p-2 hover:bg-slate-100">✕</button></div>
                <div class="grid max-h-[70vh] gap-4 overflow-y-auto p-5 sm:grid-cols-2">
                    @foreach ([
                        ['paciente', 'Paciente', 'text', true],
                        ['numhc', 'Historia clínica', 'text', true],
                        ['doc_iden', 'Documento', 'text', false],
                        ['servicio', 'Servicio', 'text', false],
                        ['fecing', 'Fecha de ingreso', 'date', false],
                        ['fecegr', 'Fecha de egreso', 'date', false],
                        ['ups', 'Código UPS', 'text', false],
                        ['sigla_servicio', 'Sigla del servicio', 'text', false],
                        ['coddiag1', 'Diagnóstico principal', 'text', false],
                        ['coddiag2', 'Diagnóstico secundario 2', 'text', false],
                        ['coddiag3', 'Diagnóstico secundario 3', 'text', false],
                        ['coddiag4', 'Diagnóstico secundario 4', 'text', false],
                    ] as [$name, $label, $type, $required])
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-bold text-slate-700">{{ $label }}</span>
                            <input name="{{ $name }}" type="{{ $type }}" @required($required) class="min-h-11 w-full rounded-xl border border-slate-300 px-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        </label>
                    @endforeach
                    <label class="block sm:col-span-2"><span class="mb-1.5 block text-sm font-bold text-slate-700">Observación</span><textarea name="observacion" maxlength="1000" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"></textarea></label>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-5 py-4"><span id="edit-status" class="mr-auto text-sm text-rose-700"></span><button type="button" data-hs-overlay="#edit-certificate-modal" class="rounded-xl border border-slate-300 px-4 py-2 font-semibold">Cancelar</button><button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 font-bold text-white">Guardar cambios</button></div>
            </form>
        </div>
    </div>

    <footer class="mt-10 border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-screen-2xl flex-col gap-2 px-4 py-5 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"><span>Hospital San José de Chincha · Unidad de Estadística e Informática</span><span>Intranet HSJ · {{ now()->year }}</span></div>
    </footer>

    <script>
        window.EGRESOS_CONFIG = {{ Illuminate\Support\Js::from([
            'dashboardUrl' => route('egresos.dashboard', [], false),
            'recordsUrl' => route('egresos.records.index', [], false),
            'patientSearchUrl' => route('egresos.patients.search', [], false),
            'importsUrl' => route('egresos.imports.index', [], false),
            'monthlyUrl' => route('egresos.statistics.monthly', [], false),
            'servicesUrl' => route('egresos.statistics.services', [], false),
            'reportCsvUrl' => route('egresos.reports.csv', [], false),
            'reportXlsxUrl' => route('egresos.reports.xlsx', [], false),
            'historyUrl' => route('egresos.certificates.index', [], false),
            'certificateUrl' => route('egresos.certificates.store', [], false),
            'configurationUrl' => route('egresos.configuration.show', [], false),
            'auditUrl' => route('egresos.audit.index', [], false),
            'abilities' => $abilities,
        ]) }};
    </script>
    <script src="/assets/vendor/preline/preline.js"></script>
    <script src="/assets/js/egresos.js"></script>
</body>
</html>
