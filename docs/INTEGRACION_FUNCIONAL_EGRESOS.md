# Integración funcional del módulo Egresos

Fecha de implementación: 25 de julio de 2026.

## Objetivo

Incorporar las funciones prioritarias del aplicativo entregado de Egresos al
Laravel 13 existente, sin replicar su autenticación MySQL ni crear usuarios,
roles o permisos locales.

El módulo usa:

- `HSJ_Identity` para usuarios, sesión, roles y permisos;
- `Intranet_HSJ.egresos` para egresos, constancias, correlativos e historial;
- `Intranet_HSJ.catalogos` para CIE-10;
- `Intranet_HSJ.auditoria` para trazabilidad funcional.

## Alcance implementado

- nuevo módulo visible en `/areas` según `egresos.view`;
- entrada directa en `/egresos` con la sesión central vigente;
- panel con totales, egresos del mes y constancias;
- búsqueda paginada por historia clínica, documento, nombres o apellidos;
- detalle del egreso y resolución de diagnósticos contra CIE-10;
- consulta del historial de constancias;
- emisión de constancias con correlativo por cuenta y año;
- historial obligatorio y evento de auditoría al emitir;
- vista institucional imprimible, compatible con impresión o PDF del navegador;
- navbar, retorno al panel, perfil, cierre de sesión y footer institucionales;
- interfaz responsive construida con Tailwind y Preline.

No se incorporaron el login ni el CRUD de usuarios del proyecto PHP entregado.

## Autorización

| Acción | Permiso central |
| --- | --- |
| Entrar al módulo | `egresos.view` |
| Consultar panel, registros y CIE-10 | `egresos.records.view` |
| Ver historial | `egresos.history.view` |
| Generar constancia | `egresos.certificates.create` |
| Consultar estadísticas | `egresos.reports.view` |

La impresión requiere acceso al módulo y, adicionalmente, permiso para
consultar historial o generar constancias.

## Endpoints Laravel

| Método | Ruta | Función |
| --- | --- | --- |
| GET | `/egresos` | Pantalla principal |
| GET | `/egresos/api/dashboard` | Indicadores |
| GET | `/egresos/api/registros` | Búsqueda paginada |
| GET | `/egresos/api/registros/{id}` | Detalle |
| GET | `/egresos/api/cie10` | Catálogo CIE-10 |
| GET | `/egresos/api/estadisticas/mensuales` | Serie mensual |
| GET | `/egresos/api/constancias` | Historial |
| POST | `/egresos/api/constancias` | Emisión transaccional |
| GET | `/egresos/constancias/{id}/imprimir` | Documento imprimible |

Estas rutas están declaradas antes del puente PHP legado y son atendidas
únicamente por controladores Laravel.

## Integridad de emisión

La emisión se ejecuta dentro de una transacción SQL Server:

1. bloquea el correlativo de la cuenta y año;
2. incrementa el número;
3. copia la información histórica del egreso y sus descripciones CIE-10;
4. registra la constancia;
5. registra `egresos.constancia_historial`;
6. registra `auditoria.eventos`;
7. confirma todo el conjunto o revierte todo ante un error.

## Validaciones ejecutadas

- 17 pruebas Laravel aprobadas;
- 54 aserciones;
- sintaxis PHP validada;
- rutas verificadas con `php artisan route:list --path=egresos`;
- consulta real validada sobre 5,872 egresos;
- búsqueda real validada por nombre;
- emisión completa verificada dentro de una transacción revertida;
- conteo posterior confirmado en 37 constancias, sin datos de prueba;
- plantillas Blade compiladas;
- `npm.cmd run build` ejecutado correctamente;
- Tailwind y Preline publicados en `public/assets`.

## Pendientes controlados

- editar y anular constancias con motivo e historial;
- alta y corrección manual de egresos;
- importación operativa desde la interfaz;
- reportes gráficos y exportaciones;
- configuración institucional de firmas e iniciales;
- conciliación manual de las 20 cuentas legadas aún pendientes.

No debe retirarse definitivamente la autenticación legada restante hasta que
las cuentas pendientes hayan sido conciliadas y los usuarios hayan superado
las pruebas de aceptación.
