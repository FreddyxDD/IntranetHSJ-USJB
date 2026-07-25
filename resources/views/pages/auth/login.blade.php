<x-layouts::auth :title="__('Iniciar sesion')">
    <div class="flex flex-col gap-6">
        <div>            
            <flux:heading size="xl" class="text-zinc-950 dark:text-white">Iniciar sesion</flux:heading>
            <flux:subheading class="mt-2 text-zinc-600 dark:text-zinc-300">Accede al portal operativo con tu cuenta autorizada.</flux:subheading>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="email"
                :label="__('Correo o usuario')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="admin@hsj.local"
            />

            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Contrasena')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Contrasena')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Olvidaste tu contrasena?') }}
                    </flux:link>
                @endif
            </div>

            <div class="flex items-center justify-between gap-4">
                <flux:checkbox name="remember" :label="__('Mantener sesion')" :checked="old('remember')" />
            </div>

            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('Ingresar al portal') }}
            </flux:button>
        </form>

        <div class="rounded-2xl bg-zinc-50 p-4 text-sm text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-800">
            El registro se valida con el personal institucional cuando corresponde. Las acciones quedan sujetas al rol asignado.
        </div>

        <p class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            Las cuentas y permisos se administran desde el Portal Operativo HSJ.
        </p>
    </div>
</x-layouts::auth>
