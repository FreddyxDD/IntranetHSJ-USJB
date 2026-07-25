# Historial de cambios

El proyecto utiliza un registro cronológico de cambios relevantes siguiendo
los principios de Keep a Changelog.

## [Sin publicar]

### Añadido

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
