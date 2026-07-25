<x-layouts::app :title="$account->display_name">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-3 border-b border-zinc-200 pb-5 dark:border-zinc-700 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <flux:heading size="xl">{{ $account->display_name }}</flux:heading>
                <flux:text class="mt-1">{{ $account->username }} @if($account->email) / {{ $account->email }} @endif</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button :href="route('security.accounts.edit', $account)" variant="primary">Editar</flux:button>
                <flux:button :href="route('security.accounts.index')" variant="ghost">Volver</flux:button>
            </div>
        </div>

        @include('pages.security.partials.flash')

        <div class="grid gap-4 lg:grid-cols-3">
            <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">Cuenta</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">Estado</dt><dd>@include('pages.security.accounts.status-badge', ['status' => $account->status])</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">Cambio de contrasena</dt><dd>{{ $account->must_change_password ? 'Pendiente' : 'No requerido' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-zinc-500">Ultimo ingreso</dt><dd>{{ $account->last_login_at?->format('d/m/Y H:i') ?: '-' }}</dd></div>
                </dl>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 lg:col-span-2">
                <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">Personal vinculado</h2>
                @if ($account->personnel)
                    <dl class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                        <div><dt class="text-xs text-zinc-500">Nombre</dt><dd class="font-medium">{{ $account->personnel->full_name }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Documento</dt><dd>{{ $account->personnel->document_label }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Profesion</dt><dd>{{ $account->personnel->profession?->name ?: '-' }}</dd></div>
                        <div><dt class="text-xs text-zinc-500">Establecimiento</dt><dd>{{ $account->personnel->establishment?->name ?: '-' }}</dd></div>
                    </dl>
                @else
                    <p class="mt-4 text-sm text-zinc-500">Cuenta sin personal vinculado.</p>
                @endif
            </section>
        </div>

        <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">Roles y permisos</h2>
            <div class="mt-4 grid gap-3 lg:grid-cols-3">
                @forelse ($account->roles as $role)
                    <div class="rounded-md border border-zinc-200 p-3 dark:border-zinc-800">
                        <div class="font-semibold text-zinc-950 dark:text-white">{{ $role->name }}</div>
                        <div class="text-xs text-zinc-500">{{ $role->code }}</div>
                        <div class="mt-3 flex flex-wrap gap-1">
                            @foreach ($role->permissions as $permission)
                                <span class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $permission->code }}</span>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">Sin roles asignados.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts::app>
