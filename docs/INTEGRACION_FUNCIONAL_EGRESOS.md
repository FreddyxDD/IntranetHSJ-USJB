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

## Fuente maestra de pacientes y documentos

La HC, el tipo y el número de documento nacen de `dbo.Pacientes` en SIGH.
Mientras el equipo se encuentra fuera de la red hospitalaria, el módulo usa la
copia local de solo lectura `SIGH_202607_LOCAL`, mediante la conexión Laravel
`sigh_local`.

El aplicativo no vuelve a concatenar el tipo con el número de documento:

- `doc_tipo_id` conserva `Pacientes.IdDocIdentidad`;
- `doc_numero` conserva `Pacientes.NroDocumento`;
- `doc_iden_original` preserva el valor recibido en la entrega histórica;
- `patient_source_id` conserva `Pacientes.IdPaciente` cuando existe cruce;
- `doc_source` identifica si el dato fue confirmado por SIGH o normalizado
  desde el legado;
- `document_verified_at` registra cuándo se confirmó el documento.

Para utilizar el SIGH institucional dentro de la red solo debe configurarse:

```dotenv
EGRESOS_PATIENT_CONNECTION=sigh
EGRESOS_PATIENT_SOURCE_CODE=sigh
```

Fuera de la red se mantienen los valores predeterminados:

```dotenv
EGRESOS_PATIENT_CONNECTION=sigh_local
EGRESOS_PATIENT_SOURCE_CODE=sigh_202607_local
```

## Alcance implementado

- nuevo módulo visible en `/areas` según `egresos.view`;
- entrada directa en `/egresos` con la sesión central vigente;
- panel con totales, egresos del mes y constancias;
- búsqueda paginada por historia clínica, documento, nombres o apellidos;
- detalle del egreso y resolución de diagnósticos contra CIE-10;
- consulta del historial de constancias;
- emisión de constancias con correlativo por cuenta y año;
- edición controlada de constancias no anuladas;
- anulación con motivo obligatorio;
- historial obligatorio y evento de auditoría al emitir;
- historial y auditoría con valores anteriores y nuevos al editar o anular;
- configuración central de iniciales, responsables y cargos institucionales;
- vista institucional imprimible, compatible con impresión o PDF del navegador;
- navbar, retorno al panel, perfil, cierre de sesión y footer institucionales;
- interfaz responsive construida con Tailwind y Preline.
- registro excepcional de egresos, protegido por permiso central y con
  validación de fechas, CIE-10 y duplicidad;
- corrección controlada de egresos con captura de valores anteriores y nuevos
  en `auditoria.eventos`;
- importación operativa de CSV, XLSX y DBF desde la interfaz;
- validación previa por fila de encabezados, fechas, campos mínimos,
  diagnósticos CIE-10 y duplicados;
- resumen por lote con insertados, omitidos y observados, asociado a la cuenta
  central y a la huella SHA-256 del archivo;
- historial de importaciones recientes;
- reportes por mes y UPS filtrables por fechas;
- exportación de egresos a CSV UTF-8 y XLSX, protegida contra fórmulas
  inyectadas desde los datos.
- consulta de pacientes por HC o documento contra `SIGH_202607_LOCAL`;
- normalización de documentos históricos sin perder el valor entregado;
- conciliación idempotente mediante `hsj:sync-egresos-patients`;
- constancias y exportaciones usando el número de documento sin el prefijo de
  tipo.

No se incorporaron el login ni el CRUD de usuarios del proyecto PHP entregado.

## Autorización

| Acción | Permiso central |
| --- | --- |
| Entrar al módulo | `egresos.view` |
| Consultar panel, registros y CIE-10 | `egresos.records.view` |
| Ver historial | `egresos.history.view` |
| Generar constancia | `egresos.certificates.create` |
| Editar constancia | `egresos.certificates.update` |
| Anular constancia | `egresos.certificates.cancel` |
| Consultar estadísticas | `egresos.reports.view` |
| Registrar una excepción | `egresos.records.create` |
| Corregir un egreso | `egresos.records.update` |
| Importar y revisar lotes | `egresos.imports.manage` |
| Exportar CSV/XLSX | `egresos.reports.view` |
| Configurar constancias | `egresos.configuration.manage` |

La impresión requiere acceso al módulo y, adicionalmente, permiso para
consultar historial o generar constancias.

## Endpoints Laravel

