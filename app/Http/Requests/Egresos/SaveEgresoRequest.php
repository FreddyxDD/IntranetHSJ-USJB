<?php

namespace App\Http\Requests\Egresos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveEgresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numhc' => ['required_without:doc_iden', 'nullable', 'string', 'max:50'],
            'doc_iden' => ['required_without:numhc', 'nullable', 'string', 'max:50'],
            'doc_tipo_id' => ['nullable', 'integer', 'between:1,10'],
            'nomb' => ['required', 'string', 'max:150'],
            'apell' => ['required', 'string', 'max:150'],
            'sexo' => ['nullable', 'string', 'max:10'],
            'edad' => ['nullable', 'string', 'max:10'],
            'tipoedad' => ['nullable', 'string', 'max:10'],
            'fecing' => ['required', 'date'],
            'fecegr' => ['required', 'date', 'after_or_equal:fecing', 'before_or_equal:today'],
            'ups' => ['required', 'string', 'max:50'],
            'condicion' => ['nullable', 'string', 'max:50'],
            'financia' => ['nullable', 'string', 'max:50'],
            'coddiag1' => ['required', 'string', 'max:50', $this->cie10Rule()],
            'coddiag2' => ['nullable', 'string', 'max:50', $this->cie10Rule()],
            'coddiag3' => ['nullable', 'string', 'max:50', $this->cie10Rule()],
            'coddiag4' => ['nullable', 'string', 'max:50', $this->cie10Rule()],
            'estado' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'numhc.required_without' => 'Debe indicar la historia clínica o el documento.',
            'doc_iden.required_without' => 'Debe indicar el documento o la historia clínica.',
            'fecegr.after_or_equal' => 'La fecha de egreso no puede ser anterior al ingreso.',
            'fecegr.before_or_equal' => 'La fecha de egreso no puede ser futura.',
            '*.exists' => 'El código CIE-10 indicado no existe en el catálogo central.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = [];
        foreach (['numhc', 'doc_iden', 'nomb', 'apell', 'sexo', 'edad', 'tipoedad', 'ups', 'condicion', 'financia', 'estado'] as $field) {
            $values[$field] = $this->filled($field) ? trim((string) $this->input($field)) : null;
        }
        foreach (range(1, 4) as $position) {
            $field = "coddiag{$position}";
            $values[$field] = $this->filled($field)
                ? strtoupper(str_replace('.', '', trim((string) $this->input($field))))
                : null;
        }
        $this->merge($values);
    }

    private function cie10Rule(): object
    {
        return Rule::exists('catalogos.cie10', 'codigo_normalizado');
    }
}
