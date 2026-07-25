<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class UserFacingError
{
    public static function report(Throwable $exception, string $scope, array $context = []): string
    {
        $reference = self::reference($scope);

        Log::error('Falla controlada para el usuario.', [
            'support_reference' => $reference,
            'scope' => $scope,
            ...$context,
            'exception' => $exception,
        ]);

        return $reference;
    }

    public static function reference(string $scope): string
    {
        $prefix = preg_replace('/[^A-Z0-9]+/', '-', Str::upper(Str::ascii($scope))) ?: 'HSJ';
        $prefix = trim(substr($prefix, 0, 14), '-');

        return sprintf('%s-%s-%s', $prefix, now()->format('YmdHis'), Str::upper(Str::random(6)));
    }

    public static function message(string $message, string $reference): string
    {
        return rtrim($message, '.').' Código de soporte: '.$reference.'.';
    }
}
