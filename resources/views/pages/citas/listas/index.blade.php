<x-layouts::app :title="__('Listas de pacientes')">
    <div class="flex h-full w-full flex-1 flex-col gap-5">
        <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <flux:heading size="xl">Listas de pacientes</flux:heading>
                    <flux:text class="mt-1">Prepara e imprime listados por consultorio con espacios libres para completar citas pendientes.</flux:text>
                </div>

                <form method="GET" action="{{ route('citas.listas.index') }}" class="grid w-full gap-3 sm:grid-cols-2 xl:max-w-5xl xl:grid-cols-6 xl:items-end">
                    <flux:input name="fecha" label="Fecha" type="date" value="{{ $filters['fecha'] }}" required />

                    <label class="grid gap-2 text-sm">
                        <span class="text-zinc-700 dark:text-zinc-300">Turno</span>
                        <select name="turno" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="todos" @selected($filters['turno'] === 'todos')>Todos</option>
                            <option value="manana" @selected($filters['turno'] === 'manana')>MaÃ±ana</option>
                            <option value="tarde" @selected($filters['turno'] === 'tarde')>Tarde</option>
                            <option value="fuera" @selected($filters['turno'] === 'fuera')>Fuera de turno</option>
                        </select>
                    </label>

                    <label class="grid gap-2 text-sm">
                        <span class="text-zinc-700 dark:text-zinc-300">Orden</span>
                        <select name="orden" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="hora" @selected($filters['orden'] === 'hora')>Hora de cita</option>
                            <option value="hc" @selected($filters['orden'] === 'hc')>Terminal de historia</option>
                        </select>
                    </label>

                    <div class="sm:col-span-2">
                        <flux:input name="q" label="Buscar" value="{{ $filters['q'] }}" placeholder="Servicio, medico, paciente, DNI, historia" />
                    </div>

                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary">Filtrar</flux:button>
                        <flux:button :href="route('citas.listas.index')" variant="ghost">Hoy</flux:button>
                    </div>
                </form>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100">
                Revisa los filtros ingresados. Hay parametros no validos para generar el reporte.
            </div>
        @endif

        <form id="patient-list-form" method="GET" action="{{ route('citas.listas.print') }}" target="_blank" class="flex flex-col gap-5">
            <input type="hidden" name="fecha" value="{{ $filters['fecha'] }}">
            <input type="hidden" name="turno" value="{{ $filters['turno'] }}">
            <input type="hidden" name="orden" value="{{ $filters['orden'] }}">
            <input type="hidden" name="q" value="{{ $filters['q'] }}">
            <input type="hidden" name="seleccion" value="1">

            <div class="grid gap-3 md:grid-cols-4">
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="text-xs font-medium uppercase text-zinc-500">Fecha</div>
                    <div class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-white">{{ \Illuminate\Support\Carbon::parse($filters['fecha'])->format('d/m/Y') }}</div>
                    <div class="text-xs text-zinc-500">Actualizado {{ $refreshedAt->format('H:i') }}</div>
                </div>
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="text-xs font-medium uppercase text-zinc-500">Pacientes registrados</div>
                    <div class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($totalCitas) }}</div>
                </div>
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="text-xs font-medium uppercase text-zinc-500">Consultorios</div>
                    <div class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($groups->count()) }}</div>
                </div>
                <div class="rounded-lg border border-teal-200 bg-teal-50 p-4 dark:border-teal-900 dark:bg-teal-950/30">
                    <div class="text-xs font-medium uppercase text-teal-700 dark:text-teal-200">Seleccionados</div>
                    <div class="mt-1 flex items-end gap-2">
                        <div id="selected-group-count" class="text-2xl font-semibold text-teal-950 dark:text-teal-100">0</div>
                        <div id="selected-patient-count" class="pb-1 text-xs text-teal-700 dark:text-teal-200">0 pacientes</div>
                    </div>
                </div>
            </div>

            <section class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">Consultorios a imprimir</h2>
                        <p class="text-xs text-zinc-500">Selecciona uno o varios. Cada consultorio se imprime en hoja separada.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            <input id="select-all-groups" type="checkbox" class="size-4 rounded border-zinc-300">
                            Seleccionar todos
                        </label>
                        <flux:button id="open-print-button" type="submit" variant="primary" size="sm">Abrir impresion</flux:button>
                    </div>
                </div>

                <div class="grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-4">
                    @forelse ($operationalGroups as $group)
                        @php($blankRows = $group['blank_rows'] ?? max(0, $group['capacity'] - $group['total']))
                        <label class="group flex cursor-pointer gap-3 rounded-lg border border-zinc-200 p-3 transition hover:border-teal-300 hover:bg-teal-50 dark:border-zinc-700 dark:hover:border-teal-800 dark:hover:bg-teal-950/30">
                            <input type="checkbox" name="grupos[]" value="{{ $group['key'] }}" class="group-checkbox mt-1 size-4 rounded border-zinc-300" data-total="{{ $group['total'] }}" data-capacity="{{ $group['capacity'] }}" @checked(in_array($group['key'], $filters['grupos'], true))>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-zinc-950 dark:text-white">{{ $group['servicio'] }} </span>
                                <span class="block truncate text-xs text-zinc-500">{{ $group['especialidad'] }}</span>
                                <span class="mt-1 block truncate text-xs text-zinc-700 dark:text-zinc-200">Medico: {{ $group['medico'] }}</span>
                                <span class="mt-2 flex flex-wrap gap-1.5">
                                    <span class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $group['total'] }}/{{ $group['capacity'] }} citas</span>
                                    <span class="rounded-md bg-sky-100 px-2 py-1 text-[11px] text-sky-800 dark:bg-sky-950 dark:text-sky-100">SIS {{ $group['sis'] }}</span>
                                    @if ($blankRows > 0)
                                        <span class="rounded-md bg-amber-100 px-2 py-1 text-[11px] text-amber-800 dark:bg-amber-950 dark:text-amber-100">{{ $blankRows }} libres</span>
                                    @endif
                                    @if ($group['adicionales'] > 0)
                                        <span class="rounded-md bg-rose-100 px-2 py-1 text-[11px] text-rose-800 dark:bg-rose-950 dark:text-rose-100">{{ $group['adicionales'] }} adicionales</span>
                                    @endif
                                </span>
                            </span>
                        </label>
                    @empty
                        <div class="col-span-full px-4 py-10 text-center text-sm text-zinc-500">No hay consultorios con citas para los filtros.</div>
                    @endforelse
                </div>

                @if ($nonOperationalGroups->isNotEmpty())
                    <details class="border-t border-zinc-200 dark:border-zinc-700">
                        <summary class="flex cursor-pointer items-center justify-between bg-amber-50 px-4 py-3 text-sm dark:bg-amber-950/30">
                            <span>
                                <span class="font-semibold text-amber-950 dark:text-amber-100">Consultorios no operativos</span>
                                <span class="ml-2 text-xs text-amber-700 dark:text-amber-200">{{ $nonOperationalGroups->count() }} consultorios separados</span>
                            </span>
                            <span class="text-xs text-amber-700 dark:text-amber-200">Configurable en Sistema</span>
                        </summary>

                        <div class="grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-4">
                            @foreach ($nonOperationalGroups as $group)
                                @php($blankRows = $group['blank_rows'] ?? max(0, $group['capacity'] - $group['total']))
                                <label class="group flex cursor-pointer gap-3 rounded-lg border border-amber-200 bg-amber-50/60 p-3 transition hover:border-amber-300 hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/20 dark:hover:border-amber-700">
                                    <input type="checkbox" name="grupos[]" value="{{ $group['key'] }}" class="group-checkbox mt-1 size-4 rounded border-zinc-300" data-total="{{ $group['total'] }}" data-capacity="{{ $group['capacity'] }}" @checked(in_array($group['key'], $filters['grupos'], true))>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-zinc-950 dark:text-white">{{ $group['servicio'] }}</span>
                                        <span class="block truncate text-xs text-amber-700 dark:text-amber-200">No operativo para entrega de historias</span>
                                        <span class="mt-1 block truncate text-xs text-zinc-700 dark:text-zinc-200">Medico: {{ $group['medico'] }}</span>
                                        <span class="mt-2 flex flex-wrap gap-1.5">
                                            <span class="rounded-md bg-white px-2 py-1 text-[11px] text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $group['total'] }}/{{ $group['capacity'] }} citas</span>
                                            @if ($blankRows > 0)
                                                <span class="rounded-md bg-amber-100 px-2 py-1 text-[11px] text-amber-800 dark:bg-amber-950 dark:text-amber-100">{{ $blankRows }} libres</span>
                                            @endif
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </details>
                @endif
            </section>

            <section class="flex flex-col gap-4">
                <div class="rounded-lg border border-zinc-200 bg-white p-4 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                    <div class="font-semibold text-zinc-950 dark:text-white">Vista previa</div>
                    <p class="mt-1">El impreso incluye financiamiento junto al numero de historia clinica, citas adicionales o turnos extendidos, filas libres hasta completar cupos y un bloque de firma para conformidad de entrega de historias clinicas.</p>
                </div>

                @foreach ($groups as $group)
                    @php($isSelected = in_array($group['key'], $filters['grupos'], true))
                    @php($blankRows = $group['blank_rows'] ?? max(0, $group['capacity'] - $group['total']))
                    <details data-preview-group="{{ $group['key'] }}" class="{{ $isSelected ? '' : 'hidden' }} overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900" open>
                        <summary class="flex cursor-pointer flex-col gap-2 bg-zinc-50 px-4 py-3 dark:bg-zinc-950 lg:flex-row lg:items-center lg:justify-between">
                            <span>
                                <span class="block text-sm font-semibold text-zinc-950 dark:text-white">{{ $group['servicio'] }} ({{ $group['servicio_id'] }})</span>
                                <span class="text-xs text-zinc-500">Medico: {{ $group['medico'] }}</span>
                            </span>
                            <span class="flex flex-wrap gap-2">
                                <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $group['total'] }}/{{ $group['capacity'] }} citas</span>
                                <span class="rounded-md bg-sky-100 px-2 py-1 text-xs text-sky-800 dark:bg-sky-950 dark:text-sky-100">SIS {{ $group['sis'] }}</span>
                                @if ($blankRows > 0)
                                    <span class="rounded-md bg-amber-100 px-2 py-1 text-xs text-amber-800 dark:bg-amber-950 dark:text-amber-100">{{ $blankRows }} filas libres</span>
                                @endif
                            </span>
                        </summary>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                <thead class="bg-white text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                                    <tr>
                                        <th class="px-4 py-3">Hora</th>
                                        <th class="px-4 py-3">N&deg; de HC</th>
                                        <th class="px-4 py-3">Financiamiento</th>
                                        <th class="px-4 py-3">Paciente</th>
                                        <th class="px-4 py-3">Doc.</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach ($group['rows'] as $row)
                                        @php($cita = $row['cita'])
                                        @if (! $cita)
                                            <tr class="bg-amber-50/50 dark:bg-amber-950/20">
                                                <td class="whitespace-nowrap px-4 py-3 text-amber-700 dark:text-amber-200">
                                                    {{ $row['slot_start'] ?: 'Libre' }}
                                                </td>
                                                <td class="px-4 py-3 text-zinc-400"></td>
                                                <td class="px-4 py-3 text-zinc-400">Financ.</td>
                                                <td class="px-4 py-3 text-zinc-400">Paciente por completar</td>
                                                <td class="px-4 py-3 text-zinc-400">Doc.</td>
                                            </tr>
                                            @continue
                                        @endif
                                        @php($markers = collect([
                                            $cita->EsCitaAdicional ? 'Cita adicional' : null,
                                            \App\Support\AppointmentTurn::isExtended($cita) ? 'Turno extendido' : null,
                                            $row['is_overflow'] ? 'Fuera de cupo programado' : null,
                                        ])->filter()->implode(' / '))
                                        <tr>
                                            <td class="whitespace-nowrap px-4 py-3">{{ trim($cita->HoraInicio) }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-zinc-950 dark:text-white">{{ \App\Support\ClinicalHistoryNumber::format($cita->paciente?->NroHistoriaClinica) }}</td>
                                            <td class="whitespace-nowrap px-4 py-3">{{ \App\Http\Controllers\Sigh\PatientListPrintController::financingLabel($cita) }}</td>
                                            <td class="min-w-64 px-4 py-3">
                                                {{ $cita->paciente?->nombre_completo ?: '-' }}
                                                @if ($markers !== '')
                                                    <span class="ml-2 rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-800 dark:bg-amber-950 dark:text-amber-100">{{ $markers }}</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3">{{ $cita->paciente?->NroDocumento ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endforeach

                <div id="empty-preview-message" class="{{ $filters['grupos'] === [] ? '' : 'hidden' }} rounded-lg border border-zinc-200 px-4 py-10 text-center text-zinc-500 dark:border-zinc-700">
                        Selecciona al menos un consultorio para previsualizar la lista.
                </div>
            </section>
        </form>

        <div id="action-modal" class="hs-overlay fixed start-0 top-0 z-80 hidden size-full overflow-y-auto overflow-x-hidden pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="action-modal-title">
            <div class="m-3 mt-0 flex min-h-[calc(100%-3.5rem)] items-center justify-center opacity-0 transition-all hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 sm:mx-auto sm:w-full sm:max-w-md">
                <div class="pointer-events-auto w-full rounded-xl border border-[var(--border)] bg-[var(--background)] p-5 shadow-xl">
                    <div class="flex gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-amber-100 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950 dark:text-amber-200 dark:ring-amber-800">
                            <flux:icon icon="exclamation-triangle" class="size-5" />
                        </span>
                        <div class="min-w-0">
                            <div id="action-modal-title" class="text-base font-semibold text-[var(--foreground)]">Seleccion requerida</div>
                            <p id="action-modal-message" class="mt-2 text-sm text-[var(--muted-foreground-2)]">Selecciona al menos un consultorio antes de abrir la impresion.</p>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end">
                        <button type="button" id="action-modal-close" data-hs-overlay="#action-modal" class="inline-flex items-center gap-2 rounded-lg bg-[var(--primary)] px-4 py-2 text-sm font-semibold text-[var(--primary-foreground)] hover:opacity-90">
                            <flux:icon icon="check" class="size-4" />
                            Entendido
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.getElementById('patient-list-form');
                const selectAll = document.getElementById('select-all-groups');
                const checks = Array.from(document.querySelectorAll('.group-checkbox'));
                const selectedCount = document.getElementById('selected-group-count');
                const selectedPatientCount = document.getElementById('selected-patient-count');
                const modal = document.getElementById('action-modal');
                const modalMessage = document.getElementById('action-modal-message');
                const modalClose = document.getElementById('action-modal-close');
                const emptyPreviewMessage = document.getElementById('empty-preview-message');
                const previewGroups = Array.from(document.querySelectorAll('[data-preview-group]'));
                const hasValidationErrors = @json($errors->any());

                const checkedItems = () => checks.filter((check) => check.checked);

                const showModal = (message = 'Selecciona al menos un consultorio antes de abrir la impresion.') => {
                    if (modalMessage) {
                        modalMessage.textContent = message;
                    }
                    if (window.HSOverlay) {
                        window.HSOverlay.open('#action-modal');
                        return;
                    }
                    modal?.classList.remove('hidden');
                };

                const hideModal = () => {
                    if (window.HSOverlay) {
                        window.HSOverlay.close('#action-modal');
                        return;
                    }
                    modal?.classList.add('hidden');
                };

                const update = () => {
                    const checked = checkedItems();
                    const checkedKeys = new Set(checked.map((check) => check.value));
                    const totalPatients = checked.reduce((sum, check) => sum + Number(check.dataset.total || 0), 0);
                    selectedCount.textContent = checked.length.toString();
                    selectedPatientCount.textContent = `${totalPatients} pacientes`;
                    selectAll.checked = checked.length > 0 && checked.length === checks.length;
                    selectAll.indeterminate = checked.length > 0 && checked.length < checks.length;

                    previewGroups.forEach((preview) => {
                        preview.classList.toggle('hidden', ! checkedKeys.has(preview.dataset.previewGroup));
                    });
                    emptyPreviewMessage?.classList.toggle('hidden', checked.length > 0);
                };

                selectAll?.addEventListener('change', () => {
                    checks.forEach((check) => check.checked = selectAll.checked);
                    update();
                });

                checks.forEach((check) => check.addEventListener('change', update));

                form?.addEventListener('submit', (event) => {
                    if (checkedItems().length === 0) {
                        event.preventDefault();
                        showModal();
                    }
                });

                modalClose?.addEventListener('click', hideModal);
                modal?.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        hideModal();
                    }
                });

                update();

                if (hasValidationErrors) {
                    showModal('Hay filtros con valores no validos. Corrige la fecha, turno, orden o busqueda antes de imprimir.');
                }
            });
        </script>
    </div>
</x-layouts::app>
