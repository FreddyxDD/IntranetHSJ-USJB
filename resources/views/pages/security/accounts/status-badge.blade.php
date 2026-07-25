@php($statusLabels = ['active' => 'Activo', 'inactive' => 'Inactivo', 'blocked' => 'Bloqueado'])
@php($statusClasses = [
    'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-800',
    'inactive' => 'bg-zinc-100 text-zinc-700 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700',
    'blocked' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950 dark:text-rose-100 dark:ring-rose-800',
])
<span class="rounded-md px-2 py-1 text-xs font-semibold ring-1 {{ $statusClasses[$status] ?? $statusClasses['inactive'] }}">
    {{ $statusLabels[$status] ?? $status }}
</span>
