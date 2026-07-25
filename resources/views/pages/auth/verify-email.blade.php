<x-layouts::auth :title="__('Verificar correo')">
    <div class="mt-4 flex flex-col gap-6">
        <x-auth-header :title="__('Verificar correo')" :description="__('Confirma tu correo desde el enlace enviado para activar el acceso.')" />

        <flux:text class="text-center">
            {{ __('Revisa tu bandeja de entrada y abre el enlace de verificacion enviado por el sistema.') }}
        </flux:text>

        @if (session('status') == 'verification-link-sent')
            <flux:text class="text-center font-medium !dark:text-green-400 !text-green-600">
                {{ __('Se envio un nuevo enlace de verificacion al correo registrado.') }}
            </flux:text>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Reenviar verificacion') }}
                </flux:button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button variant="ghost" type="submit" class="text-sm cursor-pointer" data-test="logout-button">
                    {{ __('Cerrar sesion') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::auth>
