<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="bg-zinc-50 dark:bg-zinc-950">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
