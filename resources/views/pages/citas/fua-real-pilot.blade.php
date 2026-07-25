<x-layouts::app :title="'Piloto FUA real'">
    <div class="hs-page-shell">
        <section class="hs-panel p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="text-sm font-semibold text-[var(--primary)]">FUA SIS</div>
                    <flux:heading size="xl" class="mt-2">Piloto de generacion real</flux:heading>
                    <flux:text class="mt-2">
                        Controla las FUAS generadas desde el aplicativo, con usuario, cita, cuenta, correlativo y resultado.
                    </flux:text>
                </div>

                <form method="GET" action="{{ route('citas.fua.real-pilot.index') }}" class="grid w-full gap-3 md:grid-cols-5 lg:w-[860px] lg:items-end">
                    <flux:input type="date" name="fecha_desde" label="Desde" value="{{ $filters['fecha_desde'] }}" />
                    <flux:input type="date" name="fecha_hasta" label="Hasta" value="{{ $filters['fecha_hasta'] }}" />
                    <label class="grid gap-2 text-sm">
                        <span class="text-[var(--foreground)]">Estado</span>
                        <select name="status" class="h-10 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 text-sm text-[var(--foreground)]">
                            <option value="">Todos</option>
                            <option value="success" @selected($filters['status'] === 'success')>Generada</option>
                            <option value="failed" @selected($filters['status'] === 'failed')>Observada</option>
                        </select>
                    </label>
                    <div class="md:col-span-2">
                        <flux:input name="q" label="Buscar" value="{{ $filters['q'] }}" placeholder="Paciente, documento, cuenta o FUA" />
                    </div>
                    <div class="flex gap-2 md:col-span-5">
                        <flux:button type="submit" variant="primary">Aplicar filtros</flux:button>
                        <flux:button :href="route('citas.fua.real-pilot.index')" variant="ghost">Hoy</flux:button>
                    </div>
                </form>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="hs-panel p-4">
                <div class="text-xs font-semibold uppercase text-[var(--muted-foreground)]">Intentos</div>
                <div class="mt-2 text-3xl font-semibold text-[var(--foreground)]">{{ number_format($summary['total']) }}</div>
                <p class="mt-1 text-xs text-[var(--muted-foreground)]">Generaciones ejecutadas desde el aplicativo.</p>
            </article>
            <article class="hs-panel border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                <div class="text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-200">Generadas</div>
                <div class="mt-2 text-3xl font-semibold text-emerald-900 dark:text-emerald-100">{{ number_format($summary['success']) }}</div>
                <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-200">Insertadas en SIS y con correlativo reservado.</p>
            </article>
            <article class="hs-panel border-rose-200 bg-rose-50/70 p-4 dark:border-rose-900 dark:bg-rose-950/30">
                <div class="text-xs font-semibold uppercase text-rose-700 dark:text-rose-200">Observadas</div>
                <div class="mt-2 text-3xl font-semibold text-rose-900 dark:text-rose-100">{{ number_format($summary['failed']) }}</div>
                <p class="mt-1 text-xs text-rose-700 dark:text-rose-200">Bloqueadas por validacion o error controlado.</p>
            </article>
        </section>

        <section class="hs-panel overflow-hidden">
            <div class="flex flex-col gap-2 border-b border-[var(--border)] p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-[var(--foreground)]">Bitacora del piloto</h2>
                    <p class="mt-1 text-sm text-[var(--muted-foreground)]">Usa esta vista para comparar lo generado aqui contra el sistema actual.</p>
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
                    La generacion real solo debe ejecutarse con cita SIS validada y autorizacion operativa.
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr>
                            <th class="px-5 py-4">Fecha</th>
                            <th class="px-5 py-4">Paciente</th>
                            <th class="px-5 py-4">Atencion</th>
                            <th class="px-5 py-4">FUA</th>
                            <th class="px-5 py-4">Usuario</th>
                            <th class="px-5 py-4">Resultado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($logs as $log)
                            @php($isSuccess = $log->status === \App\Models\FuaRealGenerationLog::STATUS_SUCCESS)
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-5 py-4 text-xs text-[var(--muted-foreground)]">
                                    <div>{{ $log->created_at?->format('d/m/Y H:i:s') }}</div>
                                    <div class="mt-1">IP {{ $log->ip_address ?: '-' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-[var(--foreground)]">{{ $log->paciente ?: '-' }}</div>
                                    <div class="mt-1 flex flex-wrap gap-2 text-xs">
                                        <span class="rounded-md bg-[var(--muted)] px-2 py-1 font-medium text-[var(--foreground)]">N° Historia Clinica {{ $log->historia_clinica ?: '-' }}</span>
                                        <span class="rounded-md bg-[var(--muted)] px-2 py-1 text-[var(--muted-foreground)]">Doc {{ $log->documento ?: '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-[var(--foreground)]">Cuenta {{ $log->cuenta_atencion_id ?: '-' }}</div>
                                    <div class="mt-1 text-xs text-[var(--muted-foreground)]">Cita {{ $log->cita_id ?: '-' }} / Atencion {{ $log->atencion_id ?: '-' }}</div>
                                    <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $log->especialidad ?: '-' }} / {{ $log->servicio ?: '-' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    @if ($log->fua_number)
                                        <div class="font-semibold text-emerald-700 dark:text-emerald-200">{{ $log->fua_number }}</div>
                                    @else
                                        <span class="text-[var(--muted-foreground)]">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-[var(--foreground)]">{{ $log->user?->name ?: 'Sistema' }}</div>
                                    <div class="text-xs text-[var(--muted-foreground)]">{{ $log->user?->email }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-md px-2 py-1 text-xs font-semibold ring-1 {{ $isSuccess ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-800' : 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800' }}">
                                        {{ $isSuccess ? 'Generada' : 'Observada' }}
                                    </span>
                                    <div class="mt-2 max-w-xl text-xs leading-5 text-[var(--muted-foreground)]">{{ $log->message ?: '-' }}</div>
                                    @if ($log->validation_errors)
                                        <details class="mt-2">
                                            <summary class="cursor-pointer text-xs font-semibold text-[var(--primary)]">Ver validacion</summary>
                                            <pre class="mt-2 max-h-44 overflow-auto rounded-lg bg-[var(--muted)] p-3 text-[11px] text-[var(--foreground)]">{{ json_encode($log->validation_errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-[var(--muted-foreground)]">No hay generaciones reales en el filtro seleccionado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-[var(--border)] px-5 py-4">{{ $logs->links() }}</div>
        </section>
    </div>
</x-layouts::app>
