<x-layouts::app :title="'Simulacion de registro de cita'">
    <div class="hs-page-shell">
        <section class="hs-panel p-5 lg:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="text-sm font-semibold text-[var(--primary)]">Asignacion simulada</div>
                    <flux:heading size="xl" class="mt-1">Registrar cita desde programacion medica</flux:heading>
                    <flux:text class="mt-2 max-w-3xl">
                        Flujo preliminar para elegir fecha, turno, consultorio programado y cupo disponible antes de asignar la cita al paciente.
                    </flux:text>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:button :href="route('citas.registration-simulation', array_filter(['q' => $filters['q'], 'patient_id' => $filters['patient_id'], 'patient_name' => $filters['patient_name'], 'date' => $previousDate, 'turno' => $filters['turno']]))" variant="ghost">Dia anterior</flux:button>
                    <flux:button :href="route('citas.registration-simulation', array_filter(['q' => $filters['q'], 'patient_id' => $filters['patient_id'], 'patient_name' => $filters['patient_name'], 'date' => today()->toDateString(), 'turno' => $filters['turno']]))" variant="ghost">Hoy</flux:button>
                    <flux:button :href="route('citas.registration-simulation', array_filter(['q' => $filters['q'], 'patient_id' => $filters['patient_id'], 'patient_name' => $filters['patient_name'], 'date' => $nextDate, 'turno' => $filters['turno']]))" variant="primary">Dia siguiente</flux:button>
                </div>
            </div>
        </section>

        @if ($error)
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-100">
                {{ $error }}
            </div>
        @endif

        <section class="grid gap-4 xl:grid-cols-[330px_minmax(0,1fr)]">
            <aside class="grid content-start gap-4">
                <div class="rounded-xl border border-[var(--border)] bg-[var(--background)] p-5">
                    <h2 class="text-base font-semibold text-[var(--foreground)]">1. Paciente</h2>
                    <form method="GET" action="{{ route('citas.registration-simulation') }}" class="mt-4 grid gap-3">
                        <input type="hidden" name="date" value="{{ $filters['date'] }}">
                        <input type="hidden" name="turno" value="{{ $filters['turno'] }}">
                        <input type="hidden" name="programacion_id" value="{{ $filters['programacion_id'] }}">
                        <flux:input name="q" label="DNI, N° Historia Clinica o nombres" value="{{ $filters['q'] }}" placeholder="Buscar paciente" />
                        <flux:button type="submit" variant="primary">Buscar</flux:button>
                    </form>

                    @if ($filters['patient_id'])
                        <div class="mt-4 rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950/30 dark:text-sky-100">
                            <div class="text-xs font-semibold uppercase opacity-70">Seleccionado</div>
                            <div class="mt-1 font-semibold">{{ $filters['patient_name'] ?: 'Paciente '.$filters['patient_id'] }}</div>
                        </div>
                    @endif

                    @if ($patients->isNotEmpty())
                        <div class="mt-5 grid gap-2">
                            @foreach ($patients as $patient)
                                <a href="{{ route('citas.registration-simulation', ['q' => $filters['q'], 'patient_id' => $patient->IdPaciente, 'patient_name' => $patient->Paciente, 'date' => $filters['date'], 'turno' => $filters['turno'], 'programacion_id' => $filters['programacion_id']]) }}" class="rounded-xl border border-[var(--border)] p-3 transition hover:border-[var(--primary)] hover:bg-[var(--muted)]">
                                    <div class="font-semibold text-[var(--foreground)]">{{ $patient->Paciente }}</div>
                                    <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $patient->TipoDocumento ?: 'Documento' }} {{ $patient->NroDocumento ?: '-' }}</div>
                                    <div class="mt-2 inline-flex rounded-lg bg-zinc-950 px-3 py-1 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ $patient->NroHistoriaClinica ?: '-' }}</div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="rounded-xl border border-[var(--border)] bg-[var(--background)] p-5">
                    <h2 class="text-base font-semibold text-[var(--foreground)]">2. Fecha y turno</h2>
                    <form method="GET" action="{{ route('citas.registration-simulation') }}" class="mt-4 grid gap-3">
                        <input type="hidden" name="q" value="{{ $filters['q'] }}">
                        <input type="hidden" name="patient_id" value="{{ $filters['patient_id'] }}">
                        <input type="hidden" name="patient_name" value="{{ $filters['patient_name'] }}">
                        <flux:input name="date" label="Fecha" type="date" value="{{ $filters['date'] }}" />
                        <label class="grid gap-2 text-sm">
                            <span class="text-[var(--muted-foreground-2)]">Turno</span>
                            <select name="turno" class="h-10 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 text-sm">
                                <option value="todos" @selected($filters['turno'] === 'todos')>Todos</option>
                                <option value="manana" @selected($filters['turno'] === 'manana')>Manana</option>
                                <option value="tarde" @selected($filters['turno'] === 'tarde')>Tarde</option>
                                <option value="fuera" @selected($filters['turno'] === 'fuera')>Fuera de turno</option>
                            </select>
                        </label>
                        <flux:button type="submit" variant="primary">Ver programacion</flux:button>
                    </form>
                </div>
            </aside>

            <div class="grid gap-4">
                <div class="grid gap-4 lg:grid-cols-[360px_minmax(0,1fr)]">
                    <div class="overflow-hidden rounded-xl border border-[var(--border)] bg-[var(--background)]">
                        <div class="border-b border-[var(--border)] px-5 py-4">
                            <h2 class="text-lg font-semibold text-[var(--foreground)]">Consultorios programados</h2>
                            <p class="text-sm text-[var(--muted-foreground)]">{{ \Illuminate\Support\Carbon::parse($filters['date'])->format('d/m/Y') }} / {{ $filters['turno'] === 'todos' ? 'Todos los turnos' : ucfirst($filters['turno']) }}</p>
                        </div>
                        <div class="max-h-[680px] overflow-y-auto p-2">
                            @forelse ($schedules as $schedule)
                                @php
                                    $active = (int) $filters['programacion_id'] === (int) $schedule->IdProgramacion;
                                @endphp
                                <a href="{{ route('citas.registration-simulation', ['q' => $filters['q'], 'patient_id' => $filters['patient_id'], 'patient_name' => $filters['patient_name'], 'date' => $filters['date'], 'turno' => $filters['turno'], 'programacion_id' => $schedule->IdProgramacion]) }}" class="mb-2 block rounded-xl border p-3 transition hover:-translate-y-0.5 hover:shadow-sm {{ $active ? 'border-teal-500 bg-teal-50 ring-2 ring-teal-200 dark:border-teal-400 dark:bg-teal-950/40 dark:ring-teal-800' : 'border-[var(--border)] bg-[var(--background)] hover:bg-[var(--muted)]' }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-[var(--foreground)]">{{ $schedule->Servicio }}</div>
                                            <div class="truncate text-xs text-[var(--muted-foreground)]">{{ $schedule->Especialidad }}</div>
                                        </div>
                                        <span class="rounded-md bg-white/80 px-2 py-1 text-xs font-semibold text-zinc-800 ring-1 ring-black/5 dark:bg-zinc-900 dark:text-zinc-100">{{ $schedule->Disponibles }}/{{ $schedule->Capacidad }}</span>
                                    </div>
                                    <div class="mt-2 text-xs text-[var(--muted-foreground)]">{{ trim((string) $schedule->HoraInicio) }} - {{ trim((string) ($schedule->HoraFinProgramacion ?: $schedule->HoraFin)) }}</div>
                                    <div class="mt-1 truncate text-xs font-medium text-[var(--foreground)]">{{ $schedule->Medico ?: 'Sin medico' }}</div>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <span class="rounded bg-sky-100 px-1.5 py-0.5 text-[11px] font-semibold text-sky-800 ring-1 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800">{{ $schedule->TurnoOperativo }}</span>
                                        @if ((int) $schedule->Adicionales > 0)
                                            <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[11px] font-semibold text-rose-800 ring-1 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800">{{ $schedule->Adicionales }} adicionales</span>
                                        @endif
                                    </div>
                                </a>
                            @empty
                                <div class="px-4 py-10 text-center text-sm text-[var(--muted-foreground)]">No hay consultorios operativos programados para la fecha y turno.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-[var(--border)] bg-[var(--background)]">
                        <div class="border-b border-[var(--border)] px-5 py-4">
                            <h2 class="text-lg font-semibold text-[var(--foreground)]">Cupos del consultorio</h2>
                            @if ($selectedSchedule)
                                <p class="text-sm text-[var(--muted-foreground)]">{{ $selectedSchedule->Servicio }} / {{ trim((string) $selectedSchedule->HoraInicio) }} - {{ trim((string) ($selectedSchedule->HoraFinProgramacion ?: $selectedSchedule->HoraFin)) }}</p>
                            @else
                                <p class="text-sm text-[var(--muted-foreground)]">Selecciona un consultorio programado.</p>
                            @endif
                        </div>

                        @if ($selectedSchedule)
                            <div class="grid grid-cols-1 gap-2 p-4 md:grid-cols-2 xl:grid-cols-3">
                                @forelse ($slots as $slot)
                                    @php
                                        $cita = $slot['cita'];
                                    @endphp
                                    <div class="rounded-xl border p-3 {{ $slot['available'] ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30' : ($slot['extra'] ? 'border-rose-200 bg-rose-50 dark:border-rose-800 dark:bg-rose-950/30' : 'border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950') }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="font-semibold text-[var(--foreground)]">{{ $slot['start'] }} - {{ $slot['end'] }}</div>
                                            <span class="rounded-md bg-white/70 px-2 py-0.5 text-[11px] font-semibold dark:bg-black/20">{{ $slot['available'] ? 'Libre' : ($slot['extra'] ? 'Adicional' : 'Ocupado') }}</span>
                                        </div>
                                        @if ($cita)
                                            <div class="mt-2 text-xs text-[var(--muted-foreground)]">{{ $cita->Paciente }}</div>
                                            <div class="mt-1 text-xs font-semibold text-[var(--foreground)]">HC {{ $cita->NroHistoriaClinica ?: '-' }}</div>
                                        @else
                                            <form method="GET" action="{{ route('citas.registration-simulation') }}" class="mt-3">
                                                <input type="hidden" name="q" value="{{ $filters['q'] }}">
                                                <input type="hidden" name="patient_id" value="{{ $filters['patient_id'] }}">
                                                <input type="hidden" name="patient_name" value="{{ $filters['patient_name'] }}">
                                                <input type="hidden" name="date" value="{{ $filters['date'] }}">
                                                <input type="hidden" name="turno" value="{{ $filters['turno'] }}">
                                                <input type="hidden" name="programacion_id" value="{{ $filters['programacion_id'] }}">
                                                <button type="submit" class="w-full rounded-lg bg-teal-600 px-3 py-2 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40" {{ $filters['patient_id'] ? '' : 'disabled' }}>
                                                    Validar este cupo
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @empty
                                    <div class="col-span-full px-4 py-10 text-center text-sm text-[var(--muted-foreground)]">No se pudo calcular cupos para esta programacion.</div>
                                @endforelse
                            </div>
                        @else
                            <div class="px-5 py-12 text-center text-sm text-[var(--muted-foreground)]">No hay programación seleccionada.</div>
                        @endif
                    </div>
                </div>

                @if ($result)
                    @php
                        if ($result['status'] === 'blocked') {
                            $title = 'Cita bloqueada';
                            $resultClass = 'border-rose-200 bg-rose-50 text-rose-950 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-100';
                        } elseif ($result['status'] === 'warning') {
                            $title = 'Permitido con advertencia';
                            $resultClass = 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100';
                        } else {
                            $title = 'Cita permitida';
                            $resultClass = 'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100';
                        }
                    @endphp
                    <div class="rounded-xl border p-5 {{ $resultClass }}">
                        <div class="text-sm font-semibold uppercase">{{ $title }}</div>
                        <div class="mt-1 text-lg font-semibold">{{ $result['message'] }}</div>
                        @if ($selectedSchedule)
                            <div class="mt-2 text-sm">Destino: <span class="font-semibold">{{ $selectedSchedule->Servicio }}</span> con {{ $selectedSchedule->Medico ?: 'medico programado' }}.</div>
                        @endif
                    </div>
                @endif
            </div>
        </section>
    </div>
</x-layouts::app>
