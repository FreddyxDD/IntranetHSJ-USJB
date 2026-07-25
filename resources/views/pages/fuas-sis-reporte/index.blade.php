<x-layouts::app :title="__('Reporte FUA SIS')">
    @php($total = (int) ($summary->total ?? 0))
    @php($sisNormal = (int) ($summary->sis_normal ?? 0))
    @php($sisManual = (int) ($summary->sis_manual ?? 0))
    @php($fuaGenerada = (int) ($summary->fua_generada ?? 0))
    @php($fuaPendiente = (int) ($summary->fua_pendiente ?? 0))
    @php($sinValidacion = (int) ($summary->sis_sin_validacion ?? 0))
    @php($manualRate = $total > 0 ? round(($sisManual / $total) * 100) : 0)
    @php($pendienteRate = $total > 0 ? round(($fuaPendiente / $total) * 100) : 0)
    @php($desde = \Illuminate\Support\Carbon::parse($filters['fecha_desde']))
    @php($hasta = \Illuminate\Support\Carbon::parse($filters['fecha_hasta']))
    @php($activeFilters = collect([
        $filters['tipo_sis'] !== 'todos' ? 'Tipo: '.($filters['tipo_sis'] === 'manual' ? 'SIS manual' : 'SIS normal') : null,
        $filters['estado_fua'] !== 'todos' ? 'FUA: '.($filters['estado_fua'] === 'generada' ? 'Generada' : 'Pendiente') : null,
        filled($filters['documento']) ? 'Documento: '.$filters['documento'] : null,
        filled($filters['q']) ? 'Busqueda: '.$filters['q'] : null,
    ])->filter())

    <div class="hs-page-shell">
        <section class="hs-panel overflow-hidden">
            <div class="flex flex-col gap-5 border-b border-[var(--border)] p-5 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="xl">Reporte FUA SIS</flux:heading>
                        <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ $desde->format('d/m/Y') }} - {{ $hasta->format('d/m/Y') }}
                        </span>
                    </div>
                    <flux:text class="mt-1 max-w-3xl">Control operativo de pacientes SIS con FUA generada o pendiente, diferenciando SIS normal y SIS manual.</flux:text>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse ($activeFilters as $filter)
                            <span class="rounded-md bg-teal-50 px-2 py-1 text-xs font-medium text-teal-800 ring-1 ring-teal-200 dark:bg-teal-950 dark:text-teal-100 dark:ring-teal-800">{{ $filter }}</span>
                        @empty
                            <span class="rounded-md bg-zinc-50 px-2 py-1 text-xs font-medium text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-950 dark:text-zinc-300 dark:ring-zinc-800">Sin filtros adicionales</span>
                        @endforelse
                    </div>
                </div>

                <div class="grid gap-2 text-sm sm:grid-cols-3 xl:min-w-[480px]">
                    <div class="hs-tooltip relative rounded-lg bg-amber-50 p-3 ring-1 ring-amber-200 [--placement:top] dark:bg-amber-950/30 dark:ring-amber-900">
                        <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-amber-700 dark:text-amber-200">
                            <span>Pendientes</span>
                            <span class="hs-tooltip-toggle grid size-5 cursor-help place-items-center rounded-full bg-white/80 text-[10px] ring-1 ring-amber-200 dark:bg-amber-950 dark:ring-amber-800">?</span>
                            <span class="hs-tooltip-content invisible absolute z-50 max-w-xs rounded-lg bg-zinc-900 px-3 py-2 text-xs font-normal normal-case leading-5 text-white opacity-0 shadow-lg transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">
                                Atenciones SIS que todavia no tienen FUA registrada. Son las primeras que el personal debe revisar para evitar pendientes al cierre del dia.
                            </span>
                        </div>
                        <div class="mt-1 text-2xl font-semibold text-amber-950 dark:text-amber-100">{{ number_format($fuaPendiente) }}</div>
                        <div class="text-xs text-amber-700/80 dark:text-amber-200/80">{{ $pendienteRate }}% del filtro</div>
                    </div>
                    <div class="hs-tooltip relative rounded-lg bg-violet-50 p-3 ring-1 ring-violet-200 [--placement:top] dark:bg-violet-950/30 dark:ring-violet-900">
                        <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-violet-700 dark:text-violet-200">
                            <span>SIS manual</span>
                            <span class="hs-tooltip-toggle grid size-5 cursor-help place-items-center rounded-full bg-white/80 text-[10px] ring-1 ring-violet-200 dark:bg-violet-950 dark:ring-violet-800">?</span>
                            <span class="hs-tooltip-content invisible absolute z-50 max-w-xs rounded-lg bg-zinc-900 px-3 py-2 text-xs font-normal normal-case leading-5 text-white opacity-0 shadow-lg transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">
                                Casos SIS que suelen requerir validacion manual de documento o afiliacion antes de procesar o revisar la FUA.
                            </span>
                        </div>
                        <div class="mt-1 text-2xl font-semibold text-violet-950 dark:text-violet-100">{{ number_format($sisManual) }}</div>
                        <div class="text-xs text-violet-700/80 dark:text-violet-200/80">{{ $manualRate }}% del filtro</div>
                    </div>
                    <div class="hs-tooltip relative rounded-lg bg-emerald-50 p-3 ring-1 ring-emerald-200 [--placement:top] dark:bg-emerald-950/30 dark:ring-emerald-900">
                        <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-200">
                            <span>Generadas</span>
                            <span class="hs-tooltip-toggle grid size-5 cursor-help place-items-center rounded-full bg-white/80 text-[10px] ring-1 ring-emerald-200 dark:bg-emerald-950 dark:ring-emerald-800">?</span>
                            <span class="hs-tooltip-content invisible absolute z-50 max-w-xs rounded-lg bg-zinc-900 px-3 py-2 text-xs font-normal normal-case leading-5 text-white opacity-0 shadow-lg transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">
                                Atenciones que ya cuentan con numero FUA. Sirve para confirmar que el paciente ya fue procesado y evitar doble trabajo.
                            </span>
                        </div>
                        <div class="mt-1 text-2xl font-semibold text-emerald-950 dark:text-emerald-100">{{ number_format($fuaGenerada) }}</div>
                        <div class="text-xs text-emerald-700/80 dark:text-emerald-200/80">{{ number_format($total) }} total SIS</div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('fuas-sis-reporte.filter') }}" class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                @csrf
                <div class="lg:col-span-2">
                    <flux:input name="fecha_desde" label="Desde" type="date" value="{{ $filters['fecha_desde'] }}" />
                </div>
                <div class="lg:col-span-2">
                    <flux:input name="fecha_hasta" label="Hasta" type="date" value="{{ $filters['fecha_hasta'] }}" />
                </div>

                <label class="grid gap-2 text-sm lg:col-span-2">
                    <span class="text-zinc-700 dark:text-zinc-300">Tipo SIS</span>
                    <select name="tipo_sis" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <option value="todos" @selected($filters['tipo_sis'] === 'todos')>Todos</option>
                        <option value="normal" @selected($filters['tipo_sis'] === 'normal')>SIS normal</option>
                        <option value="manual" @selected($filters['tipo_sis'] === 'manual')>SIS manual</option>
                    </select>
                </label>

                <label class="grid gap-2 text-sm lg:col-span-2">
                    <span class="text-zinc-700 dark:text-zinc-300">Estado FUA</span>
                    <select name="estado_fua" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <option value="todos" @selected($filters['estado_fua'] === 'todos')>Todos</option>
                        <option value="generada" @selected($filters['estado_fua'] === 'generada')>Generada</option>
                        <option value="pendiente" @selected($filters['estado_fua'] === 'pendiente')>Pendiente</option>
                    </select>
                </label>

                <div class="lg:col-span-2">
                    <flux:input name="documento" label="Documento" value="{{ $filters['documento'] }}" placeholder="DNI, Carnet Extranjeria" />
                </div>

                <div class="lg:col-span-2">
                    <flux:input name="q" label="Buscar" value="{{ $filters['q'] }}" placeholder="Historia, documento, paciente, FUA" />
                </div>

                <div class="flex flex-wrap gap-2 lg:col-span-12">
                    <flux:button type="submit" variant="primary">Aplicar filtros</flux:button>
                    <flux:button type="submit" variant="ghost" formaction="{{ route('fuas-sis-reporte.reset') }}">Mes actual</flux:button>
                </div>
            </form>
        </section>

        <section class="grid items-start gap-4 xl:grid-cols-[1fr_360px]">
            <div class="grid self-start gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="hs-tooltip relative rounded-lg border border-zinc-200 bg-white p-4 [--placement:top] dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-zinc-500">
                        <span>Total SIS</span>
                        <span class="hs-tooltip-toggle grid size-5 cursor-help place-items-center rounded-full bg-zinc-100 text-[10px] ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">?</span>
                        <span class="hs-tooltip-content invisible absolute z-50 max-w-xs rounded-lg bg-zinc-900 px-3 py-2 text-xs font-normal normal-case leading-5 text-white opacity-0 shadow-lg transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">
                            Total de atenciones SIS encontradas con los filtros actuales. Es la base para comparar pendientes, manuales y generadas.
                        </span>
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($total) }}</div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-full rounded-full bg-teal-600" style="width: 100%"></div>
                    </div>
                </div>
                <div class="hs-tooltip relative rounded-lg border border-zinc-200 bg-white p-4 [--placement:top] dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-sky-700 dark:text-sky-300">
                        <span>SIS normal</span>
                        <span class="hs-tooltip-toggle grid size-5 cursor-help place-items-center rounded-full bg-sky-50 text-[10px] ring-1 ring-sky-200 dark:bg-sky-950 dark:ring-sky-800">?</span>
                        <span class="hs-tooltip-content invisible absolute z-50 max-w-xs rounded-lg bg-zinc-900 px-3 py-2 text-xs font-normal normal-case leading-5 text-white opacity-0 shadow-lg transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">
                            Atenciones SIS con datos regulares de afiliacion. Normalmente deben procesarse sin correccion manual adicional.
                        </span>
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($sisNormal) }}</div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-full rounded-full bg-sky-500" style="width: {{ $total > 0 && $sisNormal > 0 ? max(3, round(($sisNormal / $total) * 100)) : 0 }}%"></div>
                    </div>
                </div>
                <div class="hs-tooltip relative rounded-lg border border-zinc-200 bg-white p-4 [--placement:top] dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-rose-700 dark:text-rose-300">
                        <span>Por revisar</span>
                        <span class="hs-tooltip-toggle grid size-5 cursor-help place-items-center rounded-full bg-rose-50 text-[10px] ring-1 ring-rose-200 dark:bg-rose-950 dark:ring-rose-800">?</span>
                        <span class="hs-tooltip-content invisible absolute z-50 max-w-xs rounded-lg bg-zinc-900 px-3 py-2 text-xs font-normal normal-case leading-5 text-white opacity-0 shadow-lg transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">
                            Atenciones que requieren revision porque no tienen una validacion clara de afiliacion. El usuario debe confirmar datos antes de cerrar el caso.
                        </span>
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-white">{{ number_format($sinValidacion) }}</div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-full rounded-full bg-rose-500" style="width: {{ $total > 0 && $sinValidacion > 0 ? max(3, round(($sinValidacion / $total) * 100)) : 0 }}%"></div>
                    </div>
                </div>
                <div class="hs-tooltip relative rounded-lg border border-zinc-200 bg-white p-4 [--placement:top] dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-zinc-500">
                        <span>Actualizado</span>
                        <span class="hs-tooltip-toggle grid size-5 cursor-help place-items-center rounded-full bg-zinc-100 text-[10px] ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">?</span>
                        <span class="hs-tooltip-content invisible absolute z-50 max-w-xs rounded-lg bg-zinc-900 px-3 py-2 text-xs font-normal normal-case leading-5 text-white opacity-0 shadow-lg transition-opacity hs-tooltip-shown:visible hs-tooltip-shown:opacity-100" role="tooltip">
                            Hora en que se cargo el reporte en pantalla. Si se acaban de registrar cambios, vuelve a aplicar filtros para refrescar.
                        </span>
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-white">{{ $refreshedAt->format('H:i') }}</div>
                    <div class="mt-2 text-xs text-zinc-500">{{ $refreshedAt->format('d/m/Y') }}</div>
                </div>
            </div>

            <aside class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-zinc-950 dark:text-white">Documentos</div>
                        <div class="text-xs text-zinc-500">Distribucion del filtro activo</div>
                    </div>
                    <span class="rounded-md bg-violet-50 px-2 py-1 text-xs font-semibold text-violet-700 ring-1 ring-violet-200 dark:bg-violet-950 dark:text-violet-100 dark:ring-violet-800">Manual</span>
                </div>

                <div class="mt-4 grid max-h-80 gap-2 overflow-y-auto pr-1">
                    @forelse ($documentSummary as $doc)
                        @php($docTotal = (int) $doc->total)
                        @php($docManual = (int) $doc->sis_manual)
                        <div class="rounded-lg bg-zinc-50 p-3 ring-1 ring-zinc-200 dark:bg-zinc-950 dark:ring-zinc-800">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <div class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $doc->TipoDocumento }}</div>
                                <div class="whitespace-nowrap text-zinc-500">{{ number_format($docTotal) }}</div>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-white dark:bg-zinc-900">
                                    <div class="h-full rounded-full bg-violet-500" style="width: {{ $docTotal > 0 && $docManual > 0 ? max(3, round(($docManual / $docTotal) * 100)) : 0 }}%"></div>
                                </div>
                                <div class="w-12 text-right text-xs font-medium text-violet-700 dark:text-violet-200">{{ number_format($docManual) }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700">
                            Sin datos documentarios.
                        </div>
                    @endforelse
                </div>
            </aside>
        </section>

        <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="text-sm font-semibold text-zinc-950 dark:text-white">Atenciones SIS</div>
                    <div class="text-xs text-zinc-500">
                        Mostrando {{ number_format($rows->firstItem() ?? 0) }}-{{ number_format($rows->lastItem() ?? 0) }} de {{ number_format($rows->total()) }} registros
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="rounded-md bg-emerald-50 px-2 py-1 font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-800">FUA generada</span>
                    <span class="rounded-md bg-amber-50 px-2 py-1 font-medium text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-800">FUA pendiente</span>
                    <span class="rounded-md bg-violet-50 px-2 py-1 font-medium text-violet-700 ring-1 ring-violet-200 dark:bg-violet-950 dark:text-violet-100 dark:ring-violet-800">SIS manual</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">Paciente</th>
                            <th class="px-4 py-3">Atencion</th>
                            <th class="px-4 py-3">Estado operativo</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($rows as $row)
                            @php($isManual = $row->TipoSis === 'SIS MANUAL')
                            @php($hasFua = (bool) $row->TieneFuaSis)
                            @php($rowAccent = $isManual ? 'border-l-violet-400' : ($hasFua ? 'border-l-emerald-400' : 'border-l-amber-400'))
                            <tr class="border-l-4 {{ $rowAccent }} align-top transition hover:bg-zinc-50 dark:hover:bg-zinc-950/70">
                                <td class="min-w-[340px] px-4 py-3">
                                    <div class="font-semibold text-zinc-950 dark:text-white">{{ trim($row->Paciente) ?: 'Sin paciente' }}</div>
                                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                        <span class="rounded-md bg-zinc-100 px-2 py-1 font-semibold text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100">N° Historia Clinica {{ $row->NroHistoriaClinica ?: '-' }}</span>
                                        <span class="rounded-md bg-white px-2 py-1 text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-700">{{ $row->TipoDocumento ?: 'Documento' }} {{ $row->NroDocumento ?: '-' }}</span>
                                    </div>
                                </td>
                                <td class="min-w-[300px] px-4 py-3">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ \Illuminate\Support\Carbon::parse($row->FechaIngreso)->format('d/m/Y') }} {{ substr((string) $row->HoraIngreso, 0, 5) }}</div>
                                    <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $row->Servicio ?: $row->Especialidad ?: 'Sin servicio' }}</div>
                                    <div class="mt-1 text-xs text-zinc-500">{{ $row->FuenteFinanciamiento ?: $row->FormaPago ?: 'Sin financiamiento' }}</div>
                                    @if ($row->Especialidad && $row->Servicio && $row->Especialidad !== $row->Servicio)
                                        <div class="mt-1 text-xs text-zinc-500">{{ $row->Especialidad }}</div>
                                    @endif
                                </td>
                                <td class="min-w-[280px] px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="rounded-md px-2 py-1 text-xs font-semibold ring-1 {{ $isManual ? 'bg-violet-50 text-violet-800 ring-violet-200 dark:bg-violet-950 dark:text-violet-100 dark:ring-violet-800' : 'bg-sky-50 text-sky-800 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800' }}">
                                            {{ $isManual ? 'SIS manual' : 'SIS normal' }}
                                        </span>
                                        <span class="rounded-md px-2 py-1 text-xs font-semibold ring-1 {{ $hasFua ? 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-800' : 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-800' }}">
                                            {{ $hasFua ? 'FUA generada' : 'FUA pendiente' }}
                                        </span>
                                        @if ((int) $row->IdSiaSis <= 0)
                                            <span class="rounded-md bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-800 ring-1 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800">Revisar afiliacion</span>
                                        @endif
                                    </div>
                                    <div class="mt-2 font-medium text-zinc-950 dark:text-white">{{ $hasFua ? trim($row->FuaNumero) : 'Aun sin numero FUA' }}</div>
                                    @if ($hasFua && $row->FuaAfiliacion)
                                        <div class="mt-1 text-xs text-zinc-500">Afiliacion {{ $row->FuaAfiliacion }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        @if ($row->IdCita)
                                            <flux:button :href="route('citas.show', $row->IdCita)" size="sm" variant="ghost">Ver cita</flux:button>
                                        @else
                                            <span class="text-xs text-zinc-400">Sin cita asociada</span>
                                        @endif
                                    </div>
                                    <details class="mt-3 text-left">
                                        <summary class="cursor-pointer text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100">Ver datos de revision</summary>
                                        <dl class="mt-2 grid gap-1 rounded-lg bg-zinc-50 p-3 text-xs text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-950 dark:text-zinc-300 dark:ring-zinc-800">
                                            <div class="flex justify-between gap-3"><dt>Cuenta de atencion</dt><dd class="font-medium">{{ $row->IdCuentaAtencion }}</dd></div>
                                            <div class="flex justify-between gap-3"><dt>Cita asociada</dt><dd class="font-medium">{{ $row->IdCita ?: '-' }}</dd></div>
                                            <div class="flex justify-between gap-3"><dt>Documento en FUA</dt><dd class="font-medium">{{ $row->FuaDocumentoTipo ?: '-' }} {{ $row->FuaDocumentoNumero ?: '' }}</dd></div>
                                            <div class="flex justify-between gap-3"><dt>Codigo prestacional</dt><dd class="font-medium">{{ $row->FuaCodigoPrestacion ?: '-' }}</dd></div>
                                            <div class="flex justify-between gap-3"><dt>UPS</dt><dd class="font-medium">{{ $row->FuaUPS ?: '-' }}</dd></div>
                                        </dl>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-14 text-center">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">Sin atenciones para el filtro seleccionado</div>
                                    <div class="mt-1 text-sm text-zinc-500">Ajusta el rango de fechas o limpia los filtros.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
                {{ $rows->links() }}
            </div>
        </section>
    </div>
</x-layouts::app>
