# Transición de PHP tradicional a Laravel 13

## Propósito

Este documento consolida la arquitectura utilizada para migrar el Intranet HSJ
desde su implementación original en PHP tradicional hacia Laravel 13 sin
interrumpir los módulos existentes.

La transición es progresiva: Laravel recibe todas las solicitudes, atiende
directamente los módulos ya refactorizados y delega temporalmente las rutas
restantes al enrutador heredado.

Este documento complementa:

- [Arquitectura central de identidad e integración](ARQUITECTURA_IDENTIDAD_Y_APLICACIONES.md).
- [Integración funcional de Egresos](INTEGRACION_FUNCIONAL_EGRESOS.md).
- [Plan de migración de Egresos y Cirugías](PLAN_MIGRACION_DATOS_EGRESOS_CIRUGIAS.md).

## Situación original

El aplicativo original utilizaba un único archivo como punto de entrada y
enrutador. Ese archivo:

- cargaba configuración, controladores y helpers mediante `require_once`;
- iniciaba y consultaba directamente la sesión PHP;
- interpretaba `REQUEST_URI` y `REQUEST_METHOD`;
- extraía identificadores de las URL con expresiones regulares;
- resolvía todas las rutas mediante un bloque `match (true)`;
- incluía directamente vistas PHP;
- invocaba controladores estáticos;
- construía respuestas, redirecciones y errores sin el ciclo HTTP de Laravel.

Esta implementación permitía operar el sistema, pero concentraba en un solo
archivo el enrutamiento, la seguridad, la compatibilidad de URL y el despacho
de todos los módulos.

## Arquitectura actual

Laravel 13 es el único punto de entrada público. El flujo de una solicitud es:

```text
Navegador o cliente HTTP
        |
        v
public/index.php
        |
        v
Kernel HTTP de Laravel 13
        |
        v
routes/web.php
        |
        +-- Ruta Laravel declarada
        |       |
        |       +-- middleware de módulo y permiso central
        |       +-- controlador Laravel
        |       +-- modelo, servicio o vista Blade
        |
        +-- Ruta todavía no migrada
                |
                v
        LegacyApplicationController
                |
                v
        legacy/index.php
                |
                +-- controlador heredado de app/controllers
                +-- vista heredada de views
```

### Responsabilidades por archivo

| Componente | Responsabilidad actual |
| --- | --- |
| `public/index.php` | Punto de entrada web; carga Composer, inicia Laravel y entrega la solicitud al kernel. |
| `bootstrap/app.php` | Registra middleware y configuración del aplicativo Laravel. |
| `routes/web.php` | Declara primero las rutas Laravel y al final la ruta temporal de compatibilidad. |
| `app/Http/Controllers/LegacyApplicationController.php` | Ejecuta el enrutador heredado dentro del ciclo de respuesta de Laravel y captura su salida. |
| `legacy/index.php` | Conserva temporalmente el despacho manual de rutas todavía no refactorizadas. |
| `app/controllers` | Controladores heredados que continúan activos durante la transición. |
| `views` | Vistas PHP heredadas que todavía no fueron convertidas a Blade. |
| `app/Http/Controllers`, `app/Models`, `app/Services` | Implementación Laravel moderna. |
| `resources/views` | Vistas Blade de los módulos refactorizados. |

## Regla de resolución de rutas

El orden de `routes/web.php` es deliberado:

1. Se declaran las rutas nativas de Laravel.
2. Cada ruta nativa aplica middleware de acceso al módulo y, cuando
   corresponde, un permiso atómico de `HSJ_Identity`.
3. Al final se declara `Route::any('/{path?}', ...)`.
4. La ruta final captura únicamente las URL que Laravel todavía no atiende de
   forma explícita.
5. `LegacyApplicationController` ejecuta `legacy/index.php` y transforma su
   salida directa en una respuesta Laravel.

