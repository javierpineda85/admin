# Fase 4: aislamiento de recursos, en curso

El primer bloque quedó registrado en `6e9a6dd5302ac3a7b1aebab5a6fa1393d079015e`.
El flujo institucional sigue en ensayo y las aulas permanecen bloqueadas.
No habilitarlo en producción: la fase 4 todavía no está completa.

## Cambios incorporados

- `ModeloTenant` centraliza comprobación de contexto activo, predicados de curso
  e institución, cadena sección/curso, lección/sección/curso y validación de
  membresías, roles e inscripciones. Sin contexto seleccionado rechaza el acceso.
- Los listados de cursos y materias filtran institución en SQL. Los accesos por
  ID y las operaciones de estos modelos validan pertenencia antes de operar.
- El alta de cursos toma la institución desde el contexto, ignorando la que
  pudiera proporcionar el formulario. Las asignaciones validan roles de membresía.
- Lecciones y recursos validan su ascendencia. Las entregas comprueban que curso,
  sección y lección coincidan, además de membresía e inscripción activa.
- El listado institucional de usuarios utiliza membresías y roles; excluye usuarios
  ajenos y no devuelve contraseñas. Los docentes asignables usan esta consulta.
- En los modelos de cursos y lecciones, el modo institucional evita DDL y
  actualizaciones globales de preparación durante peticiones.
- Se retiró el SQL directo de detalle/edición de cursos y creación/edición de
  materias. Las vistas usan los modelos que aplican el contexto.
- Los predicados comprueban en la sentencia que la identidad, institución y
  membresía siguen activas, incluso si una consulta se preparó antes de revocar
  la membresía. Las escrituras de este bloque agregan condiciones de pertenencia.
- La duplicación institucional conserva materias, lecciones en borrador,
  recursos, archivos y actividades con preguntas/opciones, sin copiar matrículas.
  El origen debe pertenecer al contexto. Un preflight valida docentes y rutas
  canónicas, y rechaza archivos ausentes, fuera de la carpeta autorizada o
  referenciados también desde recursos/entregas ajenos o sin relación válida.
- Las entregas incoherentes se excluyen de lecturas y agregados. La eliminación
  de una lección se bloquea ante dependencias inconsistentes y una materia no
  puede cambiar de curso en modo institucional, para preservar las referencias.
- Asistencia deriva el tenant por la relación clase/sección/curso. Altas,
  listados y actualizaciones rechazan combinaciones cruzadas, filtran alumnos por
  inscripción y rol institucional activos, y no exponen clases incoherentes por ID.
  El controlador valida la sección antes de conceder acceso a un administrador.
- `2026-09-14_multi_institucion_02_asistencias.sql` prepara las tablas requeridas;
  las peticiones institucionales ya no ejecutan DDL para crearlas.

## Pruebas

`php tests/multi_institucion_recursos.php` crea una base sintética nueva y ejecuta
primero las comprobaciones de autenticación y contexto. Luego prueba modelos con
cursos y materias de dos instituciones: filtrado de listados, lectura y borrado
por IDs ajenos, usuarios exclusivamente de otra institución, asignaciones,
lecciones, recursos y entregas con relaciones cruzadas o inscripción revocada.

Los casos de modelos no equivalen a una certificación de todos los endpoints.
Se conserva el bloqueo HTTP de fases anteriores y no se modifica `classroom`.

Última verificación: `campus_mt_fase2_20260913_203321_c1d88d`, 139 comprobaciones
correctas, incluidas las de fases 2 y 3. Se probaron también llamadas directas a
controladores con un POST de duplicación de curso ajeno. Los archivos sintéticos
se eliminan después de comprobar que la copia mantiene exactamente su contenido.

`tests/multi_institucion_legacy.php` verifica el comportamiento habitual de los
modelos contra la misma base sintética, con contexto desactivado. Se ejecuta
definiendo `CAMPUS_TEST_BASE` con el nombre devuelto por el ensayo de recursos;
rechaza nombres que no correspondan a estas bases de prueba. Pasaron sus siete
comprobaciones de listados, lecturas y escritura, incluidas materias y clases de
asistencia. No usa la base original.

La duplicación no promete rollback integral ante fallas de almacenamiento:
las tablas históricas MyISAM no lo permiten. Un error posterior al preflight puede
dejar filas nuevas parciales; el origen se conserva. Debe resolverse la estrategia
de transacciones/recuperación antes de habilitar el flujo en producción.

## Trabajo pendiente antes de finalizar y habilitar

- Completar calificaciones, evaluaciones, ciclos y períodos; ampliar asistencia
  con pruebas HTTP cuando se retire el bloqueo preventivo de las aulas.
- Completar actividades, plantillas, intentos y rutas públicas.
- Aislar mensajería, notificaciones y agregados de panel.
- Separar todas las lecturas y modificaciones globales de usuarios/perfiles de
  la administración de membresías; aún existen otros métodos legacy sin aislamiento.
- Terminar el tratamiento de referencias históricas inconsistentes en consultas
  de entregas, posteos, calificaciones y eliminación de dependencias.
- Completar transacciones/recuperación de duplicaciones ante fallas de almacenamiento.
- Sustituir SQL de las vistas restantes y validar todos los POST/AJAX y parámetros de recursos.
- Entregar archivos privados por controlador, bloquear acceso estático y revisar
  rutas canónicas, adjuntos y contenido enriquecido histórico.
- Trasladar preparaciones DDL a migraciones, finalizar restricciones y validar el
  delta de filas creado por el modo habitual después de la expansión.
- Revisar las escrituras restantes de los demás dominios y sus dependencias;
  el refuerzo del bloque de cursos/materias/lecciones no certifica el resto del sistema.
- Pruebas HTTP de endpoints académicos y revisión residual antes de retirar el
  bloqueo de las aulas.
