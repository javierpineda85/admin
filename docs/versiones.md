# Versiones del Campus

Este registro resume las versiones estables del sistema y el criterio usado para numerarlas.

## Criterio de versionado

Desde la version `5.2026.08.29`, el formato canonico es:

`<major>.<YYYY>.<MM>.<DD>`

- `major` representa la version mayor del sistema.
- `YYYY` representa el ano con cuatro digitos.
- `MM` representa el mes con dos digitos, desde `01` hasta `12`.
- `DD` representa el dia con dos digitos, desde `01` hasta `31`.

Por ejemplo, una entrega de la version mayor 5 publicada el 29 de agosto de 2026 se identifica como `5.2026.08.29`.

Este es un esquema de versionado calendario, no SemVer. El orden `ano.mes.dia` fue elegido deliberadamente para que las versiones de una misma version mayor sean naturalmente ordenables de izquierda a derecha y mantengan el mismo orden que sus fechas de publicacion.

### Motivo del cambio

El formato anterior era `mayor.mes.dia-ano`. Aunque expresaba la fecha, no conservaba el orden cronologico al cambiar de ano porque comparaba el mes antes que el ano. Por ejemplo:

- 29/08/2026 se representaba como `5.08.2926`.
- 30/01/2027 se representaba como `5.01.3027`.

La segunda fecha es posterior, pero una comparacion numerica o de izquierda a derecha puede interpretar `5.01.3027` como anterior a `5.08.2926`, dado que `01` es menor que `08`. Esto produce una regresion aparente de version al comenzar un nuevo ano.

Con el nuevo formato, las mismas fechas se representan asi:

- 29/08/2026: `5.2026.08.29`.
- 30/01/2027: `5.2027.01.30`.

Por lo tanto, el orden natural coincide con el cronologico: `5.2026.08.29 < 5.2027.01.30`.

Todas las versiones nuevas deben usar el formato `<major>.<YYYY>.<MM>.<DD>`. Las versiones historicas ya publicadas conservan sus identificadores originales y no deben renumerarse, salvo que exista una razon tecnica explicita y documentada para hacerlo.

## 5.2026.08.29 - Nuevo esquema cronologico

### Cambios principales

- Adopcion del formato `mayor.ano.mes.dia` para las nuevas versiones.
- Orden natural de versiones alineado con el orden cronologico, incluso al cambiar de ano.
- Conservacion de los identificadores de todas las versiones historicas.

### Estado

- Version actual publicada en `main`.
- Footer del sistema: `Version 5.2026.08.29`.

## Historial anterior al nuevo esquema

Las siguientes versiones conservan el formato con el que fueron publicadas. Sus identificadores son referencias historicas y no deben usarse como modelo para nuevas versiones.

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

- Version publicada historicamente en `main`.
- Footer de esa release: `Version 5.16.0626`.

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

- Version publicada historicamente en `main`.
- Footer de esa release: `Version 4.15.0626`.

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