Una ruta migrada nunca debe volver a declararse en `legacy/index.php`. Durante
la migración se conserva la misma URL y el mismo contrato de entrada/salida
para evitar cambios innecesarios en las vistas y clientes existentes.

La excepción temporal de CSRF pertenece únicamente al puente general. Las
rutas nuevas deben utilizar las protecciones estándar de Laravel o una
autenticación API definida explícitamente; no deben copiar esa excepción.

## Estado de transición por módulo

| Módulo | Estado | Enrutamiento y operación |
| --- | --- | --- |
| Egresos | Laravel nativo | Rutas, controladores, servicios, modelos, vistas y permisos declarados en Laravel. |
| Citas administrativas | Híbrido | Las consultas de citas diarias, pacientes y cambio de estado están en controladores Laravel; otras funciones conservan temporalmente el despacho heredado. |
| Cirugías | Híbrido | Conserva controladores y vistas heredados, pero reutiliza la sesión, perfiles y permisos centrales. No debe mantener login ni CRUD local de cuentas. |
| Administración del Intranet | Híbrido | Conserva parte de la interfaz y controladores heredados, pero administra identidad, roles y permisos en `HSJ_Identity`. |
| Registro y perfil institucional | Híbrido | La interfaz mantiene compatibilidad heredada y la persistencia se realiza sobre la identidad central. |
| UVI | Heredado pendiente | Continúa bajo `legacy/index.php`; debe migrarse sin crear otra fuente de usuarios. |
| Indicadores de producción | Laravel nativo | Ruta, middleware, controlador, consulta y vista Blade migrados; ya no participa en `legacy/index.php`. |
| Indicadores de eficiencia | Laravel nativo | Consulta y mantenimiento administrativo migrados a Laravel con validación y errores controlados. |
| Indicadores de calidad | Laravel nativo | Consulta y mantenimiento administrativo migrados a Laravel con validación y errores controlados. |
| Información institucional y páginas generales | Heredado pendiente | Conservan sus URL y vistas durante la refactorización. |

La matriz debe actualizarse en el mismo commit que complete la migración de un
módulo.

### Avance del retiro

El primer bloque retirado del enrutador heredado comprende Producción,
Eficiencia y Calidad. Las rutas conservan sus URL públicas, ahora utilizan
`IndicatorController`, vistas Blade y la conexión Laravel `modules`.

El middleware `legacy.module` también fue sustituido en las rutas Laravel por
`module.access`. La nueva implementación consulta la cuenta, roles y permisos
de `HSJ_Identity` mediante `CentralAccessService`, sin cargar
`app/config/app.php` ni `app/helpers/modulos.php`.

La conexión `modules` representa la ubicación operativa actual de las tablas
de indicadores. Su nombre no autoriza crear usuarios o contraseñas locales.
Cuando esas tablas se consoliden en SQL Server solo deberá cambiarse su
repositorio o conexión, sin modificar autenticación ni permisos.

## Cambios aplicados al enrutador original

El código original no se descartó: se trasladó a `legacy/index.php` y se
adaptó para convivir con Laravel y la identidad central. Los cambios
funcionales principales son:

### Acceso institucional

- Laravel es ahora el punto de entrada.
- La autenticación, usuarios, aplicaciones, perfiles y permisos se centralizan
  en `HSJ_Identity`.
- El registro institucional valida el DNI y controla cuentas pendientes,
  inactivas o sujetas a revisión por Legajos.
- La navegación queda bloqueada mientras el usuario no confirme las
  instrucciones iniciales de su cuenta.

### Cirugías

- Se eliminó del flujo activo el segundo formulario de login.
- La sesión compatible de Cirugías se deriva de la sesión central.
- La autorización dejó de depender únicamente del rol numérico local.
- Crear o editar registros, importar, administrar personal, consultar análisis
  y emitir reportes exige permisos centrales diferentes.
- La administración local de usuarios fue retirada del flujo activo.
- La administración de accesos redirige al CRUD central.

