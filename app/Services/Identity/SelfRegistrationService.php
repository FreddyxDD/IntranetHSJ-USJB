<?php

namespace App\Services\Identity;

use App\Models\AccessAccount;
use App\Models\AccessRole;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

final class SelfRegistrationService
{
    public const REGISTRATION_SOURCE = 'self_service_identity';

    /**
     * @return array{found:bool, registration_mode:string, dni:string, person_id?:int, personnel_record_id?:?int, display_name?:string, masked_name?:string, birth_date?:string}
     */
    public function lookupDni(string $dni): array
    {
        $dni = $this->normalizeDni($dni);
        $identity = DB::connection('identity');
        $documentTypeId = $this->dniDocumentTypeId();
        $person = $identity->table('people')
            ->where('document_type_id', $documentTypeId)
            ->where('document_number', $dni)
            ->first();

        if (! $person) {
            return ['found' => false, 'registration_mode' => 'manual_new', 'dni' => $dni];
        }

        $this->ensureAccountDoesNotExist($dni, (int) $person->id);

        if (empty($person->birth_date)) {
            if ($person->status === 'active') {
                throw new DomainException('El registro institucional no tiene fecha de nacimiento. Solicita su actualización antes de crear la cuenta.');
            }
        }

        if ($person->status !== 'active') {
            return [
                'found' => true,
                'registration_mode' => 'manual_existing',
                'dni' => $dni,
                'person_id' => (int) $person->id,
                'display_name' => $this->displayName($person),
                'masked_name' => $this->maskName($this->displayName($person)),
            ];
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
            'found' => true,
            'registration_mode' => 'existing',
            'dni' => $dni,
            'person_id' => (int) $person->id,
            'personnel_record_id' => $personnelRecordId ? (int) $personnelRecordId : null,
            'display_name' => $displayName,
            'masked_name' => $this->maskName($displayName),
            'birth_date' => CarbonImmutable::parse($person->birth_date)->format('Y-m-d'),
        ];
    }

    /**
     * @return array{dni:string, person_id:int, personnel_record_id:?int, display_name:string, masked_name:string, birth_date:string}
     */
    public function validateDni(string $dni): array
    {
        $identity = $this->lookupDni($dni);

        if ($identity['registration_mode'] !== 'existing') {
            throw new DomainException('El DNI no está habilitado en la identidad institucional. Verifica el número o solicita la actualización de tus datos.');
        }

        return [
            'dni' => $identity['dni'],
            'person_id' => $identity['person_id'],
            'personnel_record_id' => $identity['personnel_record_id'],
            'display_name' => $identity['display_name'],
            'masked_name' => $identity['masked_name'],
            'birth_date' => $identity['birth_date'],
        ];
    }

