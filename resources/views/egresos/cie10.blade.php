<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Catálogo CIE-10 - Intranet HSJ</title>
    <link rel="icon" href="/assets/images/logohsj.png">
    <link rel="stylesheet" href="/assets/css/tailwind.css">
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
        <div class="mx-auto flex max-w-screen-2xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('egresos.index') }}" class="flex min-w-0 items-center gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-blue-50 ring-1 ring-blue-100">
                    <img src="/assets/images/logohsj.png" alt="Hospital San José" class="size-9 object-contain">
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-bold text-blue-950 sm:text-base">Hospital San José de Chincha</span>
                    <span class="block truncate text-xs text-slate-500">Administración central del CIE-10</span>
                </span>
            </a>
            <div class="hs-dropdown relative inline-flex">
                <button type="button" class="hs-dropdown-toggle inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold shadow-sm hover:bg-slate-50">
                    <span class="grid size-8 place-items-center rounded-lg bg-blue-600 text-xs text-white">{{ mb_strtoupper(mb_substr($centralUser['name'], 0, 1)) }}</span>
                    <span class="hidden max-w-40 truncate sm:block">{{ $centralUser['name'] }}</span>
                </button>
                <div class="hs-dropdown-menu mt-2 hidden min-w-64 rounded-xl border border-slate-200 bg-white p-2 opacity-0 shadow-xl transition-[opacity,margin] hs-dropdown-open:opacity-100">
                    <div class="border-b border-slate-100 px-3 py-2">
                        <p class="truncate text-sm font-semibold">{{ $centralUser['name'] }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $centralUser['email'] }}</p>
                    </div>
                    <a href="{{ route('egresos.index') }}" class="mt-1 block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">Volver a Egresos</a>
                    <a href="/areas" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100">Panel principal</a>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-blue-950 via-blue-800 to-cyan-600 p-6 text-white shadow-xl sm:p-8">
            <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold ring-1 ring-white/25">Catálogo clínico compartido</span>
                    <h1 class="mt-4 text-2xl font-black sm:text-4xl">Mantenimiento CIE-10</h1>
                    <p class="mt-2 max-w-3xl text-sm text-blue-100 sm:text-base">Correcciones puntuales y actualizaciones masivas con análisis previo, control de concurrencia y auditoría central.</p>
                </div>
                <a href="{{ route('egresos.index') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-3 text-sm font-bold text-blue-900 shadow hover:bg-blue-50">← Volver a Egresos</a>
            </div>
        </section>

        <div class="mt-6 flex gap-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-sm" role="tablist">
            <button type="button" class="cie-tab whitespace-nowrap rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white" data-panel="catalog">Catálogo y CRUD</button>
            <button type="button" class="cie-tab whitespace-nowrap rounded-xl px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100" data-panel="imports">Carga masiva</button>
        </div>

        <section id="panel-catalog" class="cie-panel mt-4">
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="min-w-0 space-y-4">
                    <form id="catalog-search" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-[1fr_auto_auto_auto]">
                        <input name="q" type="search" maxlength="120" placeholder="Código o descripción" class="min-h-11 rounded-xl border border-slate-300 px-4 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        <select name="estado" class="min-h-11 rounded-xl border border-slate-300 px-3"><option value="">Todos los estados</option><option>ACTIVO</option><option>INACTIVO</option></select>
                        <select name="cotejo_sexo" class="min-h-11 rounded-xl border border-slate-300 px-3"><option value="">Todos los cotejos</option><option>AMBOS</option><option>HOMBRE</option><option>MUJER</option></select>
                        <button class="rounded-xl bg-blue-600 px-5 font-bold text-white hover:bg-blue-700">Buscar</button>
                    </form>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                            <div><h2 class="font-black text-blue-950">Códigos registrados</h2><p id="catalog-status" class="text-xs text-slate-500" aria-live="polite">Consultando…</p></div>
                            <button id="new-code" type="button" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">Nuevo código</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <tr><th class="px-4 py-3">Código</th><th class="px-4 py-3">Descripción</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Sexo</th><th class="px-4 py-3 text-right">Acciones</th></tr>
                                </thead>
                                <tbody id="catalog-body" class="divide-y divide-slate-100"></tbody>
                            </table>
                        </div>
                        <div id="catalog-pagination" class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm"></div>
                    </div>
                </div>

                <aside class="xl:sticky xl:top-24 xl:self-start">
                    <form id="catalog-form" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <input name="id" type="hidden">
                        <input name="version" type="hidden">
                        <h2 id="form-title" class="text-lg font-black text-blue-950">Nuevo código CIE-10</h2>
                        <p class="mt-1 text-xs text-slate-500">El código será inmutable después de crearse. Las modificaciones quedan auditadas.</p>
                        <label class="mt-5 block"><span class="mb-1.5 block text-sm font-bold">Código</span><input name="codigo" required maxlength="20" placeholder="Ej. A00.1" class="min-h-11 w-full rounded-xl border border-slate-300 px-3 uppercase outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"></label>
                        <label class="mt-4 block"><span class="mb-1.5 block text-sm font-bold">Descripción</span><textarea name="descripcion" required maxlength="1000" rows="5" class="w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"></textarea></label>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <label><span class="mb-1.5 block text-sm font-bold">Estado</span><select name="estado" class="min-h-11 w-full rounded-xl border border-slate-300 px-3"><option>ACTIVO</option><option>INACTIVO</option></select></label>
                            <label><span class="mb-1.5 block text-sm font-bold">Cotejo de sexo</span><select name="cotejo_sexo" class="min-h-11 w-full rounded-xl border border-slate-300 px-3"><option>AMBOS</option><option>HOMBRE</option><option>MUJER</option></select></label>
                        </div>
                        <p id="form-status" class="mt-4 min-h-5 text-sm font-semibold" aria-live="polite"></p>
                        <div class="mt-4 flex justify-end gap-2">
                            <button id="cancel-edit" type="button" class="hidden rounded-xl border border-slate-300 px-4 py-2 font-semibold hover:bg-slate-50">Cancelar</button>
                            <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2 font-bold text-white hover:bg-blue-700">Guardar</button>
                        </div>
                    </form>
                </aside>
            </div>
        </section>

        <section id="panel-imports" class="cie-panel mt-4 hidden">
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="space-y-4">
                    <form id="import-form" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-lg font-black text-blue-950">Analizar actualización masiva</h2>
                        <p class="mt-1 text-sm text-slate-500">Se aceptan CSV y XLSX de hasta 20 MB. El análisis no modifica el catálogo.</p>
                        <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950">
                            <p class="font-bold">Columnas requeridas: <code>CODIGO</code> y <code>DESCRIPCION</code>.</p>
                            <p class="mt-1">Opcionales: <code>ESTADO</code> y <code>COTEJO_SEXO</code>. Los valores permitidos son ACTIVO/INACTIVO y AMBOS/HOMBRE/MUJER.</p>
                        </div>
                        <label class="mt-5 block rounded-2xl border-2 border-dashed border-blue-200 bg-blue-50/50 p-6 text-center">
                            <span class="block text-sm font-bold text-blue-950">Seleccione el catálogo</span>
                            <input name="archivo" type="file" required accept=".csv,.xlsx" class="mt-4 block w-full text-sm">
                        </label>
                        <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                            <span id="import-status" class="mr-auto text-sm font-semibold" aria-live="polite"></span>
                            <button class="rounded-xl bg-blue-600 px-5 py-3 font-bold text-white hover:bg-blue-700">Analizar archivo</button>
                        </div>
                    </form>
                    <div id="import-analysis" class="hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"></div>
                    <div id="import-rows" class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"></div>
                </div>
                <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:sticky xl:top-24 xl:self-start">
                    <h2 class="font-black text-blue-950">Lotes recientes</h2>
                    <p class="mt-1 text-xs text-slate-500">Cada lote conserva archivo, huella, usuario y resultado.</p>
                    <div id="imports-list" class="mt-4 space-y-3"></div>
                </aside>
            </div>
        </section>
    </main>

    <footer class="mt-10 border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-screen-2xl flex-col gap-2 px-4 py-5 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"><span>Hospital San José de Chincha · Unidad de Estadística e Informática</span><span>Intranet HSJ · {{ now()->year }}</span></div>
    </footer>

    <script>
        window.CIE10_CONFIG = {{ Illuminate\Support\Js::from([
            'catalogUrl' => route('egresos.cie10.catalog.index', [], false),
            'importsUrl' => route('egresos.cie10.imports.index', [], false),
        ]) }};
    </script>
    <script src="/assets/vendor/preline/preline.js"></script>
    <script src="/assets/js/egresos-cie10.js"></script>
</body>
</html>
