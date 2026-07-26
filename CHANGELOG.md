# Historial de cambios

El proyecto utiliza un registro cronológico de cambios relevantes siguiendo
los principios de Keep a Changelog.

## [Sin publicar]

### Identidad - registro institucional por DNI

- Las identidades históricas `inactive` sin vínculo vigente ya no pueden crear
  ni reactivar cuentas desde el Intranet.
- Estos casos generan una solicitud central `personnel_review_requests`
  dirigida a `legajos_hsj`, conservando una fotografía del último legajo para
  su evaluación.
- El envío a Legajos no modifica `people` ni crea registros en `users` o
  `access_accounts`; las solicitudes duplicadas permanecen bloqueadas.
- Se agregó el registro alternativo para DNI aún inexistentes en `people`,
  solicitando nombres, ambos apellidos, fecha de nacimiento, correo y teléfono.
- Las nuevas personas y cuentas quedan en estado `pending`, sin sesión ni
  acceso a las áreas hasta recibir aprobación administrativa.
- El panel de administración identifica las solicitudes pendientes, muestra
  los datos necesarios para revisarlas y permite aprobarlas.
- La aprobación activa persona, usuario y cuenta en una transacción, guardando
  fecha e identificador del administrador responsable.
- Registro en dos pasos validando primero el DNI activo contra
  `HSJ_Identity`.
- Cuenta central vinculada con `people` y el legajo activo disponible, DNI
  como identificador y asignación exclusiva del perfil `consulta`.
- Contraseña inicial calculada con `DDMMAAAA` más los últimos cuatro dígitos
  del DNI.
- Autologin con pantalla obligatoria que presenta las credenciales, el alcance
  de consulta y la indicación para solicitar permisos adicionales.
- Navegación y APIs bloqueadas hasta registrar la aceptación de instrucciones
  en la cuenta central.
- Inicio de sesión compatible con DNI, correo o nombre de usuario.
- Límites de intentos por IP y validación temporal de diez minutos.

### Egresos - entrega funcional

- Auditoría operativa rediseñada con resúmenes en lenguaje funcional,
  correlativos de constancia formateados, responsable/fecha/origen visibles,
  etiquetas comprensibles y comparación visual de valores; IDs y códigos
  internos quedan en un detalle técnico secundario.
- CRUD responsive del catálogo CIE-10 protegido por
  `egresos.catalogs.manage`, con código inmutable, control de concurrencia,
  desactivación lógica y auditoría de valores anteriores/nuevos.
- Carga masiva CIE-10 CSV/XLSX en dos etapas, con huella SHA-256, análisis
  persistente por fila, detección de duplicados normalizados y confirmación
  transaccional.
- Validación real del archivo entregado: 12,672 filas sin cambios y dos errores
  relacionados con la colisión normalizada `U06AG` / `U06.AG`; el catálogo
  central permanece en 13,023 registros.
- Análisis de solo lectura de financiamiento y condición de alta en SIGH:
  se definió `Atenciones.idFuenteFinanciamiento` como origen y `CodigoHIS`
  como código de conciliación legado; la condición queda pendiente del
  catálogo oficial para evitar correspondencias inventadas.
- Comparación del `CIE10_2021.csv` contra `catalogos.cie10`: 12,673 códigos
  textuales coincidentes sin diferencias descriptivas, 350 códigos adicionales
  en la base central y una colisión normalizada `U06AG` / `U06.AG`.
- Diccionario funcional y técnico de las 16 tablas que participan en Egresos,
  con propósito de cada campo, relaciones físicas y lógicas, procesos que lo
  escriben y pantallas o servicios que lo consumen.
- Módulo Laravel nativo con panel, búsqueda paginada, detalle, CIE-10,
  historial y vista imprimible de constancias.
- Emisión de constancias con correlativo SQL Server por cuenta y año,
  historial obligatorio y evento central de auditoría.
- Selección de hasta diez episodios del mismo paciente, vista preliminar
  obligatoria y confirmación expresa antes de generar una constancia.
- Comprobante cifrado de previsualización, ligado al usuario y a la selección,
  para impedir emisiones directas, alteradas o confirmadas después de 15
  minutos.
- Confirmaciones de un solo uso mediante huella única, evitando duplicados por
  doble clic o repetición de solicitudes.
- Relación `egresos.constancia_episodios` con instantánea legal de cada ingreso
  y migración automática de las constancias históricas.
- Edición y anulación transaccionales con permisos separados, motivo
  obligatorio y registro de valores anteriores y nuevos.
- Configuración institucional de responsables, cargos e iniciales protegida
  por `egresos.configuration.manage`.
- Marca visible de anulación en la vista imprimible.
- Autorización exclusiva mediante sesión y permisos de `HSJ_Identity`.
- Interfaz responsive compilada con Tailwind y Preline.
- Importación masiva en dos etapas: análisis persistente por fila y
  confirmación exclusiva de episodios nuevos o reingresos.
- Detección de duplicados por identidad, fechas y servicio; conflictos de
  DNI/HC y errores de calidad explicados antes de insertar.
- Consulta inicial limitada a los 20 egresos incorporados más recientemente,
  sin descargar el historial completo de los pacientes.
- Línea de tiempo moderna solicitada bajo demanda y paginada en bloques de
  ocho hospitalizaciones, con acciones por episodio para corregirlo o emitir
  su constancia.
