<x-layouts::app :title="__('Bandeja SIS-Judicial')">
    <div class="hs-page-shell">
        <section class="hs-panel p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="text-sm font-semibold text-fuchsia-700 dark:text-fuchsia-200">Seguimiento judicial</div>
                    <flux:heading size="xl" class="mt-2">Bandeja SIS-Judicial</flux:heading>
                    <flux:text class="mt-2">Citas de pacientes SIS por orden judicial para que el medico marque el resultado de la atencion.</flux:text>
                </div>

                <form method="GET" action="{{ route('judicial-cases.index') }}" class="grid w-full gap-3 lg:w-[760px] lg:grid-cols-12 lg:items-end">
                    <div class="lg:col-span-2">
                        <flux:input name="fecha" label="Fecha" type="date" value="{{ $filters['fecha'] }}" />
                    </div>
                    <label class="grid gap-2 text-sm lg:col-span-2">
                        <span class="text-zinc-700 dark:text-zinc-300">Estado</span>
                        <select name="status" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="todos" @selected($filters['status'] === 'todos')>Todos</option>
                            <option value="scheduled" @selected($filters['status'] === 'scheduled')>Pendiente</option>
                            <option value="attended" @selected($filters['status'] === 'attended')>Atendida</option>
                            <option value="missed" @selected($filters['status'] === 'missed')>Falto</option>
                            <option value="reprogrammed" @selected($filters['status'] === 'reprogrammed')>Reprogramada</option>
                        </select>
                    </label>
                    <label class="grid gap-2 text-sm lg:col-span-2">
                        <span class="text-zinc-700 dark:text-zinc-300">Servicio</span>
                        <select name="service" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="">Todos</option>
                            @foreach ($services as $service)
                                <option value="{{ $service }}" @selected($filters['service'] === $service)>{{ $service }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-2 text-sm lg:col-span-2">
                        <span class="text-zinc-700 dark:text-zinc-300">Medico</span>
                        <select name="doctor" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="">Todos</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor }}" @selected($filters['doctor'] === $doctor)>{{ $doctor }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="lg:col-span-3">
                        <flux:input name="q" label="Buscar" value="{{ $filters['q'] }}" placeholder="HC, DNI, paciente, expediente" />
                    </div>
                    <div class="flex gap-2 lg:col-span-1">
                        <flux:button type="submit" variant="primary">Filtrar</flux:button>
                    </div>
                </form>
            </div>
        </section>

        @include('pages.security.partials.flash')

        <section class="grid gap-4 md:grid-cols-4">
            <article class="hs-kpi border-amber-200 bg-amber-50/70 dark:border-amber-900 dark:bg-amber-950/30">
                <div class="text-xs font-semibold uppercase text-amber-700 dark:text-amber-200">Pendientes</div>
                <div class="mt-2 text-3xl font-semibold text-amber-950 dark:text-amber-100">{{ $summary['scheduled'] }}</div>
                <div class="mt-1 text-sm text-amber-800/80 dark:text-amber-100/80">Por marcar</div>
            </article>
            <article class="hs-kpi border-emerald-200 bg-emerald-50/70 dark:border-emerald-900 dark:bg-emerald-950/30">
                <div class="text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-200">Atendidas</div>
                <div class="mt-2 text-3xl font-semibold text-emerald-950 dark:text-emerald-100">{{ $summary['attended'] }}</div>
                <div class="mt-1 text-sm text-emerald-800/80 dark:text-emerald-100/80">Suman al avance</div>
            </article>
            <article class="hs-kpi border-rose-200 bg-rose-50/70 dark:border-rose-900 dark:bg-rose-950/30">
                <div class="text-xs font-semibold uppercase text-rose-700 dark:text-rose-200">Faltaron</div>
                <div class="mt-2 text-3xl font-semibold text-rose-950 dark:text-rose-100">{{ $summary['missed'] }}</div>
                <div class="mt-1 text-sm text-rose-800/80 dark:text-rose-100/80">No cumplidas</div>
            </article>
            <article class="hs-kpi border-sky-200 bg-sky-50/70 dark:border-sky-900 dark:bg-sky-950/30">
                <div class="text-xs font-semibold uppercase text-sky-700 dark:text-sky-200">Reprogramadas</div>
                <div class="mt-2 text-3xl font-semibold text-sky-950 dark:text-sky-100">{{ $summary['reprogrammed'] }}</div>
                <div class="mt-1 text-sm text-sky-800/80 dark:text-sky-100/80">Requieren seguimiento</div>
            </article>
        </section>

        <section class="hs-panel overflow-hidden">
            <div class="border-b border-[var(--border)] p-5">
                <h2 class="text-lg font-semibold text-[var(--foreground)]">Citas judiciales del dia</h2>
                <p class="mt-1 text-sm text-[var(--muted-foreground)]">El medico marca el resultado. Los datos de cita vienen de SIGH como referencia; el estado judicial se guarda en la BD del aplicativo.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">Hora</th>
                            <th class="px-4 py-3">N° Historia Clinica</th>
                            <th class="px-4 py-3">Paciente</th>
                            <th class="px-4 py-3">Servicio / medico</th>
                            <th class="px-4 py-3">Caso judicial</th>
                            <th class="px-4 py-3">Avance</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-right">Marcar resultado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($appointments as $appointment)
                            @php($case = $appointment->case)
                            @php($attended = $case?->attendedAppointments() ?? 0)
                            @php($required = max(0, (int) ($case?->required_appointments ?? 0)))
                            @php($statusClass = match ($appointment->status) {
                                'attended' => 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-800',
                                'missed' => 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800',
                                'reprogrammed' => 'bg-sky-50 text-sky-800 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800',
                                default => 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-800',
                            })
                            <tr class="align-top hover:bg-zinc-50/70 dark:hover:bg-zinc-900/60">
                                <td class="whitespace-nowrap px-4 py-4 font-semibold text-[var(--foreground)]">{{ $appointment->appointment_time?->format('H:i') ?: '-' }}</td>
                                <td class="px-4 py-4">
                                    <div class="inline-flex min-w-28 flex-col rounded-lg bg-zinc-950 px-3 py-2 text-white shadow-sm dark:bg-white dark:text-zinc-950">
                                        <span class="text-[10px] font-semibold uppercase tracking-wide opacity-70">Historia</span>
                                        <span class="text-lg font-semibold leading-none">{{ \App\Support\ClinicalHistoryNumber::format($case?->history_number) }}</span>
                                    </div>
                                </td>
                                <td class="min-w-[260px] px-4 py-4">
                                    <div class="font-semibold text-[var(--foreground)]">{{ $case?->patient_name ?: '-' }}</div>
                                    <div class="mt-1 text-xs text-[var(--muted-foreground)]">Doc: {{ $case?->document_number ?: '-' }}</div>
                                </td>
                                <td class="min-w-[260px] px-4 py-4">
                                    <div class="font-medium text-[var(--foreground)]">{{ $appointment->service ?: $appointment->specialty ?: '-' }}</div>
                                    <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $appointment->doctor_name ?: 'Medico no registrado' }}</div>
                                </td>
                                <td class="min-w-[240px] px-4 py-4">
                                    <div class="font-medium text-[var(--foreground)]">{{ $case?->case_file_number ?: 'Sin expediente' }}</div>
                                    <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $case?->court_name ?: 'Juzgado no registrado' }}</div>
                                    <span class="mt-2 inline-flex rounded-md bg-fuchsia-50 px-2 py-1 text-xs font-semibold text-fuchsia-800 ring-1 ring-fuchsia-200 dark:bg-fuchsia-950 dark:text-fuchsia-100 dark:ring-fuchsia-800">SIS-Judicial</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-[var(--foreground)]">{{ $attended }} / {{ $required }}</div>
                                    <div class="mt-2 h-2 w-24 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <div class="h-full rounded-full bg-fuchsia-600" style="width: {{ $case?->progressPercentage() ?? 0 }}%"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="rounded-md px-2 py-1 text-xs font-semibold ring-1 {{ $statusClass }}">
                                        {{ match ($appointment->status) {
                                            'attended' => 'Atendida',
                                            'missed' => 'Falto',
                                            'reprogrammed' => 'Reprogramada',
                                            default => 'Pendiente',
                                        } }}
                                    </span>
                                    @if ($appointment->notes)
                                        <div class="mt-2 max-w-52 text-xs text-[var(--muted-foreground)]">{{ $appointment->notes }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <form method="POST" action="{{ route('judicial-cases.appointments.update', $appointment) }}" class="flex min-w-[320px] flex-col gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <div class="grid grid-cols-[1fr_auto] gap-2">
                                            <select name="status" class="h-9 rounded-md border border-zinc-200 bg-white px-2 text-xs dark:border-zinc-700 dark:bg-zinc-900">
                                                <option value="attended" @selected($appointment->status === 'attended')>Atendida</option>
                                                <option value="missed" @selected($appointment->status === 'missed')>Falto</option>
                                                <option value="reprogrammed" @selected($appointment->status === 'reprogrammed')>Reprogramada</option>
                                                <option value="scheduled" @selected($appointment->status === 'scheduled')>Pendiente</option>
                                            </select>
                                            <button type="submit" class="rounded-md bg-[var(--primary)] px-3 py-2 text-xs font-semibold text-[var(--primary-foreground)] hover:opacity-90">Guardar</button>
                                        </div>
                                        <input name="notes" value="{{ $appointment->notes }}" class="h-9 rounded-md border border-zinc-200 bg-white px-2 text-xs dark:border-zinc-700 dark:bg-zinc-900" placeholder="Observacion opcional">
                                        <div class="text-right">
                                            <a href="{{ route('citas.show', $appointment->sigh_cita_id) }}" class="text-xs font-semibold text-[var(--primary)] hover:underline">Ver cita</a>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-[var(--muted-foreground)]">
                                    No hay citas SIS-Judicial para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-[var(--border)] px-5 py-4">{{ $appointments->links() }}</div>
        </section>
    </div>
</x-layouts::app>
