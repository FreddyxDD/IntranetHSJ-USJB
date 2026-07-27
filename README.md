# Intranet HSJ

Plataforma institucional del Hospital San José consolidada sobre Laravel 13,
con autenticación, usuarios, roles y permisos centralizados en `HSJ_Identity`.

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
| `modules` | Datos operativos de indicadores y módulos pendientes de consolidación |

## Arquitectura actual

- `public/index.php` es el único punto de entrada web.
- `routes/web.php` declara todas las rutas de la plataforma.
- `app/Http/Controllers`, `app/Models` y `app/Services` contienen la lógica
  Laravel.
- `resources/views` contiene las vistas Blade.
- La autenticación y autorización consumen exclusivamente `HSJ_Identity`.
- Los módulos operativos no mantienen cuentas, roles ni contraseñas locales.

El flujo completo de solicitudes, el estado de migración de cada módulo, los
cambios efectuados sobre el enrutador PHP original y el cierre de su retiro se
encuentran en
[Transición de PHP tradicional a Laravel 13](docs/TRANSICION_PHP_TRADICIONAL_A_LARAVEL.md).

El contrato obligatorio para integrar Cirugías y futuras aplicaciones está
documentado en
[Arquitectura central de identidad e integración](docs/ARQUITECTURA_IDENTIDAD_Y_APLICACIONES.md).

La consolidación de las bases operativas entregadas para Egresos y Cirugías,
incluyendo conciliación de identidad, secuencia de importación, validaciones y
reversión, se encuentra definida en el
[Plan de consolidación de Egresos y Cirugías](docs/PLAN_MIGRACION_DATOS_EGRESOS_CIRUGIAS.md).

## Flujo de ramas y revisión

`main` es la rama estable y contiene la publicación consolidada del proyecto.
El
[Pull Request #1](https://github.com/FreddyxDD/IntranetHSJ-USJB/pull/1)
fue fusionado y su rama temporal fue eliminada.

Actualmente los cambios se documentan, validan y publican en `main` mediante
commits descriptivos. Cuando una mejora requiera revisión aislada se podrá
crear una rama temporal desde `main`; después de su incorporación deberá
eliminarse para evitar ramas permanentes divergentes.

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
