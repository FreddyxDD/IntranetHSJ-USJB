<?php
declare(strict_types=1);

function json_response(array $data, int $status = 200): void
{
    if (array_key_exists('debug', $data)) {
        $technicalMessage = (string) $data['debug'];
        $reference = (string) ($data['reference'] ?? '');

        if ($reference === '') {
            $reference = class_exists(\App\Support\UserFacingError::class)
                ? \App\Support\UserFacingError::reference('INTRA-LEGACY')
                : 'INTRA-'.date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        }

        if (function_exists('logger')) {
            logger()->error('Falla controlada en API heredada.', [
                'support_reference' => $reference,
                'technical_message' => $technicalMessage,
                'path' => $_SERVER['REQUEST_URI'] ?? null,
            ]);
        } else {
            error_log("[{$reference}] {$technicalMessage}");
        }

        unset($data['debug']);
        $data['reference'] = $reference;
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);

    return is_array($data) ? $data : [];
}

function normalize_email(?string $email): string
{
    return strtolower(trim((string) $email));
}

function valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function strong_password(string $password): bool
{
    return strlen($password) >= 8
        && strlen($password) <= 72
        && preg_match('/[a-z]/', $password)
        && preg_match('/[A-Z]/', $password)
        && preg_match('/\d/', $password);
}
