# Inscripciones históricas y períodos de calificación

## Inscripciones

Quitar un estudiante de un curso constituye una baja administrativa. La fila de `asignacioncursos` no se elimina: cambia de `ACTIVA` a `BAJA` y registra fecha y motivo. Las notas, cierres y demás antecedentes continúan vinculados al estudiante.

Los estudiantes con inscripción de baja no acceden al aula ni aparecen en nuevas planillas. Si regresan al mismo curso, la inscripción se reactiva y conserva todo el historial previo.

## Modalidades por curso

Cada curso define una modalidad:

- `UNICO`: Período único y Calificación final.
- `DOS_TRAMOS`: Primer período, Segundo período y Calificación final.

Intensificación es opcional en ambas modalidades. Cuando está habilitada, su planilla incluye automáticamente estudiantes activos cuya última calificación de cierre regular o final es inferior a 7.

Los cursos existentes quedan en `DOS_TRAMOS` con intensificación habilitada para preservar el funcionamiento previo. Si un curso ya contiene notas o cierres, la modalidad no puede modificarse; esto evita ocultar o mezclar antecedentes. La opción de intensificación sí puede activarse o desactivarse.

## Apertura y cierre

El estado de un período se registra por materia en `periodos_seccion_estado`. Cerrar un período en una materia bloquea sus evaluaciones y cierres, sin afectar otras materias del mismo curso.

## Despliegue

Ejecutar `sql/2026-09-14_inscripciones_periodos_configurables.sql`. El modelo también intenta preparar estas columnas y tablas para instalaciones donde el usuario de la aplicación posee permisos DDL.
