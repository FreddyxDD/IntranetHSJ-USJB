<x-layouts::app :title="'Auditoria'">
    <div class="hs-page-shell">
        <section class="hs-panel p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="text-sm font-semibold text-[var(--primary)]">Administracion</div>
                    <flux:heading size="xl" class="mt-2">Auditoria y trazabilidad</flux:heading>
                    <flux:text class="mt-2">Mide uso del aplicativo, productividad operativa y cambios administrativos con antes y despues.</flux:text>
                </div>

                <form method="GET" action="{{ route('audit.index') }}" class="grid w-full gap-3 md:grid-cols-5 lg:w-[860px] lg:items-end">
                    <flux:input type="date" name="fecha_desde" label="Desde" value="{{ $filters['fecha_desde'] }}" />
                    <flux:input type="date" name="fecha_hasta" label="Hasta" value="{{ $filters['fecha_hasta'] }}" />
                    <label class="grid gap-2 text-sm">
                        <span class="text-[var(--foreground)]">Modulo</span>
                        <select name="module" class="h-10 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 text-sm text-[var(--foreground)]">
                            <option value="">Todos</option>
                            @foreach ($modules as $module)
                                <option value="{{ $module }}" @selected($filters['module'] === $module)>{{ $module }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-2 text-sm">
                        <span class="text-[var(--foreground)]">Accion</span>
                        <select name="action" class="h-10 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 text-sm text-[var(--foreground)]">
                            <option value="">Todas</option>
                            @foreach ($actions as $action)
                                <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $action }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary">Filtrar</flux:button>
                        <flux:button :href="route('audit.index')" variant="ghost">Hoy</flux:button>
                    </div>
                    <div class="md:col-span-5">
                        <flux:input name="q" label="Buscar" value="{{ $filters['q'] }}" placeholder="Usuario, ruta, modulo o accion" />
                    </div>
                </form>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <article class="hs-panel p-4">
                <div class="text-xs font-semibold uppercase text-[var(--muted-foreground)]">Eventos</div>
                <div class="mt-2 text-3xl font-semibold text-[var(--foreground)]">{{ number_format($totals['events']) }}</div>
            </article>
            <article class="hs-panel p-4">
                <div class="text-xs font-semibold uppercase text-[var(--muted-foreground)]">Vistas</div>
                <div class="mt-2 text-3xl font-semibold text-[var(--foreground)]">{{ number_format($totals['page_views']) }}</div>
            </article>
            <article class="hs-panel p-4">
                <div class="text-xs font-semibold uppercase text-[var(--muted-foreground)]">Vista citas</div>
                <div class="mt-2 text-3xl font-semibold text-[var(--foreground)]">{{ number_format($totals['citas_views']) }}</div>
            </article>
            <article class="hs-panel p-4">
                <div class="text-xs font-semibold uppercase text-[var(--muted-foreground)]">FUAs procesadas</div>
                <div class="mt-2 text-3xl font-semibold text-[var(--foreground)]">{{ number_format($totals['fua_prints']) }}</div>
            </article>
            <article class="hs-panel p-4">
                <div class="text-xs font-semibold uppercase text-[var(--muted-foreground)]">Cambios</div>
                <div class="mt-2 text-3xl font-semibold text-[var(--foreground)]">{{ number_format($totals['changes']) }}</div>
            </article>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <article class="hs-panel p-5">
                <h2 class="text-lg font-semibold text-[var(--foreground)]">Uso por modulo</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($byModule as $row)
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="font-medium text-[var(--foreground)]">{{ $row->module }}</span>
                                <span class="text-[var(--muted-foreground)]">{{ $row->total }}</span>
                            </div>
                            <div class="mt-1 h-2 rounded-full bg-[var(--muted)]">
                                <div class="h-2 rounded-full bg-[var(--primary)]" style="width: {{ max(4, min(100, ($row->total / max(1, $totals['events'])) * 100)) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-[var(--muted-foreground)]">Sin registros en el filtro.</div>
                    @endforelse
                </div>
            </article>

            <article class="hs-panel p-5">
                <h2 class="text-lg font-semibold text-[var(--foreground)]">Usuarios con mayor actividad</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($byUser as $row)
                        <div class="flex items-center justify-between rounded-lg bg-[var(--muted)] px-3 py-2">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-[var(--foreground)]">{{ $row->user?->name ?: 'Usuario eliminado' }}</div>
                                <div class="truncate text-xs text-[var(--muted-foreground)]">{{ $row->user?->email }}</div>
                            </div>
                            <span class="rounded-md bg-[var(--background)] px-2 py-1 text-xs font-semibold text-[var(--primary)] ring-1 ring-[var(--border)]">{{ $row->total }}</span>
                        </div>
                    @empty
                        <div class="text-sm text-[var(--muted-foreground)]">Sin registros en el filtro.</div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="hs-panel overflow-hidden">
            <div class="border-b border-[var(--border)] p-5">
                <h2 class="text-lg font-semibold text-[var(--foreground)]">Detalle de eventos</h2>
                <p class="mt-1 text-sm text-[var(--muted-foreground)]">Incluye navegacion, impresiones, simulaciones y cambios administrativos.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr>
                            <th class="px-5 py-4">Fecha</th>
                            <th class="px-5 py-4">Usuario</th>
                            <th class="px-5 py-4">Modulo</th>
                            <th class="px-5 py-4">Accion</th>
                            <th class="px-5 py-4">Detalle</th>
                            <th class="px-5 py-4 text-right">Antes / despues</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($events as $event)
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-5 py-4 text-xs text-[var(--muted-foreground)]">{{ $event->occurred_at?->format('d/m/Y H:i:s') }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-[var(--foreground)]">{{ $event->user?->name ?: 'Sistema' }}</div>
                                    <div class="text-xs text-[var(--muted-foreground)]">{{ $event->ip_address }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-md bg-[var(--muted)] px-2 py-1 text-xs font-semibold text-[var(--foreground)]">{{ $event->module }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-[var(--foreground)]">{{ $event->action }}</div>
                                    <div class="text-xs text-[var(--muted-foreground)]">{{ $event->event_type }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="max-w-xl truncate text-[var(--foreground)]">{{ $event->route_name ?: class_basename($event->auditable_type ?? '') }}</div>
                                    <div class="mt-1 max-w-xl truncate text-xs text-[var(--muted-foreground)]">{{ $event->url }}</div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if ($event->old_values || $event->new_values)
                                        <details class="text-left">
                                            <summary class="cursor-pointer text-xs font-semibold text-[var(--primary)]">Ver cambio</summary>
                                            <div class="mt-2 grid gap-2 lg:grid-cols-2">
                                                <pre class="max-h-56 overflow-auto rounded-lg bg-[var(--muted)] p-3 text-[11px] text-[var(--foreground)]">{{ json_encode($event->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                <pre class="max-h-56 overflow-auto rounded-lg bg-[var(--muted)] p-3 text-[11px] text-[var(--foreground)]">{{ json_encode($event->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-xs text-[var(--muted-foreground)]">No aplica</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-[var(--muted-foreground)]">No hay eventos para el filtro seleccionado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-[var(--border)] px-5 py-4">{{ $events->links() }}</div>
        </section>
    </div>
</x-layouts::app>
