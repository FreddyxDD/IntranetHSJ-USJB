<x-layouts::app :title="'Programacion medica'">
    <div class="hs-page-shell">
        <section class="hs-panel p-5 lg:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="text-sm font-semibold text-[var(--primary)]">Consultorios externos</div>
                    <flux:heading size="xl" class="mt-1">Programacion medica mensual</flux:heading>
                    <flux:text class="mt-2">Consulta la agenda programada por medico, servicio y turno. Las acciones de mantenimiento quedan preparadas para validacion antes de escribir en SIGH.</flux:text>
                </div>

                <form method="GET" action="{{ route('programacion-medica.index') }}" class="grid w-full gap-3 rounded-xl border border-[var(--border)] bg-[var(--muted)] p-4 sm:grid-cols-2 xl:max-w-5xl xl:grid-cols-6 xl:items-end">
                    <flux:input name="mes" label="Mes" type="month" value="{{ $filters['mes'] }}" />
                    <label class="grid gap-2 text-sm">
                        <span class="text-[var(--muted-foreground-2)]">Turno</span>
                        <select name="turno" class="h-10 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 text-sm">
                            @foreach ($turnos as $key => $label)
                                <option value="{{ $key }}" @selected($filters['turno'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-2 text-sm">
                        <span class="text-[var(--muted-foreground-2)]">Agrupar</span>
                        <select name="agrupacion" class="h-10 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 text-sm">
                            <option value="dia" @selected($filters['agrupacion'] === 'dia')>Por dia</option>
                            <option value="semana" @selected($filters['agrupacion'] === 'semana')>Por semana</option>
                            <option value="ninguna" @selected($filters['agrupacion'] === 'ninguna')>Sin agrupacion</option>
                        </select>
                    </label>
                    <flux:input name="servicio" label="Servicio ID" type="number" value="{{ $filters['servicio'] }}" />
                    <flux:input name="medico" label="Medico ID" type="number" value="{{ $filters['medico'] }}" />
                    <div class="sm:col-span-2">
                        <flux:input name="q" label="Buscar" value="{{ $filters['q'] }}" placeholder="Medico, servicio, especialidad, DNI" />
                    </div>
                    <div class="flex gap-2 xl:col-span-6">
                        <flux:button type="submit" variant="primary">Filtrar</flux:button>
                        <flux:button :href="route('programacion-medica.index')" variant="ghost">Mes actual</flux:button>
                        <flux:button :href="route('programacion-medica.create', ['mes' => $filters['mes']])" variant="ghost">Nueva programacion</flux:button>
                    </div>
                </form>
            </div>
        </section>

        <section class="grid gap-3 md:grid-cols-3 xl:grid-cols-7">
            @foreach ([
                ['label' => 'Programaciones', 'value' => $summary['programaciones'], 'tone' => 'zinc'],
                ['label' => 'Medicos', 'value' => $summary['medicos'], 'tone' => 'sky'],
                ['label' => 'Servicios', 'value' => $summary['servicios'], 'tone' => 'indigo'],
                ['label' => 'Cupos', 'value' => $summary['cupos'], 'tone' => 'teal'],
                ['label' => 'Citas', 'value' => $summary['citas'], 'tone' => 'emerald'],
                ['label' => 'Disponibles', 'value' => $summary['disponibles'], 'tone' => 'amber'],
                ['label' => 'Adicionales', 'value' => $summary['adicionales'], 'tone' => 'rose'],
            ] as $metric)
                <div class="rounded-xl border border-[var(--border)] bg-[var(--background)] p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-[var(--muted-foreground)]">{{ $metric['label'] }}</div>
                    <div class="mt-1 text-2xl font-semibold text-[var(--foreground)]">{{ number_format($metric['value']) }}</div>
                </div>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-xl border border-[var(--border)] bg-[var(--background)]">
            <div class="flex flex-col gap-2 border-b border-[var(--border)] px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-[var(--foreground)]">Programaciones de {{ $monthLabel }}</h2>
                    <p class="text-sm text-[var(--muted-foreground)]">Cupos calculados por rango horario y tiempo promedio de atencion.</p>
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100">
                    Mantenimiento directo en modo validacion: no escribe en SIGH.
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--border)] text-sm">
                    <thead class="bg-[var(--muted)] text-left text-xs font-semibold uppercase text-[var(--muted-foreground)]">
                        <tr>
                            <th class="px-5 py-3">Fecha / turno</th>
                            <th class="px-5 py-3">Medico</th>
                            <th class="px-5 py-3">Servicio</th>
                            <th class="px-5 py-3">Horario</th>
                            <th class="px-5 py-3">Cupos</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @forelse ($groupedProgramaciones as $group)
                            <tr class="bg-[var(--muted)]/80">
                                <td colspan="7" class="px-5 py-3">
                                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="font-semibold text-[var(--foreground)]">{{ $group['label'] }}</div>
                                        <div class="text-xs text-[var(--muted-foreground)]">{{ $group['items']->count() }} programaciones en esta seccion</div>
                                    </div>
                                </td>
                            </tr>
                            @foreach ($group['items'] as $programacion)
                                @php($average = (int) ($programacion->TiempoPromedioAtencion ?? 0))
                                @php($start = \App\Support\AppointmentTurn::minutes(trim((string) $programacion->HoraInicio)))
                                @php($end = \App\Support\AppointmentTurn::minutes(trim((string) ($programacion->HoraFinProgramacion ?: $programacion->HoraFin))))
                                @php($capacity = $average > 0 && $end > $start ? (int) floor(($end - $start) / $average) : 0)
                                @php($used = (int) $programacion->citas_count)
                                @php($available = max(0, $capacity - $used))
                                <tr class="align-top hover:bg-[var(--muted)]/60">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <div class="font-semibold text-[var(--foreground)]">{{ $programacion->Fecha?->format('d/m/Y') }}</div>
                                        <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $programacion->turno?->Descripcion ?: 'Sin turno' }} / {{ $programacion->tipoProgramacion?->Descripcion ?: 'Sin tipo' }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-[var(--foreground)]">{{ $programacion->medico?->empleado?->nombre_completo ?: 'Sin medico' }}</div>
                                        <div class="mt-1 text-xs text-[var(--muted-foreground)]">ID {{ $programacion->IdMedico }} / DNI {{ $programacion->medico?->empleado?->DNI ?: '-' }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-[var(--foreground)]">{{ $programacion->servicio?->Nombre ?: 'Sin servicio' }}</div>
                                        <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $programacion->especialidad?->Nombre ?: 'Sin especialidad' }} / Servicio {{ $programacion->IdServicio }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <div class="font-semibold text-[var(--foreground)]">{{ trim((string) $programacion->HoraInicio) }} - {{ trim((string) ($programacion->HoraFinProgramacion ?: $programacion->HoraFin)) }}</div>
                                        <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $average ?: '-' }} min por cita</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-[var(--foreground)]">{{ $used }} / {{ $capacity }}</div>
                                        <div class="mt-2 h-2 w-32 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                            <div class="h-full rounded-full bg-teal-600" style="width: {{ $capacity > 0 ? min(100, round(($used / $capacity) * 100)) : 0 }}%"></div>
                                        </div>
                                        <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $available }} disponibles</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($programacion->citas_adicionales_count > 0)
                                            <span class="rounded-md bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800">{{ $programacion->citas_adicionales_count }} adicionales</span>
                                        @elseif ($available === 0 && $capacity > 0)
                                            <span class="rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-800">Cupos completos</span>
                                        @else
                                            <span class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-800">Disponible</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <flux:button :href="route('programacion-medica.show', ['programacion' => $programacion->IdProgramacion, 'return_url' => request()->fullUrl()])" size="sm" variant="primary">Ver agenda</flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-[var(--muted-foreground)]">No hay programaciones para los filtros seleccionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-[var(--border)] px-5 py-4">
                {{ $programaciones->links() }}
            </div>
        </section>
    </div>
</x-layouts::app>
