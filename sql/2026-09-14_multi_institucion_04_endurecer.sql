-- FASE 7: endurecimiento posterior al corte de datos legacy.
-- Ejecutar sólo después de desplegar el código institucional y validar el delta.
-- Si encuentra datos incompletos, se detiene sin alterar las columnas.

DELIMITER $$
DROP PROCEDURE IF EXISTS campus_mt_exigir_contexto$$
CREATE PROCEDURE campus_mt_exigir_contexto(IN tabla VARCHAR(64))
BEGIN
    DECLARE pendientes BIGINT DEFAULT 0;
    DECLARE existe INT DEFAULT 0;
    SELECT COUNT(*) INTO existe FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=tabla;
    IF existe=1 THEN
        SELECT COUNT(*) INTO existe FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=tabla AND column_name='id_institucion';
        IF existe=1 THEN
            SET @campus_mt_sql=CONCAT('SELECT COUNT(*) INTO @campus_mt_pendientes FROM `',tabla,'` WHERE id_institucion IS NULL');
            PREPARE campus_mt_stmt FROM @campus_mt_sql;
            EXECUTE campus_mt_stmt;
            DEALLOCATE PREPARE campus_mt_stmt;
            SET pendientes=COALESCE(@campus_mt_pendientes,0);
            IF pendientes>0 THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Existen filas institucionales sin tenant; revisar el delta antes de continuar';
            END IF;
            SET @campus_mt_sql=CONCAT('ALTER TABLE `',tabla,'` MODIFY id_institucion INT NOT NULL');
            PREPARE campus_mt_stmt FROM @campus_mt_sql;
            EXECUTE campus_mt_stmt;
            DEALLOCATE PREPARE campus_mt_stmt;
        END IF;
    END IF;
