# Multiinstitución: diseño y seguimiento

Estado: fases 1, 2 y 3 implementadas y ensayadas; el flujo nuevo está disponible solo en ensayo. La aplicación todavía no
ofrece aislamiento multiinstitución. No habilitar una segunda institución en el
Campus hasta completar las fases de contexto, permisos, consultas y archivos.

Fase 4 en curso: [alcance, pruebas y pendientes del aislamiento](multi-institucion-fase4.md).

## Arquitectura acordada

Se conserva PHP, PDO y MVC. `usuarios` y `perfiles` representan la identidad global.
Una institución es una frontera de autorización y datos, no un filtro visual.

| Entidad | Relación institucional |
| --- | --- |
| instituciones | Raíz: idInstitucion, nombre, slug único, logo, activo, fechas y motivo de baja |
| usuarios_instituciones | Membresía única (id_usuario, id_institucion), estado y fechas |
| roles | Catálogo de códigos académicos; no contiene SUPERADMIN |
| usuarios_instituciones_roles | Clave primaria compuesta (id_usuario_institucion, id_rol) |
| usuarios.esSuperAdmin | Privilegio global explícito, independiente de las membresías |
| cursos | id_institucion obligatorio al completar la activación |
| secciones | Hereda por id_curso |
| lecciones | Hereda por id_modulo → secciones.idSeccion; no existe una tabla módulos en el esquema revisado |
| recursoslecciones / archivoslecciones | Heredan por id_leccion |
| entregaslecciones y calificaciones | Verificar coherencia entre curso, sección y lección; no basta comprobar uno de sus IDs |
| entregaslecciones_adjuntos | Hereda por id_entrega |
| asignacioncursos | Su columna legacy id_seccion referencia cursos.idCurso en el código actual |
| asistencia_clases / asistencia_registros | Sección → curso, y clase → sección; verificar el id_curso redundante |
| evaluaciones / evaluaciones_calificaciones | Sección → curso y evaluación → sección |
| ciclos_lectivos | id_institucion explícito; unicidad por institución y año |
| periodos_calificacion | Hereda por id_ciclo |
| instrumentos_evaluacion | id_institucion explícito; unicidad por institución y nombre |
| periodos_seccion_estado / cierres_periodo_calificaciones | Sección y período deben pertenecer a la misma institución |
| actividades | id_institucion explícito: puede no tener curso o ser plantilla; validar curso y sección cuando existan |
| actividades_preguntas / opciones / intentos / respuestas | Heredan por actividad; validar todas las referencias cruzadas |
| mensajes / participantes / adjuntos | Institución en mensaje; participantes y adjuntos heredan |
| notificaciones / notificaciones_lecturas | Institución explícita por referencias polimórficas y claves de lectura |
| usuarios_historial | Institución explícita para acciones institucionales; NULL reservado a identidad global |

`instituciones.configuracion` reserva un documento JSON para ajustes y branding.
Planes, límites y vencimientos se incorporarán mediante migraciones posteriores;
no se implementa lógica comercial ni se conceden permisos a partir de ese JSON.

## Roles y cuentas existentes

Los códigos habilitados actualmente por los permisos son ADMINISTRADOR, DOCENTE y
ESTUDIANTE. `secciones.tutor` representa un docente adjunto; no constituye un rol
TUTOR independiente. Se conserva esa semántica. El esquema de roles permite añadir
TUTOR posteriormente con permisos explícitos.

La base local también contiene GESTOR. No se lo convierte en administrador ni en
docente: se conserva en el campo legacy y se reporta para regularización. La
membresía puede existir sin un rol académico habilitado.

No se elimina `usuarios.rol`. Durante la expansión continúa funcionando el código
anterior. Al activar el nuevo contexto dejará de ser fuente de permisos. Migrar
roles de nuevo después de una revocación estaría mal: el traslado inicial debe
registrarse una sola vez y no restituir accesos al reejecutar el script.

## Flujo de login y selección

LOCAL, WORDPRESS e HYBRID autentican la identidad global. WordPress no concede
membresías ni modifica los roles institucionales. El contexto central valida en
base de datos usuario activo, institución activa y membresía activa en cada
petición protegida; las variables de sesión no bastan como prueba de autorización.

Sin membresías se muestra una pantalla sin acceso; con una se selecciona
automáticamente; con varias se muestran tarjetas con logo, nombre y todos los
roles. La vista permite cambiar entre membresías válidas; el header queda para fase 6. El cambio
usa POST con CSRF, renueva el identificador de sesión y elimina la previsualización
de estudiante, IDs contextuales y estados de navegación de la institución anterior.
Formularios abiertos en otra pestaña deben rechazarse si su contexto quedó obsoleto.

