<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/helpers/response.php';

final class UviAuthController
{
    public static function login(): void
    {
        $input = get_json_input();

        $usuario = trim((string) ($input['usuario'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if (!$usuario || !$password) {
            json_response([
                'success' => false,
                'message' => 'Completa usuario y contraseña.',
            ], 400);
        }

        $stmt = db()->prepare("
            SELECT id, usuario, password, rol, estado
            FROM usuarios_uvi
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

        if (!password_verify($password, $user['password'])) {
            self::authError();
        }

        session_regenerate_id(true);

        $_SESSION['uvi_id'] = (int) $user['id'];
        $_SESSION['uvi_usuario'] = $user['usuario'];
        $_SESSION['uvi_rol'] = (int) $user['rol'];

        json_response([
            'success' => true,
            'message' => 'Inicio de sesión correcto.',
            'redirect' => ((int) $user['rol'] === 1)
                ? url_path('/admin-uvi')
                : url_path('/principal'),
        ]);
    }

    public static function listarUsuarios(): void
    {
        self::requireAdmin();

        $stmt = db()->query("
            SELECT id, usuario, rol, estado
            FROM usuarios_uvi
            ORDER BY id DESC
        ");

        json_response($stmt->fetchAll());
    }

    public static function crearCuenta(): void
    {
        self::requireAdmin();

        $input = get_json_input();

        $usuario = trim((string) ($input['usuario'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $rol = (int) ($input['rol'] ?? 0);

        if (!$usuario || !$password) {
            json_response([
                'success' => false,
                'message' => 'Completa todos los campos.',
            ], 400);
        }

        if (strlen($usuario) < 3 || strlen($usuario) > 80) {
            json_response([
                'success' => false,
                'message' => 'El usuario debe tener entre 3 y 80 caracteres.',
            ], 400);
        }

        if (strlen($password) < 6 || strlen($password) > 72) {
            json_response([
                'success' => false,
                'message' => 'La contraseña debe tener entre 6 y 72 caracteres.',
            ], 400);
        }

        $rol = $rol === 1 ? 1 : 0;

        $stmt = db()->prepare("
            SELECT id
            FROM usuarios_uvi
            WHERE usuario = :usuario
            LIMIT 1
        ");

        $stmt->execute([
            ':usuario' => $usuario,
        ]);

        if ($stmt->fetch()) {
            json_response([
                'success' => false,
                'message' => 'El usuario ya existe.',
            ], 409);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, [
            'cost' => 12,
        ]);

        $insert = db()->prepare("
            INSERT INTO usuarios_uvi
                (usuario, password, rol, estado)
            VALUES
                (:usuario, :password, :rol, 1)
        ");

        $insert->execute([
            ':usuario' => $usuario,
            ':password' => $hash,
            ':rol' => $rol,
        ]);

        json_response([
            'success' => true,
            'message' => 'Cuenta creada correctamente.',
        ]);
    }

    public static function actualizarUsuario(int $id): void
    {
        self::requireAdmin();

        $input = get_json_input();

        $usuario = trim((string) ($input['usuario'] ?? ''));
        $rol = (int) ($input['rol'] ?? 0);

        if ($id <= 0 || !$usuario) {
            json_response([
                'success' => false,
                'message' => 'Datos inválidos.',
            ], 400);
        }

        if (strlen($usuario) < 3 || strlen($usuario) > 80) {
            json_response([
                'success' => false,
                'message' => 'El usuario debe tener entre 3 y 80 caracteres.',
            ], 400);
        }

        $rol = $rol === 1 ? 1 : 0;

        $stmt = db()->prepare("
            UPDATE usuarios_uvi
            SET usuario = :usuario,
                rol = :rol
            WHERE id = :id
        ");

        $stmt->execute([
            ':usuario' => $usuario,
            ':rol' => $rol,
            ':id' => $id,
        ]);

        json_response([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.',
        ]);
    }

    public static function cambiarEstadoUsuario(int $id): void
    {
        self::requireAdmin();

        if ($id <= 0) {
            json_response([
                'success' => false,
                'message' => 'ID inválido.',
            ], 400);
        }

        if ((int) ($_SESSION['uvi_id'] ?? 0) === $id) {
            json_response([
                'success' => false,
                'message' => 'No puedes cambiar el estado de tu propio usuario.',
            ], 400);
        }

        $stmt = db()->prepare("
            UPDATE usuarios_uvi
            SET estado = CASE
                WHEN estado = 1 THEN 0
                ELSE 1
            END
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id,
        ]);

        json_response([
            'success' => true,
            'message' => 'Estado actualizado correctamente.',
        ]);
    }

    public static function logout(): void
    {
        unset(
            $_SESSION['uvi_id'],
            $_SESSION['uvi_usuario'],
            $_SESSION['uvi_rol']
        );

        json_response([
            'success' => true,
            'message' => 'Sesión UVI cerrada.',
        ]);
    }

    private static function requireAdmin(): void
    {
        if (
            empty($_SESSION['uvi_usuario']) ||
            (int) ($_SESSION['uvi_rol'] ?? 0) !== 1
        ) {
            json_response([
                'success' => false,
                'message' => 'No autorizado.',
            ], 403);
        }
    }

    private static function authError(): void
    {
        json_response([
            'success' => false,
            'message' => 'Credenciales inválidas.',
        ], 401);
    }
}