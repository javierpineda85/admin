# Mejoras 5.2026.11.16

Esta rama reúne mejoras para el seguimiento académico, las actividades interactivas y la operación institucional de capacitaciones.

## Calificaciones por período y curso

Las calificaciones de tareas usan el período asignado a la lección. Las notas históricas se vinculan al período por su fecha cuando existen rangos configurados; la aplicación conserva una normalización complementaria para los casos sin rangos.

Las planillas de la materia muestran evaluaciones, trabajos y cierres en un mismo período. El cálculo de cierre incorpora ambos tipos de notas. Desde calificaciones se puede consultar por curso, emitir un boletín y exportar en XLS, CSV o PDF. Las devoluciones de trabajos se abren sin abandonar la planilla.

## Actividades interactivas

El editor incorpora tarjetas de 2 a 8 elementos para relacionar pares, ordenar secuencias y completar espacios. Las tarjetas admiten texto, imagen y audio cargado o grabado. Los formularios conservan el contenido al fallar una validación y los ejercicios de completar espacios permiten varios marcadores y saltos de línea.

La experiencia de estudiante muestra las tarjetas, sus imágenes y audio, con corrección por ítem, puntaje y devolución configurada por el docente.

## Coordinador académico

`COORDINADOR` es un rol institucional de consulta. Puede recorrer toda la institución activa: cursos, materias, contenidos, estudiantes, asistencia, actividades, entregas, resultados, calificaciones y cierres. Las vistas de asistencia y calificaciones se presentan en modo de solo lectura.

No puede alterar usuarios, inscripciones, cursos, contenidos, calificaciones, cierres o configuración. En mensajería puede contactar solamente a docentes y administradores de su institución.

El sistema no contiene aún el rol o la relación de tutor legal. El campo `tutor` de una materia pertenece a un docente adjunto; por eso las comunicaciones académicas automatizadas se dirigen solo al estudiante.

## Avisos de calificaciones

Al registrar o modificar una corrección de tarea, una evaluación o un cierre de período, el estudiante recibe:

1. Una notificación interna enlazada a sus calificaciones.
2. Un correo HTML que indica que hay una calificación disponible o actualizada y enlaza al Campus.

El valor de la nota no se incluye en el asunto ni en el cuerpo del correo. El aviso solo se vuelve a enviar si cambió la nota, la devolución, la asistencia o la confirmación del cierre. La entrega requiere un `MAIL_FROM_EMAIL` válido y un servicio PHP `mail()` configurado en producción.

## Despliegue

1. Respaldar la base de datos de producción.
2. Ejecutar [2026-11-16_actualizacion_produccion.sql](../sql/2026-11-16_actualizacion_produccion.sql).
3. Publicar el código de la rama.
4. Asignar el rol `COORDINADOR` desde el formulario de usuarios si corresponde.
5. Probar una nota nueva y una modificación con una cuenta de estudiante real, verificando tanto el correo como la campanita del Campus.
