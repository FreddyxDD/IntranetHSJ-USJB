<x-layouts::app :title="__('Historial de lotes FUA')">
    @php($statusClasses = [
        'pending' => 'bg-zinc-100 text-zinc-700 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-200 dark:ring-zinc-700',
        'processing' => 'bg-sky-100 text-sky-800 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-700',
        'finished' => 'bg-emerald-100 text-emerald-800 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-700',
        'finished_with_observations' => 'bg-amber-100 text-amber-800 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-700',
        'failed' => 'bg-rose-100 text-rose-800 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-700',
        'cancelled' => 'bg-zinc-100 text-zinc-700 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-200 dark:ring-zinc-700',
    ])

    <div class="hs-page-shell">
        <section class="hs-panel p-5 lg:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="hs-soft-badge bg-teal-50 text-teal-700 ring-teal-200 dark:bg-teal-950 dark:text-teal-100 dark:ring-teal-800">FUA SIS</span>
                        <span class="hs-soft-badge bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800">Trazabilidad de impresion</span>
                    </div>
                    <flux:heading size="xl">Historial de lotes FUA</flux:heading>
                    <flux:text class="mt-1 max-w-3xl">Consulta los lotes enviados a cola, las FUAS generadas, impresas, observadas o con error.</flux:text>
                </div>

                <flux:button :href="route('citas.index')" variant="ghost">Volver a citas</flux:button>
            </div>

            <form method="GET" action="{{ route('citas.fua.print-batches.index') }}" class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                <label class="grid gap-2 text-sm lg:col-span-3">
                    <span class="text-zinc-700 dark:text-zinc-300">Estado</span>
                    <select name="estado" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['estado'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="lg:col-span-6">
                    <flux:input name="q" label="Buscar" value="{{ $filters['q'] }}" placeholder="Lote, paciente, documento, historia o FUA" />
                </div>

                <div class="flex gap-2 lg:col-span-3">
                    <flux:button type="submit" variant="primary">Filtrar</flux:button>
                    <flux:button :href="route('citas.fua.print-batches.index')" variant="ghost">Limpiar</flux:button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                <div class="text-sm font-semibold text-zinc-950 dark:text-white">Lotes registrados</div>
                <div class="text-xs text-zinc-500">Mostrando {{ $batches->firstItem() ?? 0 }}-{{ $batches->lastItem() ?? 0 }} de {{ $batches->total() }} lotes.</div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">Lote</th>
                            <th class="px-4 py-3">Usuario</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Resultado</th>
                            <th class="px-4 py-3">Fechas</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($batches as $batch)
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="font-semibold text-zinc-950 dark:text-white">#{{ $batch->id }}</div>
                                    <div class="text-xs text-zinc-500">{{ $batch->actionLabel() }}</div>
                                    <div class="text-xs text-zinc-500">{{ strtoupper($batch->output_format) }} / {{ $batch->printModeLabel() }} / {{ $batch->printer_name ?: config('fua.print.default_printer', 'Predeterminada') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $batch->user?->name ?: 'Sin usuario' }}</div>
                                    <div class="text-xs text-zinc-500">{{ $batch->user?->email ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-md px-2 py-1 text-xs font-semibold ring-1 {{ $statusClasses[$batch->status] ?? 'bg-zinc-100 text-zinc-700 ring-zinc-200' }}">{{ $batch->statusLabel() }}</span>
                                    <div class="mt-2 h-2 w-36 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <div class="h-full rounded-full bg-sky-600" style="width: {{ $batch->progressPercentage() }}%"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="grid min-w-72 grid-cols-5 gap-2 text-center text-xs">
                                        <div class="rounded-md bg-zinc-50 p-2 dark:bg-zinc-950"><div class="font-semibold">{{ $batch->total }}</div><div class="text-zinc-500">Total</div></div>
                                        <div class="rounded-md bg-sky-50 p-2 text-sky-800 dark:bg-sky-950 dark:text-sky-100"><div class="font-semibold">{{ $batch->processed }}</div><div>Proc.</div></div>
                                        <div class="rounded-md bg-emerald-50 p-2 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-100"><div class="font-semibold">{{ $batch->printed }}</div><div>Imp.</div></div>
                                        <div class="rounded-md bg-amber-50 p-2 text-amber-800 dark:bg-amber-950 dark:text-amber-100"><div class="font-semibold">{{ $batch->observed }}</div><div>Obs.</div></div>
                                        <div class="rounded-md bg-rose-50 p-2 text-rose-800 dark:bg-rose-950 dark:text-rose-100"><div class="font-semibold">{{ $batch->errors }}</div><div>Err.</div></div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                    <div>Creado {{ $batch->created_at?->format('d/m/Y H:i') }}</div>
                                    <div class="text-xs text-zinc-500">Fin {{ $batch->finished_at?->format('d/m/Y H:i') ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <flux:button :href="route('citas.fua.print-batches.show', $batch)" size="sm" variant="primary">Ver detalle</flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-zinc-500">No hay lotes con los filtros seleccionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
                {{ $batches->links() }}
            </div>
        </section>
    </div>
</x-layouts::app>
