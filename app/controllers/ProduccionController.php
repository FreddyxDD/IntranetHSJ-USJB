<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/helpers/response.php';

final class ProduccionController
{
    public static function produccion(): void
    {
        if (empty($_SESSION['ueei_correo'])) {
            json_response([
                'ok' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        try {
            $stmt = db()->query("
                SELECT
                    Orden,
                    Nom_Indicador,
                    Variables,
                    ENE,
                    ENE_Valor,
                    FEB,
                    FEB_Valor,
                    Total_Anual,
                    Valor_Final
                FROM indicadores_produccion_rendimiento
                ORDER BY Orden ASC, Variables ASC
            ");

            json_response([
                'ok' => true,
                'data' => $stmt->fetchAll(),
            ]);

        } catch (Throwable $e) {
            json_response([
                'ok' => false,
                'message' => 'Error al obtener indicadores de producción.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}