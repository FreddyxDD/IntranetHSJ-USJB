<x-layouts::app :title="__('Citas')">
    <div class="hs-page-shell">
        <div class="hs-panel flex flex-col gap-4 p-5 lg:p-6">
            <div class="flex flex-col gap-4">
                <div>
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="hs-soft-badge bg-teal-50 text-teal-700 ring-teal-200 dark:bg-teal-950 dark:text-teal-100 dark:ring-teal-800">Modulo operativo</span>
                        <span class="hs-soft-badge bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800">Preline UI activo</span>
                    </div>
                    <flux:heading size="xl">Asignacion de citas</flux:heading>
                    <flux:text class="mt-1">Consulta diaria agrupada por turno y especialidad para generación masiva de FUA.</flux:text>
                </div>

                <form method="GET" action="{{ route('citas.index') }}" class="grid w-full gap-3 rounded-xl border border-zinc-200 bg-zinc-50/80 p-4 shadow-inner shadow-zinc-200/50 sm:grid-cols-2 lg:grid-cols-12 lg:items-end dark:border-zinc-800 dark:bg-zinc-950/50 dark:shadow-black/20">
                    <div class="lg:col-span-2">
                        <flux:input name="fecha" label="Fecha" type="date" value="{{ $filters['fecha'] }}" />
                    </div>
                    <div class="lg:col-span-1">
                        <flux:input name="hora_desde" label="Desde" type="time" value="{{ $filters['hora_desde'] }}" />
                    </div>
                    <div class="lg:col-span-1">
                        <flux:input name="hora_hasta" label="Hasta" type="time" value="{{ $filters['hora_hasta'] }}" />
                    </div>

                    <label class="grid gap-2 text-sm lg:col-span-2">
                        <span class="text-zinc-700 dark:text-zinc-300">Turno</span>
                        <select name="turno" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="todos" @selected($filters['turno'] === 'todos')>Todos</option>
                            <option value="manana" @selected($filters['turno'] === 'manana')>Mañana</option>
                            <option value="tarde" @selected($filters['turno'] === 'tarde')>Tarde</option>
                            <option value="fuera" @selected($filters['turno'] === 'fuera')>Fuera de turno</option>
                        </select>
                    </label>

                    <label class="grid gap-2 text-sm lg:col-span-2">
                        <span class="text-zinc-700 dark:text-zinc-300">Ordenar por</span>
                        <select name="orden" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="hora" @selected($filters['orden'] === 'hora')>Hora de cita</option>
                            <option value="hc" @selected($filters['orden'] === 'hc')>Terminal de historia</option>
                        </select>
                    </label>

                    <div class="sm:col-span-2 lg:col-span-2">
                        <flux:input name="q" label="Buscar" value="{{ $filters['q'] }}" placeholder="DNI, paciente, medico, ID" />
                    </div>

                    <div class="flex gap-2 lg:col-span-2">
                        <flux:button type="submit" variant="primary">Filtrar</flux:button>
                        <flux:button :href="route('citas.index')" variant="ghost">Hoy</flux:button>
                    </div>
                </form>
            </div>

            @if (session('fua_error'))
                <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
                    {{ session('fua_error') }}
                </div>
            @endif

            @php
                $fuaTone = $fuaRangeStatus['exhausted']
                    ? 'border-rose-300 bg-rose-50 text-rose-950 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-100'
                    : ($fuaRangeStatus['warning']
                        ? 'border-amber-300 bg-amber-50 text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100'
                        : 'border-emerald-300 bg-emerald-50 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-100');
            @endphp

            <section class="rounded-xl border p-4 {{ $fuaTone }}">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide opacity-75">Control de numeracion FUA</div>
                        <div class="mt-1 text-lg font-semibold">
                            Rango {{ $fuaRangeStatus['disa'] }}-{{ $fuaRangeStatus['lote'] }}
                            @if ($fuaRangeStatus['restantes'] !== null)
                                / {{ number_format($fuaRangeStatus['restantes']) }} FUAS restantes
                            @endif
                        </div>
                        <div class="mt-1 text-sm opacity-80">
                            @if ($fuaRangeStatus['message'])
                                {{ $fuaRangeStatus['message'] }}
                            @elseif ($fuaRangeStatus['exhausted'])
                                Rango agotado. Registra una nueva numeracion antes de generar FUAS.
                            @elseif ($fuaRangeStatus['warning'])
                                Quedan 100 o menos numeros disponibles. Coordina el ingreso de una nueva numeracion.
                            @else
                                Numeracion disponible para generacion real de FUAS.
                            @endif
                        </div>
                    </div>
                    <div class="grid gap-2 text-sm sm:grid-cols-3 lg:min-w-[460px]">
                        <div class="rounded-lg bg-white/70 p-3 ring-1 ring-black/5 dark:bg-black/20">
                            <div class="text-xs opacity-70">Ultimo generado</div>
                            <div class="font-semibold">{{ $fuaRangeStatus['ultimo'] ?: '-' }}</div>
                        </div>
                        <div class="rounded-lg bg-white/70 p-3 ring-1 ring-black/5 dark:bg-black/20">
                            <div class="text-xs opacity-70">Siguiente</div>
                            <div class="font-semibold">{{ $fuaRangeStatus['siguiente'] ?: '-' }}</div>
                        </div>
                        <div class="rounded-lg bg-white/70 p-3 ring-1 ring-black/5 dark:bg-black/20">
                            <div class="text-xs opacity-70">Final</div>
                            <div class="font-semibold">{{ $fuaRangeStatus['final'] ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        @php
            $recentesUrl = route('citas.index', array_filter(array_merge(request()->query(), ['recientes' => 1]), fn ($value) => $value !== null && $value !== ''));
            $clearRecentesUrl = route('citas.index', array_filter(collect(request()->query())->except('recientes')->all(), fn ($value) => $value !== null && $value !== ''));
        @endphp

        @if ($filters['recientes'])
            <div class="flex flex-col gap-3 rounded-lg border border-sky-200 bg-sky-50/80 p-4 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950/40 dark:text-sky-100 sm:flex-row sm:items-center sm:justify-between">
                <span>Mostrando solo citas registradas en los ultimos {{ $recentRegisteredMinutes }} minutos.</span>
                <flux:button :href="$clearRecentesUrl" size="sm" variant="ghost">Limpiar recientes</flux:button>
            </div>
        @endif

        <div class="hidden">
            <div class="hs-kpi">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">Fecha consultada</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{{ \Illuminate\Support\Carbon::parse($filters['fecha'])->format('d/m/Y') }}</div>
                <div class="text-xs text-zinc-500">Actualizado {{ $refreshedAt->format('H:i') }}</div>
            </div>
            <div class="hs-kpi">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">Total filtrado</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{{ number_format($citas->count()) }}</div>
            </div>
            <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4 shadow-sm shadow-rose-100/60 dark:border-rose-900 dark:bg-rose-950/30 dark:shadow-black/20">
                <div class="text-xs font-medium uppercase tracking-wide text-rose-700 dark:text-rose-200">Citas adicionales</div>
                <div class="mt-1 text-2xl font-semibold text-rose-900 dark:text-rose-100">{{ number_format($additionalCount) }}</div>
                <div class="text-xs text-rose-700/80 dark:text-rose-200/80">Requieren visibilidad operativa</div>
            </div>
            <a href="{{ $recentesUrl }}" class="rounded-xl border border-sky-200 bg-sky-50/70 p-4 shadow-sm shadow-sky-100/60 transition hover:-translate-y-0.5 hover:border-sky-300 hover:bg-sky-100 dark:border-sky-900 dark:bg-sky-950/30 dark:shadow-black/20 dark:hover:border-sky-700 dark:hover:bg-sky-950/60">
                <div class="text-xs font-medium uppercase tracking-wide text-sky-700 dark:text-sky-200">Registradas recientes</div>
                <div class="mt-1 text-2xl font-semibold text-sky-900 dark:text-sky-100">{{ number_format($recentCount) }}</div>
                <div class="text-xs text-sky-700/80 dark:text-sky-200/80">{{ $filters['recientes'] ? 'Filtro activo' : 'Ver ultimos '.$recentRegisteredMinutes.' min' }}</div>
            </a>
            @foreach ($turnos as $key => $turno)
                <div class="hs-kpi">
                    <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ $turno['label'] }}</div>
                    <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{{ number_format($groupedCitas[$key]['total']) }}</div>
                    <div class="text-xs text-zinc-500">{{ $turno['range'] }}</div>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('citas.fua.print-batches.store') }}" class="flex flex-col gap-4">
            @csrf
            <input type="hidden" name="fecha" value="{{ $filters['fecha'] }}">
            <input type="hidden" name="hora_desde" value="{{ $filters['hora_desde'] }}">
            <input type="hidden" name="hora_hasta" value="{{ $filters['hora_hasta'] }}">
            <input type="hidden" name="turno" value="{{ $filters['turno'] }}">
            <input type="hidden" name="q" value="{{ $filters['q'] }}">
            <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">

            <div class="hs-panel p-4">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <label class="grid gap-2 text-sm">
                        <span class="text-zinc-700 dark:text-zinc-300">Accion del lote</span>
                        <select name="action" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="generate_and_print">Imprimir FUAS seleccionadas</option>
                            <option value="print_existing">Solo reimprimir FUAS existentes</option>
                            <option value="generate">Solo generar pendientes aptas</option>
                        </select>
                    </label>
                    <label class="grid gap-2 text-sm">
                        <span class="text-zinc-700 dark:text-zinc-300">Formato</span>
                        <select name="output_format" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="pdf">PDF</option>
                            <option value="xlsx">Excel</option>
                        </select>
                    </label>
                    <label class="grid gap-2 text-sm">
                        <span class="text-zinc-700 dark:text-zinc-300">Modo impresión</span>
                        <select name="print_mode" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="individual">Individual</option>
                            <option value="consolidated">PDF consolidado piloto</option>
                        </select>
                    </label>
                    <div>
                        <flux:input name="printer_name" label="Cola / impresora" :placeholder="config('fua.print.default_printer') ?: 'Predeterminada o nombre compartido'" />
                    </div>
                </div>
                <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                    El modo consolidado piloto une todas las FUAS en un solo PDF y envia un unico trabajo a la impresora para conservar el orden del lote.
                </p>

                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-800 dark:text-zinc-100">
                            <input id="select-all-citas" type="checkbox" class="size-4 rounded border-zinc-300">
                            Seleccionar citas visibles
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-800 dark:text-zinc-100">
                            <input id="select-all-services" type="checkbox" class="size-4 rounded border-zinc-300">
                            Seleccionar consultorios
                        </label>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span id="selected-count" class="text-sm text-zinc-500">0 citas / 0 consultorios</span>
                        <flux:button :href="route('citas.fua.print-batches.index')" variant="ghost">Historial de lotes</flux:button>
                        <flux:button type="submit" variant="ghost" formaction="{{ route('citas.fua.simulate') }}">Validar generación</flux:button>
                        <flux:button type="submit" variant="primary">Imprimir seleccionadas</flux:button>
                    </div>
                </div>
                <div class="mt-3 w-full rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900 ring-1 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-100 dark:ring-amber-900">
                    Modo lite: puedes marcar citas o consultorios completos. Las FUAS existentes se envian directo a impresion. Las citas SIS normal y SIS manual pendientes se generan con correlativo y luego se imprimen. Si la afiliacion manual no esta en observaciones, el formato saldra sin ese dato para llenado manual.
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-[320px_1fr]">
                <aside class="hs-panel h-fit overflow-hidden xl:sticky xl:top-20">
                    <div class="border-b border-[var(--border)] p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-[var(--foreground)]">Consultorios programados</div>
                                <div class="mt-1 text-xs text-[var(--muted-foreground)]">Marca uno o varios consultorios para enviarlos en un solo lote.</div>
                            </div>
                            <span id="selected-services-count" class="shrink-0 rounded-md bg-teal-50 px-2 py-1 text-xs font-semibold text-teal-800 ring-1 ring-teal-200 dark:bg-teal-950 dark:text-teal-100 dark:ring-teal-700">0</span>
                        </div>
                    </div>
                    <div id="consultorios-list" class="max-h-[680px] overflow-y-auto p-2">
                        @forelse ($serviceNavigator as $nav)
                            @php($isSelectedService = (int) $selectedServiceId === (int) $nav['id'])
                            @php($navUrl = route('citas.index', array_filter(array_merge(request()->query(), ['servicio' => $nav['id']]), fn ($value) => $value !== null && $value !== '')).'#agenda-consultorio')
                            <div class="consultorio-card mb-2 rounded-lg border p-3 transition hover:-translate-y-0.5 hover:shadow-sm {{ $isSelectedService ? 'border-teal-500 bg-teal-50 text-teal-950 ring-2 ring-teal-200 dark:border-teal-400 dark:bg-teal-950/40 dark:text-teal-100 dark:ring-teal-800' : 'border-zinc-200 bg-white text-zinc-900 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <label class="flex min-w-0 flex-1 cursor-pointer items-start gap-2">
                                        <input type="checkbox" name="servicios[]" value="{{ $nav['id'] }}" class="service-checkbox mt-0.5 size-4 shrink-0 rounded border-zinc-300" data-service="{{ $nav['id'] }}" data-total="{{ $nav['total'] }}">
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-semibold">{{ $nav['servicio'] }}</span>
                                            <span class="mt-0.5 block truncate text-[11px] opacity-75">{{ count($nav['medicos']) === 1 ? $nav['medicos'][0] : count($nav['medicos']).' medicos' }}</span>
                                        </span>
                                    </label>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <span class="rounded-md bg-white/80 px-2 py-1 text-xs font-semibold text-zinc-800 ring-1 ring-black/5 dark:bg-zinc-900/80 dark:text-zinc-100">{{ $nav['total'] }}</span>
                                        <a href="{{ $navUrl }}" class="consultorio-link rounded-md px-2 py-1 text-xs font-semibold text-teal-700 ring-1 ring-teal-200 hover:bg-teal-100 dark:text-teal-100 dark:ring-teal-700 dark:hover:bg-teal-900">Ver</a>
                                    </div>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1 text-[11px]">
                                    @if (($nav['turnos']['manana'] ?? 0) > 0)
                                        <span class="rounded bg-sky-100 px-1.5 py-0.5 font-medium text-sky-800 ring-1 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800">M {{ $nav['turnos']['manana'] }}</span>
                                    @endif
                                    @if (($nav['turnos']['tarde'] ?? 0) > 0)
                                        <span class="rounded bg-indigo-100 px-1.5 py-0.5 font-medium text-indigo-800 ring-1 ring-indigo-200 dark:bg-indigo-950 dark:text-indigo-100 dark:ring-indigo-800">T {{ $nav['turnos']['tarde'] }}</span>
                                    @endif
                                    @if ($nav['adicionales'] > 0)
                                        <span class="rounded bg-rose-100 px-1.5 py-0.5 font-semibold text-rose-800 ring-1 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800">{{ $nav['adicionales'] }} adicionales</span>
                                    @endif
                                    @if ($nav['recientes'] > 0)
                                        <span class="rounded bg-sky-100 px-1.5 py-0.5 font-semibold text-sky-800 ring-1 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800">{{ $nav['recientes'] }} recientes</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-[var(--border)] p-6 text-center text-sm text-[var(--muted-foreground)]">
                                No hay consultorios con citas para el filtro.
                            </div>
                        @endforelse
                    </div>
                </aside>

                <div id="agenda-consultorio" class="hs-accordion-group scroll-mt-20 grid gap-4" data-hs-accordion-always-open>
            @forelse ($groupedCitas as $turnoKey => $turno)
                @if ($turno['total'] > 0)
                    @php($turnoId = 'turno-'.$turnoKey)
                    <div class="hs-accordion active overflow-hidden rounded-xl border border-[var(--border)] bg-[var(--card)] shadow-sm" id="hs-citas-{{ $turnoId }}" data-citas-detail-key="{{ $turnoId }}">
                        <button type="button" class="hs-accordion-toggle flex w-full items-center justify-between gap-4 bg-[var(--muted)] px-4 py-3 text-start transition hover:bg-[var(--muted-hover)]" aria-expanded="true" aria-controls="hs-citas-{{ $turnoId }}-content">
                            <span>
                                <span class="block text-base font-semibold text-[var(--foreground)]">{{ $turno['label'] }}</span>
                                <span class="text-xs text-[var(--muted-foreground)]">{{ $turno['range'] }}</span>
                            </span>
                            <span class="flex flex-wrap items-center justify-end gap-2">
                                @if ($turno['total_adicionales'] > 0)
                                    <span class="rounded-md bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-800 ring-1 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-700">{{ $turno['total_adicionales'] }} adicionales</span>
                                @endif
                                @if ($turno['total_recientes'] > 0)
                                    <span class="rounded-md bg-sky-100 px-2 py-1 text-xs font-semibold text-sky-800 ring-1 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-700">{{ $turno['total_recientes'] }} recientes</span>
                                @endif
                                <span class="rounded-md bg-white px-2 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $turno['total'] }} citas</span>
                                <flux:icon icon="chevron-down" class="size-4 text-[var(--muted-foreground)] transition hs-accordion-active:rotate-180" />
                            </span>
                        </button>

                        <div id="hs-citas-{{ $turnoId }}-content" class="hs-accordion-content divide-y divide-zinc-200 overflow-hidden transition-[height] duration-300 dark:divide-zinc-700" role="region" aria-labelledby="hs-citas-{{ $turnoId }}">
                            @foreach ($turno['especialidades'] as $especialidad)
                                @php($especialidadId = 'especialidad-'.$turnoKey.'-'.\Illuminate\Support\Str::slug($especialidad['label']))
                                <div class="hs-accordion active bg-[var(--background)]" id="hs-citas-{{ $especialidadId }}" data-citas-detail-key="{{ $especialidadId }}">
                                    <button type="button" class="hs-accordion-toggle flex w-full items-center justify-between gap-4 border-l-4 border-[var(--primary)] bg-sky-50 px-4 py-3 text-start transition hover:bg-sky-100 dark:bg-sky-950/30 dark:hover:bg-sky-900/40" aria-expanded="true" aria-controls="hs-citas-{{ $especialidadId }}-content">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-semibold text-blue-950 dark:text-blue-100">Especialidad: {{ $especialidad['label'] }}</span>
                                            <span class="rounded-md bg-white px-2 py-1 text-xs font-medium text-blue-800 ring-1 ring-blue-300 dark:bg-blue-950 dark:text-blue-100 dark:ring-blue-500">{{ $especialidad['total'] }} citas</span>
                                            <span class="rounded-md bg-blue-200 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-blue-700 dark:text-blue-50">{{ count($especialidad['servicios']) }} servicios</span>
                                            @if ($especialidad['total_adicionales'] > 0)
                                                <span class="rounded-md bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-800 ring-1 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-700">{{ $especialidad['total_adicionales'] }} adicionales</span>
                                            @endif
                                            @if ($especialidad['total_recientes'] > 0)
                                                <span class="rounded-md bg-sky-100 px-2 py-1 text-xs font-semibold text-sky-800 ring-1 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-700">{{ $especialidad['total_recientes'] }} recientes</span>
                                            @endif
                                        </span>
                                        <flux:icon icon="chevron-down" class="size-4 shrink-0 text-blue-700 transition hs-accordion-active:rotate-180 dark:text-blue-200" />
                                    </button>

                                    <div id="hs-citas-{{ $especialidadId }}-content" class="hs-accordion-content divide-y divide-zinc-100 overflow-hidden border-t border-zinc-100 transition-[height] duration-300 dark:divide-zinc-700 dark:border-zinc-700" role="region" aria-labelledby="hs-citas-{{ $especialidadId }}">
                                        @foreach ($especialidad['servicios'] as $servicio)
                                            @php($groupId = $turnoKey.'-'.\Illuminate\Support\Str::slug($especialidad['label']).'-'.\Illuminate\Support\Str::slug($servicio['label']))
                                            @php($servicioId = 'servicio-'.$groupId)
                                            <div class="hs-accordion active bg-emerald-50 dark:bg-emerald-950/40" id="hs-citas-{{ $servicioId }}" data-citas-detail-key="{{ $servicioId }}">
                                                <div class="flex flex-col gap-3 border-l-4 border-emerald-600 bg-emerald-100 px-4 py-3 transition dark:border-emerald-300 dark:bg-emerald-900 lg:flex-row lg:items-center lg:justify-between">
                                                    <button type="button" class="hs-accordion-toggle flex min-w-0 flex-1 items-start justify-between gap-3 text-start" aria-expanded="true" aria-controls="hs-citas-{{ $servicioId }}-content">
                                                        <span class="min-w-0">
                                                            <span class="flex flex-wrap items-center gap-2">
                                                                <span class="text-sm font-semibold text-emerald-950 dark:text-emerald-100">Servicio: {{ $servicio['label'] }}</span>
                                                                <span class="rounded-md bg-white px-2 py-1 text-xs font-medium text-emerald-800 ring-1 ring-emerald-300 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-500">{{ $servicio['total'] }} citas</span>
                                                                @if ($servicio['total_adicionales'] > 0)
                                                                    <span class="rounded-md bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-800 ring-1 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-700">{{ $servicio['total_adicionales'] }} adicionales</span>
                                                                @endif
                                                                @if ($servicio['total_recientes'] > 0)
                                                                    <span class="rounded-md bg-sky-100 px-2 py-1 text-xs font-semibold text-sky-800 ring-1 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-700">{{ $servicio['total_recientes'] }} recientes</span>
                                                                @endif
                                                            </span>
                                                            <span class="mt-1 block text-xs text-emerald-700 dark:text-emerald-200">
                                                                Medico: {{ count($servicio['medicos']) === 1 ? $servicio['medicos'][0] : count($servicio['medicos']).' medicos' }}
                                                            </span>
                                                        </span>
                                                        <flux:icon icon="chevron-down" class="mt-1 size-4 shrink-0 text-emerald-700 transition hs-accordion-active:rotate-180 dark:text-emerald-200" />
                                                    </button>

                                                    <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                                                        <label class="inline-flex items-center gap-2 text-xs font-medium text-emerald-800 dark:text-emerald-100">
                                                            <input type="checkbox" class="group-checkbox size-4 rounded border-zinc-300" data-group="{{ $groupId }}">
                                                            Seleccionar servicio
                                                        </label>
                                                        <span class="hs-tooltip inline-block [--placement:top]">
                                                            <flux:button type="submit" size="sm" variant="primary" class="group-submit hs-tooltip-toggle" data-group="{{ $groupId }}">Imprimir servicio</flux:button>
                                                            <span class="hs-tooltip-content invisible absolute z-20 inline-block rounded-lg bg-zinc-900 px-2 py-1 text-xs text-white opacity-0 shadow-sm transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">
                                                                Envia solo este servicio a la cola
                                                            </span>
                                                        </span>
                                                    </div>
                                                </div>

                                                <div id="hs-citas-{{ $servicioId }}-content" class="hs-accordion-content overflow-hidden transition-[height] duration-300" role="region" aria-labelledby="hs-citas-{{ $servicioId }}">
                                                <div class="overflow-x-auto bg-white dark:bg-zinc-800">
                                                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                                                            <tr>
                                                                <th class="w-12 px-4 py-3">Sel</th>
                                                                <th class="px-4 py-3">Hora</th>
                                                                <th class="px-4 py-3">N° Historia Clinica</th>
                                                                <th class="px-4 py-3">Paciente</th>
                                                                <th class="px-4 py-3">Estado FUA</th>
                                                                <th class="px-4 py-3 text-right">Acciones</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                                            @foreach ($servicio['citas'] as $cita)
                                                                @php($fuaProcesado = $processedFuas[$cita->IdCita] ?? false)
                                                                @php($sisFua = $sisFuas[(int) ($cita->atencion?->IdCuentaAtencion ?? 0)] ?? null)
                                                                @php($esAdicional = (bool) $cita->EsCitaAdicional)
                                                                @php($fechaSolicitud = $cita->FechaSolicitud instanceof \Illuminate\Support\Carbon ? $cita->FechaSolicitud->format('Y-m-d') : \Illuminate\Support\Carbon::parse($cita->FechaSolicitud)->format('Y-m-d'))
                                                                @php($registroCita = \Illuminate\Support\Carbon::parse($fechaSolicitud.' '.trim((string) $cita->HoraSolicitud)))
                                                                @php($esReciente = $registroCita->greaterThanOrEqualTo(now()->subMinutes($recentRegisteredMinutes)))
                                                                @php($turnoExtendido = \App\Support\AppointmentTurn::isExtended($cita))
                                                                @php($judicialAppointment = $judicialAppointments[$cita->IdCita] ?? null)
                                                                @php($formaPagoReal = \App\Support\SisFinancing::fullDescription($cita->atencion))
                                                                @php($formaPago = $judicialAppointment ? 'SIS-Judicial' : \App\Support\SisFinancing::label($cita))
                                                                @php($formaPagoKey = strtoupper((string) $formaPago))
                                                                @php($formaPagoClass = $judicialAppointment ? 'bg-fuchsia-100 text-fuchsia-800 ring-fuchsia-200 dark:bg-fuchsia-950 dark:text-fuchsia-100 dark:ring-fuchsia-700' : (str_contains($formaPagoKey, 'MANUAL') ? 'bg-violet-100 text-violet-800 ring-violet-200 dark:bg-violet-950 dark:text-violet-100 dark:ring-violet-700' : (str_contains($formaPagoKey, 'SIS') ? 'bg-sky-100 text-sky-800 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-700' : (str_contains($formaPagoKey, 'SOAT') ? 'bg-violet-100 text-violet-800 ring-violet-200 dark:bg-violet-950 dark:text-violet-100 dark:ring-violet-700' : (str_contains($formaPagoKey, 'PARTICULAR') ? 'bg-zinc-100 text-zinc-800 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-100 dark:ring-zinc-700' : 'bg-stone-100 text-stone-800 ring-stone-200 dark:bg-stone-950 dark:text-stone-100 dark:ring-stone-700')))))
                                                                @php($aplicaFua = \App\Support\FuaEligibility::appliesTo($cita))
                                                                @php($hcFormateada = \App\Support\ClinicalHistoryNumber::format($cita->paciente?->NroHistoriaClinica))
                                                                @php($tipoDocumento = $cita->paciente?->tipoDocumento?->Descripcion ?: 'Documento')
                                                                @php($esSisManual = \App\Support\SisFinancing::isManual($cita->atencion))
                                                                <tr class="align-top {{ ! $aplicaFua ? 'bg-white dark:bg-zinc-800' : ($esAdicional ? 'bg-rose-50/80 dark:bg-rose-950/25' : ($fuaProcesado ? 'bg-emerald-50/70 dark:bg-emerald-950/30' : 'bg-amber-50/60 dark:bg-amber-950/20')) }} {{ $esReciente ? 'ring-1 ring-inset ring-sky-200 dark:ring-sky-800' : '' }}">
                                                                    <td class="px-4 py-3">
                                                                        <input type="checkbox" name="citas[]" value="{{ $cita->IdCita }}" class="cita-checkbox size-4 rounded border-zinc-300 disabled:cursor-not-allowed disabled:opacity-30" data-group="{{ $groupId }}" @disabled(! $aplicaFua)>
                                                                    </td>
                                                                    <td class="whitespace-nowrap px-4 py-3">
                                                                        <div class="font-medium text-zinc-900 dark:text-zinc-50">{{ trim($cita->HoraInicio) }}</div>
                                                                        <div class="text-xs text-zinc-500">{{ trim($cita->HoraFin) }}</div>
                                                                    </td>
                                                                    <td class="whitespace-nowrap px-4 py-3">
                                                                        <div class="inline-flex min-w-28 flex-col rounded-lg bg-zinc-950 px-3 py-2 text-white shadow-sm dark:bg-white dark:text-zinc-950">
                                                                            <span class="text-[10px] font-semibold uppercase tracking-wide opacity-70">Historia</span>
                                                                            <span class="text-lg font-semibold leading-none">{{ $hcFormateada }}</span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="min-w-[280px] px-4 py-3">
                                                                        <div class="font-medium text-zinc-900 dark:text-zinc-50">{{ $cita->paciente?->nombre_completo ?: 'Sin paciente' }}</div>
                                                                        <div class="mt-1 flex flex-wrap gap-2 text-xs">
                                                                            <span class="px-2 py-1 text-zinc-500">{{ $tipoDocumento }}: {{ $cita->paciente?->NroDocumento ?: '-' }}</span>
                                                                            <span class="rounded-md px-2 py-1 font-semibold ring-1 {{ $formaPagoClass }}">{{ $formaPago ?: 'Sin forma de pago' }}</span>
                                                                            @if (($judicialAppointment || $esSisManual) && $formaPagoReal !== '-')
                                                                                <span class="px-2 py-1 {{ $judicialAppointment ? 'text-fuchsia-700 dark:text-fuchsia-200' : 'text-violet-700 dark:text-violet-200' }}">Base {{ $formaPagoReal }}</span>
                                                                            @endif
                                                                            @if ($esAdicional)
                                                                                <span class="rounded-md bg-rose-100 px-2 py-1 font-semibold text-rose-800 ring-1 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-700">Adicional</span>
                                                                            @endif
                                                                            @if ($turnoExtendido)
                                                                                <span class="rounded-md bg-amber-100 px-2 py-1 font-semibold text-amber-800 ring-1 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-700">Turno extendido</span>
                                                                            @endif
                                                                            @if ($esReciente)
                                                                                <span class="rounded-md bg-sky-100 px-2 py-1 font-semibold text-sky-800 ring-1 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-700">Registro reciente {{ $registroCita->format('H:i') }}</span>
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                    <td class="px-4 py-3">
                                                                        @if ($aplicaFua)
                                                                            <span class="rounded-md px-2 py-1 text-xs font-medium {{ $fuaProcesado ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100' }}">
                                                                                {{ $sisFua ? 'FUA SIS '.$sisFua->FuaDisa.' '.$sisFua->FuaLote.' '.$sisFua->FuaNumero : ($esSisManual ? 'Pendiente SIS manual' : 'Pendiente SIS') }}
                                                                            </span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="px-4 py-3 text-right">
                                                                        <div class="flex justify-end gap-2">
                                                                            <flux:button :href="route('citas.show', ['cita' => $cita->IdCita] + request()->query())" size="sm" variant="ghost" class="cita-detail-link">Ver</flux:button>
                                                                            @if ($aplicaFua)
                                                                                <flux:button :href="route('citas.fua.excel', $cita->IdCita)" size="sm" variant="ghost">FUA</flux:button>
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @empty
                <div class="rounded-lg border border-zinc-200 px-4 py-10 text-center text-zinc-500 dark:border-zinc-700">
                    No se encontraron citas.
                </div>
            @endforelse
                </div>
            </div>
        </form>

        <section class="grid gap-3 md:grid-cols-3 xl:grid-cols-7">
            <div class="hs-kpi">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">Fecha consultada</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{{ \Illuminate\Support\Carbon::parse($filters['fecha'])->format('d/m/Y') }}</div>
                <div class="text-xs text-zinc-500">Actualizado {{ $refreshedAt->format('H:i') }}</div>
            </div>
            <div class="hs-kpi">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">Consultorio activo</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{{ number_format($citas->count()) }}</div>
                <div class="text-xs text-zinc-500">Citas visibles</div>
            </div>
            <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4 shadow-sm shadow-rose-100/60 dark:border-rose-900 dark:bg-rose-950/30 dark:shadow-black/20">
                <div class="text-xs font-medium uppercase tracking-wide text-rose-700 dark:text-rose-200">Citas adicionales</div>
                <div class="mt-1 text-2xl font-semibold text-rose-900 dark:text-rose-100">{{ number_format($additionalCount) }}</div>
                <div class="text-xs text-rose-700/80 dark:text-rose-200/80">Del consultorio activo</div>
            </div>
            <a href="{{ $recentesUrl }}" class="rounded-xl border border-sky-200 bg-sky-50/70 p-4 shadow-sm shadow-sky-100/60 transition hover:-translate-y-0.5 hover:border-sky-300 hover:bg-sky-100 dark:border-sky-900 dark:bg-sky-950/30 dark:shadow-black/20 dark:hover:border-sky-700 dark:hover:bg-sky-950/60">
                <div class="text-xs font-medium uppercase tracking-wide text-sky-700 dark:text-sky-200">Registradas recientes</div>
                <div class="mt-1 text-2xl font-semibold text-sky-900 dark:text-sky-100">{{ number_format($recentCount) }}</div>
                <div class="text-xs text-sky-700/80 dark:text-sky-200/80">{{ $filters['recientes'] ? 'Filtro activo' : 'Ver ultimos '.$recentRegisteredMinutes.' min' }}</div>
            </a>
            @foreach ($turnos as $key => $turno)
                <div class="hs-kpi">
                    <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ $turno['label'] }}</div>
                    <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{{ number_format($groupedCitas[$key]['total']) }}</div>
                    <div class="text-xs text-zinc-500">{{ $turno['range'] }}</div>
                </div>
            @endforeach
        </section>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const batchForm = document.querySelector('form[action="{{ route('citas.fua.print-batches.store') }}"]');
                const selectAll = document.getElementById('select-all-citas');
                const selectAllServices = document.getElementById('select-all-services');
                const checkboxes = Array.from(document.querySelectorAll('.cita-checkbox:not(:disabled)'));
                const serviceCheckboxes = Array.from(document.querySelectorAll('.service-checkbox'));
                const groupCheckboxes = Array.from(document.querySelectorAll('.group-checkbox'));
                const groupSubmitButtons = Array.from(document.querySelectorAll('.group-submit'));
                const selectedCount = document.getElementById('selected-count');
                const selectedServicesCount = document.getElementById('selected-services-count');
                const stateKey = `citas.index.state:${window.location.pathname}${window.location.search}`;
                const listStateKey = `citas.index.consultorios:${window.location.pathname}`;
                const detailGroups = Array.from(document.querySelectorAll('.hs-accordion[data-citas-detail-key]'));
                const consultorioList = document.getElementById('consultorios-list');

                const readState = () => {
                    try {
                        return JSON.parse(sessionStorage.getItem(stateKey) || '{}');
                    } catch (error) {
                        return {};
                    }
                };

                const writeState = (state) => {
                    sessionStorage.setItem(stateKey, JSON.stringify({
                        scrollY: window.scrollY,
                        consultorioScrollTop: consultorioList?.scrollTop || 0,
                        open: Object.fromEntries(detailGroups.map((detail) => [detail.dataset.citasDetailKey, detail.classList.contains('active')])),
                        ...state,
                    }));

                    if (consultorioList) {
                        sessionStorage.setItem(listStateKey, String(consultorioList.scrollTop));
                    }
                };

                const setAccordionOpen = (detail, open) => {
                    const content = detail.querySelector(':scope > .hs-accordion-content');
                    const toggle = detail.querySelector(':scope > .hs-accordion-toggle');

                    detail.classList.toggle('active', open);
                    toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');

                    if (content) {
                        content.classList.toggle('hidden', !open);
                        content.style.display = open ? 'block' : 'none';
                        content.style.height = '';
                    }
                };

                const restoreState = () => {
                    const state = readState();

                    if (state.open) {
                        detailGroups.forEach((detail) => {
                            if (Object.prototype.hasOwnProperty.call(state.open, detail.dataset.citasDetailKey)) {
                                setAccordionOpen(detail, Boolean(state.open[detail.dataset.citasDetailKey]));
                            }
                        });
                    }

                    if (Number.isFinite(state.scrollY)) {
                        window.setTimeout(() => window.scrollTo({ top: state.scrollY, behavior: 'auto' }), 80);
                    }

                    const listScrollTop = Number(sessionStorage.getItem(listStateKey) || state.consultorioScrollTop);
                    if (consultorioList && Number.isFinite(listScrollTop)) {
                        window.setTimeout(() => consultorioList.scrollTo({ top: listScrollTop, behavior: 'auto' }), 80);
                    }
                };

                const updateCount = () => {
                    const count = checkboxes.filter((checkbox) => checkbox.checked).length;
                    const serviceCount = serviceCheckboxes.filter((checkbox) => checkbox.checked).length;
                    const selectedServiceAppointments = serviceCheckboxes
                        .filter((checkbox) => checkbox.checked)
                        .reduce((total, checkbox) => total + Number(checkbox.dataset.total || 0), 0);

                    selectedCount.textContent = `${count} citas / ${serviceCount} consultorios`;

                    if (selectedServicesCount) {
                        selectedServicesCount.textContent = serviceCount > 0
                            ? `${serviceCount} (${selectedServiceAppointments} citas)`
                            : '0';
                    }

                    if (selectAll) {
                        selectAll.checked = count > 0 && count === checkboxes.length;
                        selectAll.indeterminate = count > 0 && count < checkboxes.length;
                    }

                    if (selectAllServices) {
                        selectAllServices.checked = serviceCount > 0 && serviceCount === serviceCheckboxes.length;
                        selectAllServices.indeterminate = serviceCount > 0 && serviceCount < serviceCheckboxes.length;
                    }

                    groupCheckboxes.forEach((groupCheckbox) => {
                        const groupItems = checkboxes.filter((checkbox) => checkbox.dataset.group === groupCheckbox.dataset.group);
                        const groupCount = groupItems.filter((checkbox) => checkbox.checked).length;
                        groupCheckbox.checked = groupCount > 0 && groupCount === groupItems.length;
                        groupCheckbox.indeterminate = groupCount > 0 && groupCount < groupItems.length;
                    });
                };

                selectAll?.addEventListener('change', () => {
                    checkboxes.forEach((checkbox) => checkbox.checked = selectAll.checked);
                    updateCount();
                });

                selectAllServices?.addEventListener('change', () => {
                    serviceCheckboxes.forEach((checkbox) => checkbox.checked = selectAllServices.checked);
                    updateCount();
                });

                groupCheckboxes.forEach((groupCheckbox) => {
                    groupCheckbox.addEventListener('change', () => {
                        checkboxes
                            .filter((checkbox) => checkbox.dataset.group === groupCheckbox.dataset.group)
                            .forEach((checkbox) => checkbox.checked = groupCheckbox.checked);
                        updateCount();
                    });
                });

                groupSubmitButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        checkboxes.forEach((checkbox) => {
                            checkbox.checked = checkbox.dataset.group === button.dataset.group;
                        });
                        updateCount();
                    });
                });

                checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateCount));
                serviceCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', updateCount));
                batchForm?.addEventListener('submit', (event) => {
                    const selectedCitas = checkboxes.some((checkbox) => checkbox.checked);
                    const selectedServices = serviceCheckboxes.some((checkbox) => checkbox.checked);
                    const isSimulation = event.submitter?.getAttribute('formaction') === '{{ route('citas.fua.simulate') }}';

                    if (! selectedCitas && ! selectedServices) {
                        event.preventDefault();
                        alert('Selecciona al menos una cita o un consultorio para crear el lote.');
                    }

                    if (isSimulation && selectedServices && ! selectedCitas) {
                        event.preventDefault();
                        alert('La validacion por ahora aplica a citas visibles. Para consultorios completos usa Imprimir seleccionadas.');
                    }
                });
                updateCount();

                detailGroups.forEach((detail) => {
                    detail.addEventListener('open.hs.accordion', () => writeState());
                    detail.addEventListener('close.hs.accordion', () => writeState());
                });
                document.querySelectorAll('.cita-detail-link, a[href], button[type="submit"]').forEach((element) => {
                    element.addEventListener('click', () => writeState());
                });
                window.addEventListener('beforeunload', () => writeState());
                restoreState();

            });
        </script>
    </div>
</x-layouts::app>
