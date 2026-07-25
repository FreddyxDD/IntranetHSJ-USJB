@include('pages.security.partials.flash')

<section class="hs-panel p-6">
    <div>
        <div class="text-sm font-semibold text-[var(--primary)]">Usuarios</div>
        <flux:heading size="xl" class="mt-2">{{ $role->exists ? 'Editar rol' : 'Nuevo rol' }}</flux:heading>
        <flux:text class="mt-2">Marca los permisos que tendra este perfil dentro del sistema.</flux:text>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-12">
        <div class="lg:col-span-3">
            <flux:input name="code" label="Codigo" value="{{ old('code', $role->code) }}" placeholder="administrador" required />
        </div>
        <div class="lg:col-span-4">
            <flux:input name="name" label="Nombre visible" value="{{ old('name', $role->name) }}" placeholder="Administrador" required />
        </div>
        <label class="grid gap-2 text-sm lg:col-span-5">
            <span class="text-zinc-700 dark:text-zinc-300">Descripcion</span>
            <input name="description" value="{{ old('description', $role->description) }}" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900" placeholder="Acceso completo al sistema.">
        </label>
    </div>

    <label class="mt-5 inline-flex items-center gap-2 rounded-lg border border-[var(--border)] px-3 py-2 text-sm text-[var(--foreground)]">
        <input type="hidden" name="is_system" value="0">
        <input type="checkbox" name="is_system" value="1" class="size-4 rounded border-zinc-300" @checked((bool) old('is_system', $role->is_system))>
        Rol predefinido del aplicativo
    </label>
</section>

<section class="hs-panel p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-[var(--foreground)]">Permisos del rol seleccionado</h2>
            <p class="mt-1 text-sm text-[var(--muted-foreground)]">Agrupados por modulo para revisar rapido el alcance del perfil.</p>
        </div>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-3">
        @foreach ($permissions as $module => $items)
            <article class="rounded-xl border border-[var(--border)] bg-[var(--background)] p-4 shadow-sm">
                <div class="mb-4 text-xs font-semibold uppercase tracking-wide text-[var(--muted-foreground)]">{{ $module }}</div>
                <div class="space-y-3">
                    @foreach ($items as $permission)
                        <label class="flex cursor-pointer gap-3 rounded-lg p-2 text-sm transition hover:bg-[var(--muted-hover)]">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="mt-1 size-4 rounded border-zinc-300 text-[var(--primary)]" @checked(in_array($permission->id, old('permissions', $selectedPermissions), false))>
                            <span>
                                <span class="block font-semibold text-[var(--foreground)]">{{ $permission->name }}</span>
                                <span class="text-xs text-[var(--muted-foreground)]">{{ $permission->code }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>
</section>

<div class="sticky bottom-0 z-20 -mx-4 border-t border-[var(--border)] bg-[var(--background)]/90 px-4 py-4 backdrop-blur sm:mx-0 sm:rounded-t-xl sm:border sm:px-5">
    <div class="flex justify-end gap-2">
        <flux:button :href="route('security.roles.index')" variant="ghost">Cancelar</flux:button>
        <flux:button type="submit" variant="primary">{{ $submitLabel }}</flux:button>
    </div>
</div>
