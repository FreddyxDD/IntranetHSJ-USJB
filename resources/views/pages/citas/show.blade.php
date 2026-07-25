<x-layouts::app :title="'Historia '.$cita->paciente?->NroHistoriaClinica">
    @php($paciente = $cita->paciente)
    @php($atencion = $cita->atencion)
    @php($formaPagoReal = \App\Support\SisFinancing::fullDescription($atencion))
    @php($formaPago = $judicialAppointment ? 'SIS-Judicial' : \App\Support\SisFinancing::label($cita))
    @php($fuenteFinanciamiento = $atencion?->fuenteFinanciamiento?->Descripcion ?: '-')
    @php($formaPagoKey = strtoupper((string) $formaPago))
    @php($formaPagoClass = $judicialAppointment ? 'bg-fuchsia-100 text-fuchsia-800 ring-fuchsia-200 dark:bg-fuchsia-950 dark:text-fuchsia-100 dark:ring-fuchsia-700' : (str_contains($formaPagoKey, 'MANUAL') ? 'bg-violet-100 text-violet-800 ring-violet-200 dark:bg-violet-950 dark:text-violet-100 dark:ring-violet-700' : (str_contains($formaPagoKey, 'SIS') ? 'bg-sky-100 text-sky-800 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-700' : (str_contains($formaPagoKey, 'SOAT') ? 'bg-violet-100 text-violet-800 ring-violet-200 dark:bg-violet-950 dark:text-violet-100 dark:ring-violet-700' : (str_contains($formaPagoKey, 'PARTICULAR') ? 'bg-zinc-100 text-zinc-800 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-100 dark:ring-zinc-700' : 'bg-stone-100 text-stone-800 ring-stone-200 dark:bg-stone-950 dark:text-stone-100 dark:ring-stone-700')))))
    @php($edadCalculada = $paciente?->FechaNacimiento ? $paciente->FechaNacimiento->age.' anos' : '-')
    @php($edadAtencion = $atencion?->Edad ? $atencion->Edad.' '.($atencion->IdTipoEdad == 1 ? 'anos' : 'unidad edad '.$atencion->IdTipoEdad) : '-')
    @php($hcFormateada = \App\Support\ClinicalHistoryNumber::format($paciente?->NroHistoriaClinica))
    @php($aplicaFua = \App\Support\FuaEligibility::appliesTo($cita))
    @php($tieneFuaGenerada = $aplicaFua && $sisFua !== null)
    @php($tipoDocumento = $paciente?->tipoDocumento?->Descripcion ?: 'Documento')
    @php($esSisManual = \App\Support\SisFinancing::isManual($atencion))
    @php($pacienteObservacion = trim((string) ($paciente?->Observacion ?? '')))
    @php($atencionObservacion = trim((string) ($atencionObservacion ?? '')))
    @php($registradorNombre = $creationAudit ? trim(collect([$creationAudit->ApellidoPaterno ?? null, $creationAudit->ApellidoMaterno ?? null, $creationAudit->Nombres ?? null])->filter()->implode(' ')) : '')
    @php($registradorPc = $creationAudit ? trim((string) ($creationAudit->nombrePc ?? '')) : '')
    @php($registradorFecha = $creationAudit?->FechaHora ? \Illuminate\Support\Carbon::parse($creationAudit->FechaHora)->format('d/m/Y H:i') : null)

    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="grid gap-0 lg:grid-cols-[280px_1fr]">
                <div class="bg-zinc-950 p-6 text-white dark:bg-white dark:text-zinc-950">
                    <div class="text-xs font-semibold uppercase tracking-wide opacity-70">N° Historia Clinica</div>
                    <div class="mt-2 text-5xl font-semibold tracking-tight">{{ $hcFormateada }}</div>
                    <div class="mt-3 text-sm opacity-75">Terminal {{ \Illuminate\Support\Str::after($hcFormateada, '-') }}</div>
                </div>
                <div class="flex flex-col gap-5 p-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ $paciente?->nombre_completo ?: 'Paciente sin datos' }}</div>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-md px-2 py-1 font-semibold ring-1 {{ $formaPagoClass }}">{{ $formaPago }}</span>
                            @if ($judicialAppointment)
                                <span class="rounded-md bg-white px-2 py-1 font-medium text-fuchsia-700 ring-1 ring-fuchsia-200 dark:bg-zinc-900 dark:text-fuchsia-200 dark:ring-fuchsia-800">Base {{ $formaPagoReal }} / {{ $fuenteFinanciamiento }}</span>
                            @elseif ($esSisManual)
                                <span class="rounded-md bg-white px-2 py-1 font-medium text-violet-700 ring-1 ring-violet-200 dark:bg-zinc-900 dark:text-violet-200 dark:ring-violet-800">Registro temporal/manual SIS</span>
                            @endif
                            <span class="rounded-md bg-zinc-50 px-2 py-1 font-medium text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-950 dark:text-zinc-200 dark:ring-zinc-700">{{ $cita->Fecha?->format('d/m/Y') }} | {{ trim($cita->HoraInicio) }} - {{ trim($cita->HoraFin) }}</span>
                            @if (! $aplicaFua)
                                <span class="rounded-md bg-zinc-100 px-2 py-1 font-medium text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700">No aplica FUA</span>
                            @elseif ($tieneFuaGenerada)
                                <span class="rounded-md bg-emerald-50 px-2 py-1 font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-800">FUA {{ $sisFua->FuaDisa }} {{ $sisFua->FuaLote }} {{ $sisFua->FuaNumero }}</span>
                            @else
                                <span class="rounded-md bg-amber-50 px-2 py-1 font-medium text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-800">{{ $esSisManual ? 'FUA pendiente SIS manual' : 'FUA pendiente' }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($tieneFuaGenerada)
                            <flux:button :href="route('citas.fua.excel', $cita->IdCita)" variant="primary">FUA Excel</flux:button>
                            <flux:button :href="route('citas.fua.pdf', $cita->IdCita)" variant="ghost">FUA PDF</flux:button>
                        @endif
                        <flux:button :href="route('citas.index', request()->query())" variant="ghost">Volver</flux:button>
                    </div>
                </div>
            </div>
            <div class="grid gap-0 border-t border-zinc-200 dark:border-zinc-800 lg:grid-cols-3">
                <section class="border-b border-zinc-200 p-5 dark:border-zinc-800 lg:border-b-0 lg:border-r">
                    <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">Cita</h2>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-zinc-500">Fecha</dt><dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cita->Fecha?->format('d/m/Y') ?: '-' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Horario</dt><dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ trim($cita->HoraInicio) }} - {{ trim($cita->HoraFin) }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Estado</dt><dd>{{ $cita->estado?->Descripcion ?: '-' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Adicional</dt><dd>{{ $cita->EsCitaAdicional ? 'Si' : 'No' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Especialidad</dt><dd>{{ $cita->especialidad?->Nombre ?: '-' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Servicio</dt><dd>{{ $cita->servicio?->Nombre ?: '-' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Fecha solicitud</dt><dd>{{ $cita->FechaSolicitud?->format('d/m/Y') }} {{ trim((string) $cita->HoraSolicitud) }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Horario programado</dt><dd>{{ trim((string) $cita->programacion?->HoraInicio) ?: '-' }} - {{ trim((string) $cita->programacion?->HoraFin) ?: '-' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs text-zinc-500">Programacion</dt><dd>{{ $cita->programacion?->Descripcion ?: 'Sin descripcion' }}</dd></div>
                        <div class="sm:col-span-2 rounded-md bg-zinc-50 p-3 ring-1 ring-zinc-200 dark:bg-zinc-950 dark:ring-zinc-800">
                            <dt class="text-xs text-zinc-500">Registrado por</dt>
                            <dd class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $registradorNombre !== '' ? $registradorNombre : 'No identificado' }}
                                @if ($creationAudit?->DNI)
                                    <span class="text-zinc-500">/ DNI {{ trim((string) $creationAudit->DNI) }}</span>
                                @endif
                            </dd>
                            <dd class="mt-1 text-xs text-zinc-500">
                                {{ $registradorFecha ? 'Registro: '.$registradorFecha : 'Sin fecha de auditoria' }}
                                @if ($registradorPc !== '')
                                    <span class="mx-1">|</span> Equipo {{ $registradorPc }}
                                @endif
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="border-b border-zinc-200 p-5 dark:border-zinc-800 lg:border-b-0 lg:border-r">
                    <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">Paciente</h2>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-zinc-500">Tipo documento</dt><dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $tipoDocumento }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Nro. documento</dt><dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $paciente?->NroDocumento ?: '-' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Edad</dt><dd>{{ $edadAtencion !== '-' ? $edadAtencion : $edadCalculada }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Nacimiento</dt><dd>{{ $paciente?->FechaNacimiento?->format('d/m/Y') ?: '-' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Telefono</dt><dd>{{ $paciente?->Telefono ?: '-' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs text-zinc-500">Direccion</dt><dd>{{ $paciente?->DireccionDomicilio ?: '-' }}</dd></div>
                    </dl>
                </section>

                <section class="p-5">
                    <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">Medico</h2>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div class="sm:col-span-2"><dt class="text-xs text-zinc-500">Nombre</dt><dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cita->medico?->empleado?->nombre_completo ?: '-' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Colegiatura</dt><dd>{{ trim((string) $cita->medico?->Colegiatura) ?: '-' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">RNE</dt><dd>{{ $cita->medico?->rne ?: '-' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs text-zinc-500">Financiamiento</dt><dd>{{ $formaPago }} @if ($formaPagoReal !== '-') <span class="text-zinc-500">({{ $formaPagoReal }})</span> @endif</dd></div>
                    </dl>
                </section>
            </div>
        </section>

        @if ($pacienteObservacion !== '' || $atencionObservacion !== '')
            <section class="rounded-lg border border-violet-200 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-950/30">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-violet-950 dark:text-violet-100">Observaciones</h2>
                        <p class="mt-1 text-xs text-violet-800/80 dark:text-violet-200/80">Dato operativo usado para validar afiliacion manual cuando el paciente no viene de SIS normal.</p>
                    </div>
                    <dl class="grid flex-1 gap-3 text-sm md:grid-cols-2">
                        @if ($pacienteObservacion !== '')
                            <div class="rounded-md bg-white/80 p-3 ring-1 ring-violet-200 dark:bg-zinc-950/50 dark:ring-violet-800">
                                <dt class="text-xs font-medium text-violet-700 dark:text-violet-200">Observacion del paciente</dt>
                                <dd class="mt-1 font-semibold text-violet-950 dark:text-violet-50">{{ $pacienteObservacion }}</dd>
                            </div>
                        @endif
                        @if ($atencionObservacion !== '')
                            <div class="rounded-md bg-white/80 p-3 ring-1 ring-violet-200 dark:bg-zinc-950/50 dark:ring-violet-800">
                                <dt class="text-xs font-medium text-violet-700 dark:text-violet-200">Observacion de la atencion</dt>
                                <dd class="mt-1 font-semibold text-violet-950 dark:text-violet-50">{{ $atencionObservacion }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </section>
        @endif

        @if (session('fua_success'))
            <div class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">
                {{ session('fua_success') }}
            </div>
        @endif

        @if (session('fua_error'))
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
                {{ session('fua_error') }}
            </div>
        @endif

        @if ($judicialAppointment)
            <section class="rounded-lg border border-fuchsia-200 bg-fuchsia-50 p-4 dark:border-fuchsia-900 dark:bg-fuchsia-950/30">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-fuchsia-950 dark:text-fuchsia-100">Caso SIS-Judicial</h2>
                        <p class="mt-1 text-sm text-fuchsia-800 dark:text-fuchsia-200">
                            {{ $judicialAppointment->case?->court_name ?: 'Juzgado no registrado' }} / {{ $judicialAppointment->case?->case_file_number ?: 'Sin expediente' }}
                        </p>
                    </div>
                    <flux:button :href="route('judicial-cases.index', ['q' => $paciente?->NroHistoriaClinica])" variant="ghost" wire:navigate>Ver seguimiento</flux:button>
                </div>
            </section>
        @endif

        <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col gap-1 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">Historial de citas del paciente</h2>
                    <p class="text-xs text-zinc-500">Ultimas {{ $appointmentHistory->count() }} citas registradas para esta historia clinica.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Servicio</th>
                            <th class="px-4 py-3">Medico</th>
                            <th class="px-4 py-3">Financiamiento</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-right">Accion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($appointmentHistory as $history)
                            @php($isCurrent = (int) $history->IdCita === (int) $cita->IdCita)
                            @php($historyPayment = \App\Support\SisFinancing::label($history))
                            <tr class="{{ $isCurrent ? 'bg-sky-50 dark:bg-sky-950/30' : '' }}">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $history->Fecha?->format('d/m/Y') ?: '-' }}</div>
                                    <div class="text-xs text-zinc-500">{{ trim((string) $history->HoraInicio) }} - {{ trim((string) $history->HoraFin) }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $history->especialidad?->Nombre ?: '-' }}</div>
                                    <div class="text-xs text-zinc-500">{{ $history->servicio?->Nombre ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $history->medico?->empleado?->nombre_completo ?: '-' }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $historyPayment }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-md bg-zinc-50 px-2 py-1 text-xs font-medium text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-950 dark:text-zinc-300 dark:ring-zinc-800">
                                        {{ $isCurrent ? 'Cita actual' : ($history->estado?->Descripcion ?: '-') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if (! $isCurrent)
                                        <flux:button :href="route('citas.show', $history->IdCita)" size="sm" variant="ghost">Ver</flux:button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-zinc-500">Sin historial de citas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts::app>
