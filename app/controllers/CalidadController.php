<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/helpers/response.php';

final class CalidadController
{
    public static function listar(): void
    {
        self::requireLogin();

        try {
            $stmt = db()->query("
                SELECT
                    Orden,
                    Nombre_Indicador,
                    Variable,
                    Ene,
                    Ene_Valor,
                    Feb,
                    Feb_Valor,
                    Total_Anual,
                    Valor_Final
                FROM indicadores_calidad
                ORDER BY Orden ASC, Variable ASC
            ");

            json_response([
                'ok' => true,
                'data' => $stmt->fetchAll(),
            ]);

        } catch (Throwable $e) {
            json_response([
                'ok' => false,
                'message' => 'Error al obtener indicadores de calidad.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public static function listarAdmin(): void
    {
        self::requireAdmin();
        self::listar();
    }

    public static function actualizar(): void
    {
        self::requireAdmin();

        $input = get_json_input();

        $originalOrden = self::parseIntOrNull($input['originalOrden'] ?? null);
        $originalNombreIndicador = self::cleanString($input['originalNombreIndicador'] ?? '');
        $originalVariable = self::cleanString($input['originalVariable'] ?? '');

        $nuevoOrden = self::parseIntOrNull($input['Orden'] ?? null);
        $nuevoNombreIndicador = self::cleanString($input['Nombre_Indicador'] ?? '');
        $nuevaVariable = self::cleanString($input['Variable'] ?? '');

        $nuevoEne = self::cleanStringNullable($input['Ene'] ?? '');
        $nuevoFeb = self::cleanStringNullable($input['Feb'] ?? '');

        $nuevoEneValor = self::parseDecimalNullable($input['Ene_Valor'] ?? '');
        $nuevoFebValor = self::parseDecimalNullable($input['Feb_Valor'] ?? '');
        $nuevoTotalAnual = self::parseDecimalNullable($input['Total_Anual'] ?? '');
        $nuevoValorFinal = self::parseDecimalNullable($input['Valor_Final'] ?? '');

        if ($originalOrden === null || !$originalNombreIndicador || !$originalVariable) {
            json_response([
                'ok' => false,
                'message' => 'Faltan los datos originales del registro.',
            ], 400);
        }

        if ($nuevoOrden === null || !$nuevoNombreIndicador || !$nuevaVariable) {
            json_response([
                'ok' => false,
                'message' => 'Orden, nombre del indicador y variable son obligatorios.',
            ], 400);
        }

        if (
            self::invalidDecimal($input['Ene_Valor'] ?? '', $nuevoEneValor) ||
            self::invalidDecimal($input['Feb_Valor'] ?? '', $nuevoFebValor) ||
            self::invalidDecimal($input['Total_Anual'] ?? '', $nuevoTotalAnual) ||
            self::invalidDecimal($input['Valor_Final'] ?? '', $nuevoValorFinal)
        ) {
            json_response([
                'ok' => false,
                'message' => 'Los campos numéricos contienen valores inválidos.',
            ], 400);
        }

        try {
            $stmt = db()->prepare("
                UPDATE indicadores_calidad
                SET
                    Orden = :nuevoOrden,
                    Nombre_Indicador = :nuevoNombreIndicador,
                    Variable = :nuevaVariable,
                    Ene = :nuevoEne,
                    Ene_Valor = :nuevoEneValor,
                    Feb = :nuevoFeb,
                    Feb_Valor = :nuevoFebValor,
                    Total_Anual = :nuevoTotalAnual,
                    Valor_Final = :nuevoValorFinal
                WHERE
                    Orden = :originalOrden
                    AND Nombre_Indicador = :originalNombreIndicador
                    AND Variable = :originalVariable
            ");

            $stmt->execute([
                ':nuevoOrden' => $nuevoOrden,
                ':nuevoNombreIndicador' => $nuevoNombreIndicador,
                ':nuevaVariable' => $nuevaVariable,
                ':nuevoEne' => $nuevoEne,
                ':nuevoEneValor' => $nuevoEneValor,
                ':nuevoFeb' => $nuevoFeb,
                ':nuevoFebValor' => $nuevoFebValor,
                ':nuevoTotalAnual' => $nuevoTotalAnual,
                ':nuevoValorFinal' => $nuevoValorFinal,

                ':originalOrden' => $originalOrden,
                ':originalNombreIndicador' => $originalNombreIndicador,
                ':originalVariable' => $originalVariable,
            ]);

            if ($stmt->rowCount() === 0) {
                json_response([
                    'ok' => false,
                    'message' => 'No se encontró el registro a actualizar.',
                ], 404);
            }

            json_response([
                'ok' => true,
                'message' => 'Registro de calidad actualizado correctamente.',
            ]);

        } catch (Throwable $e) {
            json_response([
                'ok' => false,
                'message' => 'Error al actualizar el indicador de calidad.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private static function requireLogin(): void
    {
        if (empty($_SESSION['ueei_correo'])) {
            json_response([
                'ok' => false,
                'message' => 'No autenticado.',
            ], 401);
        }
    }

    private static function requireAdmin(): void
    {
        self::requireLogin();

        if (($_SESSION['ueei_rol'] ?? '') !== 'admin') {
            json_response([
                'ok' => false,
                'message' => 'Acceso denegado.',
            ], 403);
        }
    }

    private static function cleanString(mixed $value, int $max = 255): string
    {
        return mb_substr(trim((string) $value), 0, $max);
    }

    private static function cleanStringNullable(mixed $value, int $max = 255): ?string
    {
        $value = self::cleanString($value, $max);

        return $value === '' ? null : $value;
    }

    private static function parseIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false
            ? (int) $value
            : null;
    }

    private static function parseDecimalNullable(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = str_replace(',', '', trim((string) $value));

        return is_numeric($number) ? (float) $number : null;
    }

    private static function invalidDecimal(mixed $original, ?float $parsed): bool
    {
        return $original !== '' && $original !== null && $parsed === null;
    }
}