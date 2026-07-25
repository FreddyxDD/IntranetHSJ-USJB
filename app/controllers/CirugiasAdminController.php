<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cirugias_auth.php';

class CirugiasAdminController
{
    public function listarUsuarios(): void
    {
        cirugias_require_admin_api();

        $stmt = db()->query("
            SELECT id, usuario, nombre_completo, rol, estado, creado_en
            FROM cirugias_usuarios
            ORDER BY id DESC
        ");

        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        cirugias_json([
            'ok' => true,
            'usuarios' => $usuarios
        ]);
    }

    public function crearUsuario(): void
    {
        cirugias_require_admin_api();

        $data = cirugias_input();

        $usuario = trim((string)($data['usuario'] ?? ''));
        $nombre = trim((string)($data['nombre_completo'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $rol = isset($data['rol']) ? (int)$data['rol'] : 1;

        if ($usuario === '' || $nombre === '' || $password === '') {
            cirugias_json([
                'ok' => false,
                'message' => 'Complete usuario, nombre y contraseña.'
            ], 422);
        }

        if (!in_array($rol, [0, 1], true)) {
            cirugias_json([
                'ok' => false,
                'message' => 'Rol inválido.'
            ], 422);
        }

        if (strlen($password) < 6) {
            cirugias_json([
                'ok' => false,
                'message' => 'La contraseña debe tener mínimo 6 caracteres.'
            ], 422);
        }

        $verificar = db()->prepare("
            SELECT id 
            FROM cirugias_usuarios 
            WHERE usuario = :usuario 
            LIMIT 1
        ");

        $verificar->execute([
            ':usuario' => $usuario
        ]);

        if ($verificar->fetch()) {
            cirugias_json([
                'ok' => false,
                'message' => 'El usuario ya existe.'
            ], 409);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = db()->prepare("
            INSERT INTO cirugias_usuarios 
            (usuario, nombre_completo, password_hash, rol, estado)
            VALUES
            (:usuario, :nombre_completo, :password_hash, :rol, 1)
        ");

        $stmt->execute([
            ':usuario' => $usuario,
            ':nombre_completo' => $nombre,
            ':password_hash' => $hash,
            ':rol' => $rol
        ]);

        cirugias_json([
            'ok' => true,
            'message' => 'Usuario creado correctamente.'
        ]);
    }

    public function cambiarEstado(): void
    {
        cirugias_require_admin_api();

        $data = cirugias_input();

        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $estado = isset($data['estado']) ? (int)$data['estado'] : 0;

        if ($id <= 0 || !in_array($estado, [0, 1], true)) {
            cirugias_json([
                'ok' => false,
                'message' => 'Datos inválidos.'
            ], 422);
        }

        if ($id === (int)($_SESSION['cirugias_usuario_id'] ?? 0) && $estado === 0) {
            cirugias_json([
                'ok' => false,
                'message' => 'No puedes desactivar tu propia cuenta.'
            ], 422);
        }

        $stmt = db()->prepare("
            UPDATE cirugias_usuarios
            SET estado = :estado
            WHERE id = :id
        ");

        $stmt->execute([
            ':estado' => $estado,
            ':id' => $id
        ]);

        cirugias_json([
            'ok' => true,
            'message' => 'Estado actualizado correctamente.'
        ]);
    }
}