<x-layouts::app :title="'Paciente '.$patient->NroHistoriaClinica">
    @php($birth = $patient->FechaNacimiento ? \Carbon\Carbon::parse($patient->FechaNacimiento) : null)
    @php($history = \App\Support\ClinicalHistoryNumber::format($patient->NroHistoriaClinica))

    <div class="hs-page-shell">
        <section class="hs-panel overflow-hidden">
            <div class="border-b border-[var(--border)] bg-[var(--muted)] px-6 py-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="inline-flex w-fit flex-col rounded-2xl bg-zinc-950 px-5 py-4 text-white shadow-sm dark:bg-white dark:text-zinc-950">
                            <span class="text-[10px] font-semibold uppercase tracking-wide opacity-70">N° Historia Clinica</span>
                            <span class="text-3xl font-semibold leading-none">{{ $history }}</span>
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-teal-700 dark:text-teal-200">Ficha de paciente</div>
                            <h1 class="mt-1 truncate text-2xl font-semibold text-[var(--foreground)]">{{ trim($patient->Paciente) ?: 'Paciente sin nombre' }}</h1>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-[var(--muted-foreground)]">
                                <span>{{ $patient->TipoDocumento ?: 'Documento' }}: {{ $patient->NroDocumento ?: '-' }}</span>
                                <span class="size-1 rounded-full bg-[var(--border-line-4)]"></span>
                                <span>{{ $birth ? $birth->format('d/m/Y').' / '.$birth->age.' anos' : 'Nacimiento no registrado' }}</span>
                                @if ($patient->Telefono)
                                    <span class="size-1 rounded-full bg-[var(--border-line-4)]"></span>
                                    <span>Tel: {{ $patient->Telefono }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <flux:button :href="route('patients.index', ['historia' => $patient->NroHistoriaClinica])" variant="ghost" wire:navigate>Volver a busqueda</flux:button>
                        <flux:button :href="route('citas.index', ['q' => $patient->NroHistoriaClinica])" variant="primary" wire:navigate>Ver en citas</flux:button>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 px-6 py-5 lg:grid-cols-3">
                <div class="rounded-xl border border-[var(--border)] bg-[var(--background)] p-4">
                    <div class="text-xs font-semibold uppercase text-[var(--muted-foreground)]">Datos personales</div>
                    <dl class="mt-3 grid gap-3 text-sm">
                        <div><dt class="text-xs text-[var(--muted-foreground)]">Apellido paterno</dt><dd class="font-medium text-[var(--foreground)]">{{ $patient->ApellidoPaterno ?: '-' }}</dd></div>
                        <div><dt class="text-xs text-[var(--muted-foreground)]">Apellido materno</dt><dd class="font-medium text-[var(--foreground)]">{{ $patient->ApellidoMaterno ?: '-' }}</dd></div>
                        <div><dt class="text-xs text-[var(--muted-foreground)]">Nombres</dt><dd class="font-medium text-[var(--foreground)]">{{ trim($patient->PrimerNombre.' '.$patient->SegundoNombre.' '.$patient->TercerNombre) ?: '-' }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-xl border border-[var(--border)] bg-[var(--background)] p-4">
                    <div class="text-xs font-semibold uppercase text-[var(--muted-foreground)]">Contacto y domicilio</div>
                    <dl class="mt-3 grid gap-3 text-sm">
                        <div><dt class="text-xs text-[var(--muted-foreground)]">Telefono</dt><dd class="font-medium text-[var(--foreground)]">{{ $patient->Telefono ?: '-' }}</dd></div>
                        <div><dt class="text-xs text-[var(--muted-foreground)]">Email</dt><dd class="font-medium text-[var(--foreground)]">{{ $patient->Email ?: '-' }}</dd></div>
                        <div><dt class="text-xs text-[var(--muted-foreground)]">Direccion</dt><dd class="font-medium text-[var(--foreground)]">{{ $patient->DireccionDomicilio ?: '-' }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-xl border border-violet-200 bg-violet-50/70 p-4 dark:border-violet-900 dark:bg-violet-950/30">
                    <div class="text-xs font-semibold uppercase text-violet-700 dark:text-violet-200">Observacion operativa</div>
                    <div class="mt-3 min-h-20 text-sm font-medium leading-6 text-violet-950 dark:text-violet-50">
                        {{ $patient->Observacion ?: 'Sin observacion registrada.' }}
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <article class="hs-panel overflow-hidden">
                <div class="flex items-center justify-between border-b border-[var(--border)] px-5 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-[var(--foreground)]">Historial de citas</h2>
                        <p class="mt-1 text-sm text-[var(--muted-foreground)]">Ultimas programaciones encontradas para el paciente.</p>
                    </div>
                    <span class="rounded-md bg-[var(--muted)] px-2 py-1 text-xs font-semibold text-[var(--foreground)]">{{ count($appointments) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Servicio</th>
                                <th class="px-4 py-3">Financiamiento</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3 text-right">Accion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($appointments as $appointment)
                                <tr class="align-top hover:bg-zinc-50/70 dark:hover:bg-zinc-900/60">
                                    <td class="whitespace-nowrap px-4 py-4 font-semibold text-[var(--foreground)]">
                                        {{ $appointment->Fecha ? \Carbon\Carbon::parse($appointment->Fecha)->format('d/m/Y') : '-' }}
                                        <div class="text-xs font-normal text-[var(--muted-foreground)]">{{ substr((string) $appointment->HoraInicio, 0, 5) }} - {{ substr((string) $appointment->HoraFin, 0, 5) }}</div>
                                        @if ($appointment->EsCitaAdicional)
                                            <span class="mt-1 inline-flex rounded-md bg-rose-50 px-2 py-1 text-[11px] font-semibold text-rose-800 ring-1 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800">Adicional</span>
                                        @endif
                                    </td>
                                    <td class="min-w-[220px] px-4 py-4">
                                        <div class="font-medium text-[var(--foreground)]">{{ $appointment->Servicio ?: '-' }}</div>
                                        <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $appointment->Especialidad ?: '-' }}</div>
                                    </td>
                                    <td class="min-w-[170px] px-4 py-4">
                                        <div class="font-medium text-[var(--foreground)]">{{ $appointment->FormaPago ?: '-' }}</div>
                                        <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $appointment->FuenteFinanciamiento ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-[var(--muted-foreground)]">{{ $appointment->EstadoCita ?: '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right">
                                        @if ($appointment->IdCita)
                                            <flux:button :href="route('citas.show', $appointment->IdCita)" size="sm" variant="ghost" wire:navigate>Ver cita</flux:button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-[var(--muted-foreground)]">No se encontraron citas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="hs-panel overflow-hidden">
                <div class="flex items-center justify-between border-b border-[var(--border)] px-5 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-[var(--foreground)]">Historial de atenciones</h2>
                        <p class="mt-1 text-sm text-[var(--muted-foreground)]">Ultimos ingresos asistenciales registrados en SIGH.</p>
                    </div>
                    <span class="rounded-md bg-[var(--muted)] px-2 py-1 text-xs font-semibold text-[var(--foreground)]">{{ count($attentions) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3">Ingreso</th>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3">Servicio</th>
                                <th class="px-4 py-3">Financiamiento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($attentions as $attention)
                                <tr class="align-top hover:bg-zinc-50/70 dark:hover:bg-zinc-900/60">
                                    <td class="whitespace-nowrap px-4 py-4 font-semibold text-[var(--foreground)]">
                                        {{ $attention->FechaIngreso ? \Carbon\Carbon::parse($attention->FechaIngreso)->format('d/m/Y') : '-' }}
                                        <div class="text-xs font-normal text-[var(--muted-foreground)]">{{ substr((string) $attention->HoraIngreso, 0, 5) }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-[var(--muted-foreground)]">{{ $attention->TipoServicio ?: '-' }}</td>
                                    <td class="min-w-[220px] px-4 py-4 font-medium text-[var(--foreground)]">{{ $attention->Servicio ?: '-' }}</td>
                                    <td class="min-w-[180px] px-4 py-4">
                                        <div class="font-medium text-[var(--foreground)]">{{ $attention->FormaPago ?: '-' }}</div>
                                        <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $attention->FuenteFinanciamiento ?: '-' }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-[var(--muted-foreground)]">No se encontraron atenciones.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </div>
</x-layouts::app>
