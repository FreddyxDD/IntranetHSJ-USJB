<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Egresos - Intranet HSJ</title>
    <link rel="icon" href="{{ asset('assets/images/logohsj.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/tailwind.css') }}">
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
        <div class="mx-auto flex max-w-screen-2xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <a href="/areas" class="grid size-11 shrink-0 place-items-center rounded-xl bg-blue-50 ring-1 ring-blue-100">
                    <img src="{{ asset('assets/images/logohsj.png') }}" alt="Hospital San José" class="size-9 object-contain">
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
            @if ($abilities['viewHistory'])
                <button class="eg-tab whitespace-nowrap rounded-xl px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100" data-panel="history">Historial de constancias</button>
            @endif
            @if ($abilities['manageConfiguration'])
                <button class="eg-tab whitespace-nowrap rounded-xl px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100" data-panel="configuration">Configuración</button>
            @endif
        </div>

        <section id="panel-records" class="eg-panel mt-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <form id="search-form" class="flex flex-col gap-3 sm:flex-row">
                    <label class="sr-only" for="search-query">Buscar paciente</label>
                    <input id="search-query" type="search" maxlength="150" placeholder="Historia clínica, DNI, nombres o apellidos" class="min-h-12 flex-1 rounded-xl border border-slate-300 px-4 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    <button class="min-h-12 rounded-xl bg-blue-600 px-6 font-bold text-white shadow-sm hover:bg-blue-700" type="submit">Buscar</button>
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
                    <div class="mb-5">
                        <h2 class="text-lg font-black text-blue-950">Configuración institucional de constancias</h2>
                        <p class="mt-1 text-sm text-slate-500">Estos valores se copian a las nuevas constancias. Las constancias históricas conservan sus datos originales.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            ['iniciales_director', 'Iniciales del director', 20],
                            ['iniciales_jefe', 'Iniciales del jefe', 20],
                            ['iniciales_ccp', 'Iniciales CCP', 20],
                            ['nombre_director', 'Nombre del director', 180],
                            ['nombre_jefe', 'Nombre del jefe', 180],
                            ['cargo_director', 'Cargo del director', 180],
                            ['cargo_jefe', 'Cargo del jefe', 180],
                        ] as [$name, $label, $max])
                            <label class="block">
                                <span class="mb-1.5 block text-sm font-bold text-slate-700">{{ $label }}</span>
                                <input name="{{ $name }}" maxlength="{{ $max }}" class="min-h-11 w-full rounded-xl border border-slate-300 px-3 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                            </label>
                        @endforeach
                        <label class="block sm:col-span-2 lg:col-span-3">
                            <span class="mb-1.5 block text-sm font-bold text-slate-700">Observación institucional</span>
                            <textarea name="observacion" maxlength="2000" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"></textarea>
                        </label>
                    </div>
                    <div class="mt-5 flex items-center justify-end gap-3">
                        <span id="configuration-status" class="mr-auto text-sm text-slate-500" aria-live="polite"></span>
                        <button type="submit" class="rounded-xl bg-blue-600 px-5 py-3 font-bold text-white hover:bg-blue-700">Guardar configuración</button>
                    </div>
                </form>
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
                </div>
            </div>
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
        window.EGRESOS_CONFIG = @json([
            'dashboardUrl' => route('egresos.dashboard'),
            'recordsUrl' => route('egresos.records.index'),
            'historyUrl' => route('egresos.certificates.index'),
            'certificateUrl' => route('egresos.certificates.store'),
            'configurationUrl' => route('egresos.configuration.show'),
            'abilities' => $abilities,
        ]);
    </script>
    <script src="{{ asset('assets/vendor/preline/preline.js') }}"></script>
    <script src="{{ asset('assets/js/egresos.js') }}"></script>
</body>
</html>
