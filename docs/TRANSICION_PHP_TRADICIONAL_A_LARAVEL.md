# Transición de PHP tradicional a Laravel 13

## Propósito

Este documento consolida la migración del Intranet HSJ desde su implementación
original en PHP tradicional hacia Laravel 13. La transición concluyó: Laravel
recibe y atiende todas las solicitudes y ya no existe un enrutador heredado
activo.

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
        +-- middleware de módulo y permiso central
        +-- controlador Laravel
        +-- modelo, servicio o vista Blade
```

### Responsabilidades por archivo

| Componente | Responsabilidad actual |
| --- | --- |
| `public/index.php` | Punto de entrada web; carga Composer, inicia Laravel y entrega la solicitud al kernel. |
| `bootstrap/app.php` | Registra middleware y configuración del aplicativo Laravel. |
| `routes/web.php` | Declara todas las rutas web y sus middleware. |
| `app/Http/Controllers`, `app/Models`, `app/Services` | Implementación Laravel moderna. |
| `resources/views` | Vistas Blade de todos los módulos activos. |

## Regla de resolución de rutas

Cada URL activa se declara de forma explícita en `routes/web.php`. La ruta
aplica `module.access` y, cuando corresponde, un permiso atómico de
`HSJ_Identity`. No existe una ruta comodín de compatibilidad.

Las rutas web usan la validación CSRF estándar de Laravel. Los formularios
incluyen `@csrf` y las solicitudes JavaScript del mismo origen reciben el
encabezado `X-CSRF-TOKEN` desde el recurso común `public/assets/js/csrf.js`.

## Estado de transición por módulo

| Módulo | Estado | Enrutamiento y operación |
| --- | --- | --- |
| Egresos | Laravel nativo | Rutas, controladores, servicios, modelos, vistas y permisos declarados en Laravel. |
| Citas administrativas | Laravel nativo | Página, reservas enviadas, reportes, programación diaria, pacientes y estados utilizan controladores, conexiones y rutas Laravel. |
| Cirugías | Laravel nativo | Rutas, controlador operativo, portal, vistas Blade, sesión y permisos centrales; no mantiene login ni CRUD local de cuentas. |
| Administración del Intranet | Laravel nativo | El CRUD central utiliza `IdentityAdminController`, rutas Laravel, vista Blade y persistencia exclusiva en `HSJ_Identity`. |
| Registro y perfil institucional | Laravel nativo | Login, validación de DNI, registro, confirmación, perfil y sesión se atienden mediante controlador, middleware y vistas Laravel. |
| UVI | Acceso centralizado | Se retiraron login, sesión y CRUD de cuentas locales. Los accesos históricos redirigen al portal o a la administración central según el perfil. |
| Indicadores de producción | Laravel nativo | Ruta, middleware, controlador, consulta y vista Blade migrados; ya no participa en `legacy/index.php`. |
| Indicadores de eficiencia | Laravel nativo | Consulta y mantenimiento administrativo migrados a Laravel con validación y errores controlados. |
| Indicadores de calidad | Laravel nativo | Consulta y mantenimiento administrativo migrados a Laravel con validación y errores controlados. |
| Información institucional y páginas generales | Laravel nativo | Principal, Áreas, Perfil e Información conservan sus URL y utilizan `PortalController` y vistas Blade. |

La matriz debe actualizarse en el mismo commit que complete la migración de un
módulo.

### Avance del retiro

El primer bloque retirado del enrutador heredado comprende Producción,
Eficiencia y Calidad. Las rutas conservan sus URL públicas, ahora utilizan
`IndicatorController`, vistas Blade y la conexión Laravel `modules`.

UVI no contenía una operación clínica independiente: su implementación estaba
limitada al login y mantenimiento de `usuarios_uvi`. Por ello no se trasladó
esa tabla ni su CRUD. Se retiraron ambos y las URL históricas ahora utilizan la
sesión y los perfiles centrales; los endpoints locales responden como recursos
retirados para identificar clientes desactualizados.

El acceso institucional también fue retirado del enrutador manual. La sesión
compatible `hospital_sid` se inicia mediante middleware Laravel; el login,
registro por DNI, confirmación inicial, cierre de sesión y consulta del usuario
son responsabilidad de `InstitutionalAuthController`. Principal, Áreas, Perfil
e Información son servidos por `PortalController` y vistas Blade.

El catálogo de módulos se trasladó a `config/modules.php` y
`ModuleCatalogService` calcula los módulos visibles desde los permisos
efectivos de `HSJ_Identity`. Las páginas ya no llaman
`modulos_autorizados()`, `url_path()` ni controladores globales.

La administración institucional también quedó migrada:
`IdentityAdminController` atiende el resumen, catálogos, usuarios, altas,
actualizaciones, estados y contraseñas. Las rutas dinámicas usan parámetros
Laravel validados como numéricos y la interfaz reside en
`resources/views/admin/identity.blade.php`.

Citas administrativas fue migrada por completo. La página
utiliza Blade; `AppointmentAdminController` conserva los contratos de reservas
y reportes, mientras `AppointmentApiController` atiende la programación real
de SIGH. La conexión `appointments_portal` encapsula los registros operativos
que todavía residen en MySQL y la conexión `sigh` permanece separada.

El middleware anterior fue sustituido por `module.access`. La implementación
consulta la cuenta, roles y permisos
de `HSJ_Identity` mediante `CentralAccessService`, sin cargar
configuraciones ni helpers PHP tradicionales.

Cirugías fue el último módulo operativo retirado del despacho tradicional.
`SurgeryPortalController` atiende el ingreso, página principal, manual,
sesión y salida; `SurgeryController` atiende registros, importaciones,
catálogos, personal, análisis y reportes. Las URL públicas se conservaron y
quedaron protegidas con la sesión y permisos centrales.

La conexión `modules` representa la ubicación operativa actual de las tablas
de indicadores. Su nombre no autoriza crear usuarios o contraseñas locales.
Cuando esas tablas se consoliden en SQL Server solo deberá cambiarse su
repositorio o conexión, sin modificar autenticación ni permisos.

## Cambios aplicados al enrutador original

El código original fue refactorizado por módulos, preservando las URL y los
contratos funcionales necesarios. Tras validar todos los reemplazos se
eliminaron el enrutador manual, su puente, los controladores globales, los
helpers y las vistas PHP que ya no tenían consumidores. Los cambios
funcionales principales fueron:

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
- La sesión de Cirugías se deriva de la sesión central.
- La autorización dejó de depender únicamente del rol numérico local.
- Crear o editar registros, importar, administrar personal, consultar análisis
  y emitir reportes exige permisos centrales diferentes.
- La administración local de usuarios fue retirada del flujo activo.
- La administración de accesos redirige al CRUD central.

### Citas

- El acceso administrativo utiliza la sesión general y el permiso del módulo.
- Los endpoints refactorizados controlan las excepciones y respuestas desde
  Laravel.
- Todas las funciones activas se resuelven mediante rutas Laravel explícitas.

### Seguridad y diagnóstico

- Las rutas públicas de diagnóstico `test-sigh`, `test-citas` y `debug-php`
  fueron retiradas del enrutamiento activo.
- Los módulos nuevos deben utilizar middleware, validación, manejo de
  excepciones, auditoría y respuestas institucionales.

## Procedimiento obligatorio para migrar una ruta

El procedimiento aplicado a cada ruta heredada fue:

1. Identificar URL, método HTTP, parámetros, sesión, permisos, controlador,
   vista y bases de datos utilizadas.
2. Crear el controlador, request, servicio y modelo Laravel necesarios.
3. Declarar la ruta Laravel explícita.
4. Aplicar acceso al módulo y permiso central tanto en la interfaz como en el
   endpoint.
5. Conservar el contrato HTTP existente o documentar expresamente cualquier
   cambio.
6. Incorporar manejo institucional de errores y desconexiones.
7. Agregar pruebas de acceso autorizado, acceso denegado, validación y
   operación correcta.
8. Retirar la condición equivalente del enrutador original.
9. Actualizar la matriz de este documento y `CHANGELOG.md`.
10. Publicar el cambio mediante un commit trazable.

## Cierre del retiro del enrutador heredado

El enrutador heredado se retiró después de verificar estas condiciones:

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
- `routes/web.php` ya no necesita una ruta comodín de compatibilidad.

Como resultado se eliminaron la ruta comodín, el controlador puente, el
enrutador PHP original y los controladores, helpers y vistas tradicionales sin
consumidores. Las conexiones operativas se conservaron con nombres
funcionales (`modules` y `appointments_portal`), sin variables de entorno de
compatibilidad.

## Decisiones que deben preservarse

- `HSJ_Identity` es la única fuente de usuarios, cuentas, roles y permisos.
- `Intranet_HSJ` contiene datos operativos propios del portal y sus módulos.
- Las conexiones clínicas, incluido SIGH, deben tratarse como fuentes externas
  y mantenerse en solo lectura salvo autorización expresa.
- Una aplicación futura puede estar construida en otro lenguaje siempre que
  consuma la identidad y autorización central mediante un contrato seguro; no
  debe replicar usuarios ni contraseñas.
- Los módulos nuevos deben implementarse directamente con controladores,
  servicios, middleware, validación y vistas o API de Laravel.

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
