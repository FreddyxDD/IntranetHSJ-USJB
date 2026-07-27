<?php

namespace App\Support;

final class InstitutionalSession
{
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('hospital_sid');
        session_set_cookie_params([
            'lifetime' => 60 * 60 * 4,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $_SESSION['created_at'] ??= time();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();

        return $_SESSION[$key] ?? $default;
    }

    public function hasAuthenticatedIdentity(): bool
    {
        return (int) $this->get('ueei_id', 0) > 0
            && trim((string) $this->get('ueei_correo', '')) !== '';
    }

    public function confirmationPending(): bool
    {
        return (bool) $this->get('account_confirmation_pending', false);
    }
}