El SuperAdmin tiene un panel global separado para instituciones, suspensión,
asignación de administradores y recuentos de miembros. No recibe automáticamente
permisos académicos de todas las instituciones. Su acceso global no habilita un
bypass genérico en consultas académicas. La cuenta que lo recibirá debe elegirse
explícitamente; no se hereda de ADMINISTRADOR ni de la lista legacy de WordPress.

## Aislamiento a implementar

Los modelos deben exigir contexto antes de leer o escribir datos institucionales.
Los listados y agregados llevan predicados SQL; las operaciones por ID comprueban
tenant y relaciones completas. Las asignaciones exigen membresía activa y roles
compatibles. Las bajas institucionales modifican la membresía, nunca desactivan la
cuenta global. Un administrador institucional no debe poder cambiar credenciales
globales de un miembro y así tomar control de sus accesos a otra institución.

Las actividades públicas requieren una vía separada: resolver únicamente una
actividad publicada de una institución activa por su slug, sin establecer una
membresía artificial ni habilitar acceso a listados privados, resultados o usuarios.

Los adjuntos se servirán mediante un controlador con autorización por recurso,
comprobación de ruta canónica y headers de privacidad. Bloquear el acceso estático
a los directorios privados y reemplazar enlaces también en dashboards, mensajes,
entregas y contenido enriquecido. Verificar caché y archivos históricos.

## Migración y fases

1. Expansión de esquema y traslado inicial a MenteMotion, sin borrar datos ni
   cambiar el flujo web. Ensayar sobre una copia. Comprobar emails duplicados,
   roles no soportados, huérfanos y coherencia de relaciones.
2. Contexto central, identidad global y selección, con gestión de sesiones antiguas.
3. Permisos por conjunto de roles y gestión de membresías; retirar lecturas legacy.
4. Aislamiento en todos los modelos, controladores, SQL de vistas, archivos y rutas
   públicas; finalizar NOT NULL y restricciones al activar el código compatible.
5. Panel global SuperAdmin y altas/membresías con CSRF y auditoría.
6. Header y cambio de institución, incluyendo formularios abiertos en varias pestañas.
7. Pruebas de integración y HTTP de dos instituciones, revisión residual y documentación.

No usar `classroom.sql` sobre una instalación existente: contiene DROP TABLE.
Tampoco ejecutar todas las migraciones históricas a ciegas: la de mensajería
contiene DROP TABLE y varias usan ADD COLUMN IF NOT EXISTS, no admitido por el
MySQL 8.3 local. Las nuevas migraciones deben consultar information_schema.

El DDL de MySQL produce commits implícitos. La expansión debe poder reanudarse;
un BEGIN alrededor de ALTER TABLE no promete rollback integral. La institución
obligatoria se exige en una fase posterior compatible con los INSERT nuevos;
imponerla en la expansión rompería los INSERT del Campus actual.

## Matriz mínima de pruebas pendientes

En base de prueba: MenteMotion, Instituto Demo, A (DOCENTE/ESTUDIANTE), B
(ADMINISTRADOR de Demo), C (ESTUDIANTE solo en MenteMotion), SuperAdmin separado.
Comprobar alternancia, múltiples roles simultáneos, aislamiento de listados,
denegación por IDs de otra institución en lectura/edición/baja/borrado, inscripciones,
materias, notas, entregas, asistencia, mensajes, adjuntos, suspensión/revocación en
sesión abierta y protección de credenciales globales. Probar también repetición de
migración, falta de membresía, WP/HYBRID, recuperación y enlaces públicos.

## Hallazgos de seguridad previos a las modificaciones

El inventario adjunto `multi-institucion-inventario.md` enumera lecturas/escrituras
SQL y referencias a rol del código propio en el punto de partida. Es un inventario
estático, no una afirmación de que todos los accesos sean explotables ni una
certificación de aislamiento. Los puntos anteriores siguen pendientes hasta que
cada fase indique evidencia de verificación.

## Archivos afectados por las fases siguientes

