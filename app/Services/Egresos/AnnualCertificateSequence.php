<?php

namespace App\Services\Egresos;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AnnualCertificateSequence
{
    public const OWNER_KEY = 'application:egresos';

    public function next(int $year): int
    {
        $this->lock($year);

        $counter = DB::table('egresos.correlativos')
            ->where('sequence_owner_key', self::OWNER_KEY)
            ->where('anio', $year)
            ->lockForUpdate()
            ->first();

        if (! $counter) {
            DB::table('egresos.correlativos')->insert([
                'sequence_owner_key' => self::OWNER_KEY,
                'anio' => $year,
                'ultimo_numero' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counter = DB::table('egresos.correlativos')
                ->where('sequence_owner_key', self::OWNER_KEY)
                ->where('anio', $year)
                ->lockForUpdate()
                ->first();
        }

        $number = ((int) $counter->ultimo_numero) + 1;

        DB::table('egresos.correlativos')
            ->where('id', $counter->id)
            ->update([
                'ultimo_numero' => $number,
                'updated_at' => now(),
            ]);

        return $number;
    }

    public function peek(int $year): int
    {
        $counter = (int) (DB::table('egresos.correlativos')
            ->where('sequence_owner_key', self::OWNER_KEY)
            ->where('anio', $year)
            ->value('ultimo_numero') ?? 0);
        $issued = (int) (DB::table('egresos.constancias')
            ->where('sequence_owner_key', self::OWNER_KEY)
            ->where('anio', $year)
            ->max('numero') ?? 0);

        return max($counter, $issued) + 1;
    }

    private function lock(int $year): void
    {
        if (DB::connection()->getDriverName() !== 'sqlsrv') {
            return;
        }

        $result = DB::selectOne(
            "DECLARE @result INT;
             EXEC @result = sys.sp_getapplock
                @Resource = ?,
                @LockMode = N'Exclusive',
                @LockOwner = N'Transaction',
                @LockTimeout = 10000;
             SELECT @result AS result;",
            ['intranet_hsj:egresos:constancias:'.$year]
        );

        if ((int) ($result->result ?? -999) < 0) {
            throw new RuntimeException('No fue posible reservar el correlativo anual de constancias.');
        }
    }
}
