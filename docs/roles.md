# Roles y permisos

## Transición prevista

Se implementó la separación entre identidad global, membresía institucional y múltiples
roles por membresía para el ensayo. `usuarios.rol` sigue siendo la fuente del modo
habitual mientras el indicador está desactivado. `usuarios.esSuperAdmin` se agrega
con valor 0: ningún administrador actual recibe privilegios globales implícitos.
`secciones.tutor` es docente adjunto, no un rol académico TUTOR independiente.
Consultar [el diseño y los pendientes](multi-institucion.md).

En el flujo institucional, `rolesReales()` devuelve el conjunto completo y las
rutas se autorizan por la unión de capacidades. `rolReal()` conserva un rol
principal compatible: ADMINISTRADOR, DOCENTE y ESTUDIANTE, en ese orden.
`tieneRol()` se usa para una capacidad concreta. La previsualización limita
temporalmente los permisos a ESTUDIANTE y el cambio de institución la elimina.

## Administrador

Puede administrar todo el sistema:

- Crear, editar, inactivar y reactivar usuarios.
- Crear y editar cursos.
- Crear y editar materias.
- Ver todas las calificaciones.
- Gestionar mensajes, adjuntos y papelera.
- Acceder a paneles de seguimiento global.

## Docente

Trabaja solo sobre los cursos propios y las materias en las que esta asignado como titular o docente adjunto:

- Ver sus cursos en formato de tarjetas.
- Crear y editar sus propios cursos.
- Crear materias dentro de sus cursos y sumar un docente adjunto.
- Entrar al detalle del curso.
- Ver solo las materias asignadas.
- Crear, editar y eliminar lecciones.
- Adjuntar recursos y archivos.
- Corregir tareas y cargar devoluciones.
- Enviar mensajes a estudiantes y a otros docentes/admins.

## Estudiante

Accede solo a lo que corresponde a su inscripcion:

- Ver sus cursos en formato de tarjetas.
- Entrar al detalle del curso y materias.
- Entregar tareas.
- Ver notas y devoluciones.
- Participar en preguntas tipo foro.
- Enviar mensajes solo a estudiantes que compartan curso.

## Reglas de negocio importantes

- Un estudiante no puede escribir a otro estudiante fuera de su curso.
- Un docente no puede administrar una materia donde no esta asignado.
- Una entrega calificada queda bloqueada para edicion o cancelacion.
- El administrador inscribe estudiantes; administradores y docentes crean cursos.
- Solo el administrador puede dar de baja, reactivar o eliminar cursos; la eliminacion se bloquea si existen datos asociados.
- Solo el administrador puede dar de baja, reactivar o eliminar materias; la eliminacion se bloquea si existen contenidos o actividad asociados.
- Solo el administrador puede reasignar o quitar responsables de cursos y actualizar el equipo docente de una materia.
- WordPress autentica la cuenta, pero el rol academico se administra desde Campus.
- Las calificaciones siempre guardan nota y devolucion.

## Roles cargados en el ensayo de fase 2

Con el contexto activado se elimina el rol legacy de la sesión y se cargan todos
los roles de la membresía validada mediante `ControladorInstitucion::roles()`.
`ControladorPermisos` autoriza las rutas con ese conjunto y el Campus queda
disponible sobre una base migrada de ensayo. El funcionamiento legacy descrito
arriba corresponde al indicador desactivado. SuperAdmin no recibe membresías ni
bypass académicos.
