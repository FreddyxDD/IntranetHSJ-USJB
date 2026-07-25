<x-layouts::app :title="__('Lote de impresion FUA')">
    @php($progress = $batch->progressPercentage())
    @php($statusClasses = [
        'pending' => 'bg-zinc-100 text-zinc-700 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-200 dark:ring-zinc-700',
        'processing' => 'bg-sky-100 text-sky-800 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-700',
        'finished' => 'bg-emerald-100 text-emerald-800 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-700',
        'finished_with_observations' => 'bg-amber-100 text-amber-800 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-700',
        'failed' => 'bg-rose-100 text-rose-800 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-700',
        'cancelled' => 'bg-zinc-100 text-zinc-700 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-200 dark:ring-zinc-700',
    ][$batch->status] ?? 'bg-zinc-100 text-zinc-700 ring-zinc-200')

    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="xl">Lote FUA #{{ $batch->id }}</flux:heading>
                        <span id="batch-status" class="rounded-md px-2 py-1 text-xs font-semibold ring-1 {{ $statusClasses }}">{{ $batch->statusLabel() }}</span>
                    </div>
                    <flux:text class="mt-1">Proceso asincrono: reimprime FUAS existentes y genera automaticamente las pendientes aptas antes de enviarlas a cola.</flux:text>
                    <div id="batch-message" class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $batch->last_message }}</div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button :href="$returnUrl ?: route('citas.index')" variant="ghost">Volver a citas</flux:button>
                    <flux:button :href="route('citas.fua.print-batches.index')" variant="ghost">Historial de lotes</flux:button>
                    <flux:button :href="route('citas.fua.print-batches.show', array_filter(['batch' => $batch->id, 'return_url' => $returnUrl]))" variant="primary">Actualizar</flux:button>
                </div>
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between text-sm">
                    <span id="batch-current" class="font-medium text-zinc-800 dark:text-zinc-100">{{ $batch->current_item ?: 'Esperando procesamiento' }}</span>
                    <span id="batch-progress-label" class="text-zinc-500">{{ $batch->processed }} / {{ $batch->total }} procesadas</span>
                </div>
                <div class="mt-2 h-3 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <div id="batch-progress-bar" class="h-full rounded-full bg-sky-600 transition-all" style="width: {{ $progress }}%"></div>
                </div>
            </div>
        </section>

        <section class="grid gap-3 md:grid-cols-3 xl:grid-cols-7">
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">Accion</div>
                <div class="mt-1 text-lg font-semibold text-zinc-950 dark:text-white">{{ $batch->actionLabel() }}</div>
                <div class="text-xs text-zinc-500">{{ strtoupper($batch->output_format) }} / {{ $batch->printModeLabel() }} / {{ $batch->printer_name ?: config('fua.print.default_printer', 'Cola local') }}</div>
                @if ($batch->consolidated_file)
                    <div class="mt-1 truncate text-[11px] text-zinc-400">{{ $batch->consolidated_file }}</div>
                @endif
            </div>
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-xs font-medium uppercase tracking-wide text-zinc-500">Total</div>
                <div id="metric-total" class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-white">{{ $batch->total }}</div>
            </div>
            <div class="rounded-lg border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950/30">
                <div class="text-xs font-medium uppercase tracking-wide text-sky-700 dark:text-sky-200">Procesadas</div>
                <div id="metric-processed" class="mt-1 text-2xl font-semibold text-sky-950 dark:text-sky-100">{{ $batch->processed }}</div>
            </div>
            <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-900 dark:bg-indigo-950/30">
                <div class="text-xs font-medium uppercase tracking-wide text-indigo-700 dark:text-indigo-200">Existentes</div>
                <div id="metric-existing" class="mt-1 text-2xl font-semibold text-indigo-950 dark:text-indigo-100">{{ $batch->existing }}</div>
            </div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                <div class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-200">Impresas</div>
                <div id="metric-printed" class="mt-1 text-2xl font-semibold text-emerald-950 dark:text-emerald-100">{{ $batch->printed }}</div>
            </div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                <div class="text-xs font-medium uppercase tracking-wide text-amber-700 dark:text-amber-200">Observadas</div>
                <div id="metric-observed" class="mt-1 text-2xl font-semibold text-amber-950 dark:text-amber-100">{{ $batch->observed }}</div>
            </div>
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 dark:border-rose-900 dark:bg-rose-950/30">
                <div class="text-xs font-medium uppercase tracking-wide text-rose-700 dark:text-rose-200">Errores</div>
                <div id="metric-errors" class="mt-1 text-2xl font-semibold text-rose-950 dark:text-rose-100">{{ $batch->errors }}</div>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                <div class="text-sm font-semibold text-zinc-950 dark:text-white">Detalle del lote</div>
                <div class="text-xs text-zinc-500">Las FUAS pendientes aptas se generan automaticamente. Las no aptas quedan observadas para revision.</div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">Paciente</th>
                            <th class="px-4 py-3">Cuenta</th>
                            <th class="px-4 py-3">FUA</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Observacion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($batch->items as $item)
                            @php($itemClass = match ($item->final_status) {
                                'printed' => 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-800',
                                'existing' => 'bg-indigo-50 text-indigo-800 ring-indigo-200 dark:bg-indigo-950 dark:text-indigo-100 dark:ring-indigo-800',
                                'observed', 'not_applicable' => 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-800',
                                'error' => 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800',
                                default => 'bg-zinc-50 text-zinc-700 ring-zinc-200 dark:bg-zinc-950 dark:text-zinc-300 dark:ring-zinc-800',
                            })
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-zinc-950 dark:text-white">{{ $item->paciente ?: 'Sin paciente' }}</div>
                                    <div class="mt-1 flex flex-wrap gap-2 text-xs text-zinc-500">
                                        <span>N° Historia Clinica {{ $item->historia_clinica ?: '-' }}</span>
                                        <span>Doc {{ $item->documento ?: '-' }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                    {{ $item->cuenta_atencion_id ?: '-' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $item->fua_number ?: '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-md px-2 py-1 text-xs font-semibold ring-1 {{ $itemClass }}">{{ $item->finalStatusLabel() }}</span>
                                </td>
                                <td class="min-w-[320px] px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                    {{ $item->observation ?: '-' }}
                                    @if ($item->generated_file)
                                        <div class="mt-1 text-xs text-zinc-400">{{ $item->generated_file }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const statusUrl = @json(route('citas.fua.print-batches.status', $batch));
                const progressBar = document.getElementById('batch-progress-bar');
                const progressLabel = document.getElementById('batch-progress-label');
                const current = document.getElementById('batch-current');
                const message = document.getElementById('batch-message');
                const status = document.getElementById('batch-status');
                const metrics = {
                    total: document.getElementById('metric-total'),
                    processed: document.getElementById('metric-processed'),
                    existing: document.getElementById('metric-existing'),
                    printed: document.getElementById('metric-printed'),
                    observed: document.getElementById('metric-observed'),
                    errors: document.getElementById('metric-errors'),
                };

                const poll = async () => {
                    const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                    const data = await response.json();

                    progressBar.style.width = `${data.progress}%`;
                    progressLabel.textContent = `${data.processed} / ${data.total} procesadas`;
                    current.textContent = data.current_item || 'Sin item en proceso';
                    message.textContent = data.last_message || '';
                    status.textContent = data.status_label;
                    metrics.total.textContent = data.total;
                    metrics.processed.textContent = data.processed;
                    metrics.existing.textContent = data.existing;
                    metrics.printed.textContent = data.printed;
                    metrics.observed.textContent = data.observed;
                    metrics.errors.textContent = data.errors;

                    if (data.finished) {
                        window.setTimeout(() => window.location.reload(), 1200);
                        return;
                    }

                    window.setTimeout(poll, 3000);
                };

                @if (! in_array($batch->status, ['finished', 'finished_with_observations', 'failed', 'cancelled'], true))
                    window.setTimeout(poll, 3000);
                @endif
            });
        </script>
    </div>
</x-layouts::app>
