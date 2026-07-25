<x-layouts::app :title="$permission->name">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-3 border-b border-zinc-200 pb-5 dark:border-zinc-700 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <flux:heading size="xl">{{ $permission->name }}</flux:heading>
                <flux:text class="mt-1">{{ $permission->code }}</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button :href="route('security.permissions.edit', $permission)" variant="primary">Editar</flux:button>
                <flux:button :href="route('security.permissions.index')" variant="ghost">Volver</flux:button>
            </div>
        </div>
        @include('pages.security.partials.flash')
        <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <dl class="grid gap-3 text-sm md:grid-cols-3">
                <div><dt class="text-xs text-zinc-500">Modulo</dt><dd class="font-medium">{{ $permission->module }}</dd></div>
                <div><dt class="text-xs text-zinc-500">Codigo</dt><dd>{{ $permission->code }}</dd></div>
                <div><dt class="text-xs text-zinc-500">Roles asignados</dt><dd>{{ $permission->roles->count() }}</dd></div>
                <div class="md:col-span-3"><dt class="text-xs text-zinc-500">Descripcion</dt><dd>{{ $permission->description ?: '-' }}</dd></div>
            </dl>
        </section>
        <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">Roles que usan este permiso</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                @forelse ($permission->roles as $role)
                    <span class="rounded-md bg-sky-50 px-2 py-1 text-xs text-sky-700 ring-1 ring-sky-200 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800">{{ $role->name }}</span>
                @empty
                    <span class="text-sm text-zinc-500">Sin roles asignados.</span>
                @endforelse
            </div>
        </section>
        <form method="POST" action="{{ route('security.permissions.destroy', $permission) }}" onsubmit="return confirm('Eliminar permiso?')" class="flex justify-end">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50" {{ $permission->roles->isNotEmpty() ? 'disabled' : '' }}>Eliminar permiso</button>
        </form>
    </div>
</x-layouts::app>