| Capa | Alcance detectado |
| --- | --- |
| Entrada y sesión | index.php, config.php, vistas/plantilla.php, controladores/auth.controller.php y rutas.controller.php |
| Autorización | controladores/permisos.controller.php, cursos.controller.php, materias.controller.php, lecciones.controller.php, calificaciones.controller.php, asistencias.controller.php, actividades.controller.php, usuarios.controller.php, perfiles.controller.php y mensajes.controller.php |
| Persistencia | Todos los modelos académicos, mensajería, notificaciones, panel, usuarios y perfiles; autenticación global debe quedar separada de consultas institucionales |
| SQL fuera de modelos | Revisar las llamadas a Conexion en inicio, detalle/edición de curso, creación/edición de materia y formulario de actividades |
| Interfaz | Header, aside, pantallas de identidad/membresía, roles en formularios y perfiles, nuevas pantallas de selección y SuperAdmin |
| Archivos | Enlaces en detalle-seccion, inicio y detalle-mensaje; directorios uploads/lecciones y uploads/mensajes, además de imágenes institucionales y contenido enriquecido |
| Base | Nuevas migraciones; mantener classroom.sql como dump histórico hasta preparar una instalación limpia coherente |

## Evidencia de la expansión (2026-09-13)

Rama de trabajo: `mejoras_14-09-26-B`. MySQL local 8.3.0; PHP CLI 8.3.6.
Durante la fase 1 no se aplicó la migración a `classroom` ni se modificó código de aplicación.
El ensayo copia tablas a una base nueva y compara SHA-256 de todas las filas y
columnas originales, por tabla, sin imprimir datos personales.

Ensayo satisfactorio: `campus_mt_test_20260913_153306_e8313e`.
Las bases de ensayo permanecen locales para inspección; no son instalaciones
operativas. Un ensayo anterior detectó diferencia de collations entre el código
de rol legacy y el catálogo; se corrigió mediante comparación binaria del código
normalizado y se repitió la prueba completa.

Validado:

- Preservación de datos originales de las 32 tablas copiadas.
- Migración de 13 usuarios y 2 cursos a la institución inicial, sin promociones globales.
- Reejecución que conserva suspensión de institución/membresía y revocación de rol.
- Fixtures A, B y C, roles distintos por institución y dos roles en una membresía.
- Restricción única de membresía y rechazo de emails duplicados al iniciar migración.
- Sintaxis PHP del ensayo y `git diff --check`.

El diagnóstico de solo lectura sobre la base local encontró:

| Comprobación | Filas |
| --- | ---: |
| Lecciones sin sección | 12 |
| Inscripciones sin curso | 23 |
| Inscripciones sin usuario | 31 |
| Mensajes sin remitente | 8 |
| Usuarios con rol sin equivalencia (GESTOR) | 2 |

Los conteos pueden solaparse. No se eliminaron ni reasignaron esos registros.
Al endurecer las relaciones, los recursos cuyo tenant no pueda resolverse deben
rechazarse por defecto y aparecer en un informe de regularización; no deben
adoptar un tenant enviado por el cliente. Recuperar sus relaciones originales
exige revisar los antecedentes de esos datos.

Pendiente: finalización del esquema (NOT NULL, unicidad institucional, identidad
global única y claves foráneas compatibles), fases 3–7, pruebas de aislamiento
HTTP/modelos académicos, WordPress de producción, panel SuperAdmin y cierre
de archivos estáticos. El ensayo del esquema **no prueba** esas funcionalidades.
La elección del email de SuperAdmin quedó solicitada al usuario.

La documentación histórica de WordPress describe una sincronización de rol y
estado más amplia que el código actual: el modelo conserva roles locales válidos
salvo su excepción legacy de emails privilegiados, y el UPDATE no cambia activo.
La implementación futura debe tomar como referencia el flujo real revisado y
retirar esa excepción académica, sin reutilizarla como concesión de SuperAdmin.

La expansión es un paso de despliegue en mantenimiento, no una etapa para operar
indefinidamente: si el código antiguo crea nuevas filas después del traslado,
esas filas tendrán contexto NULL. Antes de activar la versión aislada hará falta
un corte final que valide y traslade ese delta sin reponer roles revocados.

## Fase 2: contexto y autenticación (2026-09-13)

`INSTITUCIONES_CONTEXTO_ACTIVO` vale 0 por defecto. Con valor 1 en el entorno
habilita exclusivamente un ensayo sobre una base previamente migrada. No ejecuta
la migración automáticamente. Desactivado conserva el flujo habitual.

`index.php` ejecuta `ControladorInstitucion::procesarAntesDeRenderizar()` antes
del HTML. `ModeloInstituciones` consulta identidad y membresías activas; el
controlador revalida usuario, institución, membresía y roles en cada petición.
Mantiene `institucion_id`, `institucion_slug`, `institucion_membresia_id` e
`institucion_roles`. Los consumidores deben usar `actual()`, `id()`, `membresia()`,
`roles()` y `esSuperAdmin()` después de esa validación. Estos accesores representan
la petición actual; GET, POST y el rol legacy no autorizan una institución.

