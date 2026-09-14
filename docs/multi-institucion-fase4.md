# Fase 4: aislamiento de recursos

El primer bloque quedó registrado en `6e9a6dd5302ac3a7b1aebab5a6fa1393d079015e`.
El flujo institucional permanece bajo indicador de activación. El aislamiento de
los dominios actuales quedó implementado y cubierto por pruebas; los puntos finales
son condiciones de despliegue y mantenimiento.

## Cambios incorporados

- `ModeloTenant` centraliza comprobación de contexto activo, predicados de curso
  e institución, cadena sección/curso, lección/sección/curso y validación de
  membresías, roles e inscripciones. Sin contexto seleccionado rechaza el acceso.
- Los listados de cursos y materias filtran institución en SQL. Los accesos por
  ID y las operaciones de estos modelos validan pertenencia antes de operar.
- El controlador de rutas aplica una segunda frontera antes del renderizado y
  responde 403 ante IDs ajenos de cursos, materias, actividades, mensajes,
  perfiles y edición de membresías.
- La misma frontera valida los IDs relacionados enviados por POST en acciones de
  lecciones, entregas, actividades y mensajes, aunque la URL principal pertenezca
  a la institución activa.
- La revisión de formularios incorporó también los nombres legacy realmente usados
  por las vistas: `idLeccion`, `idRecursoLeccion`, `id_clase`, `id_evaluacion`,
  `id_periodo`, `id_instrumento`, `id_mensaje_respuesta` e `id_seccion_destino`.
  Curso, materia y usuario enviados por POST deben coincidir con el recurso de la URL.
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
  Si una falla aparece después del preflight, una compensación elimina en orden
  opciones, preguntas, actividades, recursos, archivos legacy, lecciones,
  materias, curso y copias físicas creadas durante ese intento.
- Las entregas incoherentes se excluyen de lecturas y agregados. Entregas,
  posteos y calificaciones también exigen una membresía actual o histórica de la
  persona en el tenant: una baja conserva antecedentes, mientras una identidad
  exclusiva de otra institución queda oculta. La eliminación de una lección se
  bloquea ante dependencias inconsistentes y una materia no puede cambiar de
  curso en modo institucional, para preservar las referencias.
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
- La resolución pública por ID usa un predicado independiente de la sesión: admite
  entregas anónimas de actividades publicadas y rechaza IDs privados manipulados.
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
- Los adjuntos de mensajes, entregas y recursos locales se descargan mediante
  `ControladorDescargas`. La ruta revalida sesión, tenant, participación en la
  conversación y acceso académico antes de resolver el archivo. Sólo acepta
  rutas canónicas dentro de `uploads/mensajes` o `uploads/lecciones`; ambas
  carpetas bloquean el acceso HTTP estático mediante `.htaccess`.
- El detalle administrativo de usuarios, sus relaciones académicas y los
  listados o contadores de conexión se limitan a membresías de la institución
  activa. El detalle no expone la contraseña global y los controladores rechazan
  IDs de otra institución antes de modificar, dar de baja o reactivar cuentas.
- La edición del perfil global exige que el ID del formulario coincida con la
  identidad autenticada. Un usuario conserva la edición de sus propios datos,
  pero ya no puede modificar otro perfil alterando el POST.
- El alta institucional reutiliza por email una identidad global existente o
  crea una nueva sin copiar el rol académico a `usuarios.rol`. La membresía
  admite varios roles y su actualización no modifica nombre, email, contraseña,
  foto ni perfil compartidos con otras instituciones.
- Las bajas y reactivaciones administrativas actúan sobre
  `usuarios_instituciones`. La identidad global y las demás membresías permanecen
  activas; los roles se conservan al reactivar. El historial registra y filtra
  `id_institucion` para que sus eventos tampoco se mezclen entre tenants.

## Pruebas

`php tests/multi_institucion_recursos.php` crea una base sintética nueva y ejecuta
primero las comprobaciones de autenticación y contexto. Luego prueba modelos con
cursos y materias de dos instituciones: filtrado de listados, lectura y borrado
por IDs ajenos, usuarios exclusivamente de otra institución, asignaciones,
lecciones, recursos y entregas con relaciones cruzadas o inscripción revocada.

Los casos de modelo se complementan con peticiones HTTP a los endpoints existentes.
El bloqueo HTTP preventivo fue retirado en bases migradas de ensayo; `classroom`
no se modifica.

