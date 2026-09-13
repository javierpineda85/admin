# Asistencia

La asistencia se registra por materia y fecha de clase. Al crear una fecha, todos los estudiantes cuya inscripción era válida ese día quedan marcados como presentes; el docente modifica solamente ausencias, tardanzas o inasistencias justificadas.

Estados disponibles: `PRESENTE`, `AUSENTE`, `TARDANZA` y `JUSTIFICADA`. Cada registro admite una observación. La combinación materia-fecha es única: volver a crear la misma fecha abre la clase existente sin duplicarla.

Docentes y administradores acceden a la planilla y al historial de sus materias. El estudiante ve un resumen por materia con cantidades y porcentaje. Para el porcentaje se computan como asistencia Presente, Tardanza y Justificada.

El historial lateral se filtra por año, mes y texto de fecha o tema; por defecto muestra el mes de la clase seleccionada para que un ciclo lectivo extenso siga siendo navegable. En la planilla, cada estudiante muestra sus faltas, tardanzas y justificadas acumuladas en la materia.

Las bajas posteriores no borran asistencias. Una clase nueva considera `fechaAlta` y `fechaBaja`, por lo que incluye únicamente a quienes estaban inscriptos en esa fecha.

Migración: `sql/2026-09-14_asistencias.sql`.
