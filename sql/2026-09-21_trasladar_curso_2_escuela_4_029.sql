-- Traslado puntual del curso 2 desde Newton (institución 1)
-- hacia Escuela 4-029 (institución 2).
--
-- Requisitos:
-- 1. Exportar una copia completa de la base antes de ejecutar este archivo.
-- 2. Ejecutarlo con el Campus en mantenimiento.
-- 3. Las cuentas relacionadas con el curso deben tener membresía activa en la
--    institución 2. El procedimiento lo valida antes de modificar datos.
--
-- Las materias, lecciones, recursos, inscripciones, entregas, calificaciones y
-- asistencias conservan sus IDs y siguen al curso mediante sus relaciones.

DELIMITER $$

DROP PROCEDURE IF EXISTS trasladar_curso_2_escuela_4_029$$
CREATE PROCEDURE trasladar_curso_2_escuela_4_029()
BEGIN
    DECLARE curso_id INT DEFAULT 2;
    DECLARE institucion_origen INT DEFAULT 1;
    DECLARE institucion_destino INT DEFAULT 2;
    DECLARE ciclo_origen INT DEFAULT 1;
    DECLARE ciclo_destino INT DEFAULT NULL;
    DECLARE filas INT DEFAULT 0;

    IF NOT EXISTS (
        SELECT 1 FROM instituciones
        WHERE idInstitucion=institucion_origen AND activo=1
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No existe o no está activa la institución de origen 1';
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM instituciones
        WHERE idInstitucion=institucion_destino AND activo=1
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No existe o no está activa la institución de destino 2';
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM cursos
        WHERE idCurso=curso_id
          AND id_institucion=institucion_origen
          AND id_ciclo_lectivo=ciclo_origen
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El curso 2 no coincide con la institución de origen o el ciclo esperados';
    END IF;

    SELECT COUNT(*) INTO filas
    FROM secciones
    WHERE id_curso=curso_id;
    IF filas<>2
       OR NOT EXISTS (SELECT 1 FROM secciones WHERE idSeccion=2 AND id_curso=curso_id)
       OR NOT EXISTS (SELECT 1 FROM secciones WHERE idSeccion=3 AND id_curso=curso_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Las materias del curso 2 no coinciden con Programación 2 y Sistemas de Información 3';
    END IF;

    IF EXISTS (SELECT 1 FROM evaluaciones WHERE id_curso=curso_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El curso ahora tiene evaluaciones; revisar períodos e instrumentos antes del traslado';
    END IF;

    IF (SELECT COUNT(*) FROM actividades WHERE id_curso=curso_id OR id_seccion IN (2,3))<>1
       OR EXISTS (
           SELECT 1 FROM actividades
           WHERE (id_curso=curso_id OR id_seccion IN (2,3))
             AND id_institucion<>institucion_origen
       ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Las actividades cambiaron desde el diagnóstico; revisar antes de trasladar';
    END IF;

    IF EXISTS (
        SELECT relacionados.id_usuario
        FROM (
            SELECT responsable AS id_usuario FROM cursos WHERE idCurso=curso_id
            UNION SELECT creadoPor FROM cursos WHERE idCurso=curso_id
            UNION SELECT docente FROM secciones WHERE id_curso=curso_id
            UNION SELECT tutor FROM secciones WHERE id_curso=curso_id
            UNION SELECT creadoPor FROM secciones WHERE id_curso=curso_id
            UNION SELECT id_estudiante FROM asignacioncursos WHERE id_seccion=curso_id
            UNION SELECT id_autor FROM actividades WHERE id_curso=curso_id OR id_seccion IN (2,3)
            UNION SELECT id_autor FROM posteos WHERE id_curso=curso_id
            UNION SELECT r.creadoPor FROM recursoslecciones r
                INNER JOIN lecciones l ON l.idLeccion=r.id_leccion
                INNER JOIN secciones s ON s.idSeccion=l.id_modulo
                WHERE s.id_curso=curso_id
            UNION SELECT id_estudiante FROM entregaslecciones WHERE id_curso=curso_id
            UNION SELECT id_estudiante FROM calificaciones WHERE id_curso=curso_id
            UNION SELECT creadaPor FROM asistencia_clases WHERE id_curso=curso_id
            UNION SELECT ar.id_estudiante FROM asistencia_registros ar
                INNER JOIN asistencia_clases ac ON ac.idClase=ar.id_clase
                WHERE ac.id_curso=curso_id
            UNION SELECT ar.actualizadoPor FROM asistencia_registros ar
                INNER JOIN asistencia_clases ac ON ac.idClase=ar.id_clase
                WHERE ac.id_curso=curso_id
        ) relacionados
        WHERE COALESCE(relacionados.id_usuario,0)>0
          AND NOT EXISTS (
              SELECT 1 FROM usuarios_instituciones ui
              WHERE ui.id_usuario=relacionados.id_usuario
                AND ui.id_institucion=institucion_destino
                AND ui.activo=1
          )
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Hay personas relacionadas con el curso sin membresía activa en Escuela 4-029';
    END IF;

    -- Crea el ciclo 2026 de destino solo si todavía no existe. Si ya existe,
    -- conserva su configuración institucional.
    INSERT INTO ciclos_lectivos
        (nombre,anio,fechaInicio,fechaFin,activo,id_institucion)
    SELECT nombre,anio,fechaInicio,fechaFin,activo,institucion_destino
    FROM ciclos_lectivos origen
    WHERE origen.idCicloLectivo=ciclo_origen
      AND origen.id_institucion=institucion_origen
      AND NOT EXISTS (
          SELECT 1 FROM ciclos_lectivos destino
          WHERE destino.id_institucion=institucion_destino
            AND destino.anio=origen.anio
      );

    SELECT destino.idCicloLectivo INTO ciclo_destino
    FROM ciclos_lectivos origen
    INNER JOIN ciclos_lectivos destino
        ON destino.id_institucion=institucion_destino
       AND destino.anio=origen.anio
    WHERE origen.idCicloLectivo=ciclo_origen
      AND origen.id_institucion=institucion_origen
    ORDER BY destino.idCicloLectivo
    LIMIT 1;

    IF ciclo_destino IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No se pudo obtener el ciclo lectivo 2026 de Escuela 4-029';
    END IF;

    START TRANSACTION;

    -- Las actividades sí guardan institución de forma directa.
    UPDATE actividades
    SET id_institucion=institucion_destino
    WHERE id_institucion=institucion_origen
      AND (id_curso=curso_id OR id_seccion IN (2,3));

    IF ROW_COUNT()<>1 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No se trasladó exactamente una actividad; operación detenida';
    END IF;

    -- Conserva en el nuevo contexto las notificaciones publicadas para las
    -- lecciones o la actividad del curso.
    UPDATE notificaciones n
    SET n.id_institucion=institucion_destino
    WHERE n.id_institucion=institucion_origen
      AND (
          (UPPER(n.referenciaTipo)='LECCION' AND EXISTS (
              SELECT 1 FROM lecciones l
              INNER JOIN secciones s ON s.idSeccion=l.id_modulo
              WHERE l.idLeccion=n.referenciaId AND s.id_curso=curso_id
          ))
          OR
          (UPPER(n.referenciaTipo)='ACTIVIDAD' AND EXISTS (
              SELECT 1 FROM actividades a
              WHERE a.idActividad=n.referenciaId
                AND (a.id_curso=curso_id OR a.id_seccion IN (2,3))
          ))
      );

    -- Las marcas de lectura de entregas, posteos y calificaciones también
    -- siguen al curso. Las claves tienen el formato tipo:id.
    UPDATE notificaciones_lecturas nl
    SET nl.id_institucion=institucion_destino
    WHERE nl.id_institucion=institucion_origen
      AND (
          (nl.claveNotificacion LIKE 'entrega:%' AND EXISTS (
              SELECT 1 FROM entregaslecciones e
              WHERE e.idEntregaLeccion=CAST(SUBSTRING_INDEX(nl.claveNotificacion,':',-1) AS UNSIGNED)
                AND e.id_curso=curso_id
          ))
          OR
          (nl.claveNotificacion LIKE 'posteo:%' AND EXISTS (
              SELECT 1 FROM posteos p
              WHERE p.idPosteo=CAST(SUBSTRING_INDEX(nl.claveNotificacion,':',-1) AS UNSIGNED)
                AND p.id_curso=curso_id
          ))
          OR
          (nl.claveNotificacion LIKE 'calificacion:%' AND EXISTS (
              SELECT 1 FROM calificaciones c
              WHERE c.idCalificacion=CAST(SUBSTRING_INDEX(nl.claveNotificacion,':',-1) AS UNSIGNED)
                AND c.id_curso=curso_id
          ))
          OR
          (nl.claveNotificacion LIKE 'notificacion:%' AND EXISTS (
              SELECT 1 FROM notificaciones n
              WHERE n.idNotificacion=CAST(SUBSTRING_INDEX(nl.claveNotificacion,':',-1) AS UNSIGNED)
                AND n.id_institucion=institucion_destino
          ))
      );

    -- Este cambio mueve, por relación, materias, lecciones, recursos,
    -- inscripciones, entregas, notas y asistencias sin modificar sus IDs.
    UPDATE cursos
    SET id_institucion=institucion_destino,
        id_ciclo_lectivo=ciclo_destino
    WHERE idCurso=curso_id
      AND id_institucion=institucion_origen
      AND id_ciclo_lectivo=ciclo_origen;

    IF ROW_COUNT()<>1 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='No se trasladó exactamente el curso 2; operación detenida';
    END IF;

    COMMIT;
END$$

CALL trasladar_curso_2_escuela_4_029()$$
DROP PROCEDURE trasladar_curso_2_escuela_4_029$$

DELIMITER ;

-- Comprobación final. Debe mostrar institución 2, ciclo 2026 de institución 2,
-- dos materias, sus lecciones y una actividad de institución 2.
SELECT c.idCurso,c.nombreCurso,c.id_institucion,c.id_ciclo_lectivo,
       cl.nombre ciclo,cl.anio,cl.id_institucion institucion_ciclo,
       COUNT(DISTINCT s.idSeccion) materias,
       COUNT(DISTINCT l.idLeccion) lecciones
FROM cursos c
LEFT JOIN ciclos_lectivos cl ON cl.idCicloLectivo=c.id_ciclo_lectivo
LEFT JOIN secciones s ON s.id_curso=c.idCurso
LEFT JOIN lecciones l ON l.id_modulo=s.idSeccion
WHERE c.idCurso=2
GROUP BY c.idCurso,c.nombreCurso,c.id_institucion,c.id_ciclo_lectivo,
         cl.nombre,cl.anio,cl.id_institucion;

SELECT idActividad,tituloActividad,id_curso,id_seccion,id_institucion
FROM actividades
WHERE id_curso=2 OR id_seccion IN (2,3);
