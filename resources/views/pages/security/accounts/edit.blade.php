<x-layouts::app :title="'Editar usuario'">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
            <flux:heading size="xl">Editar usuario</flux:heading>
            <flux:text class="mt-1">{{ $account->display_name }}</flux:text>
        </div>

        <form id="personnel-search-form" method="GET" action="{{ route('security.accounts.edit', $account) }}"></form>
        <form method="POST" action="{{ route('security.accounts.update', $account) }}">
            @csrf
            @method('PUT')
            @include('pages.security.accounts.form', ['submitLabel' => 'Guardar cambios'])
        </form>
    </div>
</x-layouts::app>
