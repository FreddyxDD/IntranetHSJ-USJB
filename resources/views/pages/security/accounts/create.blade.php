<x-layouts::app :title="'Nuevo usuario'">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
            <flux:heading size="xl">Nuevo usuario</flux:heading>
            <flux:text class="mt-1">Crea una cuenta de acceso y asigna roles operativos.</flux:text>
        </div>

        <form id="personnel-search-form" method="GET" action="{{ route('security.accounts.create') }}"></form>
        <form method="POST" action="{{ route('security.accounts.store') }}">
            @csrf
            @include('pages.security.accounts.form', ['submitLabel' => 'Crear usuario'])
        </form>
    </div>
</x-layouts::app>
