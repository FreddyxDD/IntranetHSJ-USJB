<?php

namespace App\Services\Migration;

use JsonException;

final class LegacyRowFingerprint
{
    /**
     * @param  array<string, mixed>  $row
     *
     * @throws JsonException
     */
    public static function make(array $row): string
    {
        ksort($row);

        return hash('sha256', json_encode(
            $row,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }
}
