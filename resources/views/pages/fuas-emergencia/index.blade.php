<x-layouts::app :title="__('FUA Emergencia')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-4 border-b border-zinc-200 pb-5 dark:border-zinc-700">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <flux:heading size="xl">FUA Emergencia</flux:heading>
                    <flux:text class="mt-1">FUAs creados en SIS para cuentas con producto 4598 / 99281.</flux:text>
                </div>

                <form method="GET" action="{{ route('fuas-emergencia.index') }}" class="grid w-full gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                    <div class="lg:col-span-1">
                        <flux:input name="fecha" label="Fecha" type="date" value="{{ $filters['fecha'] }}" />
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <flux:input name="q" label="Buscar" value="{{ $filters['q'] }}" placeholder="FUA, cuenta, DNI, historia, paciente, medico" />
                    </div>

                    <div class="flex gap-2 lg:col-span-1">
                        <flux:button type="submit" variant="primary">Filtrar</flux:button>
                        <flux:button :href="route('fuas-emergencia.index')" variant="ghost">Hoy</flux:button>
                    </div>
                </form>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">Fecha consultada</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{{ \Illuminate\Support\Carbon::parse($filters['fecha'])->format('d/m/Y') }}</div>
                <div class="text-xs text-zinc-500">Actualizado {{ $refreshedAt->format('H:i') }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">FUAs emergencia</div>
                <div class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{{ number_format($total) }}</div>
                <div class="text-xs text-zinc-500">Producto 4598 / 99281</div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="overflow-x-auto bg-white dark:bg-zinc-800">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">FUA</th>
                            <th class="px-4 py-3">Cuenta</th>
                            <th class="px-4 py-3">Atencion</th>
                            <th class="px-4 py-3">Paciente</th>
                            <th class="px-4 py-3">Producto</th>
                            <th class="px-4 py-3">Medico</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($fuas as $fua)
                            <tr class="align-top hover:bg-zinc-50 dark:hover:bg-zinc-900/60">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="font-semibold text-zinc-900 dark:text-zinc-50">{{ $fua->FuaDisa }} {{ $fua->FuaLote }} {{ $fua->FuaNumero }}</div>
                                    <div class="text-xs text-zinc-500">Formato {{ $fua->FuaVersionFormato ?: '-' }} / Anexo {{ $fua->FuaTipoAnexo2015 ?: '-' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-50">{{ $fua->idCuentaAtencion }}</div>
                                    <div class="text-xs text-zinc-500">{{ $fua->EstadoCuenta ?: 'Sin estado' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-50">{{ $fua->FuaAtencionFecha }} {{ $fua->FuaAtencionHora }}</div>
                                    <div class="text-xs text-zinc-500">Atencion {{ $fua->IdAtencion }} / UPS {{ $fua->FuaUPS ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-50">
                                        {{ trim($fua->Apaterno.' '.$fua->Amaterno.' '.$fua->Pnombre.' '.$fua->Onombre) }}
                                    </div>
                                    <div class="mt-1 flex flex-wrap gap-2 text-xs">
                                        <span class="rounded-md bg-white px-2 py-1 font-medium text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700">N° Historia Clinica {{ $fua->FuaNrohistoria ?: $fua->NroHistoriaClinica }}</span>
                                        <span class="px-2 py-1 text-zinc-500">Doc: {{ $fua->DocumentoNumero ?: $fua->NroDocumento }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-50">{{ $fua->ProductoCodigo }} - {{ $fua->ProductoNombre }}</div>
                                    <div class="text-xs text-zinc-500">{{ $fua->ProductoNombreMinsa ?: 'Sin nombre MINSA' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-50">{{ $fua->FuaMedico ?: '-' }}</div>
                                    <div class="text-xs text-zinc-500">DNI {{ trim((string) $fua->FuaMedicoDNI) ?: '-' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-zinc-500">
                                    No se encontraron FUAs de emergencia para la fecha consultada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts::app>
