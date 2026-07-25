<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/helpers/response.php';

use App\Support\CirugiasSessionBridge;

final class CirugiasAuthController
{
    public static function bootstrapCentralSession(): bool
    {
        self::ensureSession();

        return CirugiasSessionBridge::sync($_SESSION);
    }

    public static function centralDestination(): string
    {
        self::bootstrapCentralSession();

        return CirugiasSessionBridge::destination($_SESSION);
    }

    public static function login(): void
    {
        try {
            self::ensureSession();

            $input = get_json_input();

            $usuario = trim((string) ($input['usuario'] ?? ''));
            $password = (string) ($input['password'] ?? '');

            if ($usuario === '' || $password === '') {
                json_response([
                    'success' => false,
                    'ok' => false,
                    'message' => 'Completa todos los campos',
                ], 400);
            }

            $stmt = db()->prepare("
                SELECT id, usuario, contrasena, rol, estado
                FROM cuentas_cirugias
                WHERE usuario = :usuario
                LIMIT 1
            ");

            $stmt->execute([
                ':usuario' => $usuario,
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || (int) $user['estado'] !== 1) {
                self::generic_auth_error();
            }

            if (!password_verify($password, (string) $user['contrasena'])) {
                self::generic_auth_error();
            }

            session_regenerate_id(true);

            $_SESSION['cirugias_id'] = (int) $user['id'];
            $_SESSION['cirugias_usuario'] = (string) $user['usuario'];
            $_SESSION['cirugias_rol'] = (int) $user['rol'];

            $redirect = ((int) $user['rol'] === 0)
                ? url_path('/cirugias-admin')
                : url_path('/principal-cirugias');

            json_response([
                'success' => true,
                'ok' => true,
                'message' => 'Inicio de sesión correcto',
                'usuario' => $user['usuario'],
                'rol' => (int) $user['rol'],
                'redirect' => $redirect,
            ]);
        } catch (Throwable $e) {
            json_response([
                'success' => false,
                'ok' => false,
                'message' => 'Error interno en login de Cirugías',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }

    public static function me(): void
    {
        self::ensureSession();
        self::bootstrapCentralSession();

        if (empty($_SESSION['cirugias_usuario'])) {
            json_response([
                'success' => false,
                'ok' => false,
                'message' => 'No autenticado en Cirugías',
            ], 401);
        }

        json_response([
            'success' => true,
            'ok' => true,
            'usuario' => $_SESSION['cirugias_usuario'],
            'rol' => (int) ($_SESSION['cirugias_rol'] ?? 1),
        ]);
    }

    public static function logout(): void
    {
        self::ensureSession();

        unset(
            $_SESSION['cirugias_id'],
            $_SESSION['cirugias_usuario'],
            $_SESSION['cirugias_rol']
        );

        json_response([
            'success' => true,
            'ok' => true,
            'message' => 'Sesión de Cirugías cerrada correctamente',
            'redirect' => url_path('/areas'),
        ]);
    }

    public static function listarUsuarios(): void
    {
        self::requireAdmin();

        try {
            $stmt = db()->query("
                SELECT id, usuario, rol, estado
                FROM cuentas_cirugias
                ORDER BY id DESC
            ");

            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            json_response([
                'success' => true,
                'ok' => true,
                'usuarios' => $usuarios,
            ]);
        } catch (Throwable $e) {
            json_response([
                'success' => false,
                'ok' => false,
                'message' => 'Error al listar usuarios',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }

    public static function crearUsuario(): void
    {
        self::requireAdmin();

        try {
            $input = get_json_input();

            $usuario = trim((string) ($input['usuario'] ?? ''));
            $password = (string) ($input['password'] ?? '');
            $rol = (int) ($input['rol'] ?? 1);

            if ($usuario === '' || $password === '') {
                json_response([
                    'success' => false,
                    'ok' => false,
                    'message' => 'Completa todos los campos',
                ], 400);
            }

            if (!in_array($rol, [0, 1], true)) {
                json_response([
                    'success' => false,
                    'ok' => false,
                    'message' => 'Rol inválido',
                ], 400);
            }

            if (strlen($password) < 6) {
                json_response([
                    'success' => false,
                    'ok' => false,
                    'message' => 'La contraseña debe tener mínimo 6 caracteres',
                ], 400);
            }

            $stmtExiste = db()->prepare("
                SELECT id
                FROM cuentas_cirugias
                WHERE usuario = :usuario
                LIMIT 1
            ");

            $stmtExiste->execute([
                ':usuario' => $usuario,
            ]);

            if ($stmtExiste->fetch()) {
                json_response([
                    'success' => false,
                    'ok' => false,
                    'message' => 'El usuario ya existe',
                ], 409);
            }

            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = db()->prepare("
                INSERT INTO cuentas_cirugias
                (usuario, contrasena, rol, estado)
                VALUES
                (:usuario, :contrasena, :rol, 1)
            ");

            $stmt->execute([
                ':usuario' => $usuario,
                ':contrasena' => $passwordHash,
                ':rol' => $rol,
            ]);

            json_response([
                'success' => true,
                'ok' => true,
                'message' => 'Usuario creado correctamente',
            ]);
        } catch (Throwable $e) {
            json_response([
                'success' => false,
                'ok' => false,
                'message' => 'Error al crear usuario',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }

public static function cambiarEstadoUsuario(): void
{
    self::requireAdmin();

    try {
        $input = get_json_input();

        $id = (int) ($input['id'] ?? 0);
        $estado = (int) ($input['estado'] ?? 0);

        if ($id <= 0 || !in_array($estado, [0, 1], true)) {
            json_response([
                'success' => false,
                'ok' => false,
                'message' => 'Datos inválidos',
            ], 400);
        }

        $stmtUsuario = db()->prepare("
            SELECT id, rol
            FROM cuentas_cirugias
            WHERE id = :id
            LIMIT 1
        ");

        $stmtUsuario->execute([
            ':id' => $id,
        ]);

        $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            json_response([
                'success' => false,
                'ok' => false,
                'message' => 'El usuario no existe',
            ], 404);
        }

        if ((int) $usuario['rol'] === 0) {
            json_response([
                'success' => false,
                'ok' => false,
                'message' => 'No se puede desactivar una cuenta administradora',
            ], 403);
        }

        $stmt = db()->prepare("
            UPDATE cuentas_cirugias
            SET estado = :estado
            WHERE id = :id
        ");

        $stmt->execute([
            ':estado' => $estado,
            ':id' => $id,
        ]);

        json_response([
            'success' => true,
            'ok' => true,
            'message' => 'Estado actualizado correctamente',
        ]);
    } catch (Throwable $e) {
        json_response([
            'success' => false,
            'ok' => false,
            'message' => 'Error al cambiar estado del usuario',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

public static function eliminarUsuario(): void
{
    self::requireAdmin();

    try {
        $input = get_json_input();

        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            json_response([
                'success' => false,
                'ok' => false,
                'message' => 'ID inválido',
            ], 400);
        }

        $stmtUsuario = db()->prepare("
            SELECT id, rol
            FROM cuentas_cirugias
            WHERE id = :id
            LIMIT 1
        ");

        $stmtUsuario->execute([
            ':id' => $id,
        ]);

        $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            json_response([
                'success' => false,
                'ok' => false,
                'message' => 'El usuario no existe',
            ], 404);
        }

        if ((int) $usuario['rol'] === 0) {
            json_response([
                'success' => false,
                'ok' => false,
                'message' => 'No se puede eliminar una cuenta administradora',
            ], 403);
        }

        $stmt = db()->prepare("
            DELETE FROM cuentas_cirugias
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id,
        ]);

        json_response([
            'success' => true,
            'ok' => true,
            'message' => 'Usuario eliminado correctamente',
        ]);
    } catch (Throwable $e) {
        json_response([
            'success' => false,
            'ok' => false,
            'message' => 'Error al eliminar usuario',
            'debug' => $e->getMessage(),
        ], 500);
    }
}

    private static function requireAdmin(): void
    {
        self::ensureSession();

        if (! empty($_SESSION['ueei_id']) && function_exists('require_modulo_api')) {
            require_modulo_api('cirugias');
            self::bootstrapCentralSession();

            if (! function_exists('ueei_usuario_es_admin') || ! ueei_usuario_es_admin()) {
                json_response([
                    'success' => false,
                    'ok' => false,
                    'message' => 'No tienes permisos de administrador',
                ], 403);
            }

            return;
        }

        if (empty($_SESSION['cirugias_usuario'])) {
            json_response([
                'success' => false,
                'ok' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        if ((int) ($_SESSION['cirugias_rol'] ?? 1) !== 0) {
            json_response([
                'success' => false,
                'ok' => false,
                'message' => 'No tienes permisos de administrador',
            ], 403);
        }
    }

    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (function_exists('iniciar_sesion_segura')) {
            iniciar_sesion_segura();
            return;
        }

        session_start();
    }

    private static function generic_auth_error(): void
    {
        json_response([
            'success' => false,
            'ok' => false,
            'message' => 'Usuario o contraseña incorrectos',
        ], 401);
    }
}
