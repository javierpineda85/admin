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