Sin membresías se muestra `sin-acceso-institucional`; con una se selecciona
automáticamente; con varias aparecen tarjetas. La selección usa POST, CSRF y
versión de contexto consumible. El cambio renueva la sesión y elimina la
previsualización de estudiante. Revocaciones, suspensiones y cambios de roles
invalidan el contexto anterior; se rechazan formularios de selección obsoletos.
Las respuestas llevan `no-store` y no exponen errores SQL.

**Límite de esta fase:** la selección termina en `institucion-preparada`.
Las rutas académicas y públicas anteriores no se despachan en este modo: GET se
redirige y POST devuelve 403. Faltan permisos académicos (fase 3) y aislamiento de
consultas y archivos (fase 4). Este bloqueo del punto de entrada no protege los
archivos estáticos ni convierte los modelos antiguos en modelos aislados.
No activar este ensayo en producción. El selector del header queda para fase 6.

LOCAL, WORDPRESS e HYBRID comparten la salida de autenticación. En el ensayo,
WordPress sincroniza identidad por ID/email sin modificar roles, membresías ni
SuperAdmin; una identidad nueva queda sin acceso institucional. Se rechazan
cuentas inactivas, emails ambiguos y conflictos entre ID WordPress y email.
Se rechazan emails de más de 50 caracteres, sin truncarlos, por el esquema actual.
Un bloqueo MySQL serializa altas WordPress; la unicidad estructural definitiva
del email sigue pendiente. Los emails privilegiados legacy no conceden privilegios.

### Verificación

`php tests/multi_institucion_contexto.php` crea una base separada con cuentas
sintéticas A, B, C, sin membresía, sin rol y SuperAdmin. Ensaya PDO y peticiones
HTTP reales a servidores PHP temporales en LOCAL, WORDPRESS e HYBRID, incluido
fallback a WordPress, usando tablas WordPress sintéticas. No modifica `classroom`.

Ensayo de fase 2: `campus_mt_fase2_20260913_193422_3694f2`, 75 comprobaciones correctas.
Incluye alternancia DOCENTE/ESTUDIANTE, múltiples roles de B, rechazo de C en Demo,
revocación, suspensión, sesión manipulada, CSRF, formularios obsoletos, inactividad,
logout, bloqueo de rutas antiguas, falta de esquema y modo legacy desactivado.
También se verificó visualmente el cambio de institución y rol en navegador.
PHP lint y `git diff --check` pasaron.

La recuperación se comprobó por renderizado y delegación a WordPress sin enviar
correo. No se verificó SMTP ni WordPress de producción. Las bases sintéticas quedan
locales para inspección. No se asignó SuperAdmin a cuentas reales. El aislamiento
de cursos, materias, usuarios, entregas y archivos sigue pendiente: estas pruebas
certifican el contexto y su flujo, no el aislamiento académico completo.

## Fase 3: roles institucionales y permisos (2026-09-13)

`ControladorPermisos::rolesReales()` toma exclusivamente los roles del contexto
revalidado cuando el ensayo está activo. `tieneRol()` y
`tieneAlgunoDeLosRoles()` comprueban capacidades simultáneas. Para mantener las
vistas anteriores durante la evolución, `rolReal()` elige un rol principal estable:
ADMINISTRADOR, DOCENTE y ESTUDIANTE, en ese orden. Las rutas y menús autorizan por
la unión del conjunto completo, excepto durante la previsualización de estudiante.

`usuarioTieneRolEnInstitucion()` valida asignaciones mediante usuario, institución
y membresía activas. La reasignación del responsable de un curso, el docente
adjunto de una materia y la previsualización de estudiantes dejaron de leer
`usuarios.rol` en el flujo institucional. SuperAdmin permanece separado.

Con el indicador desactivado, los métodos conservan el único rol legacy. Con el
contexto activo, `usuarios.rol` se elimina de la sesión y no participa en permisos.
Los formularios globales de usuarios aún contienen el campo legacy y las consultas
académicas que clasifican personas todavía filtran `u.rol`; están bloqueadas por
fase 2 y se reemplazarán junto con sus filtros de tenant en fase 4.

El ensayo `campus_mt_fase2_20260913_194859_160508` completó 82 comprobaciones:
roles múltiples, prioridad estable, unión de permisos, ausencia de herencia legacy
y asignaciones aceptadas o rechazadas según institución. Las aulas siguen
bloqueadas: esta fase verifica la decisión de permisos, no la seguridad SQL.
