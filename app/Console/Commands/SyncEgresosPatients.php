<?php

namespace App\Console\Commands;

use App\Models\Egresos\Egreso;
use App\Services\Egresos\SighPatientSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SyncEgresosPatients extends Command
{
    protected $signature = 'hsj:sync-egresos-patients
        {--apply : Aplicar la conciliación; sin esta opción solo informa}
        {--chunk=500 : Tamaño de lote para consultar SIGH}';

    protected $description = 'Concilia tipo y número de documento de Egresos contra la tabla Pacientes de SIGH.';

    public function handle(SighPatientSource $source): int
    {
        $apply = (bool) $this->option('apply');
        $chunk = max(50, min(1000, (int) $this->option('chunk')));
        $summary = [
            'evaluados' => 0,
            'coincidencias_hc' => 0,
            'confirmados' => 0,
            'corregidos' => 0,
            'completados' => 0,
            'sin_documento_sigh' => 0,
        ];

        $reconcile = function () use ($source, $apply, $chunk, &$summary): void {
            Egreso::query()->orderBy('id')->chunkById($chunk, function ($egresos) use ($source, $apply, &$summary): void {
                $patients = $source->byHistories($egresos->pluck('numhc')->all());

                foreach ($egresos as $egreso) {
                    $summary['evaluados']++;
                    $patient = $patients->get(trim((string) $egreso->numhc));
                    if (! $patient) {
                        continue;
                    }
                    $summary['coincidencias_hc']++;
                    $document = trim((string) $patient->NroDocumento);
                    if ($document === '') {
                        $summary['sin_documento_sigh']++;

                        continue;
                    }

                    $current = trim((string) $egreso->doc_numero);
                    if ($current === $document) {
                        $summary['confirmados']++;
                    } elseif ($current === '') {
                        $summary['completados']++;
                    } else {
                        $summary['corregidos']++;
                    }

                    if ($apply) {
                        $egreso->forceFill([
                            'doc_tipo_id' => $patient->IdDocIdentidad ?: null,
                            'doc_numero' => $document,
                            'doc_source' => $source->sourceCode(),
                            'patient_source_id' => $patient->IdPaciente,
                            'document_verified_at' => now(),
                        ])->save();
                    }
                }
            });

            if ($apply) {
                $this->recordAudit($source, $summary);
            }
        };

        $apply ? DB::transaction($reconcile) : $reconcile();

        $this->table(
            ['Métrica', 'Cantidad'],
            collect($summary)->map(fn ($value, $key): array => [$key, $value])->values()->all()
        );

        if (! $apply) {
            $this->warn('Simulación completada. Ejecute nuevamente con --apply para guardar.');

            return self::SUCCESS;
        }

        $this->info('Conciliación aplicada desde '.$source->sourceCode().'.');

        return self::SUCCESS;
    }

    private function recordAudit(SighPatientSource $source, array $summary): void
    {
        DB::table('auditoria.eventos')->insert([
            'event_uuid' => (string) Str::uuid(),
            'application_code' => 'intranet_hsj',
            'module' => 'egresos',
            'event_type' => 'patients.reconciled',
            'action' => 'reconcile',
            'subject_type' => Egreso::class,
            'subject_id' => null,
            'actor_username' => 'artisan',
            'actor_display_name' => 'Proceso de conciliación SIGH',
            'data_after' => json_encode([
                'source' => $source->sourceCode(),
                ...$summary,
            ], JSON_UNESCAPED_UNICODE),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }
}
