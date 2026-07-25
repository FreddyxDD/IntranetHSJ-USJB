<x-layouts::app :title="__('Simulacion FUA')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-3 border-b border-zinc-200 pb-5 dark:border-zinc-700 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <flux:heading size="xl">Simulacion de generacion FUA</flux:heading>
                <flux:text class="mt-1">Validacion previa sin ejecutar SP, sin reservar correlativo y sin escribir en SIGH.</flux:text>
            </div>
            <flux:button :href="route('citas.index')" variant="ghost">Volver a citas</flux:button>
        </div>

        <div class="rounded-lg border border-sky-200 bg-sky-50/80 p-4 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950/40 dark:text-sky-100">
            Esta pantalla valida los datos antes de generar. El numero simulado usa el ultimo FUA real encontrado para {{ $disa }}-{{ $lote }} y no queda reservado hasta ejecutar la generacion real.
            @if (config('fua.real_generation.enabled'))
                <span class="font-semibold">La generacion real esta habilitada para usuarios autorizados.</span>
            @else
                <span class="font-semibold">La generacion real esta deshabilitada por configuracion.</span>
            @endif
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-100">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">Seleccionadas</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{{ number_format($summary['total']) }}</div>
            </div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                <div class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-200">Generarian</div>
                <div class="mt-1 text-2xl font-semibold text-emerald-900 dark:text-emerald-100">{{ number_format($summary['generaria']) }}</div>
            </div>
            <div class="rounded-lg border border-blue-200 bg-blue-50/70 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                <div class="text-xs font-medium uppercase tracking-wide text-blue-700 dark:text-blue-200">Ya tienen FUA</div>
                <div class="mt-1 text-2xl font-semibold text-blue-900 dark:text-blue-100">{{ number_format($summary['ya_existe']) }}</div>
            </div>
            <div class="rounded-lg border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                <div class="text-xs font-medium uppercase tracking-wide text-amber-700 dark:text-amber-200">Observadas</div>
                <div class="mt-1 text-2xl font-semibold text-amber-900 dark:text-amber-100">{{ number_format($summary['observado']) }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">No aplica</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{{ number_format($summary['no_aplica']) }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">Ultimo real</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{{ $ultimo_numero_real ?: '-' }}</div>
                <div class="text-xs text-zinc-500">{{ $generated_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Paciente</th>
                        <th class="px-4 py-3">Atencion</th>
                        <th class="px-4 py-3">SIS</th>
                        <th class="px-4 py-3">Resultado</th>
                        <th class="px-4 py-3">FUA</th>
                        <th class="px-4 py-3">Motivo</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($items as $item)
                        @php($stateClass = match ($item['estado']) {
                            'generaria' => 'bg-emerald-50/80 dark:bg-emerald-950/30',
                            'ya_existe' => 'bg-blue-50/80 dark:bg-blue-950/30',
                            'observado' => 'bg-amber-50/80 dark:bg-amber-950/25',
                            default => 'bg-zinc-50/80 dark:bg-zinc-900',
                        })
                        @php($badgeClass = match ($item['estado']) {
                            'generaria' => 'bg-emerald-100 text-emerald-800 ring-emerald-200 dark:bg-emerald-900 dark:text-emerald-100 dark:ring-emerald-700',
                            'ya_existe' => 'bg-blue-100 text-blue-800 ring-blue-200 dark:bg-blue-900 dark:text-blue-100 dark:ring-blue-700',
                            'observado' => 'bg-amber-100 text-amber-800 ring-amber-200 dark:bg-amber-900 dark:text-amber-100 dark:ring-amber-700',
                            default => 'bg-zinc-100 text-zinc-800 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-100 dark:ring-zinc-700',
                        })
                        <tr class="align-top {{ $stateClass }}">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-zinc-900 dark:text-zinc-50">{{ $item['paciente'] }}</div>
                                <div class="mt-1 flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-md bg-white px-2 py-1 font-medium text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700">N° Historia Clinica {{ $item['historia'] }}</span>
                                    <span class="px-2 py-1 text-zinc-500">Doc: {{ $item['documento'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-zinc-50">{{ $item['hora'] }} / Cuenta {{ $item['cuenta'] ?: '-' }}</div>
                                <div class="text-xs text-zinc-500">{{ $item['especialidad'] }} - {{ $item['servicio'] }}</div>
                                <div class="text-xs text-zinc-500">{{ $item['forma_pago'] }} / {{ $item['fuente_financiamiento'] }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-zinc-50">{{ $item['tipo_sis'] }}</div>
                                <div class="text-xs text-zinc-500">IdSiaSis {{ $item['id_siasis'] ?: '-' }}</div>
                                <div class="text-xs text-zinc-500">Prestacion {{ $item['codigo_prestacion'] ?: '-' }}</div>
                                @if ($item['observacion'])
                                    <div class="mt-2 rounded-md bg-white px-2 py-1 text-xs text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700">
                                        Obs: {{ $item['observacion'] }}
                                    </div>
                                @endif
                                @if ($item['tipo_sis'] === 'SIS manual' && $item['afiliacion_manual']['numero'])
                                    <div class="mt-1 text-xs font-medium text-emerald-700 dark:text-emerald-200">
                                        Afiliacion propuesta: {{ $item['afiliacion_manual']['diresa'] }} - {{ $item['afiliacion_manual']['tipo_formato'] }} - {{ $item['afiliacion_manual']['numero'] }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-md px-2 py-1 text-xs font-semibold ring-1 {{ $badgeClass }}">{{ $item['etiqueta'] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($item['fua_existente'])
                                    <div class="font-semibold text-blue-900 dark:text-blue-100">
                                        {{ $item['fua_existente']->FuaDisa }} {{ $item['fua_existente']->FuaLote }} {{ $item['fua_existente']->FuaNumero }}
                                    </div>
                                    <div class="text-xs text-zinc-500">Registro real existente</div>
                                @elseif ($item['numero_simulado'])
                                    <div class="font-semibold text-emerald-900 dark:text-emerald-100">{{ $disa }} {{ $lote }} {{ $item['numero_simulado'] }}</div>
                                    <div class="text-xs text-zinc-500">Simulado, no reservado</div>
                                @else
                                    <span class="text-zinc-500">-</span>
                                @endif
                            </td>
                            <td class="max-w-xl px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                {{ $item['motivo'] }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($item['estado'] === 'generaria' && auth()->user()->hasPermission('citas.fua.generate_real') && config('fua.real_generation.enabled'))
                                    <form
                                        method="POST"
                                        action="{{ route('citas.fua.generate-real', $item['cita_id']) }}"
                                        onsubmit="const code = prompt('Esta accion genera una FUA real y reserva correlativo. Escriba GENERAR para continuar.'); if (code !== 'GENERAR') return false; this.querySelector('[name=confirmation_code]').value = code; return true;"
                                    >
                                        @csrf
                                        <input type="hidden" name="confirmation_code" value="">
                                        <flux:button type="submit" size="sm" variant="primary">Generar real</flux:button>
                                    </form>
                                @elseif ($item['tipo_sis'] === 'SIS manual')
                                    <flux:button :href="route('citas.fua.manual-excel', $item['cita_id'])" size="sm" variant="primary">FUA manual</flux:button>
                                @else
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