| Método | Ruta | Función |
| --- | --- | --- |
| GET | `/egresos` | Pantalla principal |
| GET | `/egresos/api/dashboard` | Indicadores |
| GET | `/egresos/api/registros` | Búsqueda paginada |
| GET | `/egresos/api/registros/{id}` | Detalle |
| GET | `/egresos/api/pacientes-sigh` | Buscar paciente por HC o documento |
| POST | `/egresos/api/registros` | Registro excepcional |
| PUT | `/egresos/api/registros/{id}` | Corrección auditada |
| GET | `/egresos/api/cie10` | Catálogo CIE-10 |
| GET | `/egresos/api/estadisticas/mensuales` | Serie mensual |
| GET | `/egresos/api/estadisticas/servicios` | Totales por UPS |
| GET | `/egresos/api/importaciones` | Historial de lotes |
| POST | `/egresos/api/importaciones` | Procesar CSV, XLSX o DBF |
| GET | `/egresos/reportes/egresos.csv` | Exportación CSV |
| GET | `/egresos/reportes/egresos.xlsx` | Exportación XLSX |
| GET | `/egresos/api/constancias` | Historial |
| POST | `/egresos/api/constancias` | Emisión transaccional |
| PUT | `/egresos/api/constancias/{id}` | Edición transaccional |
| DELETE | `/egresos/api/constancias/{id}` | Anulación transaccional |
| GET | `/egresos/api/configuracion-constancias` | Leer configuración |
| PUT | `/egresos/api/configuracion-constancias` | Actualizar configuración |
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

- 20 pruebas Laravel aprobadas;
- 83 aserciones;
- sintaxis PHP validada;
- rutas verificadas con `php artisan route:list --path=egresos`;
- consulta real validada sobre 5,872 egresos;
- búsqueda real validada por nombre;
- emisión completa verificada dentro de una transacción revertida;
- edición, anulación y configuración verificadas dentro de una transacción
  revertida;
- conteo posterior confirmado en 37 constancias, sin datos de prueba;
- historial posterior confirmado en 41 registros y constancia de control
  restaurada a su estado original;
- plantillas Blade compiladas;
- `npm.cmd run build` ejecutado correctamente;
- Tailwind y Preline publicados en `public/assets`.
- migración `2026_07_25_200000_enable_central_egresos_operations` aplicada
  en `Intranet_HSJ`;
- importación CSV verificada contra SQL Server dentro de una transacción:
  1 insertado, 0 omitidos y 0 observados;
- rollback de control confirmado, conservando exactamente 5,872 egresos;
- exportación XLSX real verificada con 5,872 registros.
- 5,601 documentos normalizados;
- 419 egresos conciliados por HC contra `SIGH_202607_LOCAL`;
- 363 documentos confirmados, 16 corregidos y 40 completados;
- 271 registros permanecen sin documento válido y no reciben valores
  inventados;
- 5,872 valores originales preservados en `doc_iden_original`;
- 37 constancias normalizadas, conservando sus 37 valores originales;
- segunda conciliación en simulación: 419 confirmados y cero cambios, lo que
  verifica la idempotencia.

## Pendientes controlados

- incorporación de imágenes de firmas mediante almacenamiento seguro;
- conciliación manual de las 20 cuentas legadas aún pendientes.

No debe retirarse definitivamente la autenticación legada restante hasta que
las cuentas pendientes hayan sido conciliadas y los usuarios hayan superado
las pruebas de aceptación.

## Conservación del formato institucional de la constancia

La migración a Laravel mantiene el formato histórico de la
`CONSTANCIA DE HOSPITALIZACION`. La modernización del módulo no autoriza
cambios visuales o textuales en este documento.

Se conservaron:

- el logotipo del Ministerio de Salud y el encabezado de la Dirección Regional
  de Salud;
- el correlativo `N° 000-AAAA-HSJ-SERVICIO` dentro de su recuadro;
- la marca de agua institucional del Hospital San José;
- el título, la introducción, `HACE CONSTAR`, el texto de hospitalización y la
  relación de diagnósticos CIE-10;
- la fecha de expedición, el bloque de firma y las iniciales responsables;
- la distribución original en una sola hoja A4.

Laravel ahora prepara los datos mediante
`ConstanciaDocumentPresenter` y Blade se limita a representar esta plantilla.
El formato fue validado mediante una prueba de regresión y una impresión real
de control de una página A4.
