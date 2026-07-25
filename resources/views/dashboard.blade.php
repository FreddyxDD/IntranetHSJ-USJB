<x-layouts::app :title="__('Panel')">
    @php($maxHourly = max(1, collect($hourly)->max('total') ?? 1))
    @php($maxState = max(1, collect($states)->max('total') ?? 1))
    @php($maxTurn = max(1, collect($turns)->max('total') ?? 1))
    @php($maxSpecialty = max(1, collect($topSpecialties)->max('total') ?? 1))
    @php($maxFinancing = max(1, collect($financing)->max('total') ?? 1))

    <div class="hs-page-shell">
        <section class="hs-panel overflow-hidden">
            <div class="grid gap-0 xl:grid-cols-[1fr_360px]">
                <div class="p-6 lg:p-8">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="hs-soft-badge bg-teal-50 text-teal-700 ring-teal-200 dark:bg-teal-950 dark:text-teal-100 dark:ring-teal-800">Operacion diaria</span>
                        <span class="hs-soft-badge bg-zinc-50 text-zinc-600 ring-zinc-200 dark:bg-zinc-950 dark:text-zinc-300 dark:ring-zinc-800">{{ $date->format('d/m/Y') }}</span>
                        <span class="hs-soft-badge bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800">Actualizado {{ $refreshedAt->format('H:i') }}</span>
                    </div>

                    <div class="mt-5 max-w-4xl">
                        <h1 class="text-2xl font-semibold tracking-normal text-zinc-950 dark:text-white sm:text-3xl">Panel operativo institucional</h1>
                        <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                            Vista ejecutiva de citas, demanda asistencial, financiamiento, FUA, impresion por lotes y seguimientos especiales del Hospital San Jose de Chincha.
                        </p>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        @if (auth()->user()->hasPermission('citas.view'))
                            <a href="{{ route('citas.index') }}" wire:navigate class="inline-flex items-center gap-x-2 rounded-lg bg-teal-700 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">
                                Revisar citas
                            </a>
                        @endif
                        @if (auth()->user()->hasPermission('fuas.report.view'))
                            <a href="{{ route('fuas-sis-reporte.index') }}" wire:navigate class="inline-flex items-center gap-x-2 rounded-lg border border-zinc-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                Revisar FUA SIS
                            </a>
                        @endif
                    </div>
                </div>

                <div class="border-t border-zinc-200 bg-zinc-50/80 p-6 dark:border-zinc-800 dark:bg-zinc-950/40 xl:border-l xl:border-t-0">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('images/logo/logo.png') }}" alt="Hospital San Jose de Chincha" class="size-20 rounded-2xl bg-white object-contain p-1 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
                        <div>
                            <div class="text-base font-semibold text-zinc-950 dark:text-white">HSJ Chincha</div>
                            <div class="mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400">Citas, FUA SIS, reportes, trazabilidad y seguimiento operativo.</div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-2xl bg-white p-4 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
                        <div class="flex items-end justify-between gap-4">
                            <div>
                                <div class="text-xs font-semibold uppercase text-zinc-500">Avance del dia</div>
                                <div class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $summary['progress'] }}%</div>
                            </div>
                            <div class="text-right text-xs text-zinc-500">
                                <div>{{ number_format($summary['attended']) }} atendidas</div>
                                <div>{{ number_format($summary['pending']) }} pendientes</div>
                            </div>
                        </div>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full rounded-full bg-teal-600" style="width: {{ min(100, $summary['progress']) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            @foreach ([
                ['label' => 'Citas hoy', 'value' => $summary['total'], 'hint' => 'Pacientes programados', 'tone' => 'teal', 'icon' => 'calendar-days'],
                ['label' => 'Atendidas', 'value' => $summary['attended'], 'hint' => $summary['progress'].'% de avance', 'tone' => 'emerald', 'icon' => 'check-circle'],
                ['label' => 'Pendientes', 'value' => $summary['pending'], 'hint' => 'Por resolver', 'tone' => 'amber', 'icon' => 'clock'],
                ['label' => 'Ocupacion', 'value' => $summary['occupancy'].'%', 'hint' => 'Incluye sobrecupos', 'tone' => $summary['occupancy'] > 100 ? 'rose' : 'sky', 'icon' => 'chart-bar'],
                ['label' => 'Adicionales', 'value' => $summary['additional'], 'hint' => 'Fuera de cupo regular', 'tone' => 'rose', 'icon' => 'plus-circle'],
                ['label' => 'FUA pendientes', 'value' => $summary['fua_pending'], 'hint' => 'SIS por revisar', 'tone' => 'violet', 'icon' => 'document-text'],
            ] as $card)
                @php($toneClasses = [
                    'teal' => 'bg-teal-50 text-teal-700 ring-teal-200 dark:bg-teal-950 dark:text-teal-100 dark:ring-teal-800',
                    'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-800',
                    'amber' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-800',
                    'sky' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800',
                    'rose' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800',
                    'violet' => 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-950 dark:text-violet-100 dark:ring-violet-800',
                ][$card['tone']])
                <article class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-3">
                        <span class="grid size-10 place-items-center rounded-xl ring-1 {{ $toneClasses }}">
                            <flux:icon :icon="$card['icon']" class="size-5" />
                        </span>
                        <span class="text-xs font-medium text-zinc-500">{{ $card['hint'] }}</span>
                    </div>
                    <div class="mt-5 text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $card['label'] }}</div>
                    <div class="mt-1 text-3xl font-semibold text-zinc-950 dark:text-white">{{ is_numeric($card['value']) ? number_format($card['value']) : $card['value'] }}</div>
                </article>
            @endforeach
        </section>

        <section class="grid gap-5 xl:grid-cols-[1.4fr_.8fr_.8fr]">
            <article class="hs-panel p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Evolucion de citas durante el dia</h2>
                        <p class="mt-1 text-sm text-zinc-500">Volumen de citas por hora registrada.</p>
                    </div>
                </div>
                <div class="mt-6 flex h-64 items-end gap-2 overflow-x-auto pb-2">
                    @forelse ($hourly as $point)
                        <div class="flex min-w-10 flex-1 flex-col items-center gap-2">
                            <div class="w-full rounded-t-lg bg-teal-600/90" style="height: {{ max(8, round(($point['total'] / $maxHourly) * 210)) }}px"></div>
                            <div class="text-[11px] text-zinc-500">{{ $point['label'] }}</div>
                        </div>
                    @empty
                        <div class="flex h-full w-full items-center justify-center text-sm text-zinc-500">Sin citas para graficar.</div>
                    @endforelse
                </div>
            </article>

            <article class="hs-panel p-5">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Distribucion por estado</h2>
                <div class="mt-5 grid gap-3">
                    @forelse ($states as $state)
                        <div>
                            <div class="flex justify-between gap-3 text-sm">
                                <span class="truncate text-zinc-700 dark:text-zinc-200">{{ $state['label'] }}</span>
                                <span class="font-semibold text-zinc-950 dark:text-white">{{ number_format($state['total']) }}</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-sky-500" style="width: {{ round(($state['total'] / $maxState) * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">Sin estados registrados.</p>
                    @endforelse
                </div>
            </article>

            <article class="hs-panel p-5">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Distribucion por turno</h2>
                <div class="mt-5 grid gap-3">
                    @forelse ($turns as $turn)
                        <div class="rounded-xl bg-zinc-50 p-3 ring-1 ring-zinc-200 dark:bg-zinc-950 dark:ring-zinc-800">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $turn['label'] }}</span>
                                <span class="text-lg font-semibold text-zinc-950 dark:text-white">{{ number_format($turn['total']) }}</span>
                            </div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white dark:bg-zinc-900">
                                <div class="h-full rounded-full bg-violet-500" style="width: {{ round(($turn['total'] / $maxTurn) * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">Sin turnos registrados.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-2">
            <article class="hs-panel p-5">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Especialidades con mayor demanda</h2>
                <div class="mt-5 grid gap-3">
                    @forelse ($topSpecialties as $specialty)
                        <div>
                            <div class="flex justify-between gap-3 text-sm">
                                <span class="truncate text-zinc-700 dark:text-zinc-200">{{ $specialty['label'] }}</span>
                                <span class="font-semibold text-zinc-950 dark:text-white">{{ number_format($specialty['total']) }}</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-teal-600" style="width: {{ round(($specialty['total'] / $maxSpecialty) * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">Sin demanda registrada.</p>
                    @endforelse
                </div>
            </article>

            <article class="hs-panel p-5">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Consultorios saturados</h2>
                <p class="mt-1 text-sm text-zinc-500">Priorizado por adicionales y volumen de citas.</p>
                <div class="mt-5 grid gap-3">
                    @forelse ($saturatedServices as $service)
                        <div class="rounded-xl bg-zinc-50 p-3 ring-1 ring-zinc-200 dark:bg-zinc-950 dark:ring-zinc-800">
                            <div class="flex justify-between gap-3 text-sm">
                                <span class="truncate font-medium text-zinc-800 dark:text-zinc-100">{{ $service['label'] }}</span>
                                <span class="font-semibold text-zinc-950 dark:text-white">{{ number_format($service['total']) }}</span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-xs text-zinc-500">
                                <span>{{ number_format($service['additional']) }} adicionales</span>
                                <span>{{ $service['load'] }}% carga</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">Sin consultorios saturados.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-[1fr_1fr_.8fr]">
            <article class="hs-panel p-5">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Distribucion por financiamiento</h2>
                <div class="mt-5 grid gap-3">
                    @forelse ($financing as $item)
                        <div>
                            <div class="flex justify-between gap-3 text-sm">
                                <span class="truncate text-zinc-700 dark:text-zinc-200">{{ $item['label'] }}</span>
                                <span class="font-semibold text-zinc-950 dark:text-white">{{ number_format($item['total']) }}</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-indigo-500" style="width: {{ round(($item['total'] / $maxFinancing) * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">Sin financiamiento registrado.</p>
                    @endforelse
                </div>
            </article>

            <article class="hs-panel p-5">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Estado de FUA</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-200 dark:bg-emerald-950/30 dark:ring-emerald-900">
                        <div class="text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-200">Generadas</div>
                        <div class="mt-2 text-3xl font-semibold text-emerald-950 dark:text-emerald-100">{{ number_format($summary['fua_generated']) }}</div>
                    </div>
                    <div class="rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200 dark:bg-amber-950/30 dark:ring-amber-900">
                        <div class="text-xs font-semibold uppercase text-amber-700 dark:text-amber-200">Pendientes</div>
                        <div class="mt-2 text-3xl font-semibold text-amber-950 dark:text-amber-100">{{ number_format($summary['fua_pending']) }}</div>
                    </div>
                </div>
                @if (auth()->user()->hasPermission('fuas.report.view'))
                    <a href="{{ route('fuas-sis-reporte.index', ['estado_fua' => 'pendiente']) }}" wire:navigate class="mt-5 inline-flex text-sm font-semibold text-teal-700 hover:underline dark:text-teal-200">Ver reporte FUA SIS</a>
                @endif
            </article>

            <article class="hs-panel p-5">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Impresion por lotes</h2>
                <div class="mt-5 rounded-2xl bg-zinc-50 p-4 ring-1 ring-zinc-200 dark:bg-zinc-950 dark:ring-zinc-800">
                    <div class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $printBatch['label'] }}</div>
                    <div class="mt-2 text-xs text-zinc-500">{{ number_format($printBatch['processed']) }} de {{ number_format($printBatch['total']) }} procesados</div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-white dark:bg-zinc-900">
                        <div class="h-full rounded-full bg-teal-600" style="width: {{ $printBatch['progress'] }}%"></div>
                    </div>
                    <div class="mt-3 text-xs text-zinc-500">{{ number_format($printBatch['observed']) }} observaciones</div>
                </div>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-[1fr_1fr]">
            <article class="hs-panel p-5">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Seguimientos especiales</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @if (auth()->user()->hasPermission('judicial.view'))
                        <a href="{{ route('judicial-cases.index') }}" wire:navigate class="rounded-2xl bg-fuchsia-50 p-4 ring-1 ring-fuchsia-200 dark:bg-fuchsia-950/30 dark:ring-fuchsia-900">
                            <div class="text-xs font-semibold uppercase text-fuchsia-700 dark:text-fuchsia-200">Judicial</div>
                            <div class="mt-2 text-3xl font-semibold text-fuchsia-950 dark:text-fuchsia-100">{{ number_format($specialTracking['judicial_today']) }}</div>
                            <div class="mt-1 text-sm text-fuchsia-800/80 dark:text-fuchsia-100/80">{{ number_format($specialTracking['judicial_pending']) }} pendientes</div>
                        </a>
                    @else
                        <div class="rounded-2xl bg-fuchsia-50 p-4 ring-1 ring-fuchsia-200 dark:bg-fuchsia-950/30 dark:ring-fuchsia-900">
                            <div class="text-xs font-semibold uppercase text-fuchsia-700 dark:text-fuchsia-200">Judicial</div>
                            <div class="mt-2 text-3xl font-semibold text-fuchsia-950 dark:text-fuchsia-100">{{ number_format($specialTracking['judicial_today']) }}</div>
                            <div class="mt-1 text-sm text-fuchsia-800/80 dark:text-fuchsia-100/80">{{ number_format($specialTracking['judicial_pending']) }} pendientes</div>
                        </div>
                    @endif

                    @if (auth()->user()->hasPermission('soat.view'))
                        <a href="{{ route('soat-cases.index') }}" wire:navigate class="rounded-2xl bg-violet-50 p-4 ring-1 ring-violet-200 dark:bg-violet-950/30 dark:ring-violet-900">
                            <div class="text-xs font-semibold uppercase text-violet-700 dark:text-violet-200">SOAT/AFOCAT</div>
                            <div class="mt-2 text-3xl font-semibold text-violet-950 dark:text-violet-100">{{ number_format($specialTracking['soat_today']) }}</div>
                            <div class="mt-1 text-sm text-violet-800/80 dark:text-violet-100/80">{{ number_format($specialTracking['soat_observed']) }} observados</div>
                        </a>
                    @else
                        <div class="rounded-2xl bg-violet-50 p-4 ring-1 ring-violet-200 dark:bg-violet-950/30 dark:ring-violet-900">
                            <div class="text-xs font-semibold uppercase text-violet-700 dark:text-violet-200">SOAT/AFOCAT</div>
                            <div class="mt-2 text-3xl font-semibold text-violet-950 dark:text-violet-100">{{ number_format($specialTracking['soat_today']) }}</div>
                            <div class="mt-1 text-sm text-violet-800/80 dark:text-violet-100/80">{{ number_format($specialTracking['soat_observed']) }} observados</div>
                        </div>
                    @endif
                </div>
            </article>

            <article class="hs-panel p-5">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Alertas criticas</h2>
                <div class="mt-5 grid gap-3">
                    @forelse ($criticalAlerts as $alert)
                        @php($alertClass = match ($alert['tone']) {
                            'rose' => 'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-100',
                            default => 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100',
                        })
                        <div class="rounded-2xl border p-4 {{ $alertClass }}">
                            <div class="font-semibold">{{ $alert['title'] }}</div>
                            <div class="mt-1 text-sm opacity-80">{{ $alert['description'] }}</div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-100">
                            <div class="font-semibold">Sin alertas criticas</div>
                            <div class="mt-1 text-sm opacity-80">No hay pendientes prioritarios para los indicadores principales.</div>
                        </div>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
</x-layouts::app>
