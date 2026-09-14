# Base de datos

## Expansión multiinstitución

`sql/2026-09-14_multi_institucion_00_diagnostico.sql` comprueba relaciones y roles
sin modificar datos. Requiere las tablas académicas actuales.
`sql/2026-09-14_multi_institucion_01_expandir.sql` crea instituciones, membresías,
roles por membresía y el marcador de privilegio global; traslada los datos
existentes a MenteMotion sin cambiar IDs ni eliminar columnas.

Esta expansión no activa multi-tenancy. Las columnas nuevas admiten NULL para
preservar los INSERT del código anterior; las restricciones definitivas y los
índices únicos institucionales quedan pendientes de la activación del código
aislado. No aplicar el dump base sobre datos existentes. Detalles y evidencia:
[multi-institucion.md](multi-institucion.md).

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
- `evaluaciones`: evaluaciones creadas por tema y fecha sin depender de una leccion o trabajo practico.
- `evaluaciones_calificaciones`: calificaciones y devoluciones de cada estudiante para una evaluacion independiente.
- `actividades`: configuracion principal de actividades privadas, publicas, ocultas o guardadas como plantilla.
- `actividades_preguntas`: preguntas asociadas a una actividad.
- `actividades_opciones`: opciones de multiple choice.
- `actividades_intentos`: entregas de estudiantes o visitantes.
- `actividades_respuestas`: respuestas registradas por intento.

Campos relevantes agregados al modulo de actividades:

- `intentosPermitidos`: limita la cantidad de entregas por estudiante.
- `esPlantilla`: marca si la actividad pertenece al banco de actividades.
- `alcancePlantilla`: clasifica la plantilla como `personal` o `institucional`.
- `destacadaPublica`: permite marcar actividades publicas para destacarlas en integraciones y listados.
- `id_actividad_origen`: referencia la actividad desde la cual se duplico o guardo la plantilla.
- `codigoBase`: guarda el fragmento fuente para las actividades de deteccion de errores en codigo.
- `lenguajeCodigo`: permite adaptar la consigna de codigo segun el lenguaje elegido.
- `variantesCodigo`: guarda alternativas validas para una misma correccion esperada.
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

## Lecturas del contexto (fase 2)

`ModeloInstituciones` verifica el marcador de expansión y consulta identidad,
institución y membresía activas, con sus roles. Esta fase no añade otra migración
ni aplica la expansión automáticamente. Los ensayos usan bases separadas.

La fase 3 agrega una consulta para validar los roles efectivos de cualquier usuario
en una institución concreta. Exige que usuario, institución y membresía estén
activos y solo reconoce los códigos académicos habilitados. Las consultas legacy
que filtran `usuarios.rol` se sustituirán al incorporar el tenant en fase 4.

En el primer bloque de fase 4, los listados de usuarios y matrículas del curso usan
roles de membresía. Las referencias redundantes de entregas se comprueban contra
la cadena lección → sección → curso; las filas incoherentes no se reasignan ni
eliminan automáticamente. Entregas, posteos y calificaciones exigen además que la
persona tenga una membresía actual o histórica en la institución; una baja conserva
la trazabilidad, pero una identidad exclusiva de otro tenant queda oculta. La
preparación DDL de cursos/lecciones queda desactivada
durante el ensayo institucional. Consultar [los pendientes de despliegue](multi-institucion-fase4.md).
