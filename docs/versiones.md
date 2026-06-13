# Versiones del Campus

Este registro resume las versiones estables del sistema y el criterio usado para numerarlas.

## Criterio de versionado

- `4.xx.xxxx`: cambios sustanciales realizados sobre el sistema.
- `x.13.xxxx`: dia del cambio.
- `x.xx.0626`: mes y anio del cambio.

## 4.13.0626 - Estable

Version estable cerrada el 13/06/2026.

### Cambios principales

- Integracion WordPress/TutorLMS funcionando para ingresar al Campus con usuarios de `mentemotion.com`.
- Sincronizacion automatica de usuarios WordPress hacia usuarios locales del Campus.
- Soporte de autenticacion `LOCAL`, `HYBRID` y `WORDPRESS`.
- Validacion compatible con hashes legacy de WordPress tipo `phpass`.
- Usuario administrador asignable como docente o tutor de una materia.
- Mensajes habilitados entre estudiantes y sus docentes o tutores, incluyendo administradores asignados a una materia.
- Edicion completa de perfiles: DNI, telefono, fecha de nacimiento, domicilio, provincia y "Sobre mi".
- Manual de usuario con capturas para estudiantes y docentes.
- Favicon transparente aplicado en panel, login y recuperacion de contrasena.

### Estado

- Marcada como ultima estable.
- Footer del sistema: `Version 4.13.0626`.
- Tag Git previsto: `v4.13.0626`.

### Observaciones

- Algunas capturas del manual pueden actualizarse luego con datos definitivos del servidor.
- Se recomienda mantener `AUTH_MODE=HYBRID` mientras convivan usuarios locales y usuarios WordPress.
- El debug de autenticacion debe quedar desactivado en produccion.
