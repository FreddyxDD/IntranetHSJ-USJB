<x-layouts::app :title="'Editar rol'">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
            <flux:heading size="xl">Editar rol</flux:heading>
            <flux:text class="mt-1">{{ $role->name }}</flux:text>
        </div>
        <form method="POST" action="{{ route('security.roles.update', $role) }}">
            @csrf
            @method('PUT')
            @include('pages.security.roles.form', ['submitLabel' => 'Guardar cambios'])
        </form>
    </div>
</x-layouts::app>
