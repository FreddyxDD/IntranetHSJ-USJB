<?php

namespace Tests\Unit;

use App\Support\Cie10Code;
use PHPUnit\Framework\TestCase;

final class Cie10CodeTest extends TestCase
{
    public function test_codes_are_formatted_and_normalized_consistently(): void
    {
        self::assertSame('U06.AG', Cie10Code::format(' u06.ag '));
        self::assertSame('U06AG', Cie10Code::normalize('U06.AG'));
        self::assertSame(
            Cie10Code::normalize('U06AG'),
            Cie10Code::normalize('U06.AG')
        );
    }

    public function test_only_supported_cie10_format_is_accepted(): void
    {
        self::assertTrue(Cie10Code::isValid('A00'));
        self::assertTrue(Cie10Code::isValid('A00.1'));
        self::assertTrue(Cie10Code::isValid('U06.AG'));
        self::assertFalse(Cie10Code::isValid('U06AG'));
        self::assertFalse(Cie10Code::isValid(''));
    }
}