- Índices compuestos para ordenar cargas recientes y consultar episodios por
  HC o documento sin degradar progresivamente la pantalla.
- Flujo visual de importación ordenado como carga, análisis del lote e
  importaciones recientes.
- Reimpresión de constancias anuladas bloqueada en servidor; la consulta
  histórica conserva el documento con sello de anulación y sin controles de
  impresión.
- Registro auditado de cada habilitación de impresión, con contador, fecha y
  usuario responsable.
- Configuración institucional versionada: formulario limpio, ayudas
  contextuales, campos a la izquierda, vista preliminar a la derecha e
  historial de registros activados.
- Historial legal enriquecido sin alterar el orden por correlativo: fecha de
  generación, identidad, egreso, servicio, emisor y agrupamiento desplegable
  de constancias del mismo paciente.
- Siguiente número anual visible sobre el historial, calculado desde el
  correlativo central sin reservarlo anticipadamente.
- 23 pruebas Laravel aprobadas, 1 omitida en SQLite y 129 aserciones.
- Consulta comprobada sobre 5,872 egresos y emisión validada dentro de una
  transacción revertida, sin datos de prueba residuales.
- Línea de tiempo validada contra un paciente real con 10 episodios: ocho
  registros en la primera página y disponibilidad del bloque siguiente.

### Planificado

- Consolidación de Egresos y Cirugías en `Intranet_HSJ`, manteniendo
  `HSJ_Identity` y SIGH como límites independientes.
- Esquemas operativos, permisos de Egresos, conciliación temporal, secuencia
  de importación, conteos de validación y procedimiento de reversión.

### Añadido

- Esquemas SQL Server `egresos`, `cirugias`, `catalogos`, `auditoria` y
  `staging` administrados mediante migraciones Laravel.
- Modelo definitivo de Egresos con relaciones internas, índices, restricciones,
  correlativos transaccionales, auditoría y tablas temporales de conciliación.
- Doce permisos centrales de Egresos y perfiles `consulta_egresos`,
  `operador_egresos` y `gestor_egresos`.
- Lectura controlada de respaldos MySQL, verificación SHA-256 e importadores
  Artisan idempotentes para Egresos y Cirugías.
- Validadores de conteos, huellas, relaciones, historiales, correlativos y
  participantes quirúrgicos.
- Inventario de conciliación en `staging` sin creación automática de cuentas
  ni modificación del personal central.
- Estándar institucional para integrar aplicaciones nuevas con
  `HSJ_Identity`, reutilizando usuarios, perfiles y permisos centrales.
- Permisos funcionales de Cirugías para análisis, reportes, registros,
  importaciones y mantenimiento de personal.
- Perfil `cirugias` reutilizado para consulta y perfil central
  `gestor_cirugias` incorporado para la operación completa.
- Navegación y footer institucionales dentro del módulo de Cirugías.
- Integración inicial de Preline UI 4.2.
- Navbar responsive con navegación institucional y menú de usuario.
- Acceso administrativo al CRUD existente de perfiles y accesos.
- Footer institucional reutilizando la identidad visual del Hospital San José.
- Scripts npm reproducibles para compilar Tailwind y publicar Preline.
- Documentación inicial de instalación, arquitectura y bases de datos.

### Corregido

- Error interno al abrir `/egresos` causado por la compilación del bloque de
  configuración JavaScript de Blade.
- Recursos y endpoints de Egresos ahora respetan el host y puerto actuales en
  lugar de depender del `APP_URL` configurado para otro entorno.
- Retorno visible desde Cirugías al portal de módulos y acceso al perfil
  central.
- Opciones y endpoints de Cirugías protegidos por capacidades centrales, no
  solo por el rol local heredado.
- Administración local de cuentas de Cirugías retirada del flujo activo; el
  acceso administrativo redirige al CRUD central.
- Acceso a Cirugías reutilizando la sesión y los permisos centrales de
  `HSJ_Identity`, sin solicitar un segundo inicio de sesión.
- Redirección automática del administrador central al panel administrativo de
  Cirugías y del personal autorizado al panel operativo.
- Persistencia del perfil de acceso al editar usuarios: el formulario ya no
  mezcla el rol central con un área anterior y recarga el registro guardado.
- Los módulos del usuario se muestran como permisos heredados del perfil
  central, evitando selecciones visuales que antes no se almacenaban.
- Actualización inmediata de la sesión cuando el administrador modifica su
  propio correo o perfil.
- Deformación de la página `/areas` en resoluciones intermedias y móviles.
- Desbordamiento horizontal de tarjetas y accesos rápidos.
- Tamaño sin restricciones de imágenes utilizadas como iconos de módulos.
- Distribución del acceso rápido cuando el usuario tiene un único módulo.
- Cache busting de `Areas.css` mediante la versión `v=3`.

### Validación

- Matriz central verificada: `cirugias` con 3 permisos de consulta,
  `gestor_cirugias` con 6 permisos operativos y `administrador` con acceso
  completo.
- Acceso, retorno al portal, footer, redirección administrativa y retiro de la
  API local de cuentas comprobados por HTTP.
- Pruebas unitarias del puente de sesión central hacia Cirugías.
- Verificación de sintaxis PHP y compilación de recursos frontend.
- Visualización comprobada en escritorio y viewport móvil.
- Navbar colapsable y menú de perfil comprobados con Preline.
- Once pruebas Laravel aprobadas con 33 aserciones.
