@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="FUA SIS HSJ" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-teal-50 text-teal-700 ring-1 ring-teal-200 dark:bg-teal-950 dark:text-teal-200 dark:ring-teal-800">
            <x-app-logo-icon class="size-6" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="FUA SIS HSJ" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-teal-50 text-teal-700 ring-1 ring-teal-200 dark:bg-teal-950 dark:text-teal-200 dark:ring-teal-800">
            <x-app-logo-icon class="size-6" />
        </x-slot>
    </flux:brand>
@endif
