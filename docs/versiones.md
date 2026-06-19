# Versiones del Campus

Este registro resume las versiones estables del sistema y el criterio usado para numerarlas.

## Criterio de versionado

- `5.xx.xxxx`: numero principal para cambios sustanciales realizados sobre el sistema.
- `x.06.xxxx`: mes del cambio, expresado con dos digitos.
- `x.xx.2126`: dia y anio del cambio, expresados como `DDAA`.

Ejemplo: `5.06.2126` identifica una version principal 5 realizada en junio, el dia 21 del ano 2026. Este orden evita que una version de julio resulte numericamente menor que una version de junio.

Este es un esquema de versionado calendario, no SemVer. Las versiones anteriores mantienen su numeracion historica; `5.06.2126` es la primera que aplica el nuevo criterio y no debe compararse segmento por segmento con `5.16.0626`.

## 5.06.2126 - Seguridad y mejoras operativas

Version identificada bajo el nuevo criterio cronologico.

### Cambios principales

- Nuevo formato de versionado `mayor.mes.dia-anio` para mantener un orden cronologico claro.
- Control para mostrar u ocultar la contrasena en el login.
- Cierre automatico de sesion luego de 30 minutos sin actividad.
- Cards del inicio adaptadas a dos columnas en celular y tablet, con contenido centrado y navegacion por modulo.
- Respuesta directa a mensajes recibidos con validacion del remitente original.
- Eliminacion permanente de actividades con modal de confirmacion y limpieza de preguntas, intentos y respuestas asociadas.

### Estado

- Version en desarrollo sobre la rama `v5.06.2126`.
- Footer del sistema: `Version 5.06.2126`.

### Observaciones

- La eliminacion de actividades es irreversible.
- El limite de inactividad puede configurarse con `SESSION_INACTIVITY_TIMEOUT`; el valor predeterminado es `1800` segundos.

## 5.16.0626 - Modulo de actividades version 5

Version generada el 17/06/2026.

### Cambios principales

- Nuevo modulo de actividades integrado al campus con alta, edicion, resolucion y resultados.
- Tipos iniciales disponibles: multiple choice, verdadero o falso, completar espacios y recurso externo embebible.
- Configuracion de visibilidad por actividad: privada para curso, publica y oculta por enlace directo.
- Ruta publica para listar y resolver actividades desde la web, preparada para integracion con el home WordPress de `mentemotion.com`.
- Intentos permitidos por estudiante con bloqueo real al alcanzar el limite configurado.
- Feedback por pregunta mediante pista y explicacion opcional cuando la respuesta es incorrecta.
- Slug generado automaticamente desde el titulo, sin exponerlo como campo tecnico al docente.
- Listado de actividades redisenado con cards, buscador y una presentacion mas clara para docente y estudiante.
- Formulario de actividades dividido en parciales para simplificar mantenimiento y evolucion del modulo.

### Estado

- Version actual publicada en `main`.
- Footer del sistema: `Version 5.16.0626`.

### Observaciones

- Esta release cubre las etapas 1 y 2 del plan de actividades.
- La fase 3 queda abierta para banco de actividades y evolucion del editor de codigo con tipos pedagogicos adicionales.

## 4.15.0626 - Iteracion de experiencia y vistas

Version generada el 15/06/2026.

### Cambios principales

- Modo de vista estudiante para administradores y docentes desde el header, sin alterar el rol real de la cuenta.
- Previsualizacion de cursos y materias como estudiante para recorrer el campus y guiar mejor a los alumnos.
- Ajuste del listado de cursos para que admin y docente puedan elegir una experiencia de navegacion mas cercana a la de un estudiante.
- Eliminacion de recargas forzadas luego de los toasts: el feedback visual queda visible sin romper el flujo.
- Modo horario de base de datos ajustado a UTC-03 para registrar eventos en hora Argentina.
- DataTables traducidos al espanol y botones de exportacion mas compactos.
- Botones y textos de experiencia visual afinados para mantener una interfaz mas limpia.
- Documentacion y referencias actualizadas para acompañar el cierre de la iteracion.

### Estado

- Version actual publicada en `main`.
- Footer del sistema: `Version 4.15.0626`.

### Observaciones

- La version 4.13.0626 se mantiene como referencia de la ultima estable anterior.
- El modo vista estudiante se apoya en la navegacion por cursos y materias, no en la inscripcion real del usuario administrador o docente.

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
