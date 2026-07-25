<x-layouts::app :title="__('Seguimiento SOAT')">
    <div class="hs-page-shell">
        <section class="hs-panel p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <div class="text-sm font-semibold text-violet-700 dark:text-violet-200">Trazabilidad SOAT / AFOCAT</div>
                    <flux:heading size="xl" class="mt-2">Seguimiento de pacientes SOAT</flux:heading>
                    <flux:text class="mt-2 max-w-3xl">
                        Relaciona la atencion inicial de emergencia con controles posteriores en consultorios, terapias u hospitalizacion. SIGH se usa como fuente de lectura; el estado operativo se guarda en la base del aplicativo.
                    </flux:text>
                </div>

                <form method="GET" action="{{ route('soat-cases.index') }}" class="grid w-full gap-3 xl:w-[880px] xl:grid-cols-12 xl:items-end">
                    <div class="xl:col-span-2">
                        <flux:input name="fecha_desde" label="Desde" type="date" value="{{ $filters['fecha_desde'] }}" />
                    </div>
                    <div class="xl:col-span-2">
                        <flux:input name="fecha_hasta" label="Hasta" type="date" value="{{ $filters['fecha_hasta'] }}" />
                    </div>
                    <label class="grid gap-2 text-sm xl:col-span-2">
                        <span class="text-zinc-700 dark:text-zinc-300">Caso</span>
                        <select name="estado_caso" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="todos" @selected($filters['estado_caso'] === 'todos')>Todos</option>
                            <option value="active" @selected($filters['estado_caso'] === 'active')>Activo</option>
                            <option value="observed" @selected($filters['estado_caso'] === 'observed')>Observado</option>
                            <option value="closed" @selected($filters['estado_caso'] === 'closed')>Cerrado</option>
                        </select>
                    </label>
                    <label class="grid gap-2 text-sm xl:col-span-2">
                        <span class="text-zinc-700 dark:text-zinc-300">Evento</span>
                        <select name="estado_evento" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="todos" @selected($filters['estado_evento'] === 'todos')>Todos</option>
                            <option value="pending" @selected($filters['estado_evento'] === 'pending')>Pendiente</option>
                            <option value="verified" @selected($filters['estado_evento'] === 'verified')>Verificado</option>
                            <option value="observed" @selected($filters['estado_evento'] === 'observed')>Observado</option>
                        </select>
                    </label>
                    <div class="xl:col-span-3">
                        <flux:input name="q" label="Buscar" value="{{ $filters['q'] }}" placeholder="Historia, DNI, paciente, servicio" />
                    </div>
                    <div class="flex gap-2 xl:col-span-1">
                        <flux:button type="submit" variant="primary">Filtrar</flux:button>
                    </div>
                </form>
            </div>
        </section>

        @include('pages.security.partials.flash')

        <section class="grid gap-4 md:grid-cols-5">
            <article class="hs-kpi hs-tooltip [--placement:top] border-violet-200 bg-violet-50/70 dark:border-violet-900 dark:bg-violet-950/30">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase text-violet-700 dark:text-violet-200">
                    <span>Casos</span>
                    <span class="hs-tooltip-toggle grid size-5 cursor-help place-items-center rounded-full bg-white/80 text-[10px] ring-1 ring-violet-200 dark:bg-violet-950 dark:ring-violet-800">?</span>
                    <span class="hs-tooltip-content invisible absolute z-50 max-w-xs rounded-lg bg-zinc-900 px-3 py-2 text-xs font-normal normal-case leading-5 text-white opacity-0 shadow-lg transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">
                        Pacientes agrupados por SOAT/AFOCAT. Cada caso representa la trazabilidad de un paciente desde su ingreso inicial y sus controles asociados.
                    </span>
                </div>
                <div class="mt-2 text-3xl font-semibold text-violet-950 dark:text-violet-100">{{ number_format($summary['cases']) }}</div>
                <div class="mt-1 text-sm text-violet-800/80 dark:text-violet-100/80">Pacientes agrupados</div>
            </article>
            <article class="hs-kpi hs-tooltip [--placement:top]">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase text-[var(--muted-foreground)]">
                    <span>Eventos</span>
                    <span class="hs-tooltip-toggle grid size-5 cursor-help place-items-center rounded-full bg-[var(--muted)] text-[10px] ring-1 ring-[var(--border)]">?</span>
                    <span class="hs-tooltip-content invisible absolute z-50 max-w-xs rounded-lg bg-zinc-900 px-3 py-2 text-xs font-normal normal-case leading-5 text-white opacity-0 shadow-lg transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">
                        Atenciones encontradas para esos pacientes: emergencia, hospitalizacion, consultorios o terapias vinculadas por paciente y financiamiento SOAT/AFOCAT.
                    </span>
                </div>
                <div class="mt-2 text-3xl font-semibold text-[var(--foreground)]">{{ number_format($summary['events']) }}</div>
                <div class="mt-1 text-sm text-[var(--muted-foreground)]">Emergencia y controles</div>
            </article>
            <article class="hs-kpi hs-tooltip [--placement:top] border-amber-200 bg-amber-50/70 dark:border-amber-900 dark:bg-amber-950/30">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase text-amber-700 dark:text-amber-200">
                    <span>Pendientes</span>
                    <span class="hs-tooltip-toggle grid size-5 cursor-help place-items-center rounded-full bg-white/80 text-[10px] ring-1 ring-amber-200 dark:bg-amber-950 dark:ring-amber-800">?</span>
                    <span class="hs-tooltip-content invisible absolute z-50 max-w-xs rounded-lg bg-zinc-900 px-3 py-2 text-xs font-normal normal-case leading-5 text-white opacity-0 shadow-lg transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">
                        Eventos sincronizados que el personal aun no revisa. Es el estado inicial despues de traer datos desde SIGH.
                    </span>
                </div>
                <div class="mt-2 text-3xl font-semibold text-amber-950 dark:text-amber-100">{{ number_format($summary['pending']) }}</div>
                <div class="mt-1 text-sm text-amber-800/80 dark:text-amber-100/80">Por validar</div>
            </article>
            <article class="hs-kpi hs-tooltip [--placement:top] border-emerald-200 bg-emerald-50/70 dark:border-emerald-900 dark:bg-emerald-950/30">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-200">
                    <span>Verificados</span>
                    <span class="hs-tooltip-toggle grid size-5 cursor-help place-items-center rounded-full bg-white/80 text-[10px] ring-1 ring-emerald-200 dark:bg-emerald-950 dark:ring-emerald-800">?</span>
                    <span class="hs-tooltip-content invisible absolute z-50 max-w-xs rounded-lg bg-zinc-900 px-3 py-2 text-xs font-normal normal-case leading-5 text-white opacity-0 shadow-lg transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">
                        Eventos confirmados por el usuario como parte real del seguimiento SOAT del paciente. Estos suman al avance validado del caso.
                    </span>
                </div>
                <div class="mt-2 text-3xl font-semibold text-emerald-950 dark:text-emerald-100">{{ number_format($summary['verified']) }}</div>
                <div class="mt-1 text-sm text-emerald-800/80 dark:text-emerald-100/80">Con seguimiento revisado</div>
            </article>
            <article class="hs-kpi hs-tooltip [--placement:top] border-rose-200 bg-rose-50/70 dark:border-rose-900 dark:bg-rose-950/30">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase text-rose-700 dark:text-rose-200">
                    <span>Observados</span>
                    <span class="hs-tooltip-toggle grid size-5 cursor-help place-items-center rounded-full bg-white/80 text-[10px] ring-1 ring-rose-200 dark:bg-rose-950 dark:ring-rose-800">?</span>
                    <span class="hs-tooltip-content invisible absolute z-50 max-w-xs rounded-lg bg-zinc-900 px-3 py-2 text-xs font-normal normal-case leading-5 text-white opacity-0 shadow-lg transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">
                        Eventos que requieren revision porque puede faltar sustento, no corresponder al accidente, tener aseguradora dudosa o necesitar validacion administrativa.
                    </span>
                </div>
                <div class="mt-2 text-3xl font-semibold text-rose-950 dark:text-rose-100">{{ number_format($summary['observed']) }}</div>
                <div class="mt-1 text-sm text-rose-800/80 dark:text-rose-100/80">Requieren revision</div>
            </article>
        </section>

        <section class="grid gap-4">
            @forelse ($cases as $case)
                @php($caseStatusClass = match ($case->status) {
                    'closed' => 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-800',
                    'observed' => 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800',
                    default => 'bg-violet-50 text-violet-800 ring-violet-200 dark:bg-violet-950 dark:text-violet-100 dark:ring-violet-800',
                })
                <article class="hs-panel overflow-hidden">
                    <div class="border-b border-[var(--border)] bg-[var(--muted)] px-5 py-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center">
                                <div class="inline-flex w-fit flex-col rounded-xl bg-zinc-950 px-4 py-3 text-white shadow-sm dark:bg-white dark:text-zinc-950">
                                    <span class="text-[10px] font-semibold uppercase tracking-wide opacity-70">N° Historia Clinica</span>
                                    <span class="text-2xl font-semibold leading-none">{{ \App\Support\ClinicalHistoryNumber::format($case->history_number) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <h2 class="truncate text-lg font-semibold text-[var(--foreground)]">{{ $case->patient_name }}</h2>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-[var(--muted-foreground)]">
                                        <span>Doc: {{ $case->document_number ?: '-' }}</span>
                                        <span class="size-1 rounded-full bg-[var(--border-line-4)]"></span>
                                        <span>{{ $case->insurer_name ?: 'SOAT/AFOCAT' }}</span>
                                        <span class="rounded-md px-2 py-1 font-semibold ring-1 {{ $caseStatusClass }}">
                                            {{ match ($case->status) {
                                                'closed' => 'Caso cerrado',
                                                'observed' => 'Caso observado',
                                                default => 'Caso activo',
                                            } }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-3 text-center sm:min-w-[360px]">
                                <div class="rounded-lg border border-[var(--border)] bg-[var(--background)] p-3">
                                    <div class="text-lg font-semibold text-[var(--foreground)]">{{ $case->events->count() }}</div>
                                    <div class="text-[11px] text-[var(--muted-foreground)]">Eventos</div>
                                </div>
                                <div class="rounded-lg border border-[var(--border)] bg-[var(--background)] p-3">
                                    <div class="text-lg font-semibold text-[var(--foreground)]">{{ $case->followUpEvents() }}</div>
                                    <div class="text-[11px] text-[var(--muted-foreground)]">Controles</div>
                                </div>
                                <div class="rounded-lg border border-[var(--border)] bg-[var(--background)] p-3">
                                    <div class="text-lg font-semibold text-[var(--foreground)]">{{ $case->progressPercentage() }}%</div>
                                    <div class="text-[11px] text-[var(--muted-foreground)]">Validado</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-4">
                        <div class="mb-4 grid gap-3 text-sm lg:grid-cols-3">
                            <div>
                                <div class="text-xs font-semibold uppercase text-[var(--muted-foreground)]">Ingreso inicial</div>
                                <div class="mt-1 font-medium text-[var(--foreground)]">{{ $case->emergency_date?->format('d/m/Y') ?: '-' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase text-[var(--muted-foreground)]">Servicio de emergencia</div>
                                <div class="mt-1 font-medium text-[var(--foreground)]">{{ $case->emergency_service ?: '-' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase text-[var(--muted-foreground)]">Lectura funcional</div>
                                <div class="mt-1 text-[var(--muted-foreground)]">El vinculo se infiere por paciente y financiamiento SOAT/AFOCAT; no modifica SIGH.</div>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-[var(--border)]">
                            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                                    <tr>
                                        <th class="px-4 py-3">Fecha / hora</th>
                                        <th class="px-4 py-3">Tipo</th>
                                        <th class="px-4 py-3">Servicio</th>
                                        <th class="px-4 py-3">Medico</th>
                                        <th class="px-4 py-3">Financiamiento</th>
                                        <th class="px-4 py-3">Estado</th>
                                        <th class="px-4 py-3 text-right">Seguimiento</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @forelse ($case->events as $event)
                                        @php($typeClass = match ($event->event_type) {
                                            'emergency' => 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800',
                                            'hospitalization' => 'bg-sky-50 text-sky-800 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800',
                                            default => 'bg-violet-50 text-violet-800 ring-violet-200 dark:bg-violet-950 dark:text-violet-100 dark:ring-violet-800',
                                        })
                                        @php($eventStatusClass = match ($event->status) {
                                            'verified' => 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-800',
                                            'observed' => 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800',
                                            default => 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-800',
                                        })
                                        <tr class="align-top hover:bg-zinc-50/70 dark:hover:bg-zinc-900/60">
                                            <td class="whitespace-nowrap px-4 py-4 font-semibold text-[var(--foreground)]">
                                                {{ $event->event_date?->format('d/m/Y') ?: '-' }}
                                                <div class="text-xs font-normal text-[var(--muted-foreground)]">{{ $event->event_time?->format('H:i') ?: '-' }}</div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="rounded-md px-2 py-1 text-xs font-semibold ring-1 {{ $typeClass }}">
                                                    {{ match ($event->event_type) {
                                                        'emergency' => 'Emergencia',
                                                        'hospitalization' => 'Hospitalizacion',
                                                        default => 'Control',
                                                    } }}
                                                </span>
                                            </td>
                                            <td class="min-w-[240px] px-4 py-4">
                                                <div class="font-medium text-[var(--foreground)]">{{ $event->service ?: '-' }}</div>
                                                <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $event->specialty ?: '-' }}</div>
                                            </td>
                                            <td class="min-w-[200px] px-4 py-4 text-[var(--muted-foreground)]">{{ $event->doctor_name ?: 'No registrado' }}</td>
                                            <td class="min-w-[180px] px-4 py-4">
                                                <div class="font-medium text-[var(--foreground)]">{{ $event->payment_type ?: '-' }}</div>
                                                <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $event->funding_source ?: '-' }}</div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="rounded-md px-2 py-1 text-xs font-semibold ring-1 {{ $eventStatusClass }}">
                                                    {{ match ($event->status) {
                                                        'verified' => 'Verificado',
                                                        'observed' => 'Observado',
                                                        default => 'Pendiente',
                                                    } }}
                                                </span>
                                                @if ($event->notes)
                                                    <div class="mt-2 max-w-52 text-xs text-[var(--muted-foreground)]">{{ $event->notes }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4">
                                                @if (auth()->user()->hasPermission('soat.update'))
                                                    <form method="POST" action="{{ route('soat-cases.events.update', $event) }}" class="ml-auto flex min-w-[320px] flex-col gap-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <div class="grid grid-cols-[1fr_auto] gap-2">
                                                            <select name="status" class="h-9 rounded-md border border-zinc-200 bg-white px-2 text-xs dark:border-zinc-700 dark:bg-zinc-900">
                                                                <option value="pending" @selected($event->status === 'pending')>Pendiente</option>
                                                                <option value="verified" @selected($event->status === 'verified')>Verificado</option>
                                                                <option value="observed" @selected($event->status === 'observed')>Observado</option>
                                                            </select>
                                                            <button type="submit" class="rounded-md bg-[var(--primary)] px-3 py-2 text-xs font-semibold text-[var(--primary-foreground)] hover:opacity-90">Guardar</button>
                                                        </div>
                                                        <input name="notes" value="{{ $event->notes }}" class="h-9 rounded-md border border-zinc-200 bg-white px-2 text-xs dark:border-zinc-700 dark:bg-zinc-900" placeholder="Observacion operativa">
                                                    </form>
                                                @else
                                                    <div class="text-right text-xs text-[var(--muted-foreground)]">Solo lectura</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center text-[var(--muted-foreground)]">No hay eventos para este caso en el rango filtrado.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            @empty
                <section class="hs-panel p-10 text-center">
                    <div class="mx-auto grid size-12 place-items-center rounded-xl bg-[var(--muted)] text-[var(--primary)]">
                        <flux:icon icon="shield-check" class="size-6" />
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-[var(--foreground)]">No hay casos SOAT sincronizados</h2>
                    <p class="mt-2 text-sm text-[var(--muted-foreground)]">Ejecuta la sincronizacion para traer las atenciones SOAT/AFOCAT desde SIGH hacia la base del aplicativo.</p>
                    <code class="mt-4 inline-block rounded-lg bg-zinc-950 px-3 py-2 text-xs text-white">php artisan soat:sync --from={{ $filters['fecha_desde'] }} --to={{ $filters['fecha_hasta'] }}</code>
                </section>
            @endforelse
        </section>

        <div>{{ $cases->links() }}</div>
    </div>
</x-layouts::app>
