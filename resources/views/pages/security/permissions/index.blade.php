<x-layouts::app :title="'Permisos'">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-4 border-b border-zinc-200 pb-5 dark:border-zinc-700">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <flux:heading size="xl">Permisos</flux:heading>
                    <flux:text class="mt-1">Acciones disponibles para armar roles y controlar accesos del aplicativo.</flux:text>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:button :href="route('security.roles.index')" variant="ghost">Roles</flux:button>
                    @if (auth()->user()->hasPermission('roles.create'))
                        <flux:button :href="route('security.permissions.create')" variant="primary">Nuevo permiso</flux:button>
                    @endif
                </div>
            </div>
            @include('pages.security.partials.flash')
            <form method="GET" action="{{ route('security.permissions.index') }}" class="flex gap-2">
                <flux:input name="q" label="Buscar" value="{{ $q }}" placeholder="Codigo, nombre o modulo" class="flex-1" />
                <div class="pt-6"><flux:button type="submit" variant="primary">Filtrar</flux:button></div>
            </form>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Permiso</th>
                        <th class="px-4 py-3">Modulo</th>
                        <th class="px-4 py-3">Roles</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($permissions as $permission)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-zinc-950 dark:text-white">{{ $permission->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $permission->code }}</div>
                                @if ($permission->description)<div class="mt-1 text-xs text-zinc-500">{{ $permission->description }}</div>@endif
                            </td>
                            <td class="px-4 py-3">{{ $permission->module }}</td>
                            <td class="px-4 py-3">{{ $permission->roles_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <flux:button :href="route('security.permissions.show', $permission)" size="sm" variant="ghost">Ver</flux:button>
                                    @if (auth()->user()->hasPermission('roles.update'))
                                        <flux:button :href="route('security.permissions.edit', $permission)" size="sm" variant="primary">Editar</flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-zinc-500">No se encontraron permisos.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-800">{{ $permissions->links() }}</div>
        </div>
    </div>
</x-layouts::app>