### Citas

- El acceso administrativo utiliza la sesión general y el permiso del módulo.
- Los endpoints refactorizados controlan las excepciones y respuestas desde
  Laravel.
- Se mantiene compatibilidad temporal para las funciones todavía no migradas.

### Seguridad y diagnóstico

- Las rutas públicas de diagnóstico `test-sigh`, `test-citas` y `debug-php`
  fueron retiradas del enrutamiento activo.
- Los módulos nuevos deben utilizar middleware, validación, manejo de
  excepciones, auditoría y respuestas institucionales.

## Procedimiento obligatorio para migrar una ruta

Cada ruta heredada debe migrarse de forma independiente y verificable:

1. Identificar URL, método HTTP, parámetros, sesión, permisos, controlador,
   vista y bases de datos utilizadas.
2. Crear el controlador, request, servicio y modelo Laravel necesarios.
3. Declarar la ruta antes del puente de compatibilidad.
4. Aplicar acceso al módulo y permiso central tanto en la interfaz como en el
   endpoint.
5. Conservar el contrato HTTP existente o documentar expresamente cualquier
   cambio.
6. Incorporar manejo institucional de errores y desconexiones.
7. Agregar pruebas de acceso autorizado, acceso denegado, validación y
   operación correcta.
8. Retirar la condición equivalente de `legacy/index.php`.
9. Actualizar la matriz de este documento y `CHANGELOG.md`.
10. Publicar el cambio mediante un commit trazable.

## Condiciones para retirar `legacy/index.php`

El puente no puede eliminarse solo porque Laravel ya esté instalado.
`legacy/index.php` podrá retirarse cuando se cumplan todas estas condiciones:

- no existen rutas funcionales atendidas por el bloque `match` heredado;
- las páginas activas utilizan controladores Laravel y vistas Blade o una
  interfaz frontend formalmente integrada;
- ningún módulo solicita credenciales adicionales;
- no existen CRUD de usuarios, roles o contraseñas fuera de `HSJ_Identity`;
- todas las rutas tienen middleware de autenticación y autorización central;
- las escrituras relevantes generan auditoría;
- los errores de base de datos y servicios externos muestran pantallas o
  respuestas institucionales sin revelar detalles técnicos;
- las pruebas automatizadas cubren las rutas reemplazadas;
- se completaron pruebas funcionales y existe un procedimiento de reversión;
- `routes/web.php` ya no necesita la ruta comodín hacia
  `LegacyApplicationController`.

Después de verificar estas condiciones se eliminarán, en un cambio separado y
revisable:

1. la ruta comodín;
2. `LegacyApplicationController`;
3. `legacy/index.php`;
4. los controladores y vistas heredados que no tengan consumidores;
5. las conexiones y variables de entorno exclusivas del legado.

## Decisiones que deben preservarse

- `HSJ_Identity` es la única fuente de usuarios, cuentas, roles y permisos.
- `Intranet_HSJ` contiene datos operativos propios del portal y sus módulos.
- Las conexiones clínicas, incluido SIGH, deben tratarse como fuentes externas
  y mantenerse en solo lectura salvo autorización expresa.
- Una aplicación futura puede estar construida en otro lenguaje siempre que
  consuma la identidad y autorización central mediante un contrato seguro; no
  debe replicar usuarios ni contraseñas.
- La compatibilidad heredada es temporal y no debe utilizarse como plantilla
  para desarrollar módulos nuevos.

## Trazabilidad

Todo cambio de transición debe registrar:

- módulo y rutas migradas;
- permisos centrales utilizados;
- tablas y conexiones afectadas;
- pruebas ejecutadas;
- comportamiento de reversión;
- actualización de este documento y de `CHANGELOG.md`.

La rama estable del repositorio es `main`. Los cambios publicados deben quedar
identificados mediante commits descriptivos; si se utiliza una rama temporal,
esta se elimina después de incorporarse a `main`.
