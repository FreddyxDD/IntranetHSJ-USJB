<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-950">
        <flux:header container class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <flux:sidebar.toggle class="mr-2 lg:hidden" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Panel') }}
                </flux:navbar.item>
                <flux:navbar.item icon="layout-grid" :href="route('citas.index')" :current="request()->routeIs('citas.index') || request()->routeIs('citas.show') || request()->routeIs('citas.fua.*')" wire:navigate>
                    {{ __('Citas') }}
                </flux:navbar.item>
                <flux:navbar.item icon="printer" :href="route('citas.listas.index')" :current="request()->routeIs('citas.listas.*')" wire:navigate>
                    {{ __('Listas') }}
                </flux:navbar.item>
                <flux:navbar.item icon="layout-grid" :href="route('fuas-sis-reporte.index')" :current="request()->routeIs('fuas-sis-reporte.*')" wire:navigate>
                    {{ __('Reporte FUA SIS') }}
                </flux:navbar.item>
                <flux:navbar.item icon="layout-grid" :href="route('judicial-cases.index')" :current="request()->routeIs('judicial-cases.*')" wire:navigate>
                    {{ __('Judicial') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <x-desktop-user-menu />
        </flux:header>

        <flux:sidebar collapsible="mobile" sticky class="border-e border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900 lg:hidden">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Operacion')">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Panel') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="layout-grid" :href="route('citas.index')" :current="request()->routeIs('citas.index') || request()->routeIs('citas.show') || request()->routeIs('citas.fua.*')" wire:navigate>
                        {{ __('Citas') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="printer" :href="route('citas.listas.index')" :current="request()->routeIs('citas.listas.*')" wire:navigate>
                        {{ __('Listas de pacientes') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="layout-grid" :href="route('fuas-sis-reporte.index')" :current="request()->routeIs('fuas-sis-reporte.*')" wire:navigate>
                        {{ __('Reporte FUA SIS') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="layout-grid" :href="route('fuas-emergencia.index')" :current="request()->routeIs('fuas-emergencia.*')" wire:navigate>
                        {{ __('FUA Emergencia') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="layout-grid" :href="route('judicial-cases.index')" :current="request()->routeIs('judicial-cases.*')" wire:navigate>
                        {{ __('Seguimiento judicial') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cog" :href="route('system-settings.index')" :current="request()->routeIs('system-settings.*')" wire:navigate>
                        {{ __('Sistema') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="shield-check" :href="route('security.accounts.index')" :current="request()->routeIs('security.*')" wire:navigate>
                        {{ __('Seguridad') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
