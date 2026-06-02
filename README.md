# Classroom

Sistema web tipo classroom para administradores, docentes y estudiantes.

## Que incluye

- Login y recuperacion de contraseña.
- Panel por rol con accesos y métricas.
- CRUD de usuarios, cursos y materias.
- Lecciones tipo material, tarea y pregunta.
- Calificaciones con devolución.
- Mensajeria con adjuntos, papelera y reglas de destinatarios.
- Perfil de usuario con foto, alta/baja y ultima conexion.

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
