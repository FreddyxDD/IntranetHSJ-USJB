# Análisis de catálogos de Egresos, SIGH y CIE-10

Fecha de análisis: 25 de julio de 2026.

## Objetivo

Determinar si el financiamiento y la condición de alta requeridos por Egresos
pueden obtenerse de las bases utilizadas por el Portal de Citas, y comparar el
catálogo `CIE10_2021.csv` entregado con el catálogo central
`Intranet_HSJ.catalogos.cie10`.

El análisis fue de solo lectura. No se modificaron registros ni estructuras de
las bases de datos.

## Fuentes revisadas

| Fuente | Estado | Uso identificado |
| --- | --- | --- |
| `Intranet_HSJ` | Disponible | Egresos y catálogo CIE-10 central |
| `SIGH_202607_LOCAL` | Disponible | Copia local de pacientes, atenciones y financiamiento |
| SIGH institucional | No disponible fuera de la red hospitalaria | Fuente clínica productiva |
| MySQL legado `citas` (`192.168.3.86`) | Sin respuesta de red | Datos heredados del Portal de Citas |
| `CIE10_2021.csv` | Disponible | Catálogo entregado para evaluación |

El Portal de Citas consulta SIGH para los datos clínicos. La base MySQL heredada
de Citas no debe convertirse en la fuente maestra de financiamiento o condición
de alta.

## Financiamiento

La fuente correcta es:

```text
SIGH.dbo.Atenciones.idFuenteFinanciamiento
  -> SIGH.dbo.FuentesFinanciamiento.IdFuenteFinanciamiento
```

`FuentesFinanciamiento` contiene el identificador, descripción,
`IdTipoFinanciamiento`, `CodigoHIS` y
`CodigoFuenteFinanciamientoSEM`, entre otros atributos.

Los valores importados en `egresos.egresos.financia` utilizan el código HIS
representado como texto y, en parte de los registros, sin normalizar:

| Valor legado | Registros |
| --- | ---: |
| `02` / `2` | 4,016 / 1,641 |
| `01` / `1` | 90 / 46 |
| `04` / `4` | 54 / 32 |
| `03` / `3` | 41 / 18 |
| `08` / `8` | 11 / 3 |
| Otros (`05`, `06`, `07`, `10` y variantes) | 18 |

Ejemplos comprobados del catálogo SIGH:

| Fuente SIGH | Descripción | `CodigoHIS` |
| ---: | --- | ---: |
| 1 | PARTICULAR | 1 |
| 2 | SOAT | 4 |
| 3 | SIS | 2 |
| 6 | SALUDPOL | 8 |
| 16 | ESSALUD | 3 |
| 21 | SIS MANUAL | 2 |

### Regla recomendada

1. Vincular cada egreso con su atención SIGH mediante el identificador de
   atención cuando esté disponible.
2. Guardar `idFuenteFinanciamiento` como referencia técnica y conservar una
   instantánea de su descripción para trazabilidad.
3. Utilizar `CodigoHIS` para conciliar el campo legado `financia`, normalizando
   `1` y `01` al mismo código.
4. No deducir una fuente específica únicamente desde `CodigoHIS`: varios
   registros de `FuentesFinanciamiento` comparten el mismo código (por ejemplo,
   SIS y SIS MANUAL). La atención SIGH es la que elimina esa ambigüedad.

## Condición y tipo de alta

`SIGH.dbo.Atenciones` contiene:

- `IdCondicionAlta`;
- `IdTipoAlta`;
- `IdTipoCondicionAlServicio`;
- `IdTipoCondicionALEstab`.

Para Egresos interesan `IdCondicionAlta` y `IdTipoAlta`. Las condiciones al
servicio o al establecimiento corresponden a otro momento del episodio y no
deben reutilizarse como condición de egreso.

La copia `SIGH_202607_LOCAL` no contiene tablas descriptivas ni relaciones
foráneas para esos códigos. En esa copia, `IdCondicionAlta` usa principalmente
los valores `2` y `3`, mientras que el archivo legado importado en
`egresos.egresos.condicion` contiene:

| Código legado | Registros |
| --- | ---: |
| 1 | 5,404 |
| 2 | 263 |
| 5 | 235 |
| 3 | 67 |
| 4 | 1 |

No fue posible construir un cruce histórico por historia clínica y fechas:
los 5,970 egresos actualmente almacenados pertenecen a un período distinto del
subconjunto disponible en `SIGH_202607_LOCAL`.

### Regla recomendada

- Conservar por ahora `condicion` como dato original del archivo.
- No asignar descripciones inventadas a los códigos `1` a `5`.
- Al recuperar acceso al SIGH institucional, extraer el catálogo oficial o la
  regla usada por su reporte de egresos y validar una muestra contra episodios
  reales.
- Después de esa validación, crear catálogos internos versionados para
  condición y tipo de alta, guardando además los IDs de origen SIGH.

## Evaluación del catálogo CIE-10 entregado

El CSV no es UTF-8 válido y requiere detección/normalización de codificación
durante su lectura. El análisis se realizó con `@oai/artifact-tool`, sin
importar datos.

| Control | Resultado |
| --- | ---: |
| Filas de datos en el CSV | 12,674 |
| Códigos textuales únicos | 12,674 |
| Códigos vacíos | 0 |
| Descripciones vacías | 0 |
| Duplicados | 0 |
| Filas actuales en `catalogos.cie10` | 13,023 |
| Códigos comunes | 12,673 |
| Códigos realmente nuevos después de normalizar | 0 |
| Códigos de la base ausentes en el CSV | 350 |
| Diferencias descriptivas entre códigos comunes | 0 |

El CSV contiene `U06AG` y también `U06.AG`, ambos descritos como “Zika
Asintomático en Gestantes”. Aunque son textos distintos, los dos se normalizan
como `U06AG`; `U06.AG` ya existe en la base. La validación masiva detecta esta
colisión y además rechaza `U06AG` por no usar el separador del formato CIE-10.
Por ello no existe un código realmente nuevo que deba incorporarse.

El catálogo central existente:

- contiene 350 códigos adicionales;
- conserva `estado` y `cotejo_sexo` informados en sus 13,023 registros;
- mantiene trazabilidad mediante `source_system`, `source_id`,
  `source_fingerprint` y fechas de origen/importación.

El CSV solo proporciona código y descripción, por lo que reemplazar el catálogo
central eliminaría información útil y reduciría su cobertura.

## Decisión de integración

1. Mantener `catalogos.cie10` como catálogo maestro de Egresos.
2. No importar ni reemplazar CIE-10 con el CSV entregado.
3. Corregir o retirar la fila `U06AG` del archivo antes de cualquier carga;
   conservar `U06.AG`, ya presente en el catálogo central.
4. Obtener financiamiento desde la atención SIGH y no desde la base MySQL
   heredada de Citas.
5. Mantener los textos legados de financiamiento y condición como evidencia de
   origen hasta completar la conciliación.
6. Posponer la traducción definitiva de condición/tipo de alta hasta consultar
   el SIGH institucional o su catálogo oficial.

## Próxima implementación propuesta

En una migración posterior y revisable:

- agregar a Egresos referencias opcionales para `sigh_atencion_id`,
  `sigh_fuente_financiamiento_id`, `sigh_condicion_alta_id` y
  `sigh_tipo_alta_id`;
- crear catálogos locales sincronizables de fuentes de financiamiento,
  condiciones y tipos de alta;
- implementar una conciliación idempotente que nunca sobrescriba los valores
  originales importados;
- auditar cada sincronización, cambio de correspondencia y corrección manual.
