# Intranet HSJ

Plataforma institucional del Hospital San José migrada progresivamente desde
PHP tradicional hacia Laravel 13. El proyecto conserva temporalmente módulos
legacy mientras centraliza autenticación, usuarios, roles y permisos.

## Requisitos

- PHP 8.3 o superior con `sqlsrv` y `pdo_sqlsrv`.
- Composer 2.
- Node.js 20 o superior y npm.
- SQL Server.

## Instalación local

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
npm run build
php artisan migrate
php artisan serve
```

Las credenciales y nombres reales de servidores se configuran únicamente en
`.env`. Ese archivo no debe incorporarse al repositorio.

## Bases de datos

| Conexión | Responsabilidad |
| --- | --- |
| `DB_CONNECTION` | Datos propios del aplicativo Intranet HSJ |
| `identity` | Usuarios, cuentas, roles y permisos centralizados en `HSJ_Identity` |
| `sigh` | Consulta de información clínica/citas en modo de solo lectura |
| `legacy` | Compatibilidad temporal con módulos heredados |

## Arquitectura de transición

- `routes/web.php` recibe las solicitudes Laravel.
- `app/Http/Controllers/LegacyApplicationController.php` mantiene el puente
  temporal hacia `legacy/index.php`.
- `app/controllers` y `views` contienen módulos heredados aún no refactorizados.
- `app/Models`, `app/Services` y `resources/views` contienen implementaciones
  Laravel modernas.
- La autenticación y autorización nuevas consumen la base central
  `HSJ_Identity`.

## Recursos frontend

Preline UI y Tailwind CSS se instalan mediante npm. Para generar los recursos
publicables:

```bash
npm run build
```

El proceso compila Tailwind y copia la distribución oficial de Preline hacia
`public/assets/vendor/preline`.

## Pruebas

```bash
composer test
```

## Seguridad

- Nunca versionar `.env`, contraseñas, tokens ni copias de bases de datos.
- La conexión clínica debe permanecer en solo lectura salvo una autorización
  funcional explícita.
- Los accesos administrativos dependen de los roles y permisos centralizados.

Consulta [CHANGELOG.md](CHANGELOG.md) para el historial funcional.
