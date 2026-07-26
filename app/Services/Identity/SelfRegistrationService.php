<?php

namespace App\Services\Identity;

use App\Models\AccessAccount;
use App\Models\AccessRole;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class SelfRegistrationService
{
    public const REGISTRATION_SOURCE = 'self_service_identity';

    /**
     * @return array{dni:string, person_id:int, personnel_record_id:?int, display_name:string, masked_name:string, birth_date:string}
     */
    public function validateDni(string $dni): array
    {
        $dni = $this->normalizeDni($dni);
        $identity = DB::connection('identity');
        $documentTypeId = $identity->table('personnel_document_types')
            ->where('code', 'DNI')
            ->value('id');

        $person = $identity->table('people')
            ->where('document_type_id', $documentTypeId)
            ->where('document_number', $dni)
            ->where('status', 'active')
            ->first();

        if (! $person) {
            throw new DomainException('El DNI no está habilitado en la identidad institucional. Verifica el número o solicita la actualización de tus datos.');
        }

        if (empty($person->birth_date)) {
            throw new DomainException('El registro institucional no tiene fecha de nacimiento. Solicita su actualización antes de crear la cuenta.');
        }

        if (
            User::query()->where('registration_document_number', $dni)->exists()
            || AccessAccount::query()->where('username', $dni)->exists()
            || AccessAccount::query()->where('person_id', $person->id)->exists()
        ) {
            throw new DomainException('Este DNI ya tiene una cuenta. Utiliza la opción de inicio de sesión o solicita ayuda al administrador.');
        }

        $personnelRecordId = $identity->table('personnel_records')
            ->where('document_type_id', $documentTypeId)
            ->where('document_number', $dni)
            ->where('is_active', true)
            ->latest('source_registered_at')
            ->latest('id')
            ->value('id');
        $displayName = $this->displayName($person);

        return [
            'dni' => $dni,
            'person_id' => (int) $person->id,
            'personnel_record_id' => $personnelRecordId ? (int) $personnelRecordId : null,
            'display_name' => $displayName,
            'masked_name' => $this->maskName($displayName),
            'birth_date' => CarbonImmutable::parse($person->birth_date)->format('Y-m-d'),
        ];
    }

    /**
     * @return array{user:User, username:string, initial_password:string, display_name:string}
     */
    public function createAccount(string $dni): array
    {
        return DB::connection('identity')->transaction(function () use ($dni): array {
            $identity = $this->validateDni($dni);
            $role = AccessRole::query()
                ->where('code', 'consulta')
                ->whereHas('application', fn ($query) => $query
                    ->where('code', config('access.application'))
                    ->where('is_active', true))
                ->first();

            if (! $role) {
                throw new DomainException('El perfil institucional de consulta no está configurado. Comunícate con el administrador.');
            }

            $email = $this->accountEmail($identity['dni'], $identity['person_id']);
            $password = self::initialPassword($identity['birth_date'], $identity['dni']);
            $hash = Hash::make($password);

            $user = User::query()->create([
                'person_id' => $identity['person_id'],
                'personnel_record_id' => $identity['personnel_record_id'],
                'registration_document_number' => $identity['dni'],
                'registration_source' => self::REGISTRATION_SOURCE,
                'name' => $identity['display_name'],
                'email' => $email,
                'password' => $hash,
                'rol' => 'consulta',
                'tipo_usuario' => 'institucional',
                'activo' => true,
            ]);

            $account = AccessAccount::query()->create([
                'user_id' => $user->id,
                'person_id' => $identity['person_id'],
                'personnel_record_id' => $identity['personnel_record_id'],
                'username' => $identity['dni'],
                'email' => $email,
                'password' => $hash,
                'display_name' => $identity['display_name'],
                'status' => 'active',
                'must_change_password' => false,
                'registration_instructions_acknowledged_at' => null,
                'created_by' => null,
            ]);

            $account->roles()->attach($role->id, [
                'assigned_at' => now(),
                'assigned_by' => null,
            ]);

            return [
                'user' => $user,
                'username' => $identity['dni'],
                'initial_password' => $password,
                'display_name' => $identity['display_name'],
            ];
        }, 3);
    }

    public static function initialPassword(string $birthDate, string $dni): string
    {
        return CarbonImmutable::parse($birthDate)->format('dmY').substr($dni, -4);
    }

    /**
     * @return array{username:string, initial_password:string, password_rule:string}
     */
    public function accountInstructions(User $user): array
    {
        $dni = (string) $user->registration_document_number;
        $birthDate = DB::connection('identity')->table('people')
            ->where('id', $user->person_id)
            ->value('birth_date');

        return [
            'username' => $dni,
            'initial_password' => self::initialPassword((string) $birthDate, $dni),
            'password_rule' => 'Fecha de nacimiento en formato DDMMAAAA + últimos 4 dígitos del DNI',
        ];
    }

    private function normalizeDni(string $dni): string
    {
        $dni = preg_replace('/\D+/', '', trim($dni)) ?? '';

        if (! preg_match('/^\d{8}$/', $dni)) {
            throw new DomainException('Ingresa un DNI válido de 8 dígitos.');
        }

        return $dni;
    }

    private function displayName(object $person): string
    {
        return trim(implode(' ', array_filter([
            $person->names ?? null,
            $person->paternal_last_name ?? null,
            $person->maternal_last_name ?? null,
        ])));
    }

    private function maskName(string $name): string
    {
        return collect(preg_split('/\s+/', $name) ?: [])
            ->map(static fn (string $part): string => mb_substr($part, 0, 1).'***')
            ->implode(' ');
    }

    private function accountEmail(string $dni, int $personId): string
    {
        $personEmail = strtolower(trim((string) DB::connection('identity')
            ->table('people')
            ->where('id', $personId)
            ->value('email')));

        if (
            filter_var($personEmail, FILTER_VALIDATE_EMAIL)
            && ! User::query()->where('email', $personEmail)->exists()
            && ! AccessAccount::query()->where('email', $personEmail)->exists()
        ) {
            return $personEmail;
        }

        return $dni.'@identity.hsj.local';
    }
}
