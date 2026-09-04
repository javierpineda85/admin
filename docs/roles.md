# Roles y permisos

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
- WordPress autentica la cuenta, pero el rol academico se administra desde Campus.
- Las calificaciones siempre guardan nota y devolucion.
