<x-layouts::app :title="'Roles y permisos'">
    <div class="hs-page-shell">
        <section class="hs-panel p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="text-sm font-semibold text-[var(--primary)]">Usuarios</div>
                    <flux:heading size="xl" class="mt-2">Roles y permisos</flux:heading>
                    <flux:text class="mt-2">Define perfiles y controla que puede ver o modificar cada usuario dentro del aplicativo.</flux:text>
                </div>

                <form method="GET" action="{{ route('security.roles.index') }}" class="grid w-full gap-3 sm:grid-cols-[1fr_auto] lg:w-[520px] lg:items-end">
                    <flux:input name="q" label="Buscar" value="{{ $q }}" placeholder="Rol o descripcion" />
                    <flux:button type="submit" variant="primary">Buscar</flux:button>
                </form>
            </div>
        </section>

        @include('pages.security.partials.flash')

        <section class="hs-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-[var(--border)] p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-[var(--foreground)]">Perfiles registrados</h2>
                    <p class="mt-1 text-sm text-[var(--muted-foreground)]">Mostrando {{ $roles->firstItem() ?? 0 }}-{{ $roles->lastItem() ?? 0 }} de {{ $roles->total() }} roles.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:button :href="route('security.accounts.index')" variant="ghost">Ver usuarios</flux:button>
                    @if (auth()->user()->hasPermission('roles.create'))
                        <flux:button :href="route('security.roles.create')" variant="primary">Nuevo rol</flux:button>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr>
                            <th class="px-5 py-4">Rol</th>
                            <th class="px-5 py-4">Permisos</th>
                            <th class="px-5 py-4">Usuarios</th>
                            <th class="px-5 py-4">Tipo</th>
                            <th class="px-5 py-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($roles as $role)
                            <tr class="hover:bg-zinc-50/70 dark:hover:bg-zinc-900/70">
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-[var(--foreground)]">{{ $role->name }}</div>
                                    <div class="mt-1 text-xs text-[var(--muted-foreground)]">{{ $role->code }} @if($role->description) / {{ $role->description }} @endif</div>
                                </td>
                                <td class="px-5 py-4 text-base font-semibold text-[var(--foreground)]">{{ $role->permissions_count }}</td>
                                <td class="px-5 py-4 text-base font-semibold text-[var(--foreground)]">{{ $role->accounts_count }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-md px-2 py-1 text-xs font-medium ring-1 {{ $role->is_system ? 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800' : 'bg-zinc-100 text-zinc-700 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700' }}">
                                        {{ $role->is_system ? 'Predefinido' : 'Personalizado' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            data-hs-overlay="#role-detail-{{ $role->id }}"
                                            class="inline-flex items-center justify-center rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 py-1.5 text-xs font-semibold text-[var(--foreground)] shadow-sm transition hover:bg-[var(--muted-hover)] focus:outline-hidden focus:ring-2 focus:ring-[var(--primary)]"
                                        >
                                            Ver
                                        </button>
                                        @if (auth()->user()->hasPermission('roles.update'))
                                            <button
                                                type="button"
                                                data-hs-overlay="#role-edit-{{ $role->id }}"
                                                class="inline-flex items-center justify-center rounded-lg bg-[var(--primary)] px-3 py-1.5 text-xs font-semibold text-[var(--primary-foreground)] shadow-sm transition hover:opacity-90 focus:outline-hidden focus:ring-2 focus:ring-[var(--primary)]"
                                            >
                                                Editar
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-zinc-500">No se encontraron roles.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-[var(--border)] px-5 py-4">{{ $roles->links() }}</div>
        </section>

        @foreach ($roles as $role)
            <div id="role-detail-{{ $role->id }}" class="hs-overlay fixed start-0 top-0 z-80 hidden size-full overflow-y-auto overflow-x-hidden pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="role-detail-title-{{ $role->id }}">
                <div class="m-3 mt-0 flex min-h-[calc(100%-3.5rem)] items-center justify-center opacity-0 transition-all hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 sm:mx-auto sm:w-full sm:max-w-2xl">
                    <div class="pointer-events-auto flex w-full flex-col rounded-2xl border border-[var(--border)] bg-[var(--background)] shadow-xl">
                        <div class="flex items-start justify-between gap-4 border-b border-[var(--border)] px-5 py-4">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-[var(--primary)]">Detalle del rol</div>
                                <h3 id="role-detail-title-{{ $role->id }}" class="mt-1 text-xl font-semibold text-[var(--foreground)]">{{ $role->name }}</h3>
                                <p class="mt-1 text-sm text-[var(--muted-foreground)]">{{ $role->code }} @if($role->description) / {{ $role->description }} @endif</p>
                            </div>
                            <button type="button" data-hs-overlay="#role-detail-{{ $role->id }}" class="grid size-9 shrink-0 place-items-center rounded-lg text-[var(--muted-foreground)] transition hover:bg-[var(--muted-hover)] hover:text-[var(--foreground)]" aria-label="Cerrar detalle">
                                <flux:icon icon="x-mark" class="size-5" />
                            </button>
                        </div>

                        <div class="space-y-4 p-5">
                            <div class="grid gap-2 sm:grid-cols-3">
                                <div class="rounded-lg bg-[var(--muted)] px-3 py-2">
                                    <div class="text-[11px] font-semibold uppercase text-[var(--muted-foreground)]">Tipo</div>
                                    <div class="mt-1 text-sm font-semibold text-[var(--foreground)]">{{ $role->is_system ? 'Predefinido' : 'Personalizado' }}</div>
                                </div>
                                <div class="rounded-lg bg-[var(--muted)] px-3 py-2">
                                    <div class="text-[11px] font-semibold uppercase text-[var(--muted-foreground)]">Usuarios</div>
                                    <div class="mt-1 text-sm font-semibold text-[var(--foreground)]">{{ $role->accounts_count }}</div>
                                </div>
                                <div class="rounded-lg bg-[var(--muted)] px-3 py-2">
                                    <div class="text-[11px] font-semibold uppercase text-[var(--muted-foreground)]">Permisos</div>
                                    <div class="mt-1 text-sm font-semibold text-[var(--foreground)]">{{ $role->permissions_count }}</div>
                                </div>
                            </div>

                            <section>
                                <h4 class="text-sm font-semibold text-[var(--foreground)]">Usuarios asignados</h4>
                                <div class="mt-2 flex max-h-20 flex-wrap gap-1.5 overflow-y-auto">
                                    @forelse ($role->accounts as $account)
                                        <span class="rounded-md bg-sky-50 px-2 py-1 text-xs font-medium text-sky-700 ring-1 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800">{{ $account->display_name }}</span>
                                    @empty
                                        <span class="text-sm text-[var(--muted-foreground)]">Sin usuarios asignados.</span>
                                    @endforelse
                                </div>
                            </section>

                            <section>
                                <h4 class="text-sm font-semibold text-[var(--foreground)]">Permisos asignados</h4>
                                <div class="mt-2 max-h-72 space-y-3 overflow-y-auto pr-1">
                                    @forelse ($role->permissions->groupBy('module') as $module => $modulePermissions)
                                        <article>
                                            <div class="text-xs font-semibold uppercase tracking-wide text-[var(--muted-foreground)]">{{ $module }}</div>
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                @foreach ($modulePermissions as $modulePermission)
                                                    <span title="{{ $modulePermission->code }}" class="rounded-md bg-[var(--muted)] px-2 py-1 text-xs font-medium text-[var(--foreground)] ring-1 ring-[var(--border)]">{{ $modulePermission->name }}</span>
                                                @endforeach
                                            </div>
                                        </article>
                                    @empty
                                        <div class="rounded-xl border border-dashed border-[var(--border)] p-4 text-sm text-[var(--muted-foreground)]">Este rol no tiene permisos asignados.</div>
                                    @endforelse
                                </div>
                            </section>
                        </div>

                        <div class="flex flex-wrap justify-end gap-2 border-t border-[var(--border)] px-5 py-4">
                            <button type="button" data-hs-overlay="#role-detail-{{ $role->id }}" class="inline-flex items-center justify-center rounded-lg border border-[var(--border)] px-4 py-2 text-sm font-semibold text-[var(--foreground)] transition hover:bg-[var(--muted-hover)]">
                                Cerrar
                            </button>
                            @if (auth()->user()->hasPermission('roles.update'))
                                <button
                                    type="button"
                                    data-hs-overlay="#role-edit-{{ $role->id }}"
                                    class="inline-flex items-center justify-center rounded-lg bg-[var(--primary)] px-4 py-2 text-sm font-semibold text-[var(--primary-foreground)] transition hover:opacity-90"
                                >
                                    Editar rol
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if (auth()->user()->hasPermission('roles.update'))
                @php($selectedPermissionIds = $role->permissions->pluck('id')->all())
                <div id="role-edit-{{ $role->id }}" class="hs-overlay fixed start-0 top-0 z-80 hidden size-full overflow-y-auto overflow-x-hidden pointer-events-none" role="dialog" tabindex="-1" aria-labelledby="role-edit-title-{{ $role->id }}">
                    <div class="m-3 mt-0 flex min-h-[calc(100%-3.5rem)] items-center justify-center opacity-0 transition-all hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-300 sm:mx-auto sm:w-full sm:max-w-3xl">
                        <form method="POST" action="{{ route('security.roles.update', $role) }}" class="pointer-events-auto flex w-full flex-col rounded-2xl border border-[var(--border)] bg-[var(--background)] shadow-xl">
                            @csrf
                            <input type="hidden" name="_method" value="PUT">

                            <div class="flex items-start justify-between gap-4 border-b border-[var(--border)] px-5 py-4">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-[var(--primary)]">Editar rol</div>
                                    <h3 id="role-edit-title-{{ $role->id }}" class="mt-1 text-xl font-semibold text-[var(--foreground)]">{{ $role->name }}</h3>
                                    <p class="mt-1 text-sm text-[var(--muted-foreground)]">Actualiza el perfil y sus permisos sin salir de la lista.</p>
                                </div>
                                <button type="button" data-hs-overlay="#role-edit-{{ $role->id }}" class="grid size-9 shrink-0 place-items-center rounded-lg text-[var(--muted-foreground)] transition hover:bg-[var(--muted-hover)] hover:text-[var(--foreground)]" aria-label="Cerrar edicion">
                                    <flux:icon icon="x-mark" class="size-5" />
                                </button>
                            </div>

                            <div class="space-y-4 p-5">
                                <div class="grid gap-3 md:grid-cols-12">
                                    <label class="grid gap-1.5 text-sm md:col-span-3">
                                        <span class="font-medium text-[var(--foreground)]">Codigo</span>
                                        <input name="code" value="{{ old('code', $role->code) }}" class="h-10 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 text-sm text-[var(--foreground)]" required>
                                    </label>
                                    <label class="grid gap-1.5 text-sm md:col-span-4">
                                        <span class="font-medium text-[var(--foreground)]">Nombre visible</span>
                                        <input name="name" value="{{ old('name', $role->name) }}" class="h-10 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 text-sm text-[var(--foreground)]" required>
                                    </label>
                                    <label class="grid gap-1.5 text-sm md:col-span-5">
                                        <span class="font-medium text-[var(--foreground)]">Descripcion</span>
                                        <input name="description" value="{{ old('description', $role->description) }}" class="h-10 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 text-sm text-[var(--foreground)]">
                                    </label>
                                </div>

                                <label class="inline-flex items-center gap-2 rounded-lg border border-[var(--border)] px-3 py-2 text-sm text-[var(--foreground)]">
                                    <input type="hidden" name="is_system" value="0">
                                    <input type="checkbox" name="is_system" value="1" class="size-4 rounded border-zinc-300 text-[var(--primary)]" @checked($role->is_system)>
                                    Rol predefinido del aplicativo
                                </label>

                                <section>
                                    <h4 class="text-sm font-semibold text-[var(--foreground)]">Permisos</h4>
                                    <div class="mt-2 max-h-80 space-y-3 overflow-y-auto rounded-xl border border-[var(--border)] p-3">
                                        @foreach ($permissionGroups as $module => $items)
                                            <article>
                                                <div class="text-xs font-semibold uppercase tracking-wide text-[var(--muted-foreground)]">{{ $module }}</div>
                                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                                    @foreach ($items as $permissionItem)
                                                        <label class="flex cursor-pointer items-start gap-2 rounded-lg px-2 py-1.5 text-sm transition hover:bg-[var(--muted-hover)]">
                                                            <input type="checkbox" name="permissions[]" value="{{ $permissionItem->id }}" class="mt-1 size-4 rounded border-zinc-300 text-[var(--primary)]" @checked(in_array($permissionItem->id, $selectedPermissionIds, false))>
                                                            <span>
                                                                <span class="block font-medium text-[var(--foreground)]">{{ $permissionItem->name }}</span>
                                                                <span class="block text-xs text-[var(--muted-foreground)]">{{ $permissionItem->code }}</span>
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                </section>
                            </div>

                            <div class="flex flex-wrap justify-end gap-2 border-t border-[var(--border)] px-5 py-4">
                                <button type="button" data-hs-overlay="#role-edit-{{ $role->id }}" class="inline-flex items-center justify-center rounded-lg border border-[var(--border)] px-4 py-2 text-sm font-semibold text-[var(--foreground)] transition hover:bg-[var(--muted-hover)]">
                                    Cancelar
                                </button>
                                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[var(--primary)] px-4 py-2 text-sm font-semibold text-[var(--primary-foreground)] transition hover:opacity-90">
                                    Guardar cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</x-layouts::app>
