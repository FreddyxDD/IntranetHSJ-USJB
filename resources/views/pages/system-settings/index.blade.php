<x-layouts::app :title="__('Configuracion del sistema')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
            <flux:heading size="xl">Configuracion del sistema</flux:heading>
            <flux:text class="mt-1">Reglas locales del aplicativo. Estos cambios no modifican SIGH.</flux:text>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100">
                Revisa los datos ingresados antes de guardar.
            </div>
        @endif

        <section class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-4 py-4 dark:border-zinc-700">
                <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Consultorios operativos del aplicativo</h2>
                <p class="mt-1 text-sm text-zinc-500">Los consultorios no operativos se ocultan de la lista operativa de citas y se separan en reportes donde no participan del flujo de trabajo diario.</p>
            </div>

            <form method="POST" action="{{ route('system-settings.report-services.store') }}" class="grid gap-3 border-b border-zinc-200 p-4 dark:border-zinc-700 md:grid-cols-[180px_220px_minmax(0,1fr)_auto] md:items-end">
                @csrf
                <flux:input name="sigh_service_id" label="Id servicio SIGH" type="number" min="1" placeholder="Ej. 145" />

                <label class="grid gap-2 text-sm">
                    <span class="text-zinc-700 dark:text-zinc-300">Grupo</span>
                    <select name="group" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        @foreach ($groups as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <flux:input name="notes" label="Observacion" placeholder="Motivo de clasificacion" />
                <flux:button type="submit" variant="primary">Agregar</flux:button>
            </form>

            <form method="POST" action="{{ route('system-settings.report-services.update') }}">
                @csrf
                @method('PUT')

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3">Id</th>
                                <th class="px-4 py-3">Consultorio</th>
                                <th class="px-4 py-3">Grupo</th>
                                <th class="px-4 py-3">Observacion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($settings as $setting)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-zinc-950 dark:text-white">{{ $setting->sigh_service_id }}</td>
                                    <td class="min-w-80 px-4 py-3">
                                        <div class="font-medium text-zinc-950 dark:text-white">{{ $setting->service_name }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <select name="settings[{{ $setting->id }}][group]" class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                            @foreach ($groups as $value => $label)
                                                <option value="{{ $value }}" @selected($setting->group === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input name="settings[{{ $setting->id }}][notes]" value="{{ $setting->notes }}" class="h-10 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm dark:border-zinc-700 dark:bg-zinc-900" placeholder="Opcional">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-zinc-500">Aun no hay consultorios configurados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end border-t border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:button type="submit" variant="primary">Guardar cambios</flux:button>
                </div>
            </form>
        </section>
    </div>
</x-layouts::app>
