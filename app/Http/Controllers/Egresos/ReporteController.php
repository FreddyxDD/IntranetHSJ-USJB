<?php

namespace App\Http\Controllers\Egresos;

use App\Http\Controllers\Controller;
use App\Models\Egresos\Egreso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReporteController extends Controller
{
    private const HEADERS = [
        'Historia clínica', 'Documento', 'Nombres', 'Apellidos', 'Sexo',
        'Fecha ingreso', 'Fecha egreso', 'UPS', 'Condición', 'Financiamiento',
        'Diagnóstico 1', 'Diagnóstico 2', 'Diagnóstico 3', 'Diagnóstico 4',
    ];

    private const FIELDS = [
        'numhc', 'doc_iden', 'nomb', 'apell', 'sexo', 'fecing', 'fecegr', 'ups',
        'condicion', 'financia', 'coddiag1', 'coddiag2', 'coddiag3', 'coddiag4',
    ];

    public function csv(Request $request): StreamedResponse
    {
        $query = $this->query($request);
        $filename = 'egresos_'.$this->suffix($request).'.csv';

        return response()->streamDownload(function () use ($query): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, self::HEADERS, ';', '"', '\\');
            $query->chunkById(500, function ($rows) use ($stream): void {
                foreach ($rows as $row) {
                    fputcsv($stream, $this->values($row), ';', '"', '\\');
                }
            });
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function xlsx(Request $request): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Egresos');
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);
        $line = 2;
        $this->query($request)->chunkById(500, function ($rows) use ($sheet, &$line): void {
            foreach ($rows as $row) {
                foreach ($this->values($row) as $index => $value) {
                    $cell = Coordinate::stringFromColumnIndex($index + 1).$line;
                    $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), DataType::TYPE_STRING);
                }
                $line++;
            }
        });
        foreach (range('A', 'N') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'egresos_');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()
            ->download($path, 'egresos_'.$this->suffix($request).'.xlsx')
            ->deleteFileAfterSend(true);
    }

    private function query(Request $request): Builder
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'ups' => ['nullable', 'string', 'max:50'],
        ]);
        $query = Egreso::query()->orderBy('id');
        $text = trim((string) ($validated['q'] ?? ''));
        if ($text !== '') {
            $query->where(function ($builder) use ($text): void {
                $like = '%'.str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $text).'%';
                $builder->where('numhc', 'like', $like)
                    ->orWhere('doc_numero', 'like', $like)
                    ->orWhere('doc_iden', 'like', $like)
                    ->orWhere('nomb', 'like', $like)
                    ->orWhere('apell', 'like', $like);
            });
        }

        return $query
            ->when($validated['date_from'] ?? null, fn ($builder, $date) => $builder->whereDate('fecegr', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($builder, $date) => $builder->whereDate('fecegr', '<=', $date))
            ->when($validated['ups'] ?? null, fn ($builder, $ups) => $builder->where('ups', $ups));
    }

    private function values(Egreso $row): array
    {
        return collect(self::FIELDS)->map(function (string $field) use ($row) {
            $value = $field === 'doc_iden' ? $row->documento : $row->getAttribute($field);

            $value = $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
            if (is_string($value) && preg_match('/^[=+\-@]/', $value)) {
                return "'".$value;
            }

            return $value;
        })->all();
    }

    private function suffix(Request $request): string
    {
        $from = $request->string('date_from')->toString() ?: 'inicio';
        $to = $request->string('date_to')->toString() ?: now()->format('Y-m-d');

        return Str::slug($from.'_a_'.$to);
    }
}
