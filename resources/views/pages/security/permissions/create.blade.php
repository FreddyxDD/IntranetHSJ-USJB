<x-layouts::app :title="'Nuevo permiso'">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
            <flux:heading size="xl">Nuevo permiso</flux:heading>
            <flux:text class="mt-1">Registra una accion controlable por roles.</flux:text>
        </div>
        <form method="POST" action="{{ route('security.permissions.store') }}">
            @csrf
            @include('pages.security.permissions.form', ['submitLabel' => 'Crear permiso'])
        </form>
    </div>
</x-layouts::app>
