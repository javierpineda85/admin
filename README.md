# Classroom

Sistema web tipo classroom para administradores, docentes y estudiantes.

## Que incluye

- Login y recuperacion de contrasena.
- Modo de autenticacion `LOCAL`, `WORDPRESS` o `HYBRID`.
- Panel por rol con accesos y metricas.
- CRUD de usuarios, cursos y materias.
- Lecciones tipo material, tarea y pregunta.
- Calificaciones con devolucion.
- Mensajeria con adjuntos, papelera y reglas de destinatarios.
- Perfil de usuario con foto, alta/baja y ultima conexion.

## Estado actual

- Rediseño visual global del panel y del classroom.
- Materias con tabs para tablon, trabajo de clase, personas y calificaciones.
- Lecciones con borradores, carga multiple de adjuntos y enlaces, y orden desde la mas nueva.
- Mensajeria ampliada para estudiantes, docentes y tutores.
- Header con notificaciones reales y marcado de lectura.
- Footer actualizado con la firma visual actual del proyecto.

## Roles

- Administrador: gestiona usuarios, cursos y materias.
- Docente: trabaja sobre las materias asignadas, publica clases, corrige tareas y carga devoluciones.
- Estudiante: accede a sus cursos, materias, tareas, notas y mensajes permitidos.

## Instalacion rapida

1. Copiar el proyecto en `C:\wamp64\www\admin` o la ruta local equivalente.
2. Crear la base de datos desde `classroom.sql`.
3. Ejecutar las migraciones adicionales en `sql/` si tu base ya estaba creada.
4. Ajustar `config.php` si cambia la zona horaria o el entorno local.
5. Abrir `http://localhost/admin`.

## Estructura principal

- `controladores/`: logica de aplicacion y flujo por accion.
- `modelos/`: consultas SQL y acceso a datos.
- `vistas/`: interfaz visual por modulo.
- `css/`: estilos globales del classroom.
- `sql/`: dump base y migraciones.
- `uploads/`: archivos adjuntos de lecciones y mensajes.

## Documentacion

- [Panorama general](docs/README.md)
- [Arquitectura](docs/arquitectura.md)
- [Roles y permisos](docs/roles.md)
- [Modulos](docs/modulos.md)
- [Base de datos](docs/base-datos.md)
- [Flujos de uso](docs/flujos.md)
- [Despliegue multiinstitución](docs/multi-institucion-despliegue.md)
- [Cambios Junio 2026](docs/cambios-2026-06.md)
- [Integracion WordPress](docs/integracion-wordpress.md)
- [Cierre de integracion WordPress](docs/integracion-wordpress-cierre.md)
- [Manual de usuario](docs/manual-usuario/manual-usuario.md)
- [Versiones](docs/versiones.md)

## Evolución multiinstitución

El diseño y seguimiento están en [docs/multi-institucion.md](docs/multi-institucion.md).
La expansión de esquema, el aislamiento académico, el panel SuperAdmin y el
selector del header están disponibles para ensayo sobre una base migrada. Antes
de producción deben validarse el delta de datos legacy y los pendientes de
despliegue documentados. El ensayo
`php tests/multi_institucion_migracion.php` crea una base nueva, conserva la original
y verifica preservación de datos y reejecución.

La fase 2 agrega contexto central y selección institucional. El indicador
`INSTITUCIONES_CONTEXTO_ACTIVO` vale 0 por defecto. Usar 1 solamente en una base
migrada de ensayo: una membresía válida abre el Campus y cada petición revalida el
contexto. `php tests/multi_institucion_contexto.php` prueba contexto y login
HTTP LOCAL/WORDPRESS/HYBRID con cuentas sintéticas en una base separada, sin correo
ni conexión con WordPress de producción.

La fase 3 conecta los roles de membresía con `ControladorPermisos`, admite roles
simultáneos y valida institucionalmente las personas asignables.

La fase 4 se verifica con `php tests/multi_institucion_recursos.php`, que ensaya aislamiento
de recursos y peticiones HTTP reales sobre datos sintéticos. Los accesos directos a
cursos, materias, actividades, mensajes, perfiles y membresías pasan por una
validación central. La batería incluye asistencia, calificaciones, mensajes y
entregas por HTTP, con casos permitidos, IDs relacionados manipulados y cruces
institucionales denegados. También comprueba intentos públicos sin sesión. Los
formularios MVC revalidan centralmente sus recursos antes de ejecutar controladores.
El borrado de lecciones valida sus dependencias, incluye archivos legacy y adjuntos,
y elimina archivos físicos sólo después de confirmar el borrado institucional. Las
rutas compartidas se conservan y ninguna limpieza sale de `uploads/lecciones`.
La duplicación de cursos compensa filas y archivos parciales si una copia falla sobre
las tablas históricas MyISAM.
`php tests/multi_institucion_superadmin.php` verifica el panel global, su CSRF,
la separación del administrador institucional y la gestión de instituciones.
`sql/2026-09-14_multi_institucion_04_endurecer.sql` queda reservado para el corte
posterior a la validación del delta: se detiene ante datos incompletos y recién
entonces aplica las restricciones definitivas compatibles.
El [seguimiento de fase 4](docs/multi-institucion-fase4.md) detalla las pruebas y
los controles que deben mantenerse al agregar nuevas rutas.

La fase 7 agrega diagnósticos de solo lectura para identidades huérfanas y
relaciones sin membresía, valida el historial institucional y completa la
regresión multiinstitución, SuperAdmin y legacy. La activación productiva sigue la
[guía de despliegue](docs/multi-institucion-despliegue.md) y requiere regularizar
primero todas las incidencias informadas, sin reasignarlas automáticamente.

Para una importación manual existe un instalador autocontenido:
`sql/2026-09-14_multi_institucion_instalacion_completa.sql`. Su primera ejecución
mantiene desactivado el endurecimiento y puede usarse sobre una copia de la base
para recorrer la interfaz antes del corte definitivo.
