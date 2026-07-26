<?php

namespace App\Support;

final class Cie10Code
{
    public static function format(?string $code): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', trim((string) $code)));
    }

    public static function normalize(?string $code): string
    {
        return mb_strtoupper(preg_replace('/[^A-Z0-9]/i', '', self::format($code)));
    }

    public static function isValid(?string $code): bool
    {
        return preg_match(
            '/^[A-Z][0-9]{2}(?:\.[0-9A-Z]{1,4})?$/',
            self::format($code)
        ) === 1;
    }
}
