<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-zinc-950 antialiased dark:bg-zinc-950 dark:text-white">
        <div class="grid min-h-svh lg:grid-cols-2">
            <section class="flex min-h-svh items-center justify-center bg-white px-6 py-10 dark:bg-zinc-950">
                <div class="w-full max-w-md">
                    <a href="{{ route('home') }}" wire:navigate class="mb-10 inline-flex items-center gap-3 text-sm font-medium text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                        <flux:icon icon="chevron-left" class="size-4" />
                        Volver a la pagina principal
                    </a>

                    <div class="mb-8 flex items-center gap-3">
                        <img src="{{ asset('images/logo/logo.png') }}" alt="Hospital San Jose de Chincha" class="size-14 rounded-2xl bg-white object-contain p-1 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
                        <div>
                            <div class="text-base font-semibold text-zinc-950 dark:text-white">Intranet HSJ - Citas</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">Hospital San José de Chincha</div>
                        </div>
                    </div>

                    {{ $slot }}
                </div>
            </section>

            <section class="relative hidden min-h-svh overflow-hidden bg-[#151a53] lg:flex lg:items-center lg:justify-center">
                <div class="absolute inset-0 opacity-30">
                    <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(255,255,255,.08)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,.08)_1px,transparent_1px)] bg-[size:56px_56px]"></div>
                    <div class="absolute right-24 top-10 size-16 bg-white/10"></div>
                    <div class="absolute bottom-24 left-28 size-20 bg-white/10"></div>
                </div>

                <div class="relative max-w-lg px-10 text-center">
                    <img src="{{ asset('images/logo/logo-dark.png') }}" alt="Hospital San Jose de Chincha" class="mx-auto size-32 object-contain">
                    <h1 class="mt-8 text-4xl font-semibold tracking-tight text-white">Gestión diaria de citas</h1>
                    <p class="mt-4 text-base leading-7 text-indigo-100">
                        Consulta programaciones, cupos, pacientes, estados y reportes desde una sola interfaz conectada a SIGH.
                    </p>

                    <div class="mt-8 grid gap-3 text-left sm:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-white/10 p-4 text-white backdrop-blur">
                            <div class="text-sm font-semibold">Operacion diaria</div>
                            <div class="mt-1 text-xs text-indigo-100">Agenda, turnos, adicionales y demanda.</div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/10 p-4 text-white backdrop-blur">
                            <div class="text-sm font-semibold">Trazabilidad</div>
                            <div class="mt-1 text-xs text-indigo-100">FUA SIS, lotes, casos especiales y auditoria.</div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
