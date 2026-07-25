<x-layouts::app :title="'Usuarios'">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-4 border-b border-zinc-200 pb-5 dark:border-zinc-700">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <flux:heading size="xl">Usuarios</flux:heading>
                    <flux:text class="mt-1">Cuentas propias del aplicativo vinculadas al personal institucional.</flux:text>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if (auth()->user()->hasPermission('roles.view'))
                        <flux:button :href="route('security.roles.index')" variant="ghost">Roles y permisos</flux:button>
                    @endif
                    @if (auth()->user()->hasPermission('users.create'))
                        <flux:button :href="route('security.accounts.create')" variant="primary">Nuevo usuario</flux:button>
                    @endif
                </div>
            </div>

            @include('pages.security.partials.flash')

            <form method="GET" action="{{ route('security.accounts.index') }}" class="grid gap-3 md:grid-cols-[1fr_180px_auto] md:items-end">
                <flux:input name="q" label="Buscar" value="{{ $filters['q'] }}" placeholder="Usuario, nombre, documento, personal" />
                <label class="grid gap-2 text-sm">
                    <span class="text-zinc-700 dark:text-zinc-300">Estado</span>
                    <select name="status" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <option value="all" @selected($filters['status'] === 'all')>Todos</option>
                        <option value="active" @selected($filters['status'] === 'active')>Activos</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>Inactivos</option>
                        <option value="blocked" @selected($filters['status'] === 'blocked')>Bloqueados</option>
                    </select>
                </label>
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">Filtrar</flux:button>
                    <flux:button :href="route('security.accounts.index')" variant="ghost">Limpiar</flux:button>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">Usuario</th>
                            <th class="px-4 py-3">Personal</th>
                            <th class="px-4 py-3">Roles</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($accounts as $account)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-zinc-950 dark:text-white">{{ $account->display_name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $account->username }} @if($account->email) / {{ $account->email }} @endif</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($account->personnel)
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $account->personnel->full_name }}</div>
                                        <div class="text-xs text-zinc-500">{{ $account->personnel->document_label }} / {{ $account->personnel->profession?->name ?: 'Sin profesion' }}</div>
                                    @else
                                        <span class="text-zinc-400">Sin personal vinculado</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse ($account->roles as $role)
                                            <span class="rounded-md bg-sky-50 px-2 py-1 text-xs font-medium text-sky-700 ring-1 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800">{{ $role->name }}</span>
                                        @empty
                                            <span class="text-zinc-400">Sin roles</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @include('pages.security.accounts.status-badge', ['status' => $account->status])
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <flux:button :href="route('security.accounts.show', $account)" size="sm" variant="ghost">Ver</flux:button>
                                        @if (auth()->user()->hasPermission('users.update'))
                                            <flux:button :href="route('security.accounts.edit', $account)" size="sm" variant="primary">Editar</flux:button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-zinc-500">No se encontraron usuarios.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">
                {{ $accounts->links() }}
            </div>
        </div>
    </div>
</x-layouts::app>
