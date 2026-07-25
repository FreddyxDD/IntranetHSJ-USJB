<?php

namespace Tests\Feature;

use App\Services\Egresos\AnnualCertificateSequence;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AnnualCertificateSequenceTest extends TestCase
{
    public function test_sequence_is_global_and_restarts_for_each_year(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlsrv') {
            $this->markTestSkipped('La secuencia utiliza bloqueos transaccionales propios de SQL Server.');
        }

        DB::beginTransaction();

        try {
            DB::table('egresos.correlativos')
                ->where('sequence_owner_key', AnnualCertificateSequence::OWNER_KEY)
                ->whereIn('anio', [2098, 2099])
                ->delete();

            $sequence = app(AnnualCertificateSequence::class);

            self::assertSame(1, $sequence->next(2098));
            self::assertSame(2, $sequence->next(2098));
            self::assertSame(1, $sequence->next(2099));
        } finally {
            DB::rollBack();
        }
    }
}
