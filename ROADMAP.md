# Roadmap Classroom

## Objetivo
Construir un classroom para estudiantes y docentes con roles claros, buena UX/UI, mensajería con restricciones y un backend ordenado y escalable.

## Fase 1: Base técnica
- Revisar estructura actual del proyecto.
- Unificar convenciones de nombres entre controladores, modelos, vistas y tablas.
- Ordenar el router central.
- Confirmar la estrategia visual base: seguir con Bootstrap 4/AdminLTE como base inicial y personalizar fuerte antes de migrar a otra stack.

## Fase 2: Autenticación
- Crear inicio de sesión.
- Manejar sesiones y cierre de sesión.
- Recuperación de contraseña.
- Redirección según rol.
- Protección de rutas públicas y privadas.

## Fase 3: Permisos por rol
- Administrador: CRUD de cursos, materias y usuarios.
- Docente: crear/editar clases, adjuntos, enlaces y notas.
- Estudiante: acceso a cursos, clases y notas.
- Bloqueo en backend de acciones fuera de rol.

## Fase 4: Núcleo académico
- Cursos.
- Materias.
- Secciones / clases / lecciones.
- Archivos adjuntos y enlaces.
- Calificaciones.
- Vistas de detalle por curso.

## Fase 5: Mensajería
- Bandeja de entrada.
- Enviados.
- Nuevo mensaje.
- Regla de estudiantes: solo pueden escribirse si comparten curso.
- Regla de docentes y administradores: pueden escribirse entre sí y a estudiantes.
- Validación de acceso en backend, no solo en frontend.

## Fase 6: UX/UI
- Rediseñar panel general.
- Mejorar jerarquía visual, navegación y estados vacíos.
- Mantener una estética moderna, llamativa y sobria.
- Revisar responsividad mobile y desktop.

## Fase 7: Calidad
- Validar formularios.
- Corregir inconsistencias de base de datos.
- Revisar seguridad de contraseñas y sesiones.
- Probar flujos completos por rol.

## Orden recomendado
1. Login y sesiones.
2. Router con control de acceso.
3. Permisos por rol.
4. CRUD administrativo.
5. Classroom académico.
6. Mensajería restringida.
7. Rediseño visual.