La verificación incluye peticiones HTTP reales al panel y a listados, recursos
propios y recursos ajenos. Se probaron también llamadas directas a
controladores con un POST de duplicación de curso ajeno y descargas de adjuntos
o recursos propios y ajenos. Los archivos sintéticos se eliminan después de
comprobar que la copia mantiene exactamente su contenido. El borrado de una
lección reúne primero sus rutas, elimina en base de datos recursos,
`archivoslecciones`, entregas y adjuntos dentro del tenant, y recién después
borra los archivos físicos exclusivos. Si una referencia es incoherente se
conservan filas y archivos; si otra lección comparte la ruta, el archivo tampoco
se elimina. La misma verificación global se aplica al reemplazo y borrado de
recursos individuales y a los archivos de entregas: sólo se retiran rutas sin
referencias restantes y físicamente ubicadas dentro de `uploads/lecciones`.
Además, una comprobación HTTP local confirmó respuesta `403` al intentar acceder
directamente a archivos de ambas carpetas protegidas.

Última verificación: `campus_mt_fase2_20260914_160449_3fa968`, 322 comprobaciones
correctas. Incluye apertura del panel, listados institucionales, lectura de un curso
propio y respuestas 403 ante lectura o escritura cruzada de cursos, materias,
asistencia, calificaciones, actividades, mensajes y usuarios. Por HTTP también
reutiliza una identidad global, cambia roles y da de baja/reactiva exclusivamente
su membresía. Las pruebas envían, actualizan y cancelan entregas válidas dentro de
Instituto Demo; ejercitan lectura, papelera, restauración y eliminación personal de
mensajes; y rechazan IDs relacionados de MenteMotion ocultos en formularios propios.
También registran una actividad pública sin sesión y evitan forzar una privada.
Los casos ocultos dentro de una URL válida comprueban cursos, usuarios, lecciones,
recursos, clases, evaluaciones, catálogos y referencias de mensajería de otro tenant.
También alternan Instituto Demo y MenteMotion desde el selector del header y
verifican que cambien los cursos y el contexto sin cerrar sesión.

`tests/multi_institucion_legacy.php` verifica el comportamiento habitual de los
modelos contra la misma base sintética, con contexto desactivado. Se ejecuta
definiendo `CAMPUS_TEST_BASE` con el nombre devuelto por el ensayo de recursos;
rechaza nombres que no correspondan a estas bases de prueba. Pasaron sus veintiséis
comprobaciones de listados, lecturas y escritura, incluidas materias, clases de
asistencia, actividades, usuarios, relaciones académicas, contadores de mensajes
y una descarga autorizada. No usa la base original.

Las tablas históricas MyISAM no ofrecen rollback transaccional. La duplicación usa
una recuperación compensatoria y registra en el log cualquier paso que no pudiera
limpiar. Si una fila no puede retirarse, conserva las copias físicas para no romper
referencias parciales. La prueba provoca una falla SQL intermedia real y comprueba
que los conteos de ocho tablas y los archivos regresen al estado anterior.

## Controles pendientes antes de habilitar en producción

- Mantener la revisión HTTP al incorporar nuevas rutas o nombres de parámetros. El
  inventario actual no contiene endpoints AJAX propios separados de los controladores MVC.
- Mantener la revisión de referencias históricas al incorporar nuevas consultas.
  Entregas, posteos y calificaciones ya aplican relaciones académicas y membresías
  históricas mediante los predicados centrales, y el borrado se bloquea antes de
  modificar filas o archivos si encuentra inconsistencias.
- Mantener monitoreada la recuperación compensatoria de duplicaciones mientras las
  tablas históricas continúen en MyISAM; los fallos de limpieza quedan en el log.
- Revisar las acciones administrativas restantes que no transportan recursos académicos
  y conservar la frontera central al agregar nuevos formularios.
- Revisar enlaces de archivos que pudieran estar embebidos dentro de contenido
  enriquecido histórico. Las rutas estructuradas de `archivoslecciones`, adjuntos,
  entregas y recursos ya participan del borrado protegido.
- Trasladar preparaciones DDL a migraciones, finalizar restricciones y validar el
  delta de filas creado por el modo habitual después de la expansión.
- Repetir la batería HTTP contra la configuración real de producción antes de
  autorizar el indicador y cada vez que se agregue un nuevo dominio o endpoint.
