<x-layouts::app :title="'Nuevo rol'">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
            <flux:heading size="xl">Nuevo rol</flux:heading>
            <flux:text class="mt-1">Define responsabilidades y permisos asociados.</flux:text>
        </div>
        <form method="POST" action="{{ route('security.roles.store') }}">
            @csrf
            @include('pages.security.roles.form', ['submitLabel' => 'Crear rol'])
        </form>
    </div>
</x-layouts::app>
