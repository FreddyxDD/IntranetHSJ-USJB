@include('pages.security.partials.flash')

@php
    $selectedRoleIds = old('roles', $selectedRoles);
    $firstSelectedRole = collect($roles)->first(fn ($role) => in_array($role->id, $selectedRoleIds, false));
    $rolePermissions = $firstSelectedRole?->permissions?->groupBy('module') ?? collect();
    $selectedPersonnel = $personnelOptions->firstWhere('id', old('personnel_record_id', $account->personnel_record_id));
@endphp

<section class="hs-panel p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ $account->exists ? 'Editar usuario' : 'Nuevo usuario' }}</flux:heading>
            <flux:text class="mt-2">Para personal asistencial valida el documento contra maestro personal. Para administrativos completa los datos manualmente.</flux:text>
        </div>
        <flux:button :href="route('security.accounts.index')" variant="ghost">Cerrar</flux:button>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-12">
        <label class="grid gap-2 text-sm lg:col-span-3">
            <span class="text-zinc-700 dark:text-zinc-300">Tipo de usuario</span>
            <select name="user_type" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <option value="asistencial">Personal asistencial</option>
                <option value="administrativo">Administrativo</option>
            </select>
        </label>

        <label class="grid gap-2 text-sm lg:col-span-3">
            <span class="text-zinc-700 dark:text-zinc-300">Tipo de documento</span>
            <select name="document_type" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <option value="dni">DNI</option>
                <option value="ce">Carnet extranjeria</option>
                <option value="otro">Otro</option>
            </select>
        </label>

        <div class="lg:col-span-3">
            <flux:input name="personnel_q" form="personnel-search-form" label="Documento" value="{{ $personnelSearch }}" placeholder="DNI o nombre" />
        </div>

        <div class="flex items-end lg:col-span-1">
            <flux:button type="submit" form="personnel-search-form" variant="ghost">Validar</flux:button>
        </div>

        <label class="grid gap-2 text-sm lg:col-span-2">
            <span class="text-zinc-700 dark:text-zinc-300">Personal</span>
            <select name="personnel_record_id" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">Sin personal vinculado</option>
                @foreach ($personnelOptions as $person)
                    <option value="{{ $person->id }}" @selected((string) old('personnel_record_id', $account->personnel_record_id) === (string) $person->id)>
                        {{ $person->full_name }} - {{ $person->document_label }}
                    </option>
                @endforeach
            </select>
        </label>

        <div class="lg:col-span-5">
            <flux:input name="email" label="Correo electronico" type="email" value="{{ old('email', $account->email) }}" />
        </div>

        <div class="lg:col-span-3">
            <flux:input name="username" label="Usuario" value="{{ old('username', $account->username ?: $selectedPersonnel?->document_number) }}" required />
        </div>

        <label class="grid gap-2 text-sm lg:col-span-2">
            <span class="text-zinc-700 dark:text-zinc-300">Rol</span>
            <select name="roles[]" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <option value="">Selecciona un rol</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected(in_array($role->id, $selectedRoleIds, false))>{{ $role->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex items-center justify-center gap-2 rounded-lg border border-[var(--border)] px-3 py-2 text-sm font-medium text-[var(--foreground)] lg:col-span-2 lg:self-end">
            <input type="hidden" name="status" value="inactive">
            <input type="checkbox" name="status" value="active" class="size-4 rounded border-zinc-300" @checked(old('status', $account->status ?: 'active') === 'active')>
            Usuario activo
        </label>

        <div class="rounded-xl border border-[var(--border)] bg-[var(--muted)] p-4 lg:col-span-12">
            <div class="flex flex-col gap-4 md:flex-row md:items-center">
                <div class="grid size-24 place-items-center rounded-full bg-sky-50 text-xl font-bold text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800">
                    {{ strtoupper(substr($selectedPersonnel?->names ?: $account->display_name ?: 'SN', 0, 2)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-semibold text-[var(--foreground)]">Foto del personal</div>
                    <div class="mt-2 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 py-3 text-sm text-[var(--muted-foreground)]">
                        La carga de foto queda preparada para una siguiente fase. Por ahora se usa avatar por iniciales.
                    </div>
                    <p class="mt-2 text-xs text-[var(--muted-foreground)]">Formatos previstos: JPG, PNG o WebP. Maximo 2 MB.</p>
                </div>
            </div>
        </div>

        <div class="lg:col-span-12">
            <flux:input name="display_name" label="Nombre visible" value="{{ old('display_name', $account->display_name ?: $selectedPersonnel?->full_name) }}" required />
        </div>

        <div class="lg:col-span-6">
            <flux:input name="password" label="{{ $account->exists ? 'Nueva contrasena' : 'Contrasena' }}" type="password" />
        </div>
        <div class="lg:col-span-6">
            <flux:input name="password_confirmation" label="Confirmar contrasena" type="password" />
        </div>

        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 lg:col-span-12">
            <input type="hidden" name="must_change_password" value="0">
            <input type="checkbox" name="must_change_password" value="1" class="size-4 rounded border-zinc-300" @checked((bool) old('must_change_password', $account->must_change_password ?? true))>
            Cambiar contrasena al ingresar
        </label>
    </div>
</section>

<section class="hs-panel p-6">
    <h2 class="text-lg font-semibold text-[var(--foreground)]">Permisos del rol seleccionado</h2>
    <div class="mt-5 grid gap-4 lg:grid-cols-3">
        @forelse ($rolePermissions as $module => $permissions)
            <article class="rounded-xl border border-[var(--border)] bg-[var(--background)] p-4">
                <div class="mb-3 text-xs font-semibold uppercase tracking-wide text-[var(--muted-foreground)]">{{ $module }}</div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($permissions as $permission)
                        <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-100 dark:bg-sky-950 dark:text-sky-100 dark:ring-sky-800">{{ $permission->name }}</span>
                    @endforeach
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-[var(--border)] p-5 text-sm text-[var(--muted-foreground)] lg:col-span-3">
                Selecciona un rol y guarda para ver sus permisos asociados.
            </div>
        @endforelse
    </div>
</section>

<div class="sticky bottom-0 z-20 -mx-4 border-t border-[var(--border)] bg-[var(--background)]/90 px-4 py-4 backdrop-blur sm:mx-0 sm:rounded-t-xl sm:border sm:px-5">
    <div class="flex justify-end gap-2">
        <flux:button :href="route('security.accounts.index')" variant="ghost">Cancelar</flux:button>
        <flux:button type="submit" variant="primary">{{ $submitLabel }}</flux:button>
    </div>
</div>