END$$
DROP PROCEDURE IF EXISTS campus_mt_endurecer_contexto$$
CREATE PROCEDURE campus_mt_endurecer_contexto()
BEGIN
    DECLARE completada INT DEFAULT 0;
    DECLARE tieneUnico INT DEFAULT 0;
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='campus_migraciones')
       OR NOT EXISTS (SELECT 1 FROM campus_migraciones WHERE codigo='multi_institucion_01_expandir') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ejecute primero la expansión multiinstitución 01';
    END IF;
    SELECT COUNT(*) INTO completada FROM campus_migraciones WHERE codigo='multi_institucion_04_endurecer';
    IF completada=0 THEN
        IF EXISTS (SELECT 1 FROM usuarios WHERE TRIM(email)='' OR email IS NULL)
           OR EXISTS (SELECT LOWER(TRIM(email)) FROM usuarios GROUP BY LOWER(TRIM(email)) HAVING COUNT(*)>1) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Resolver emails vacíos o duplicados antes de endurecer la identidad global';
        END IF;
        IF EXISTS (
            SELECT 1 FROM usuarios_instituciones ui LEFT JOIN usuarios u ON u.idUsuario=ui.id_usuario
            WHERE u.idUsuario IS NULL
        ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Existen membresías asociadas a identidades inexistentes';
        END IF;
        IF EXISTS (
            SELECT 1 FROM cursos c LEFT JOIN usuarios u ON u.idUsuario=c.responsable
            WHERE c.id_institucion IS NOT NULL AND COALESCE(c.responsable,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=c.responsable AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=s.docente
            WHERE c.id_institucion IS NOT NULL AND COALESCE(s.docente,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=s.docente AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=s.tutor
            WHERE c.id_institucion IS NOT NULL AND COALESCE(s.tutor,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=s.tutor AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM asignacioncursos a INNER JOIN cursos c ON c.idCurso=a.id_seccion LEFT JOIN usuarios u ON u.idUsuario=a.id_estudiante
            WHERE c.id_institucion IS NOT NULL AND COALESCE(a.id_estudiante,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=a.id_estudiante AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM mensajes m LEFT JOIN usuarios u ON u.idUsuario=m.id_remitente
            WHERE m.id_institucion IS NOT NULL AND COALESCE(m.id_remitente,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=m.id_remitente AND ui.id_institucion=m.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM mensajes m LEFT JOIN usuarios u ON u.idUsuario=m.id_destinatario
            WHERE m.id_institucion IS NOT NULL AND COALESCE(m.id_destinatario,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=m.id_destinatario AND ui.id_institucion=m.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM mensajes_participantes mp INNER JOIN mensajes m ON m.idMensaje=mp.id_mensaje LEFT JOIN usuarios u ON u.idUsuario=mp.id_usuario
            WHERE m.id_institucion IS NOT NULL AND COALESCE(mp.id_usuario,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=mp.id_usuario AND ui.id_institucion=m.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM notificaciones n LEFT JOIN usuarios u ON u.idUsuario=n.id_usuario
            WHERE n.id_institucion IS NOT NULL AND COALESCE(n.id_usuario,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=n.id_usuario AND ui.id_institucion=n.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM notificaciones_lecturas nl LEFT JOIN usuarios u ON u.idUsuario=nl.id_usuario
            WHERE nl.id_institucion IS NOT NULL AND COALESCE(nl.id_usuario,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=nl.id_usuario AND ui.id_institucion=nl.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM actividades a LEFT JOIN usuarios u ON u.idUsuario=a.id_autor
            WHERE a.id_institucion IS NOT NULL AND COALESCE(a.id_autor,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=a.id_autor AND ui.id_institucion=a.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM evaluaciones e INNER JOIN cursos c ON c.idCurso=e.id_curso LEFT JOIN usuarios u ON u.idUsuario=e.id_autor
            WHERE c.id_institucion IS NOT NULL AND COALESCE(e.id_autor,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=e.id_autor AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM entregaslecciones e INNER JOIN cursos c ON c.idCurso=e.id_curso LEFT JOIN usuarios u ON u.idUsuario=e.id_estudiante
            WHERE c.id_institucion IS NOT NULL AND COALESCE(e.id_estudiante,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=e.id_estudiante AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM calificaciones n INNER JOIN cursos c ON c.idCurso=n.id_curso LEFT JOIN usuarios u ON u.idUsuario=n.id_estudiante
            WHERE c.id_institucion IS NOT NULL AND COALESCE(n.id_estudiante,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=n.id_estudiante AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM evaluaciones_calificaciones ec INNER JOIN evaluaciones e ON e.idEvaluacion=ec.id_evaluacion
            INNER JOIN cursos c ON c.idCurso=e.id_curso LEFT JOIN usuarios u ON u.idUsuario=ec.id_estudiante
            WHERE c.id_institucion IS NOT NULL AND COALESCE(ec.id_estudiante,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=ec.id_estudiante AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM cierres_periodo_calificaciones cp INNER JOIN secciones s ON s.idSeccion=cp.id_seccion
            INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=cp.id_estudiante
            WHERE c.id_institucion IS NOT NULL AND COALESCE(cp.id_estudiante,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=cp.id_estudiante AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM cierres_periodo_calificaciones cp INNER JOIN secciones s ON s.idSeccion=cp.id_seccion
            INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=cp.actualizadoPor
            WHERE c.id_institucion IS NOT NULL AND COALESCE(cp.actualizadoPor,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=cp.actualizadoPor AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM periodos_seccion_estado pe INNER JOIN secciones s ON s.idSeccion=pe.id_seccion
            INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=pe.cerradoPor
            WHERE c.id_institucion IS NOT NULL AND COALESCE(pe.cerradoPor,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=pe.cerradoPor AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM periodos_seccion_estado pe INNER JOIN secciones s ON s.idSeccion=pe.id_seccion
            INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=pe.reabiertoPor
            WHERE c.id_institucion IS NOT NULL AND COALESCE(pe.reabiertoPor,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=pe.reabiertoPor AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM asistencia_clases ac INNER JOIN cursos c ON c.idCurso=ac.id_curso LEFT JOIN usuarios u ON u.idUsuario=ac.creadaPor
            WHERE c.id_institucion IS NOT NULL AND COALESCE(ac.creadaPor,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=ac.creadaPor AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM asistencia_registros ar INNER JOIN asistencia_clases ac ON ac.idClase=ar.id_clase
            INNER JOIN cursos c ON c.idCurso=ac.id_curso LEFT JOIN usuarios u ON u.idUsuario=ar.id_estudiante
            WHERE c.id_institucion IS NOT NULL AND COALESCE(ar.id_estudiante,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=ar.id_estudiante AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM asistencia_registros ar INNER JOIN asistencia_clases ac ON ac.idClase=ar.id_clase
            INNER JOIN cursos c ON c.idCurso=ac.id_curso LEFT JOIN usuarios u ON u.idUsuario=ar.actualizadoPor
            WHERE c.id_institucion IS NOT NULL AND COALESCE(ar.actualizadoPor,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=ar.actualizadoPor AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM posteos p INNER JOIN cursos c ON c.idCurso=p.id_curso LEFT JOIN usuarios u ON u.idUsuario=p.id_autor
            WHERE c.id_institucion IS NOT NULL AND COALESCE(p.id_autor,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=p.id_autor AND ui.id_institucion=c.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM recursoslecciones r INNER JOIN lecciones l ON l.idLeccion=r.id_leccion
            INNER JOIN secciones s ON s.idSeccion=l.id_modulo INNER JOIN cursos c ON c.idCurso=s.id_curso
            LEFT JOIN usuarios u ON u.idUsuario=r.creadoPor
            WHERE c.id_institucion IS NOT NULL AND COALESCE(r.creadoPor,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=r.creadoPor AND ui.id_institucion=c.id_institucion)
        ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Existen relaciones con usuarios sin membresía en el tenant del recurso';
        END IF;
        CALL campus_mt_exigir_contexto('cursos');
        CALL campus_mt_exigir_contexto('mensajes');
        CALL campus_mt_exigir_contexto('actividades');
        CALL campus_mt_exigir_contexto('ciclos_lectivos');
        CALL campus_mt_exigir_contexto('instrumentos_evaluacion');
        CALL campus_mt_exigir_contexto('notificaciones');
        CALL campus_mt_exigir_contexto('notificaciones_lecturas');
        CALL campus_mt_exigir_contexto('usuarios_historial');
        SELECT COUNT(*) INTO tieneUnico FROM information_schema.statistics
            WHERE table_schema=DATABASE() AND table_name='usuarios' AND column_name='email' AND non_unique=0;
        IF tieneUnico=0 THEN
            ALTER TABLE usuarios ADD UNIQUE KEY uq_usuarios_email_global (email);
        END IF;
        INSERT INTO campus_migraciones(codigo) VALUES ('multi_institucion_04_endurecer');
    END IF;
END$$
CALL campus_mt_endurecer_contexto()$$
DROP PROCEDURE campus_mt_endurecer_contexto$$
DROP PROCEDURE campus_mt_exigir_contexto$$
DELIMITER ;