    /**
     * @return array{user:User, username:string, initial_password:string, display_name:string}
     */
    public function createAccount(string $dni): array
    {
        return DB::connection('identity')->transaction(function () use ($dni): array {
            $identity = $this->validateDni($dni);
            $role = $this->queryRole();

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
                'approved_at' => now(),
                'approved_by' => null,
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

    /**
     * @param  array<string, mixed>  $input
     * @return array{user:User, username:string, initial_password:string, display_name:string}
     */
    public function createPendingAccount(array $input): array
    {
        $data = $this->validateManualIdentity($input);

        return DB::connection('identity')->transaction(function () use ($data): array {
            $lookup = $this->lookupDni($data['dni']);
            if ($lookup['registration_mode'] === 'existing') {
                throw new DomainException('El DNI fue incorporado a la identidad institucional durante el registro. Vuelve a validarlo.');
            }

            if (
                User::query()->where('email', $data['email'])->exists()
                || AccessAccount::query()->where('email', $data['email'])->exists()
            ) {
                throw new DomainException('El correo ingresado ya está asociado a otra cuenta.');
            }

            $role = $this->queryRole();
            $personValues = [
                'document_type_id' => $this->dniDocumentTypeId(),
                'document_number' => $data['dni'],
                'paternal_last_name' => $data['paternal_last_name'],
                'maternal_last_name' => $data['maternal_last_name'],
                'names' => $data['names'],
                'birth_date' => $data['birth_date'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'status' => 'pending',
                'data_origin' => 'self_registration',
                'last_synced_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $personId = isset($lookup['person_id'])
                ? (int) $lookup['person_id']
                : (int) DB::connection('identity')->table('people')->insertGetId($personValues);

            if (isset($lookup['person_id'])) {
                unset($personValues['created_at']);
                DB::connection('identity')->table('people')
                    ->where('id', $personId)
                    ->update($personValues);
            }
            $displayName = trim($data['names'].' '.$data['paternal_last_name'].' '.$data['maternal_last_name']);
            $password = self::initialPassword($data['birth_date'], $data['dni']);
            $hash = Hash::make($password);

            $user = User::query()->create([
                'person_id' => $personId,
                'personnel_record_id' => null,
                'registration_document_number' => $data['dni'],
                'registration_source' => self::REGISTRATION_SOURCE,
                'name' => $displayName,
                'email' => $data['email'],
                'password' => $hash,
                'rol' => 'consulta',
                'tipo_usuario' => 'institucional',
                'activo' => false,
            ]);

            $account = AccessAccount::query()->create([
                'user_id' => $user->id,
                'person_id' => $personId,
                'personnel_record_id' => null,
                'username' => $data['dni'],
                'email' => $data['email'],
                'password' => $hash,
                'display_name' => $displayName,
                'status' => 'pending',
                'must_change_password' => false,
                'registration_instructions_acknowledged_at' => null,
                'approved_at' => null,
                'approved_by' => null,
                'created_by' => null,
            ]);
            $account->roles()->attach($role->id, [
                'assigned_at' => now(),
                'assigned_by' => null,
            ]);

            return [
                'user' => $user,
                'username' => $data['dni'],
                'initial_password' => $password,
                'display_name' => $displayName,
            ];
        }, 3);
    }

    public function approvePendingAccount(User $user, int $approvedBy): User
    {
        $user->loadMissing(['person', 'accessAccount']);

        if (! $user->accessAccount || $user->accessAccount->status !== 'pending') {
            throw new DomainException('La cuenta seleccionada no tiene una solicitud pendiente.');
        }

        if (! $user->person || $user->person->status !== 'pending') {
            throw new DomainException('La persona vinculada no se encuentra pendiente de aprobación.');
        }

        DB::connection('identity')->transaction(function () use ($user, $approvedBy): void {
            $user->person->forceFill(['status' => 'active'])->save();
            $user->forceFill(['activo' => true])->save();
            $user->accessAccount->forceFill([
                'status' => 'active',
                'approved_at' => now(),
                'approved_by' => $approvedBy ?: null,
            ])->save();
        });

        return $user->fresh(['person', 'accessAccount.roles']);
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

    /**
     * @param  array<string, mixed>  $input
     * @return array{dni:string,names:string,paternal_last_name:string,maternal_last_name:string,birth_date:string,email:string,phone:string}
     */
    private function validateManualIdentity(array $input): array
    {
        $input['dni'] = $this->normalizeDni((string) ($input['dni'] ?? ''));
        $input['email'] = strtolower(trim((string) ($input['email'] ?? '')));
        $input['phone'] = preg_replace('/[^\d+]/', '', (string) ($input['phone'] ?? '')) ?? '';

        $validator = Validator::make($input, [
            'dni' => ['required', 'regex:/^\d{8}$/'],
            'names' => ['required', 'string', 'min:2', 'max:180'],
            'paternal_last_name' => ['required', 'string', 'min:2', 'max:80'],
            'maternal_last_name' => ['required', 'string', 'min:2', 'max:80'],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before:today'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'regex:/^\+?\d{7,15}$/', 'max:30'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'phone.regex' => 'Ingresa un teléfono válido de 7 a 15 dígitos.',
        ], [
            'names' => 'nombres',
            'paternal_last_name' => 'apellido paterno',
            'maternal_last_name' => 'apellido materno',
            'birth_date' => 'fecha de nacimiento',
            'email' => 'correo',
            'phone' => 'teléfono',
        ]);

        if ($validator->fails()) {
            throw new DomainException($validator->errors()->first());
        }

        /** @var array{dni:string,names:string,paternal_last_name:string,maternal_last_name:string,birth_date:string,email:string,phone:string} $validated */
        $validated = $validator->validated();

        return array_map(static fn (string $value): string => trim($value), $validated);
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

    private function dniDocumentTypeId(): int
    {
        $id = DB::connection('identity')->table('personnel_document_types')
            ->where('code', 'DNI')
            ->value('id');

        if (! $id) {
            throw new DomainException('El tipo documental DNI no está configurado en HSJ_Identity.');
        }

        return (int) $id;
    }

    private function ensureAccountDoesNotExist(string $dni, int $personId): void
    {
        if (
            User::query()->where('registration_document_number', $dni)->exists()
            || AccessAccount::query()->where('username', $dni)->exists()
            || AccessAccount::query()->where('person_id', $personId)->exists()
        ) {
            throw new DomainException('Este DNI ya tiene una cuenta. Utiliza la opción de inicio de sesión o solicita ayuda al administrador.');
        }
    }

    private function queryRole(): AccessRole
    {
        $role = AccessRole::query()
            ->where('code', 'consulta')
            ->whereHas('application', fn ($query) => $query
                ->where('code', config('access.application'))
                ->where('is_active', true))
            ->first();

        if (! $role) {
            throw new DomainException('El perfil institucional de consulta no está configurado. Comunícate con el administrador.');
        }

        return $role;
    }
}
