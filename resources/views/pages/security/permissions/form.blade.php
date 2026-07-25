@include('pages.security.partials.flash')

<section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
    <h2 class="text-sm font-semibold text-zinc-950 dark:text-white">Datos del permiso</h2>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <flux:input name="code" label="Codigo" value="{{ old('code', $permission->code) }}" placeholder="modulo.accion" required />
        <flux:input name="module" label="Modulo" value="{{ old('module', $permission->module) }}" required />
        <flux:input name="name" label="Nombre" value="{{ old('name', $permission->name) }}" required class="md:col-span-2" />
        <label class="grid gap-2 text-sm md:col-span-2">
            <span class="text-zinc-700 dark:text-zinc-300">Descripcion</span>
            <textarea name="description" rows="4" class="rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">{{ old('description', $permission->description) }}</textarea>
        </label>
    </div>
</section>

<div class="mt-6 flex justify-end gap-2">
    <flux:button :href="route('security.permissions.index')" variant="ghost">Cancelar</flux:button>
    <flux:button type="submit" variant="primary">{{ $submitLabel }}</flux:button>
</div>
