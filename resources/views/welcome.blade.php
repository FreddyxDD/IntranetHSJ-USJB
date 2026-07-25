<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-950 antialiased dark:bg-zinc-950 dark:text-white">
        <main class="min-h-screen">
            <section class="relative overflow-hidden bg-white dark:bg-zinc-950">
                <div class="absolute inset-y-0 right-0 hidden w-1/2 bg-[#151a53] lg:block">
                    <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(255,255,255,.08)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,.08)_1px,transparent_1px)] bg-[size:56px_56px] opacity-40"></div>
                </div>

                <div class="relative mx-auto grid min-h-screen max-w-7xl lg:grid-cols-2">
                    <div class="flex flex-col justify-center px-6 py-10 sm:px-10 lg:px-12">
                        <div class="mb-10 flex items-center gap-4">
                            <img src="{{ asset('images/logo/logo.png') }}" alt="Hospital San Jose de Chincha" class="size-16 rounded-2xl bg-white object-contain p-1 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800">
                            <div>
                                <div class="text-lg font-semibold">Portal Operativo HSJ</div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">Hospital San Jose de Chincha</div>
                            </div>
                        </div>

                        <div class="max-w-2xl">
                            <span class="inline-flex rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-teal-700 ring-1 ring-teal-200 dark:bg-teal-950 dark:text-teal-100 dark:ring-teal-800">Gestion asistencial y administrativa</span>
                            <h1 class="mt-5 text-4xl font-semibold tracking-tight sm:text-5xl">Citas, FUA SIS y trazabilidad operativa en un solo portal</h1>
                            <p class="mt-5 text-base leading-8 text-zinc-600 dark:text-zinc-300">
                                El aplicativo centraliza la consulta de citas, listas por consultorio, seguimiento de FUA SIS, impresion por lotes, casos judiciales, SOAT/AFOCAT y alertas criticas para la toma de decisiones diaria.
                            </p>
                        </div>

                        <div class="mt-8 flex flex-wrap gap-3">
                            @auth
                                <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-teal-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">Ir al dashboard</a>
                            @else
                                <a href="{{ route('login') }}" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-teal-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">Iniciar sesion</a>
                            @endauth
                        </div>

                        <div class="mt-10 grid gap-3 sm:grid-cols-3">
                            @foreach ([
                                ['title' => 'Agenda diaria', 'desc' => 'Turnos, adicionales, demanda y alertas.'],
                                ['title' => 'FUA SIS', 'desc' => 'Generadas, pendientes, manuales e impresion.'],
                                ['title' => 'Seguimientos', 'desc' => 'Judicial, SOAT/AFOCAT y trazabilidad.'],
                            ] as $item)
                                <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                                    <div class="font-semibold">{{ $item['title'] }}</div>
                                    <div class="mt-2 text-sm leading-6 text-zinc-500 dark:text-zinc-400">{{ $item['desc'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="relative hidden items-center justify-center p-10 lg:flex">
                        <div class="relative w-full max-w-lg rounded-[2rem] border border-white/10 bg-white/10 p-6 text-white shadow-2xl backdrop-blur">
                            <div class="flex items-center gap-4">
                                <img src="{{ asset('images/logo/logo-dark.png') }}" alt="Hospital San Jose de Chincha" class="size-20 object-contain">
                                <div>
                                    <div class="text-xl font-semibold">Panel ejecutivo</div>
                                    <div class="text-sm text-indigo-100">Indicadores agregados, sin datos sensibles.</div>
                                </div>
                            </div>

                            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                                <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                                    <div class="text-xs uppercase text-indigo-100">Citas hoy</div>
                                    <div class="mt-2 text-3xl font-semibold">+ agenda</div>
                                </div>
                                <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                                    <div class="text-xs uppercase text-indigo-100">FUA pendientes</div>
                                    <div class="mt-2 text-3xl font-semibold">control</div>
                                </div>
                                <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                                    <div class="text-xs uppercase text-indigo-100">Sobrecupo</div>
                                    <div class="mt-2 text-3xl font-semibold">alertas</div>
                                </div>
                                <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                                    <div class="text-xs uppercase text-indigo-100">Casos especiales</div>
                                    <div class="mt-2 text-3xl font-semibold">traza</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        @fluxScripts
    </body>
</html>
