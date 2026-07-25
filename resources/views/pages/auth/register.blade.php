<x-layouts::auth :title="__('Registro')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Crear cuenta')" :description="__('Registra un usuario operativo para el modulo FUA SIS.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <div class="rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm text-sky-900 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-100">
                Ingresa tu DNI. Si perteneces al personal institucional, el sistema vinculara tu cuenta con tus datos registrados.
            </div>

            <flux:input
                name="document_number"
                :label="__('DNI')"
                :value="old('document_number')"
                type="text"
                required
                inputmode="numeric"
                autocomplete="off"
                maxlength="20"
                placeholder="Ej. 12345678"
            />

            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Nombre completo')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Nombre y apellidos')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Correo institucional')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Crear cuenta') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Ya tienes cuenta?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Iniciar sesion') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
