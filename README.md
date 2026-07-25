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

El contrato obligatorio para integrar Cirugías y futuras aplicaciones está
documentado en
[Arquitectura central de identidad e integración](docs/ARQUITECTURA_IDENTIDAD_Y_APLICACIONES.md).

La consolidación de las bases operativas entregadas para Egresos y Cirugías,
incluyendo conciliación de identidad, secuencia de importación, validaciones y
reversión, se encuentra definida en el
[Plan de consolidación de Egresos y Cirugías](docs/PLAN_MIGRACION_DATOS_EGRESOS_CIRUGIAS.md).

## Flujo de ramas y revisión

El repositorio utiliza ramas temporales y Pull Requests para que cada entrega
pueda revisarse antes de incorporarse a la versión estable.

- `main`: rama estable y base del repositorio.
- `agent/responsive-areas-preline`: contiene la publicación inicial del
  proyecto y los cambios responsive que se encuentran en revisión.

Como el repositorio estaba completamente vacío, primero se creó `main`.
Posteriormente, el proyecto se publicó en una rama separada para que GitHub
pudiera mostrar y revisar los 285 archivos antes de incorporarlos.

La publicación inicial se revisa mediante el
[Pull Request #1](https://github.com/FreddyxDD/IntranetHSJ-USJB/pull/1).
Cuando sea aprobado y fusionado:

1. Todo el proyecto pasará a `main`.
2. Se podrá eliminar la rama `agent/responsive-areas-preline`.
3. Las siguientes mejoras partirán de `main` y utilizarán ramas temporales
   independientes.

Este flujo evita modificar directamente la rama estable sin una revisión
previa y conserva un historial claro de cada mejora.

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
