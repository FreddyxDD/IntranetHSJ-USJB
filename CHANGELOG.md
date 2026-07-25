# Historial de cambios

El proyecto utiliza un registro cronológico de cambios relevantes siguiendo
los principios de Keep a Changelog.

## [Sin publicar]

### Egresos - entrega funcional

- Módulo Laravel nativo con panel, búsqueda paginada, detalle, CIE-10,
  historial y vista imprimible de constancias.
- Emisión de constancias con correlativo SQL Server por cuenta y año,
  historial obligatorio y evento central de auditoría.
- Edición y anulación transaccionales con permisos separados, motivo
  obligatorio y registro de valores anteriores y nuevos.
- Configuración institucional de responsables, cargos e iniciales protegida
  por `egresos.configuration.manage`.
- Marca visible de anulación en la vista imprimible.
- Autorización exclusiva mediante sesión y permisos de `HSJ_Identity`.
- Interfaz responsive compilada con Tailwind y Preline.
- 17 pruebas Laravel aprobadas con 58 aserciones.
- Consulta comprobada sobre 5,872 egresos y emisión validada dentro de una
  transacción revertida, sin datos de prueba residuales.

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
