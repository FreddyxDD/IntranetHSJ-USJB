<x-layouts::app :title="'Planificador medico'">
    @php
        $monthDate = \Illuminate\Support\Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        $weekdayLabels = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];
    @endphp

    <div class="hs-page-shell" data-programacion-planner>
        <section class="hs-panel p-5 lg:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div class="text-sm font-semibold text-[var(--primary)]">Planificador operativo</div>
                    <flux:heading size="xl" class="mt-1">Calendario de programacion medica</flux:heading>
                    <flux:text class="mt-2 max-w-3xl">
                        Planifica el mes completo, marca feriados o fechas festivas y simula nuevas programaciones por especialidad, medico, servicio y turno. La programacion aun no escribe en SIGH.
                    </flux:text>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button :href="route('programacion-medica.index', ['mes' => $mes])" variant="ghost">Volver al listado</flux:button>
                    <flux:button type="button" variant="primary" disabled>Guardar programacion deshabilitado</flux:button>
                </div>
            </div>

            @if (session('status'))
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_390px]">
            <div class="grid gap-4">
                <div class="rounded-xl border border-[var(--border)] bg-[var(--background)] p-4">
                    <form method="GET" action="{{ route('programacion-medica.create') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div class="grid gap-3 sm:grid-cols-[220px_minmax(0,1fr)] lg:flex-1">
                            <flux:input name="mes" label="Mes a programar" type="month" value="{{ $mes }}" />
                            <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950/30 dark:text-sky-100">
                                El calendario muestra programaciones reales ya existentes y marcas propias del aplicativo.
                            </div>
                        </div>
                        <flux:button type="submit" variant="primary">Cambiar mes</flux:button>
                    </form>
                </div>

                <div class="overflow-hidden rounded-xl border border-[var(--border)] bg-[var(--background)]">
                    <div class="flex flex-col gap-3 border-b border-[var(--border)] px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-[var(--foreground)]">{{ $monthDate->translatedFormat('F Y') }}</h2>
                            <p class="text-sm text-[var(--muted-foreground)]">Selecciona un dia para marcarlo o previsualizar una nueva programacion.</p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-emerald-50 px-3 py-1 font-semibold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-800">Con programacion</span>
                            <span class="rounded-full bg-rose-50 px-3 py-1 font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800">Feriado bloquea</span>
                            <span class="rounded-full bg-amber-50 px-3 py-1 font-semibold text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-800">Festivo informa</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-7 border-b border-[var(--border)] bg-[var(--muted)] text-center text-xs font-semibold uppercase text-[var(--muted-foreground)]">
                        @foreach ($weekdayLabels as $label)
                            <div class="px-2 py-3">{{ $label }}</div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-7">
                        @foreach ($calendarDays as $day)
                            @php
                                $holiday = $day['holiday'];
                                $isBlocked = $holiday?->blocks_scheduling;
                                $dayClasses = $day['inMonth'] ? 'bg-[var(--background)]' : 'bg-[var(--muted)]/40 text-[var(--muted-foreground)]';
                                $dayClasses .= $isBlocked ? ' ring-1 ring-inset ring-rose-200 dark:ring-rose-800' : '';
                            @endphp
                            <button
                                type="button"
                                class="min-h-[156px] border-b border-r border-[var(--border)] p-2 text-left transition hover:bg-sky-50/70 focus:outline-none focus:ring-2 focus:ring-[var(--primary)] dark:hover:bg-sky-950/20 {{ $dayClasses }}"
                                data-calendar-day
                                data-date="{{ $day['date'] }}"
                                data-day="{{ $day['day'] }}"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold {{ $day['isToday'] ? 'bg-[var(--primary)] text-white' : 'text-[var(--foreground)]' }}">
                                        {{ $day['day'] }}
                                    </span>
                                    @if ($day['programacionesCount'] > 0)
                                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-800">
                                            {{ $day['programacionesCount'] }}
                                        </span>
                                    @endif
                                </div>

                                @if ($holiday)
                                    <div class="mt-2 rounded-lg {{ $holiday->type === 'feriado' ? 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-950/40 dark:text-rose-100 dark:ring-rose-800' : 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-100 dark:ring-amber-800' }} px-2 py-1 text-[11px] font-semibold ring-1">
                                        {{ $holiday->name }}
                                    </div>
                                @endif

                                <div class="mt-2 grid gap-1" data-preview-target="{{ $day['date'] }}">
                                    @foreach ($day['programaciones'] as $programacion)
                                        <div class="rounded-md bg-emerald-50 px-2 py-1 text-[11px] text-emerald-900 ring-1 ring-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-100 dark:ring-emerald-800">
                                            <div class="truncate font-semibold">{{ $programacion->servicio?->Nombre ?: 'Servicio' }}</div>
                                            <div class="truncate">{{ trim((string) $programacion->HoraInicio) }} - {{ trim((string) ($programacion->HoraFinProgramacion ?: $programacion->HoraFin)) }}</div>
                                        </div>
                                    @endforeach
                                    @if ($day['programacionesCount'] > $day['programaciones']->count())
                                        <div class="text-[11px] font-semibold text-[var(--muted-foreground)]">+ {{ $day['programacionesCount'] - $day['programaciones']->count() }} mas</div>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <aside class="grid content-start gap-4">
                <div class="rounded-xl border border-[var(--border)] bg-[var(--background)] p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-[var(--foreground)]">Dia seleccionado</h2>
                            <p class="text-sm text-[var(--muted-foreground)]" data-selected-label>Seleccione una fecha del calendario.</p>
                        </div>
                        <span class="rounded-lg bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">Peru</span>
                    </div>
                </div>

                <div class="rounded-xl border border-[var(--border)] bg-[var(--background)] p-5">
                    <h2 class="text-lg font-semibold text-[var(--foreground)]">Marcar feriado o festivo</h2>
                    <form method="POST" action="{{ route('programacion-medica.holidays.store') }}" class="mt-4 grid gap-3">
                        @csrf
                        <input type="hidden" name="mes" value="{{ $mes }}">
                        <flux:input name="date" label="Fecha" type="date" data-selected-date value="{{ $monthDate->toDateString() }}" required />
                        <flux:input name="name" label="Nombre" placeholder="Ejemplo: Fiestas Patrias" required />
                        <label class="grid gap-2 text-sm">
                            <span class="text-[var(--muted-foreground-2)]">Tipo</span>
                            <select name="type" class="h-10 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 text-sm">
                                <option value="feriado">Feriado</option>
                                <option value="festivo">Festivo</option>
                            </select>
                        </label>
                        <label class="flex items-center gap-2 rounded-lg border border-[var(--border)] p-3 text-sm">
                            <input type="checkbox" name="blocks_scheduling" value="1" checked class="size-4 rounded border-zinc-300 text-[var(--primary)]">
                            <span>Bloquear programacion en esta fecha</span>
                        </label>
                        <flux:button type="submit" variant="primary">Guardar marca</flux:button>
                    </form>

                    @if ($holidays->isNotEmpty())
                        <div class="mt-5 grid gap-2">
                            <div class="text-xs font-semibold uppercase text-[var(--muted-foreground)]">Marcas del mes</div>
                            @foreach ($holidays as $holiday)
                                <div class="flex items-center justify-between gap-3 rounded-lg border border-[var(--border)] px-3 py-2 text-sm">
                                    <div>
                                        <div class="font-semibold text-[var(--foreground)]">{{ $holiday->date->format('d/m') }} - {{ $holiday->name }}</div>
                                        <div class="text-xs text-[var(--muted-foreground)]">{{ $holiday->type === 'feriado' ? 'Feriado' : 'Festivo' }}{{ $holiday->blocks_scheduling ? ' / bloquea' : ' / informa' }}</div>
                                    </div>
                                    <form method="POST" action="{{ route('programacion-medica.holidays.destroy', ['holiday' => $holiday, 'mes' => $mes]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 dark:text-rose-200 dark:hover:bg-rose-950/40">Retirar</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="rounded-xl border border-[var(--border)] bg-[var(--background)] p-5">
                    <h2 class="text-lg font-semibold text-[var(--foreground)]">Nueva programacion</h2>
                    <p class="mt-1 text-sm text-[var(--muted-foreground)]">Previsualiza el bloque que ocuparia el medico en el calendario.</p>

                    <form class="mt-4 grid gap-3" data-preview-form>
                        <label class="grid gap-2 text-sm">
                            <span class="text-[var(--muted-foreground-2)]">Especialidad</span>
                            <select name="especialidad" data-specialty-select class="h-10 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 text-sm" required>
                                <option value="">Seleccione especialidad</option>
                                @foreach ($especialidades as $especialidad)
                                    <option value="{{ $especialidad->IdEspecialidad }}">{{ $especialidad->Nombre }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="grid gap-2 text-sm">
                            <span class="text-[var(--muted-foreground-2)]">Medico</span>
                            <select name="medico" data-doctor-select class="h-10 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 text-sm" required>
                                <option value="">Primero seleccione especialidad</option>
                            </select>
                        </label>

                        <label class="grid gap-2 text-sm">
                            <span class="text-[var(--muted-foreground-2)]">Servicio / consultorio</span>
                            <select name="servicio" data-service-select class="h-10 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 text-sm" required>
                                <option value="">Seleccione servicio</option>
                                @foreach ($servicios as $servicio)
                                    <option value="{{ $servicio->IdServicio }}" data-specialty="{{ $servicio->especialidad?->IdEspecialidad }}">{{ $servicio->Nombre }}</option>
                                @endforeach
                            </select>
                        </label>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="grid gap-2 text-sm">
                                <span class="text-[var(--muted-foreground-2)]">Turno</span>
                                <select name="turno" data-turn-select class="h-10 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 text-sm">
                                    @foreach ($turnos as $turno)
                                        <option value="{{ $turno->IdTurno }}">{{ $turno->Descripcion ?? ('Turno '.$turno->IdTurno) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-2 text-sm">
                                <span class="text-[var(--muted-foreground-2)]">Minutos</span>
                                <input name="minutos" type="number" value="15" min="1" class="h-10 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 text-sm">
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <flux:input name="inicio" label="Inicio" type="time" value="07:00" required />
                            <flux:input name="fin" label="Fin" type="time" value="12:00" required />
                        </div>

                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100">
                            Guardar en SIGH se habilitara luego de validar cruces, reprogramacion de pacientes y auditoria.
                        </div>

                        <flux:button type="submit" variant="primary">Agregar a previsualizacion</flux:button>
                    </form>
                </div>
            </aside>
        </section>
    </div>

    <script type="application/json" data-medicos-json>@json($medicosPorEspecialidad)</script>
    <script>
        (() => {
            const planner = document.querySelector('[data-programacion-planner]');
            if (!planner) return;

            const medicosMap = JSON.parse(document.querySelector('[data-medicos-json]')?.textContent || '{}');
            const dateInput = planner.querySelector('[data-selected-date]');
            const label = planner.querySelector('[data-selected-label]');
            const specialtySelect = planner.querySelector('[data-specialty-select]');
            const doctorSelect = planner.querySelector('[data-doctor-select]');
            const serviceSelect = planner.querySelector('[data-service-select]');
            const previewForm = planner.querySelector('[data-preview-form]');
            let selectedDate = dateInput?.value;

            const setSelected = (date, day) => {
                selectedDate = date;
                if (dateInput) dateInput.value = date;
                if (label) label.textContent = `Dia ${day} seleccionado`;

                planner.querySelectorAll('[data-calendar-day]').forEach((button) => {
                    button.classList.toggle('ring-2', button.dataset.date === date);
                    button.classList.toggle('ring-[var(--primary)]', button.dataset.date === date);
                });
            };

            planner.querySelectorAll('[data-calendar-day]').forEach((button) => {
                button.addEventListener('click', () => setSelected(button.dataset.date, button.dataset.day));
            });

            const refreshDoctors = () => {
                const specialty = specialtySelect.value;
                const doctors = medicosMap[specialty] || [];
                doctorSelect.innerHTML = '';

                if (doctors.length === 0) {
                    const option = new Option('Sin medicos programados en el mes', '');
                    doctorSelect.add(option);
                    return;
                }

                doctorSelect.add(new Option('Seleccione medico', ''));
                doctors.forEach((doctor) => doctorSelect.add(new Option(doctor.name, doctor.id)));
            };

            const refreshServices = () => {
                const specialty = specialtySelect.value;
                Array.from(serviceSelect.options).forEach((option) => {
                    if (!option.value) return;
                    option.hidden = specialty && option.dataset.specialty !== specialty;
                });
                serviceSelect.value = '';
            };

            specialtySelect?.addEventListener('change', () => {
                refreshDoctors();
                refreshServices();
            });

            previewForm?.addEventListener('submit', (event) => {
                event.preventDefault();
                if (!selectedDate) return;

                const serviceText = serviceSelect.selectedOptions[0]?.textContent?.trim();
                const doctorText = doctorSelect.selectedOptions[0]?.textContent?.trim();
                const start = previewForm.elements.inicio.value;
                const end = previewForm.elements.fin.value;
                const target = planner.querySelector(`[data-preview-target="${selectedDate}"]`);

                if (!target || !serviceSelect.value || !doctorSelect.value || !start || !end) return;

                const item = document.createElement('div');
                item.className = 'rounded-md bg-sky-50 px-2 py-1 text-[11px] text-sky-900 ring-1 ring-sky-200 dark:bg-sky-950/40 dark:text-sky-100 dark:ring-sky-800';
                item.innerHTML = `<div class="truncate font-semibold">${serviceText}</div><div class="truncate">${start} - ${end}</div><div class="truncate">${doctorText}</div>`;
                target.prepend(item);
            });
        })();
    </script>
</x-layouts::app>
