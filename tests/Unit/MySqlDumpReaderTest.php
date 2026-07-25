<?php

namespace Tests\Unit;

use App\Services\Migration\LegacyRowFingerprint;
use App\Services\Migration\MySqlDumpReader;
use PHPUnit\Framework\TestCase;

final class MySqlDumpReaderTest extends TestCase
{
    private string $dumpPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dumpPath = tempnam(sys_get_temp_dir(), 'hsj_dump_');
        file_put_contents($this->dumpPath, <<<'SQL'
CREATE TABLE `demo` (
  `id` int NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `detalle` text
) ENGINE=InnoDB;
INSERT INTO `demo` VALUES (1,'Registro A','Línea 1\nLínea 2'),(2,NULL,'Texto con \'comillas\'');
INSERT INTO `demo` VALUES (3,'Registro B','Valor, con coma');
SQL);
    }

    protected function tearDown(): void
    {
        @unlink($this->dumpPath);

        parent::tearDown();
    }

    public function test_reads_all_extended_insert_blocks_without_exposing_sql_details(): void
    {
        $reader = new MySqlDumpReader($this->dumpPath);
        $rows = iterator_to_array($reader->rows('demo'));

        self::assertSame(['demo'], $reader->tables());
        self::assertCount(3, $rows);
        self::assertSame('Registro A', $rows[0]['nombre']);
        self::assertSame("Línea 1\nLínea 2", $rows[0]['detalle']);
        self::assertNull($rows[1]['nombre']);
        self::assertSame("Texto con 'comillas'", $rows[1]['detalle']);
        self::assertSame('Valor, con coma', $rows[2]['detalle']);
    }

    public function test_fingerprint_is_stable_regardless_of_key_order(): void
    {
        self::assertSame(
            LegacyRowFingerprint::make(['id' => 1, 'nombre' => 'Demo']),
            LegacyRowFingerprint::make(['nombre' => 'Demo', 'id' => 1])
        );
    }
}
