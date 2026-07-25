@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <flux:heading size="xl" class="text-zinc-950 dark:text-white">{{ $title }}</flux:heading>
    <flux:subheading class="mt-2 text-zinc-600 dark:text-zinc-300">{{ $description }}</flux:subheading>
</div>
