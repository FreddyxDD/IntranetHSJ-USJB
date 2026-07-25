<x-layouts::app :title="'Editar permiso'">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
            <flux:heading size="xl">Editar permiso</flux:heading>
            <flux:text class="mt-1">{{ $permission->code }}</flux:text>
        </div>
        <form method="POST" action="{{ route('security.permissions.update', $permission) }}">
            @csrf
            @method('PUT')
            @include('pages.security.permissions.form', ['submitLabel' => 'Guardar cambios'])
        </form>
    </div>
</x-layouts::app>
