@php
    $user = auth()->user();
    $changeNotifications = \Illuminate\Support\Facades\Schema::hasTable('app_notifications')
        ? \App\Models\AppNotification::query()
            ->with('actor:id,name')
            ->latest()
            ->limit(6)
            ->get()
        : collect();
    $unreadChangeCount = \Illuminate\Support\Facades\Schema::hasTable('app_notifications')
        ? \App\Models\AppNotification::query()->whereNull('read_at')->count()
        : 0;
    $notificationItems = collect([
        [
            'title' => 'Lotes de impresion activos',
            'description' => 'Hay lotes pendientes o en proceso.',
            'count' => \App\Models\FuaPrintBatch::query()->whereIn('status', ['pending', 'processing'])->count(),
            'href' => $user->hasPermission('citas.fua.print') ? route('citas.index') : null,
            'tone' => 'teal',
        ],
        [
            'title' => 'Seguimiento judicial pendiente',
            'description' => 'Citas judiciales del dia aun por marcar.',
            'count' => $user->hasPermission('judicial.view') ? \App\Models\JudicialCaseAppointment::query()->whereDate('appointment_date', today())->where('status', \App\Models\JudicialCaseAppointment::STATUS_SCHEDULED)->count() : 0,
            'href' => $user->hasPermission('judicial.view') ? route('judicial-cases.index') : null,
            'tone' => 'fuchsia',
        ],
        [
            'title' => 'SOAT/AFOCAT observado',
            'description' => 'Eventos SOAT del dia que requieren revision.',
            'count' => $user->hasPermission('soat.view') ? \App\Models\SoatCaseEvent::query()->whereDate('event_date', today())->where('status', \App\Models\SoatCaseEvent::STATUS_OBSERVED)->count() : 0,
            'href' => $user->hasPermission('soat.view') ? route('soat-cases.index') : null,
            'tone' => 'violet',
        ],
    ])->filter(fn ($item) => (int) $item['count'] > 0)->values();
    $notificationCount = $notificationItems->sum('count') + $unreadChangeCount;
    $navSections = [
        [
            'title' => 'Operacion',
            'items' => [
                ['label' => 'Panel', 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard'), 'icon' => 'home', 'permission' => 'dashboard.view'],
                ['label' => 'Citas', 'href' => route('citas.index'), 'active' => request()->routeIs('citas.index') || request()->routeIs('citas.show') || (request()->routeIs('citas.fua.*') && ! request()->routeIs('citas.fua.real-pilot.*')), 'icon' => 'calendar-days', 'permission' => 'citas.view'],
                ['label' => 'Simular registro cita', 'href' => route('citas.registration-simulation'), 'active' => request()->routeIs('citas.registration-simulation'), 'icon' => 'clipboard-document-check', 'permission' => 'citas.view'],
                ['label' => 'Programacion medica', 'href' => route('programacion-medica.index'), 'active' => request()->routeIs('programacion-medica.*'), 'icon' => 'calendar', 'permission' => 'citas.view'],
                ['label' => 'Pacientes', 'href' => route('patients.index'), 'active' => request()->routeIs('patients.*'), 'icon' => 'magnifying-glass', 'permission' => 'patients.search'],
                ['label' => 'Listas de pacientes', 'href' => route('citas.listas.index'), 'active' => request()->routeIs('citas.listas.*'), 'icon' => 'printer', 'permission' => 'reports.patient_lists'],
                ['label' => 'Reporte FUA SIS', 'href' => route('fuas-sis-reporte.index'), 'active' => request()->routeIs('fuas-sis-reporte.*'), 'icon' => 'document-text', 'permission' => 'fuas.report.view'],
                ['label' => 'Piloto FUA real', 'href' => route('citas.fua.real-pilot.index'), 'active' => request()->routeIs('citas.fua.real-pilot.*'), 'icon' => 'beaker', 'permission' => 'citas.fua.pilot'],
                ['label' => 'FUA Emergencia', 'href' => route('fuas-emergencia.index'), 'active' => request()->routeIs('fuas-emergencia.*'), 'icon' => 'bolt', 'permission' => 'fuas.emergency.view'],
                ['label' => 'Seguimiento judicial', 'href' => route('judicial-cases.index'), 'active' => request()->routeIs('judicial-cases.*'), 'icon' => 'scale', 'permission' => 'judicial.view'],
                ['label' => 'Seguimiento SOAT', 'href' => route('soat-cases.index'), 'active' => request()->routeIs('soat-cases.*'), 'icon' => 'shield-check', 'permission' => 'soat.view'],
            ],
        ],
        [
            'title' => 'Sistema',
            'items' => [
                ['label' => 'Mi configuracion', 'href' => route('profile.edit'), 'active' => request()->routeIs('profile.*') || request()->routeIs('appearance.*'), 'icon' => 'user-circle', 'permission' => null],
                ['label' => 'Sistema', 'href' => route('system-settings.index'), 'active' => request()->routeIs('system-settings.*'), 'icon' => 'cog-6-tooth', 'permission' => 'settings.system'],
                ['label' => 'Usuarios', 'href' => route('security.accounts.index'), 'active' => request()->routeIs('security.accounts.*'), 'icon' => 'users', 'permission' => 'users.view'],
                ['label' => 'Roles y permisos', 'href' => route('security.roles.index'), 'active' => request()->routeIs('security.roles.*') || request()->routeIs('security.permissions.*'), 'icon' => 'key', 'permission' => 'roles.view'],
                ['label' => 'Auditoria', 'href' => route('audit.index'), 'active' => request()->routeIs('audit.*'), 'icon' => 'chart-bar-square', 'permission' => 'audit.view'],
            ],
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="theme-hsj-clinical">
    <head>
        @include('partials.head')
        <style>
            @media (min-width: 1024px) {
                html.hs-sidebar-collapsed .hs-app-sidebar {
                    display: none !important;
                }

                html.hs-sidebar-collapsed .hs-app-main {
                    padding-inline-start: 0 !important;
                }
            }
        </style>
        <script>
            if (localStorage.getItem('hs-sidebar-collapsed') === '1') {
                document.documentElement.classList.add('hs-sidebar-collapsed');
            }
        </script>
    </head>
    <body class="min-h-screen bg-[var(--background-1)] text-[var(--foreground)] antialiased">
        <x-citas-monitor-alert />

        <aside class="hs-app-sidebar fixed inset-y-0 start-0 z-40 hidden w-72 border-e border-[var(--border)] bg-[var(--background)] shadow-sm lg:flex lg:flex-col">
            <div class="border-b border-[var(--border)] px-5 py-5">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3" wire:navigate>
                    <span class="grid size-11 place-items-center rounded-xl bg-white p-1 shadow-sm ring-1 ring-[var(--border)]">
                        <img src="{{ asset('images/logo/logo.png') }}" alt="Hospital San Jose de Chincha" class="size-full object-contain">
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold text-[var(--foreground)]">Portal Operativo</span>
                        <span class="mt-0.5 block truncate text-xs text-[var(--muted-foreground)]">HSJ Chincha</span>
                    </span>
                </a>
            </div>

            <nav class="min-h-0 flex-1 overflow-y-auto px-3 py-4">
                @foreach ($navSections as $section)
                    <div class="mb-5">
                        <div class="px-3 text-[11px] font-semibold uppercase tracking-wide text-[var(--muted-foreground)]">{{ $section['title'] }}</div>
                        <div class="mt-2 grid gap-1">
                            @foreach ($section['items'] as $item)
                                @continue($item['permission'] && ! $user->hasPermission($item['permission']))
                                <a
                                    href="{{ $item['href'] }}"
                                    wire:navigate
                                    class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $item['active'] ? 'bg-[var(--primary)] text-[var(--primary-foreground)] shadow-sm' : 'text-[var(--muted-foreground-2)] hover:bg-[var(--muted-hover)] hover:text-[var(--foreground)]' }}"
                                >
                                    <span class="grid size-8 shrink-0 place-items-center rounded-lg {{ $item['active'] ? 'bg-white/15 text-white' : 'bg-[var(--muted)] text-[var(--primary)] group-hover:bg-[var(--background)]' }}">
                                        <flux:icon :icon="$item['icon']" class="size-4" />
                                    </span>
                                    <span class="truncate">{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            <div class="border-t border-[var(--border)] p-4">
                <div class="rounded-xl border border-[var(--border)] bg-[var(--muted)] p-3 text-xs">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-semibold text-[var(--foreground)]">Entorno operativo</span>
                        <span class="rounded-md bg-[var(--background)] px-1.5 py-0.5 text-[10px] font-semibold uppercase text-[var(--primary)] ring-1 ring-[var(--border)]">{{ app()->environment() }}</span>
                    </div>
                    <div class="mt-2 truncate text-[var(--muted-foreground-2)]">{{ config('sigh.databases.main', 'SIGH') }}</div>
                </div>

                <div class="mt-3 flex items-center gap-3 rounded-xl px-2 py-2">
                    <div class="grid size-9 place-items-center rounded-full bg-[var(--primary)] text-xs font-semibold text-[var(--primary-foreground)]">{{ $user->initials() }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold text-[var(--foreground)]">{{ $user->name }}</div>
                        <div class="truncate text-xs text-[var(--muted-foreground)]">{{ $user->email }}</div>
                    </div>
                </div>
            </div>
        </aside>

        <div id="hs-portal-sidebar-mobile" class="hs-overlay fixed start-0 top-0 bottom-0 z-60 hidden w-72 -translate-x-full transform border-e border-[var(--border)] bg-[var(--background)] transition-all duration-300 hs-overlay-open:translate-x-0 lg:hidden" role="dialog" tabindex="-1" aria-label="Menu principal">
            <div class="flex h-full flex-col">
                <div class="flex items-center justify-between border-b border-[var(--border)] px-5 py-5">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3" wire:navigate>
                        <span class="grid size-10 place-items-center rounded-xl bg-white p-1 ring-1 ring-[var(--border)]">
                            <img src="{{ asset('images/logo/logo.png') }}" alt="Hospital San Jose de Chincha" class="size-full object-contain">
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-[var(--foreground)]">Portal Operativo</span>
                            <span class="block text-xs text-[var(--muted-foreground)]">HSJ Chincha</span>
                        </span>
                    </a>
                    <button type="button" class="flex size-9 items-center justify-center rounded-lg text-[var(--muted-foreground)] hover:bg-[var(--muted-hover)]" data-hs-overlay="#hs-portal-sidebar-mobile" aria-label="Cerrar menu">
                        <flux:icon icon="x-mark" class="size-5" />
                    </button>
                </div>

                <nav class="min-h-0 flex-1 overflow-y-auto px-3 py-4">
                    @foreach ($navSections as $section)
                        <div class="mb-5">
                            <div class="px-3 text-[11px] font-semibold uppercase tracking-wide text-[var(--muted-foreground)]">{{ $section['title'] }}</div>
                            <div class="mt-2 grid gap-1">
                                @foreach ($section['items'] as $item)
                                    @continue($item['permission'] && ! $user->hasPermission($item['permission']))
                                    <a href="{{ $item['href'] }}" wire:navigate class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $item['active'] ? 'bg-[var(--primary)] text-[var(--primary-foreground)] shadow-sm' : 'text-[var(--muted-foreground-2)] hover:bg-[var(--muted-hover)] hover:text-[var(--foreground)]' }}">
                                        <span class="grid size-8 shrink-0 place-items-center rounded-lg {{ $item['active'] ? 'bg-white/15 text-white' : 'bg-[var(--muted)] text-[var(--primary)] group-hover:bg-[var(--background)]' }}">
                                            <flux:icon :icon="$item['icon']" class="size-4" />
                                        </span>
                                        <span class="truncate">{{ $item['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </nav>
            </div>
        </div>

        <div class="hs-app-main min-h-screen lg:ps-72">
            <header class="hs-topbar">
                <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" class="grid size-9 shrink-0 place-items-center rounded-lg border border-[var(--border)] bg-[var(--background)] text-[var(--foreground)] shadow-sm hover:bg-[var(--muted-hover)] lg:hidden" data-hs-overlay="#hs-portal-sidebar-mobile" aria-label="Abrir menu">
                            <flux:icon icon="bars-3" class="size-5" />
                        </button>
                        <button type="button" id="hs-sidebar-toggle" class="hidden size-9 shrink-0 place-items-center rounded-lg border border-[var(--border)] bg-[var(--background)] text-[var(--foreground)] shadow-sm hover:bg-[var(--muted-hover)] lg:grid" aria-label="Ocultar o mostrar menu lateral" title="Ocultar o mostrar menu lateral">
                            <flux:icon icon="bars-3" class="size-5" />
                        </button>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-[var(--foreground)]">Portal Operativo HSJ</div>
                            <div class="mt-0.5 flex items-center gap-2 text-xs text-[var(--muted-foreground)]">
                                <span class="truncate">SIGH: {{ config('sigh.databases.main', 'SIGH') }}</span>
                                <span class="size-1 rounded-full bg-[var(--border-line-4)]"></span>
                                <span>{{ now()->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            x-data
                            x-on:click="$flux.dark = ! $flux.dark"
                            class="grid size-10 place-items-center rounded-full border border-[var(--border)] bg-[var(--background)] text-[var(--foreground)] shadow-sm hover:bg-[var(--muted-hover)] focus:outline-hidden focus:ring-2 focus:ring-[var(--primary)]"
                            aria-label="Cambiar modo claro u oscuro"
                            title="Cambiar modo claro u oscuro"
                        >
                            <flux:icon icon="moon" class="size-5 dark:hidden" />
                            <flux:icon icon="sun" class="hidden size-5 dark:block" />
                        </button>

                        <div class="hs-dropdown relative inline-flex">
                            <button id="hs-notifications" type="button" class="relative grid size-10 place-items-center rounded-full border border-[var(--border)] bg-[var(--background)] text-[var(--foreground)] shadow-sm hover:bg-[var(--muted-hover)] focus:outline-hidden focus:ring-2 focus:ring-[var(--primary)]" aria-haspopup="menu" aria-expanded="false" aria-label="Notificaciones">
                                <flux:icon icon="bell" class="size-5" />
                                @if ($notificationCount > 0)
                                    <span class="absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-rose-600 px-1 text-[10px] font-semibold leading-5 text-white ring-2 ring-[var(--background)]">{{ $notificationCount > 9 ? '9+' : $notificationCount }}</span>
                                @endif
                            </button>

                            <div class="hs-dropdown-menu duration mt-2 hidden w-96 rounded-2xl border border-[var(--border)] bg-[var(--background)] p-2 opacity-0 shadow-xl transition-[opacity,margin] hs-dropdown-open:opacity-100" role="menu" aria-orientation="vertical" aria-labelledby="hs-notifications">
                                <div class="flex items-center justify-between px-3 py-2">
                                    <div>
                                        <div class="text-sm font-semibold text-[var(--foreground)]">Notificaciones</div>
                                        <div class="text-xs text-[var(--muted-foreground)]">Alertas operativas y cambios recientes</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if ($unreadChangeCount > 0)
                                            <form method="POST" action="{{ route('notifications.mark-read') }}">
                                                @csrf
                                                <button type="submit" class="rounded-md px-2 py-1 text-xs font-semibold text-[var(--primary)] hover:bg-[var(--muted-hover)]">Marcar leidas</button>
                                            </form>
                                        @endif
                                        <span class="rounded-full bg-[var(--muted)] px-2 py-1 text-xs font-semibold text-[var(--primary)]">{{ $notificationCount }}</span>
                                    </div>
                                </div>
                                <div class="my-1 border-t border-[var(--border)]"></div>
                                @if ($notificationItems->isNotEmpty())
                                    <div class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-[var(--muted-foreground)]">Operativas</div>
                                    @foreach ($notificationItems as $item)
                                        @php($dotClass = match ($item['tone']) {
                                            'fuchsia' => 'bg-fuchsia-500',
                                            'violet' => 'bg-violet-500',
                                            default => 'bg-teal-500',
                                        })
                                        <a href="{{ $item['href'] ?: '#' }}" @if($item['href']) wire:navigate @endif class="flex gap-3 rounded-xl px-3 py-3 hover:bg-[var(--muted-hover)]">
                                            <span class="mt-1 size-2.5 shrink-0 rounded-full {{ $dotClass }}"></span>
                                            <span class="min-w-0 flex-1">
                                                <span class="flex items-center justify-between gap-3">
                                                    <span class="truncate text-sm font-semibold text-[var(--foreground)]">{{ $item['title'] }}</span>
                                                    <span class="rounded-md bg-[var(--muted)] px-1.5 py-0.5 text-xs font-semibold text-[var(--foreground)]">{{ $item['count'] }}</span>
                                                </span>
                                                <span class="mt-1 block text-xs leading-5 text-[var(--muted-foreground)]">{{ $item['description'] }}</span>
                                            </span>
                                        </a>
                                    @endforeach
                                @endif

                                @if ($changeNotifications->isNotEmpty())
                                    <div class="px-3 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-wide text-[var(--muted-foreground)]">Cambios recientes</div>
                                    <div class="max-h-80 overflow-y-auto pr-1">
                                        @foreach ($changeNotifications as $change)
                                            <a href="{{ $change->action_url ?: '#' }}" @if($change->action_url) wire:navigate @endif class="flex gap-3 rounded-xl px-3 py-3 hover:bg-[var(--muted-hover)]">
                                                <span class="mt-1 grid size-7 shrink-0 place-items-center rounded-full {{ $change->read_at ? 'bg-[var(--muted)] text-[var(--muted-foreground)]' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950 dark:text-amber-100 dark:ring-amber-800' }}">
                                                    <flux:icon icon="pencil-square" class="size-4" />
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="flex items-center justify-between gap-3">
                                                        <span class="truncate text-sm font-semibold text-[var(--foreground)]">{{ $change->title }}</span>
                                                        @if (! $change->read_at)
                                                            <span class="size-2 shrink-0 rounded-full bg-amber-500"></span>
                                                        @endif
                                                    </span>
                                                    <span class="mt-1 block text-xs leading-5 text-[var(--muted-foreground)]">{{ $change->message }}</span>
                                                    <span class="mt-1 block text-[11px] text-[var(--muted-foreground)]">
                                                        {{ $change->actor?->name ?: 'Sistema' }} / {{ $change->created_at?->diffForHumans() }}
                                                    </span>
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($notificationItems->isEmpty() && $changeNotifications->isEmpty())
                                    <div class="px-3 py-8 text-center">
                                        <div class="mx-auto grid size-10 place-items-center rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-100 dark:ring-emerald-800">
                                            <flux:icon icon="check" class="size-5" />
                                        </div>
                                        <div class="mt-3 text-sm font-semibold text-[var(--foreground)]">Sin alertas pendientes</div>
                                        <div class="mt-1 text-xs text-[var(--muted-foreground)]">No hay alertas ni cambios recientes.</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="hs-dropdown relative inline-flex">
                            <button id="hs-quick-actions" type="button" class="inline-flex items-center gap-x-2 rounded-lg border border-[var(--border)] bg-[var(--background)] px-3 py-2 text-sm font-medium text-[var(--foreground)] shadow-sm hover:bg-[var(--muted-hover)] focus:outline-hidden focus:ring-2 focus:ring-[var(--primary)]" aria-haspopup="menu" aria-expanded="false" aria-label="Acciones rapidas">
                                Acciones rapidas
                                <svg class="size-4 text-[var(--muted-foreground)] hs-dropdown-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>
                            </button>

                            <div class="hs-dropdown-menu duration mt-2 hidden min-w-60 rounded-xl border border-[var(--border)] bg-[var(--background)] p-2 opacity-0 shadow-lg transition-[opacity,margin] hs-dropdown-open:opacity-100" role="menu" aria-orientation="vertical" aria-labelledby="hs-quick-actions">
                                @if ($user->hasPermission('citas.view'))
                                    <a class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm text-[var(--foreground)] hover:bg-[var(--muted-hover)]" href="{{ route('citas.index') }}" wire:navigate>Citas del dia</a>
                                @endif
                                @if ($user->hasPermission('patients.search'))
                                    <a class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm text-[var(--foreground)] hover:bg-[var(--muted-hover)]" href="{{ route('patients.index') }}" wire:navigate>Buscar pacientes</a>
                                @endif
                                @if ($user->hasPermission('fuas.report.view'))
                                    <a class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm text-[var(--foreground)] hover:bg-[var(--muted-hover)]" href="{{ route('fuas-sis-reporte.index') }}" wire:navigate>Reporte FUA SIS</a>
                                @endif
                                @if ($user->hasPermission('reports.patient_lists'))
                                    <a class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm text-[var(--foreground)] hover:bg-[var(--muted-hover)]" href="{{ route('citas.listas.index') }}" wire:navigate>Listas de pacientes</a>
                                @endif
                                @if ($user->hasPermission('soat.view'))
                                    <a class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm text-[var(--foreground)] hover:bg-[var(--muted-hover)]" href="{{ route('soat-cases.index') }}" wire:navigate>Seguimiento SOAT</a>
                                @endif
                            </div>
                        </div>

                        <div class="hs-dropdown relative inline-flex">
                            <button id="hs-user-menu" type="button" class="inline-flex items-center gap-3 rounded-full border border-[var(--border)] bg-[var(--background)] py-1 pe-2 ps-1 shadow-sm hover:bg-[var(--muted-hover)] focus:outline-hidden focus:ring-2 focus:ring-[var(--primary)]" aria-haspopup="menu" aria-expanded="false" aria-label="Menu de usuario">
                                <span class="grid size-9 place-items-center rounded-full bg-[var(--primary)] text-xs font-semibold text-[var(--primary-foreground)]">{{ $user->initials() }}</span>
                                <span class="hidden min-w-0 text-left lg:block">
                                    <span class="block max-w-36 truncate text-sm font-semibold text-[var(--foreground)]">{{ $user->name }}</span>
                                    <span class="block max-w-36 truncate text-xs text-[var(--muted-foreground)]">{{ $user->accessAccount?->roles?->first()?->name ?? $user->rol ?? 'Usuario' }}</span>
                                </span>
                                <flux:icon icon="chevron-down" class="hidden size-4 text-[var(--muted-foreground)] lg:block" />
                            </button>

                            <div class="hs-dropdown-menu duration mt-2 hidden min-w-72 rounded-2xl border border-[var(--border)] bg-[var(--background)] p-2 opacity-0 shadow-xl transition-[opacity,margin] hs-dropdown-open:opacity-100" role="menu" aria-orientation="vertical" aria-labelledby="hs-user-menu">
                                <div class="flex items-center gap-3 rounded-xl bg-[var(--muted)] px-3 py-3">
                                    <div class="grid size-12 place-items-center rounded-full bg-[var(--primary)] text-sm font-semibold text-[var(--primary-foreground)]">{{ $user->initials() }}</div>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-[var(--foreground)]">{{ $user->name }}</div>
                                        <div class="truncate text-xs text-[var(--muted-foreground)]">{{ $user->email }}</div>
                                        <div class="mt-1 truncate text-[11px] font-semibold uppercase text-[var(--primary)]">{{ $user->accessAccount?->roles?->first()?->name ?? $user->rol ?? 'Usuario' }}</div>
                                    </div>
                                </div>
                                <div class="my-2 border-t border-[var(--border)]"></div>
                                <a href="{{ route('profile.edit') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-[var(--foreground)] hover:bg-[var(--muted-hover)]">
                                    <flux:icon icon="cog-6-tooth" class="size-4" />
                                    Configuracion
                                </a>
                                <form method="POST" action="{{ route('institutional.logout') }}" class="w-full">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-rose-700 hover:bg-rose-50 dark:text-rose-200 dark:hover:bg-rose-950" data-test="logout-button">
                                        <flux:icon icon="arrow-right-start-on-rectangle" class="size-4" />
                                        Cerrar sesion
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="border-b border-[var(--border)] bg-[var(--background)] px-4 py-2 sm:px-6 lg:hidden">
                <div class="flex gap-2 overflow-x-auto">
                    @foreach ($navSections[0]['items'] as $item)
                        @continue($item['permission'] && ! $user->hasPermission($item['permission']))
                        <a href="{{ $item['href'] }}" wire:navigate class="whitespace-nowrap rounded-lg px-3 py-2 text-xs font-semibold {{ $item['active'] ? 'bg-[var(--primary)] text-[var(--primary-foreground)]' : 'text-[var(--muted-foreground-2)] hover:bg-[var(--muted-hover)]' }}">{{ $item['label'] }}</a>
                    @endforeach
                </div>
            </div>

            <main class="min-h-[calc(100vh-4rem)]">
                {{ $slot }}
            </main>

            <footer class="border-t border-[var(--border)] bg-[var(--background)] px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-2 text-xs text-[var(--muted-foreground)] sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2">
                        <img src="{{ asset('images/logo/logo.png') }}" alt="Hospital San Jose de Chincha" class="size-6 rounded bg-white object-contain ring-1 ring-[var(--border)]">
                        <span>Hospital San Jose de Chincha - Portal Operativo</span>
                    </div>
                    <div class="flex flex-wrap gap-x-3 gap-y-1">
                        <span>OITE</span>
                        <span>{{ now()->format('Y') }}</span>
                        <span>{{ app()->environment() }}</span>
                    </div>
                </div>
            </footer>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
        <script>
            document.getElementById('hs-sidebar-toggle')?.addEventListener('click', () => {
                const collapsed = document.documentElement.classList.toggle('hs-sidebar-collapsed');
                localStorage.setItem('hs-sidebar-collapsed', collapsed ? '1' : '0');
            });
        </script>
    </body>
</html>
