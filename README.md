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
- [Cambios Junio 2026](docs/cambios-2026-06.md)
- [Integracion WordPress](docs/integracion-wordpress.md)
- [Cierre de integracion WordPress](docs/integracion-wordpress-cierre.md)
- [Manual de usuario](docs/manual-usuario/manual-usuario.md)
- [Versiones](docs/versiones.md)
