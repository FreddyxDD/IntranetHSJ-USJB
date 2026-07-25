<x-layouts::app :title="$role->name">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-3 border-b border-zinc-200 pb-5 dark:border-zinc-700 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <flux:heading size="xl">{{ $role->name }}</flux:heading>
                <flux:text class="mt-1">{{ $role->code }}</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button :href="route('security.roles.edit', $role)" variant="primary">Editar</flux:button>
                <flux:button :href="route('security.roles.index')" variant="ghost">Volver</flux:button>
            </div>
        </div>
        @include('pages.security.partials.flash')
        <div class="grid gap-4 lg:grid-cols-3">
            <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">Resumen</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">Tipo</dt><dd>{{ $role->is_system ? 'Sistema' : 'Personalizado' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">Usuarios</dt><dd>{{ $role->accounts->count() }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">Permisos</dt><dd>{{ $role->permissions->count() }}</dd></div>
                </dl>
                @if ($role->description)<p class="mt-4 text-sm text-zinc-600 dark:text-zinc-300">{{ $role->description }}</p>@endif
            </section>
            <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 lg:col-span-2">
                <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">Permisos asignados</h2>
                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse ($role->permissions as $permission)
                        <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $permission->module }} / {{ $permission->code }}</span>
                    @empty
                        <span class="text-sm text-zinc-500">Sin permisos.</span>
                    @endforelse
                </div>
            </section>
        </div>
        <form method="POST" action="{{ route('security.roles.destroy', $role) }}" onsubmit="return confirm('Eliminar rol?')" class="flex justify-end">
            @csrf
            <input type="hidden" name="_method" value="DELETE">
            <flux:button type="submit" variant="danger" :disabled="$role->is_system || $role->accounts->isNotEmpty()">Eliminar rol</flux:button>
        </form>
    </div>
</x-layouts::app>
