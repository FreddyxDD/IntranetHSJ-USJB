<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/helpers/response.php';

final class CitasAdminAuthController
{
    public static function login(): void
    {
        try {
            $input = get_json_input();

            $usuario = trim((string) ($input['usuario'] ?? ''));
            $password = (string) ($input['password'] ?? '');

            if ($usuario === '' || $password === '') {
                json_response([
                    'success' => false,
                    'message' => 'Completa usuario y contraseña.',
                ], 400);
            }

            $stmt = db()->prepare("
                SELECT id, usuario, contrasena, estado
                FROM cuentas_citas_admin
                WHERE usuario = :usuario
                LIMIT 1
            ");

            $stmt->execute([
                ':usuario' => $usuario,
            ]);

            $user = $stmt->fetch();

            if (!$user || (int) $user['estado'] !== 1) {
                self::authError();
            }

            if (!password_verify($password, (string) $user['contrasena'])) {
                self::authError();
            }

            session_regenerate_id(true);

            $_SESSION['citas_admin_id'] = (int) $user['id'];
            $_SESSION['citas_admin_usuario'] = $user['usuario'];

            json_response([
                'success' => true,
                'message' => 'Inicio de sesión correcto.',
                'usuario' => $user['usuario'],
                'redirect' => url_path('/citas-admin'),
            ]);

        } catch (Throwable $e) {
            json_response([
                'success' => false,
                'message' => 'Error interno en login de Citas Admin.',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }

    public static function me(): void
    {
        if (empty($_SESSION['citas_admin_usuario'])) {
            json_response([
                'ok' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        json_response([
            'ok' => true,
            'usuario' => $_SESSION['citas_admin_usuario'],
        ]);
    }

    public static function logout(): void
    {
        unset(
            $_SESSION['citas_admin_id'],
            $_SESSION['citas_admin_usuario']
        );

        json_response([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
            'redirect' => url_path('/citas-admin-login'),
        ]);
    }

    private static function authError(): void
    {
        json_response([
            'success' => false,
            'message' => 'Usuario o contraseña incorrectos.',
        ], 401);
    }
}