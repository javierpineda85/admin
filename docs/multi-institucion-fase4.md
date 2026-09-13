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
- Las calificaciones de tareas validan en SQL la relación curso/materia/lección,
  la inscripción activa y el rol ESTUDIANTE de la membresía. Los listados por
  materia o estudiante filtran registros incoherentes y el controlador valida
  la materia antes de conceder gestión a administradores o docentes.
- Los listados de alumnos evaluables ya no consultan `usuarios.rol`: usan el rol
  de la membresía actual. En modo institucional, la preparación de calificaciones
  y evaluaciones tampoco intenta ejecutar DDL durante la petición.
- Ciclos lectivos e instrumentos de evaluación usan catálogos separados por
  institución. Los períodos heredan el tenant desde el ciclo y las evaluaciones
  validan conjuntamente curso, materia, período, instrumento y autor.
- Las evaluaciones y sus planillas filtran el tenant en accesos por ID, edición,
  eliminación y calificación. Una nota de evaluación exige inscripción activa y
  rol ESTUDIANTE. La migración `03_catalogos_calificacion` reemplaza las claves
  únicas globales por claves compuestas con institución y puede reanudarse.
- El cálculo y confirmación de cierres, así como la apertura o cierre de períodos,
  validan período, materia, actor y sesión institucional. Los promedios sólo usan
  evaluaciones coherentes de estudiantes con inscripción y membresía activas.
- Los historiales generales combinan tareas, evaluaciones y cierres después de
  aplicar el tenant a cada origen. Los resúmenes y tarjetas de materias también
  filtran sus agregados, evitando conteos o nombres de otras instituciones.
- Actividades y plantillas validan institución, curso, materia y autor en altas,
  edición, borrado, listados y accesos por ID. Preguntas, opciones, intentos,
  resultados y métricas heredan el tenant de la actividad; un estudiante debe
  conservar membresía, rol e inscripción activa para responder.
- El catálogo público no usa un `idInstitucion` del visitante: deriva la
  institución desde la actividad y exige que esté activa y que sus relaciones
  sean coherentes. Los intentos anónimos sólo se aceptan para actividades
  publicadas, visibles por enlace o públicas y habilitadas para visitantes.
- Los mensajes toman `id_institucion` de la sesión y validan que remitente y
  destinatarios sean miembros activos. Bandejas, detalle, lectura, papelera,
  eliminación, adjuntos y contadores aplican el tenant en SQL. El selector de
  personas y materias usa membresías, roles e inscripciones institucionales.
- Los indicadores y la actividad reciente del panel reutilizan el modelo de
  mensajes aislado, por lo que al cambiar de institución se recalculan sin
  conservar conversaciones ni contadores del contexto anterior.
- Las notificaciones validan el recurso publicado, el curso y la membresía del
  estudiante. Tanto el alta como el listado y las marcas de lectura guardan y
  filtran `id_institucion`; el modo institucional tampoco ejecuta DDL durante
  una petición.
- Las tarjetas, pendientes y fuentes de actividad reciente del panel filtran
  miembros, cursos, materias, lecciones, entregas, posteos y calificaciones con
  el contexto central. Los `LEFT JOIN` de notas también descartan relaciones
  incoherentes para no alterar los conteos de pendientes.
- El inicio y el formulario de actividades ya no consultan cursos o materias
  directamente desde las vistas: reutilizan los controladores y modelos
  institucionales. Los posteos validan conjuntamente autor, curso y lección en
  altas y lecturas, incluso ante IDs manipulados.

## Pruebas

`php tests/multi_institucion_recursos.php` crea una base sintética nueva y ejecuta
primero las comprobaciones de autenticación y contexto. Luego prueba modelos con
cursos y materias de dos instituciones: filtrado de listados, lectura y borrado
por IDs ajenos, usuarios exclusivamente de otra institución, asignaciones,
lecciones, recursos y entregas con relaciones cruzadas o inscripción revocada.

Los casos de modelos no equivalen a una certificación de todos los endpoints.
Se conserva el bloqueo HTTP de fases anteriores y no se modifica `classroom`.

Última verificación: `campus_mt_fase2_20260913_225834_c47009`, 216 comprobaciones
correctas, incluidas las de fases 2 y 3. Se probaron también llamadas directas a
controladores con un POST de duplicación de curso ajeno. Los archivos sintéticos
se eliminan después de comprobar que la copia mantiene exactamente su contenido.

`tests/multi_institucion_legacy.php` verifica el comportamiento habitual de los
modelos contra la misma base sintética, con contexto desactivado. Se ejecuta
definiendo `CAMPUS_TEST_BASE` con el nombre devuelto por el ensayo de recursos;
rechaza nombres que no correspondan a estas bases de prueba. Pasaron sus diecisiete
comprobaciones de listados, lecturas y escritura, incluidas materias, clases de
asistencia, actividades y contadores de mensajes. No usa la base original.

La duplicación no promete rollback integral ante fallas de almacenamiento:
las tablas históricas MyISAM no lo permiten. Un error posterior al preflight puede
dejar filas nuevas parciales; el origen se conserva. Debe resolverse la estrategia
de transacciones/recuperación antes de habilitar el flujo en producción.

## Trabajo pendiente antes de finalizar y habilitar

- Ampliar asistencia con pruebas HTTP cuando se retire el bloqueo preventivo;
  revisar las vistas de calificaciones antes de habilitarlas.
- Validar los endpoints HTTP de actividades cuando se retire el bloqueo
  preventivo de las aulas.
- Separar todas las lecturas y modificaciones globales de usuarios/perfiles de
  la administración de membresías; aún existen otros métodos legacy sin aislamiento.
- Terminar el tratamiento de referencias históricas inconsistentes en consultas
  de entregas, posteos, calificaciones y eliminación de dependencias.
- Completar transacciones/recuperación de duplicaciones ante fallas de almacenamiento.
- Completar la validación residual de todos los POST/AJAX y parámetros de recursos.
- Entregar archivos privados por controlador, bloquear acceso estático y revisar
  rutas canónicas, adjuntos de mensajes y contenido enriquecido histórico.
- Trasladar preparaciones DDL a migraciones, finalizar restricciones y validar el
  delta de filas creado por el modo habitual después de la expansión.
- Revisar las escrituras restantes de los demás dominios y sus dependencias;
  el refuerzo del bloque de cursos/materias/lecciones no certifica el resto del sistema.
- Pruebas HTTP de endpoints académicos y revisión residual antes de retirar el
  bloqueo de las aulas.
