# Historial de cambios

El proyecto utiliza un registro cronológico de cambios relevantes siguiendo
los principios de Keep a Changelog.

## [Sin publicar]

### Añadido

- Integración inicial de Preline UI 4.2.
- Navbar responsive con navegación institucional y menú de usuario.
- Acceso administrativo al CRUD existente de perfiles y accesos.
- Footer institucional reutilizando la identidad visual del Hospital San José.
- Scripts npm reproducibles para compilar Tailwind y publicar Preline.
- Documentación inicial de instalación, arquitectura y bases de datos.

### Corregido

- Deformación de la página `/areas` en resoluciones intermedias y móviles.
- Desbordamiento horizontal de tarjetas y accesos rápidos.
- Tamaño sin restricciones de imágenes utilizadas como iconos de módulos.
- Distribución del acceso rápido cuando el usuario tiene un único módulo.
- Cache busting de `Areas.css` mediante la versión `v=3`.

### Validación

- Visualización comprobada en escritorio y viewport móvil.
- Navbar colapsable y menú de perfil comprobados con Preline.
- Seis pruebas Laravel aprobadas con 19 aserciones.
