<x-layouts::app :title="'Programacion '.$programacion->IdProgramacion">
    @php($average = (int) ($programacion->TiempoPromedioAtencion ?? 0))
    @php($capacity = $slotRows->filter(fn ($row) => ! $row['extra'])->count())
    @php($used = $programacion->citas->count())
    @php($additional = $programacion->citas->where('EsCitaAdicional', true)->count())

    <div class="hs-page-shell">
        <section class="overflow-hidden rounded-xl border border-[var(--border)] bg-[var(--background)]">
            <div class="grid lg:grid-cols-[320px_1fr]">
                <div class="bg-zinc-950 p-6 text-white dark:bg-white dark:text-zinc-950">
                    <div class="text-xs font-semibold uppercase tracking-wide opacity-70">Programacion</div>
                    <div class="mt-2 text-4xl font-semibold">#{{ $programacion->IdProgramacion }}</div>
                    <div class="mt-4 text-sm opacity-80">{{ $programacion->Fecha?->format('d/m/Y') }} / {{ trim((string) $programacion->HoraInicio) }} - {{ trim((string) ($programacion->HoraFinProgramacion ?: $programacion->HoraFin)) }}</div>
                </div>
                <div class="p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <flux:heading size="xl">{{ $programacion->servicio?->Nombre ?: 'Sin servicio' }}</flux:heading>
                            <flux:text class="mt-1">{{ $programacion->especialidad?->Nombre ?: 'Sin especialidad' }} / {{ $programacion->turno?->Descripcion ?: 'Sin turno' }}</flux:text>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-md bg-sky-50 px-2 py-1 font-semibold text-sky-700 ring-1 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800">{{ $used }} citas</span>
                                <span class="rounded-md bg-teal-50 px-2 py-1 font-semibold text-teal-700 ring-1 ring-teal-200 dark:bg-teal-950 dark:text-teal-100 dark:ring-teal-800">{{ $capacity }} cupos programados</span>
                                @if ($additional > 0)
                                    <span class="rounded-md bg-rose-50 px-2 py-1 font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800">{{ $additional }} adicionales</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <flux:button :href="$returnUrl ?: route('programacion-medica.index')" variant="ghost">Volver</flux:button>
                            <flux:button type="button" variant="primary" disabled>Editar programacion</flux:button>
                            <flux:button type="button" variant="ghost" disabled>Reprogramar pacientes</flux:button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-[var(--border)] bg-[var(--background)] p-5">
                <h2 class="text-sm font-semibold text-[var(--foreground)]">Medico</h2>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div><dt class="text-xs text-[var(--muted-foreground)]">Nombre</dt><dd class="font-semibold text-[var(--foreground)]">{{ $programacion->medico?->empleado?->nombre_completo ?: '-' }}</dd></div>
                    <div><dt class="text-xs text-[var(--muted-foreground)]">DNI</dt><dd>{{ $programacion->medico?->empleado?->DNI ?: '-' }}</dd></div>
                    <div><dt class="text-xs text-[var(--muted-foreground)]">Colegiatura</dt><dd>{{ trim((string) $programacion->medico?->Colegiatura) ?: '-' }}</dd></div>
                </dl>
            </div>
            <div class="rounded-xl border border-[var(--border)] bg-[var(--background)] p-5">
                <h2 class="text-sm font-semibold text-[var(--foreground)]">Horario</h2>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div><dt class="text-xs text-[var(--muted-foreground)]">Rango programado</dt><dd class="font-semibold text-[var(--foreground)]">{{ trim((string) $programacion->HoraInicio) }} - {{ trim((string) ($programacion->HoraFinProgramacion ?: $programacion->HoraFin)) }}</dd></div>
                    <div><dt class="text-xs text-[var(--muted-foreground)]">Tiempo promedio</dt><dd>{{ $average ?: '-' }} min</dd></div>
                    <div><dt class="text-xs text-[var(--muted-foreground)]">Fecha de registro</dt><dd>{{ $programacion->FechaReg?->format('d/m/Y H:i') ?: '-' }}</dd></div>
                </dl>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100">
                <h2 class="text-sm font-semibold">Mantenimiento controlado</h2>
                <p class="mt-2 text-sm">Para editar horarios o reprogramar pacientes se requiere validar reglas: citas ya atendidas, FUAS generadas, cambio de medico, disponibilidad del nuevo cupo y auditoria del cambio.</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-[var(--border)] bg-[var(--background)]">
            <div class="border-b border-[var(--border)] px-5 py-4">
                <h2 class="text-lg font-semibold text-[var(--foreground)]">Agenda y cupos</h2>
                <p class="text-sm text-[var(--muted-foreground)]">Los cupos se reconstruyen desde la programacion. Las citas adicionales aparecen al final como fuera de cupo programado.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--border)] text-sm">
                    <thead class="bg-[var(--muted)] text-left text-xs font-semibold uppercase text-[var(--muted-foreground)]">
                        <tr>
                            <th class="px-5 py-3">Cupo</th>
                            <th class="px-5 py-3">Hora</th>
                            <th class="px-5 py-3">Paciente</th>
                            <th class="px-5 py-3">Financiamiento</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3 text-right">Accion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        @foreach ($slotRows as $row)
                            @php($cita = $row['cita'])
                            <tr class="{{ $cita ? '' : 'bg-amber-50/50 dark:bg-amber-950/20' }}">
                                <td class="px-5 py-3 font-semibold">{{ $row['number'] }}</td>
                                <td class="whitespace-nowrap px-5 py-3">{{ $row['start'] }} - {{ $row['end'] }}</td>
                                <td class="px-5 py-3">
                                    @if ($cita)
                                        <div class="font-semibold text-[var(--foreground)]">{{ $cita->paciente?->nombre_completo ?: 'Paciente sin datos' }}</div>
                                        <div class="mt-1 text-xs text-[var(--muted-foreground)]">Historia {{ \App\Support\ClinicalHistoryNumber::format($cita->paciente?->NroHistoriaClinica) }} / Doc {{ $cita->paciente?->NroDocumento ?: '-' }}</div>
                                    @else
                                        <span class="text-amber-700 dark:text-amber-200">Cupo libre</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">{{ $cita ? \App\Support\SisFinancing::label($cita) : '-' }}</td>
                                <td class="px-5 py-3">
                                    @if ($row['extra'])
                                        <span class="rounded-md bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800">Fuera de cupo</span>
                                    @elseif ($cita)
                                        <span class="rounded-md bg-sky-50 px-2 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800">{{ $cita->estado?->Descripcion ?: 'Con cita' }}</span>
                                    @else
                                        <span class="rounded-md bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-800">Libre</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if ($cita)
                                        <flux:button :href="route('citas.show', $cita->IdCita)" size="sm" variant="ghost">Ver cita</flux:button>
                                    @else
                                        <span class="text-xs text-[var(--muted-foreground)]">Sin accion</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts::app>
