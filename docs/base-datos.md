# Base de datos

## Archivos principales

- `classroom.sql`: dump base del sistema, pensado como origen limpio del proyecto.
- `sql/2026-06-01_*.sql`: primeras migraciones funcionales.
- `sql/2026-06-02_expand_academic_titles.sql`: ampliaciones de campos academicos y banners.

## Tablas principales

- `usuarios`: credenciales, rol, estado, fechas y ultima conexion.
- `perfiles`: datos personales.
- `cursos`: aulas principales.
- `secciones`: materias o clases dentro de un curso.
- `lecciones`: materiales, tareas y preguntas.
- `recursoslecciones`: adjuntos y enlaces de una leccion.
- `entregaslecciones`: entregas de tareas por estudiante.
- `calificaciones`: notas y devolucion.
- `actividades`: configuracion principal de actividades privadas, publicas u ocultas.
- `actividades_preguntas`: preguntas asociadas a una actividad.
- `actividades_opciones`: opciones de multiple choice.
- `actividades_intentos`: entregas de estudiantes o visitantes.
- `actividades_respuestas`: respuestas registradas por intento.
- `mensajes`: hilo principal del mensaje.
- `mensajes_participantes`: estado por usuario, leido, papelera y eliminacion.
- `mensajes_adjuntos`: archivos adjuntos.
- `usuarios_historial`: registro de acciones de alta, baja y reactivacion.

## Relaciones importantes

- Un curso puede tener muchas secciones.
- Una seccion puede tener muchas lecciones.
- Una leccion puede tener recursos, posts y entregas.
- Una entrega puede tener una calificacion asociada.
- Un mensaje puede tener varios destinatarios y varios adjuntos.

## Reglas de persistencia

- La entrega de tareas se guarda como archivo y comentario opcional.
- La calificacion guarda la nota y la devolucion.
- Las secciones pueden guardar banner o degradado.
- La baja de usuarios es logica, no fisica.

## Notas de mantenimiento

- Si la base ya existia, ejecutar primero las migraciones.
- Si se necesita reconstruir desde cero, usar `classroom.sql`.
- Mantener consistencia en nombres de columnas nuevas antes de crear mas vistas.
