<x-layouts::app :title="__('Busqueda de pacientes')">
    <div class="hs-page-shell">
        <section class="hs-panel p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <div class="text-sm font-semibold text-teal-700 dark:text-teal-200">Consulta global SIGH</div>
                    <flux:heading size="xl" class="mt-2">Busqueda de pacientes</flux:heading>
                    <flux:text class="mt-2 max-w-3xl">
                        Ubica coincidencias por documento, numero de historia clinica, apellidos o nombres. La busqueda es solo lectura y permite revisar ultimas citas, atenciones y financiamiento relacionado.
                    </flux:text>
                </div>

                <form method="GET" action="{{ route('patients.index') }}" class="grid w-full gap-3 xl:w-[900px] xl:grid-cols-12 xl:items-end">
                    <div class="xl:col-span-4">
                        <flux:input name="q" label="Busqueda global" value="{{ $filters['q'] }}" placeholder="DNI, historia, apellidos o nombres" autofocus />
                    </div>
                    <div class="xl:col-span-2">
                        <flux:input name="documento" label="Documento" value="{{ $filters['documento'] }}" />
                    </div>
                    <div class="xl:col-span-2">
                        <flux:input name="historia" label="N° Historia Clinica" value="{{ $filters['historia'] }}" />
                    </div>
                    <div class="flex gap-2 xl:col-span-4">
                        <flux:button type="submit" variant="primary">Buscar</flux:button>
                        <flux:button :href="route('patients.index')" variant="ghost" wire:navigate>Limpiar</flux:button>
                    </div>

                    <div class="xl:col-span-4">
                        <flux:input name="apellido_paterno" label="Apellido paterno" value="{{ $filters['apellido_paterno'] }}" />
                    </div>
                    <div class="xl:col-span-4">
                        <flux:input name="apellido_materno" label="Apellido materno" value="{{ $filters['apellido_materno'] }}" />
                    </div>
                    <div class="xl:col-span-4">
                        <flux:input name="nombre" label="Nombres" value="{{ $filters['nombre'] }}" />
                    </div>
                </form>
            </div>
        </section>

        @if ($error)
            <section class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-100">
                {{ $error }}
            </section>
        @endif

        <section class="grid gap-4 md:grid-cols-3">
            <article class="hs-kpi">
                <div class="text-xs font-semibold uppercase text-[var(--muted-foreground)]">Coincidencias</div>
                <div class="mt-2 text-3xl font-semibold text-[var(--foreground)]">{{ number_format($summary['total']) }}</div>
                <div class="mt-1 text-sm text-[var(--muted-foreground)]">{{ $hasSearch ? 'Primeros 100 resultados' : 'Ingresa un criterio de busqueda' }}</div>
            </article>
            <article class="hs-kpi border-sky-200 bg-sky-50/70 dark:border-sky-900 dark:bg-sky-950/30">
                <div class="text-xs font-semibold uppercase text-sky-700 dark:text-sky-200">Con atencion previa</div>
                <div class="mt-2 text-3xl font-semibold text-sky-950 dark:text-sky-100">{{ number_format($summary['with_recent_attention']) }}</div>
                <div class="mt-1 text-sm text-sky-800/80 dark:text-sky-100/80">Pacientes con movimiento asistencial</div>
            </article>
            <article class="hs-kpi border-violet-200 bg-violet-50/70 dark:border-violet-900 dark:bg-violet-950/30">
                <div class="text-xs font-semibold uppercase text-violet-700 dark:text-violet-200">Con cita registrada</div>
                <div class="mt-2 text-3xl font-semibold text-violet-950 dark:text-violet-100">{{ number_format($summary['with_recent_appointment']) }}</div>
                <div class="mt-1 text-sm text-violet-800/80 dark:text-violet-100/80">Pacientes con programacion encontrada</div>
            </article>
        </section>

        <section class="hs-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-[var(--border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-[var(--foreground)]">Lista de pacientes</h2>
                    <p class="mt-1 text-sm text-[var(--muted-foreground)]">Se muestran coincidencias ordenadas por ultimo movimiento y datos personales.</p>
                </div>
                <span class="rounded-md bg-[var(--muted)] px-2 py-1 text-xs font-semibold text-[var(--foreground)]">{{ number_format($patients->count()) }} visibles</span>
            </div>

            @if (! $hasSearch)
                <div class="px-5 py-12 text-center">
                    <div class="mx-auto grid size-12 place-items-center rounded-full bg-teal-50 text-teal-700 ring-1 ring-teal-200 dark:bg-teal-950 dark:text-teal-100 dark:ring-teal-800">
                        <flux:icon icon="magnifying-glass" class="size-6" />
                    </div>
                    <div class="mt-4 text-sm font-semibold text-[var(--foreground)]">Empieza con un criterio de busqueda</div>
                    <p class="mt-1 text-sm text-[var(--muted-foreground)]">Puedes buscar por DNI, historia clinica, apellidos, nombres o combinaciones.</p>
                </div>
            @elseif ($patients->isEmpty() && ! $error)
                <div class="px-5 py-12 text-center">
                    <div class="mx-auto grid size-12 place-items-center rounded-full bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-800">
                        <flux:icon icon="exclamation-triangle" class="size-6" />
                    </div>
                    <div class="mt-4 text-sm font-semibold text-[var(--foreground)]">No se encontraron coincidencias</div>
                    <p class="mt-1 text-sm text-[var(--muted-foreground)]">Revisa el documento, la historia clinica o busca por apellidos separados.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3">N° Historia Clinica</th>
                                <th class="px-4 py-3">Paciente</th>
                                <th class="px-4 py-3">Documento</th>
                                <th class="px-4 py-3">Nacimiento</th>
                                <th class="px-4 py-3">Ultima atencion</th>
                                <th class="px-4 py-3">Ultima cita</th>
                                <th class="px-4 py-3">Movimientos</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($patients as $patient)
                                @php($birth = $patient->FechaNacimiento ? \Carbon\Carbon::parse($patient->FechaNacimiento) : null)
                                <tr class="align-top hover:bg-zinc-50/70 dark:hover:bg-zinc-900/60">
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <div class="inline-flex flex-col rounded-xl bg-zinc-950 px-3 py-2 text-white shadow-sm dark:bg-white dark:text-zinc-950">
                                            <span class="text-[10px] font-semibold uppercase opacity-70">Historia</span>
                                            <span class="text-lg font-semibold leading-none">{{ \App\Support\ClinicalHistoryNumber::format($patient->NroHistoriaClinica) }}</span>
                                        </div>
                                    </td>
                                    <td class="min-w-[260px] px-4 py-4">
                                        <div class="font-semibold text-[var(--foreground)]">{{ trim($patient->Paciente) ?: 'Sin nombre registrado' }}</div>
                                        <div class="mt-1 flex flex-wrap gap-2 text-xs text-[var(--muted-foreground)]">
                                            @if ($patient->Telefono)
                                                <span>Tel: {{ $patient->Telefono }}</span>
                                            @endif
                                            @if ($patient->Observacion)
                                                <span class="rounded-md bg-violet-50 px-2 py-1 font-medium text-violet-800 ring-1 ring-violet-200 dark:bg-violet-950 dark:text-violet-100 dark:ring-violet-800">Con observacion</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <div class="font-medium text-[var(--foreground)]">{{ $patient->TipoDocumento ?: 'Documento' }}</div>
                                        <div class="text-[var(--muted-foreground)]">{{ $patient->NroDocumento ?: '-' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <div class="font-medium text-[var(--foreground)]">{{ $birth?->format('d/m/Y') ?: '-' }}</div>
                                        <div class="text-xs text-[var(--muted-foreground)]">{{ $birth ? $birth->age.' anos' : 'Edad no registrada' }}</div>
                                    </td>
                                    <td class="min-w-[240px] px-4 py-4">
                                        @if ($patient->UltimaAtencionFecha)
                                            <div class="font-semibold text-[var(--foreground)]">{{ \Carbon\Carbon::parse($patient->UltimaAtencionFecha)->format('d/m/Y') }} {{ substr((string) $patient->UltimaAtencionHora, 0, 5) }}</div>
                                            <div class="mt-1 text-[var(--muted-foreground)]">{{ $patient->UltimoServicioIngreso ?: $patient->UltimoTipoServicio ?: '-' }}</div>
                                            <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $patient->UltimaFormaPago ?: '-' }} / {{ $patient->UltimaFuenteFinanciamiento ?: '-' }}</div>
                                        @else
                                            <span class="text-[var(--muted-foreground)]">Sin atenciones</span>
                                        @endif
                                    </td>
                                    <td class="min-w-[220px] px-4 py-4">
                                        @if ($patient->UltimaCitaFecha)
                                            <div class="font-semibold text-[var(--foreground)]">{{ \Carbon\Carbon::parse($patient->UltimaCitaFecha)->format('d/m/Y') }} {{ substr((string) $patient->UltimaCitaHora, 0, 5) }}</div>
                                            <div class="mt-1 text-[var(--muted-foreground)]">{{ $patient->UltimaCitaServicio ?: '-' }}</div>
                                            <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $patient->UltimaCitaEspecialidad ?: '-' }}</div>
                                        @else
                                            <span class="text-[var(--muted-foreground)]">Sin citas</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <div class="flex gap-2">
                                            <span class="rounded-md bg-sky-50 px-2 py-1 text-xs font-semibold text-sky-800 ring-1 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800">{{ number_format((int) $patient->TotalAtenciones) }} atenciones</span>
                                            <span class="rounded-md bg-violet-50 px-2 py-1 text-xs font-semibold text-violet-800 ring-1 ring-violet-200 dark:bg-violet-950 dark:text-violet-100 dark:ring-violet-800">{{ number_format((int) $patient->TotalCitas) }} citas</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <flux:button :href="route('patients.show', $patient->IdPaciente)" size="sm" variant="primary" wire:navigate>Ver detalle</flux:button>
                                            <flux:button :href="route('citas.index', ['q' => $patient->NroHistoriaClinica])" size="sm" variant="ghost" wire:navigate>Citas</flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts::app>
