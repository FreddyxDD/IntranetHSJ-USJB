<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Identity\ModuleCatalogService;
use App\Services\Identity\SelfRegistrationService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

final class InstitutionalAuthController extends Controller
{
    public static function me(): void
    {
        $userId = (int) ($_SESSION['ueei_id'] ?? 0);

        if ($userId <= 0) {
            self::json(['ok' => false, 'message' => 'No autenticado UEeI'], 401);
        }

        $user = self::userQuery()->find($userId);

        if (! $user || ! $user->activo) {
            self::destruirSesion();
            self::json(['ok' => false, 'message' => 'Sesión UEeI inválida o expirada.'], 401);
        }

        self::establecerSesion($user);
        self::respondAuthenticated($user);
    }

    public static function register(): void
    {
        self::guardSelfRegistrationRate('create', 3);
        $input = request()->json()->all();
        $dni = preg_replace('/\D+/', '', (string) ($input['dni'] ?? '')) ?? '';
        $validation = $_SESSION['self_registration_validation'] ?? null;

        if (
            ! is_array($validation)
            || ! hash_equals((string) ($validation['dni'] ?? ''), $dni)
            || (int) ($validation['expires_at'] ?? 0) < time()
        ) {
            self::json([
                'success' => false,
                'ok' => false,
                'message' => 'Primero valida el DNI. La validación tiene una vigencia de 10 minutos.',
            ], 422);
        }

        try {
            $registrationMode = (string) ($validation['mode'] ?? 'existing');
            $manualRegistration = str_starts_with($registrationMode, 'manual_');

            if ($registrationMode === 'personnel_review') {
                $request = app(SelfRegistrationService::class)->createPersonnelReviewRequest($input);
                unset($_SESSION['self_registration_validation']);

                logger()->info('Solicitud de evaluación laboral enviada a Legajos.', [
                    'request_id' => $request['request_id'],
                    'person_id' => $request['person_id'],
                    'document_number_hash' => hash('sha256', $request['document_number']),
                    'target_application' => 'legajos_hsj',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ]);

                self::json([
                    'success' => true,
                    'ok' => true,
                    'personnel_review_pending' => true,
                    'request_id' => $request['request_id'],
                    'message' => 'La solicitud fue enviada a Legajos. No se creó ni reactivó ninguna cuenta.',
                ], 201);
            }

            $result = $manualRegistration
                ? app(SelfRegistrationService::class)->createPendingAccount($input)
                : app(SelfRegistrationService::class)->createAccount($dni);
            unset($_SESSION['self_registration_validation']);

            if ($manualRegistration) {
                logger()->info('Solicitud de cuenta institucional creada por autoservicio.', [
                    'user_id' => $result['user']->id,
                    'person_id' => $result['user']->person_id,
                    'registration_source' => SelfRegistrationService::REGISTRATION_SOURCE,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ]);

                self::json([
                    'success' => true,
                    'ok' => true,
                    'pending_approval' => true,
                    'message' => 'Tu solicitud fue registrada y está pendiente de aprobación por un administrador.',
                    'account_instructions' => [
                        'username' => $result['username'],
                        'initial_password' => $result['initial_password'],
                        'password_rule' => 'Fecha de nacimiento en formato DDMMAAAA + últimos 4 dígitos del DNI',
                        'access_level' => 'Pendiente de aprobación',
                        'access_request_message' => 'Podrás iniciar sesión cuando el administrador apruebe y active tu cuenta.',
                    ],
                ], 201);
            }

            session_regenerate_id(true);
            $user = self::userQuery()->findOrFail($result['user']->id);
            self::establecerSesion($user);

            logger()->info('Cuenta institucional creada por autoservicio.', [
                'user_id' => $user->id,
                'person_id' => $user->person_id,
                'registration_source' => SelfRegistrationService::REGISTRATION_SOURCE,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            self::respondAuthenticated($user, 'Tu cuenta fue creada y activada correctamente.', [
                'requires_account_confirmation' => true,
                'account_instructions' => [
                    'username' => $result['username'],
                    'initial_password' => $result['initial_password'],
                    'password_rule' => 'Fecha de nacimiento en formato DDMMAAAA + últimos 4 dígitos del DNI',
                    'access_level' => 'Consulta',
                    'access_request_message' => 'Si necesitas más accesos, debes solicitarlos al administrador de la plataforma.',
                ],
            ]);
        } catch (DomainException $exception) {
            self::json(['success' => false, 'ok' => false, 'message' => $exception->getMessage()], 422);
        } catch (QueryException $exception) {
            logger()->warning('Conflicto al crear una cuenta institucional.', [
                'dni_hash' => hash('sha256', $dni),
                'error' => $exception->getMessage(),
            ]);
            self::json([
                'success' => false,
                'ok' => false,
                'message' => 'No se pudo crear la cuenta. El DNI o sus datos de acceso ya se encuentran registrados.',
            ], 409);
        } catch (Throwable $exception) {
            report($exception);
            self::json([
                'success' => false,
                'ok' => false,
                'message' => 'No se pudo completar el registro institucional. Inténtalo nuevamente o comunícate con el administrador.',
            ], 500);
        }
    }

    public static function validateRegistrationDni(): void
    {
        self::guardSelfRegistrationRate('validate', 5);
        $dni = (string) request()->json('dni', '');

        try {
            $identity = app(SelfRegistrationService::class)->lookupDni($dni);
            $_SESSION['self_registration_validation'] = [
                'dni' => $identity['dni'],
                'person_id' => $identity['person_id'] ?? null,
                'mode' => $identity['registration_mode'],
                'expires_at' => time() + 600,
            ];

            self::json([
                'success' => true,
                'ok' => true,
                'message' => match ($identity['registration_mode']) {
                    'existing' => 'DNI validado con la identidad institucional.',
                    'personnel_review' => 'El DNI corresponde a una persona sin vínculo laboral activo. Completa los datos para solicitar una evaluación al administrador de Legajos.',
                    default => 'El DNI no existe en HSJ_Identity. Completa tus datos para enviar una solicitud de registro.',
                },
                'data' => [
                    'dni' => $identity['dni'],
                    'found' => $identity['found'],
                    'registration_mode' => $identity['registration_mode'],
                    'masked_name' => $identity['masked_name'] ?? null,
                    'expires_in_minutes' => 10,
                ],
            ]);
        } catch (DomainException $exception) {
            unset($_SESSION['self_registration_validation']);
            self::json(['success' => false, 'ok' => false, 'message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);
            self::json([
                'success' => false,
                'ok' => false,
                'message' => 'No se pudo validar el DNI con la identidad institucional. Inténtalo nuevamente.',
            ], 503);
        }
    }

    public static function confirmAccountInstructions(): void
    {
        $user = self::userQuery()->find((int) ($_SESSION['ueei_id'] ?? 0));

        if (! $user || ! self::requiresAccountConfirmation($user)) {
            self::json(['success' => false, 'ok' => false, 'message' => 'No existe una activación pendiente.'], 409);
        }

        if ((bool) request()->json('acknowledged', false) !== true) {
            self::json(['success' => false, 'ok' => false, 'message' => 'Debes confirmar que leíste las instrucciones.'], 422);
        }

        DB::connection('identity')->transaction(function () use ($user): void {
            $user->accessAccount->forceFill([
                'registration_instructions_acknowledged_at' => now(),
                'last_login_at' => now(),
            ])->save();
        });
        $_SESSION['account_confirmation_pending'] = false;

        logger()->info('Usuario confirmó las instrucciones de activación.', [
            'user_id' => $user->id,
            'person_id' => $user->person_id,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        self::json([
            'success' => true,
            'ok' => true,
            'message' => 'Confirmación registrada. Ya puedes navegar con el perfil de consulta.',
            'redirect' => url('/pages/principal.html'),
        ]);
    }

    public static function login(): void
    {
        $input = request()->json()->all();
        $identifier = strtolower(trim((string) ($input['correo'] ?? '')));
        $password = (string) ($input['password'] ?? '');

        if ($identifier === '' || $password === '') {
            self::json(['success' => false, 'ok' => false, 'message' => 'Completa todos los campos.'], 400);
        }

        if ((! filter_var($identifier, FILTER_VALIDATE_EMAIL) && ! preg_match('/^\d{8}$/', $identifier)) || strlen($password) > 200) {
            self::genericAuthError();
        }

        $user = self::userQuery()
            ->where(function ($query) use ($identifier): void {
                $query->where('email', $identifier);
                if (preg_match('/^\d{8}$/', $identifier)) {
                    $query->orWhere('registration_document_number', $identifier)
                        ->orWhereHas('accessAccount', fn ($account) => $account->where('username', $identifier));
                }
            })
            ->first();

        if (! $user || ! Hash::check($password, (string) $user->password)) {
            self::genericAuthError();
        }

        if ($user->accessAccount?->status === 'pending') {
            self::json([
                'success' => false,
                'ok' => false,
                'message' => 'Tu solicitud todavía está pendiente de aprobación por un administrador.',
            ], 403);
        }

        if (! $user->activo || ($user->accessAccount && $user->accessAccount->status !== 'active')) {
            self::genericAuthError();
        }

        session_regenerate_id(true);
        self::establecerSesion($user);

        if ($user->accessAccount) {
            $user->accessAccount->forceFill(['last_login_at' => now()])->save();
        }

        $extras = [];
        if (self::requiresAccountConfirmation($user)) {
            $extras['requires_account_confirmation'] = true;
            $extras['account_instructions'] = array_merge(
                app(SelfRegistrationService::class)->accountInstructions($user),
                [
                    'access_level' => 'Consulta',
                    'access_request_message' => 'Si necesitas más accesos, debes solicitarlos al administrador de la plataforma.',
                ]
            );
        }

        self::respondAuthenticated($user, 'Inicio de sesión correcto.', $extras);
    }

    public static function logout(): void
    {
        self::destruirSesion();
        self::json(['ok' => true, 'success' => true, 'message' => 'Sesión cerrada correctamente']);
    }

    private static function userQuery()
    {
        return User::query()->with([
            'accessAccount.roles.application',
            'accessAccount.roles.permissions.application',
        ]);
    }

    private static function establecerSesion(User $user): void
    {
        $application = (string) config('access.application');
        $applicationRoles = $user->accessAccount?->roles
            ->filter(fn ($role): bool => $role->application?->code === $application && $role->application?->is_active)
            ->values() ?? collect();
        $roleCodes = $applicationRoles->pluck('code')->values()->all();
        $role = $user->hasRole('administrador') ? 'admin' : ($roleCodes[0] ?? $user->rol ?? 'consulta');

        $_SESSION['ueei_id'] = (int) $user->id;
        $_SESSION['ueei_correo'] = (string) $user->email;
        $_SESSION['ueei_nombre'] = (string) $user->name;
        $_SESSION['ueei_rol'] = $role;
        $_SESSION['ueei_area_id'] = null;
        $_SESSION['identity_roles'] = $roleCodes;
        $_SESSION['identity_permissions'] = $applicationRoles
            ->flatMap->permissions
            ->filter(fn ($permission): bool => $permission->application?->code === $application)
            ->pluck('code')
            ->unique()
            ->values()
            ->all() ?? [];
        $_SESSION['account_confirmation_pending'] = self::requiresAccountConfirmation($user);
    }

    public static function refrescarSesion(User $user): void
    {
        self::establecerSesion($user);
    }

    private static function respondAuthenticated(User $user, ?string $message = null, array $extras = []): never
    {
        $payload = [
            'success' => true,
            'ok' => true,
            'id' => (int) $user->id,
            'nombre' => (string) $user->name,
            'correo' => (string) $user->email,
            'rol' => (string) $_SESSION['ueei_rol'],
            'area_id' => null,
            'roles' => $_SESSION['identity_roles'],
            'permisos' => $_SESSION['identity_permissions'],
            'modulos' => app(ModuleCatalogService::class)->forUser($user),
        ];

        $payload = array_merge($payload, $extras);

        if (! array_key_exists('requires_account_confirmation', $payload)) {
            $payload['requires_account_confirmation'] = self::requiresAccountConfirmation($user);
        }

        if ($payload['requires_account_confirmation'] && ! array_key_exists('account_instructions', $payload)) {
            $payload['account_instructions'] = array_merge(
                app(SelfRegistrationService::class)->accountInstructions($user),
                [
                    'access_level' => 'Consulta',
                    'access_request_message' => 'Si necesitas más accesos, debes solicitarlos al administrador de la plataforma.',
                ]
            );
        }

        if ($message !== null) {
            $payload['message'] = $message;
        }

        self::json($payload);
    }

    private static function destruirSesion(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', (bool) ($params['secure'] ?? false), (bool) ($params['httponly'] ?? true));
        }

        session_destroy();
    }

    private static function genericAuthError(): never
    {
        self::json(['success' => false, 'ok' => false, 'message' => 'Credenciales inválidas.'], 401);
    }

    private static function requiresAccountConfirmation(User $user): bool
    {
        return $user->registration_source === SelfRegistrationService::REGISTRATION_SOURCE
            && $user->accessAccount
            && $user->accessAccount->registration_instructions_acknowledged_at === null;
    }

    private static function guardSelfRegistrationRate(string $action, int $maxAttempts): void
    {
        $key = 'identity-self-registration:'.$action.':'.($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            self::json([
                'success' => false,
                'ok' => false,
                'message' => 'Se realizaron demasiados intentos. Espera un minuto antes de volver a intentar.',
                'retry_after_seconds' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key, 60);
    }

    private static function json(array $payload, int $status = 200): never
    {
        throw new HttpResponseException(response()->json($payload, $status));
    }
}
