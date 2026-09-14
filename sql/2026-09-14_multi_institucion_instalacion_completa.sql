-- CAMPUS.MM - INSTALADOR MULTIINSTITUCION AUTOCONTENIDO
-- Compatible con MySQL 8.x y con una instalación existente.
-- No elimina datos. Seleccione primero la base destino y conserve un backup verificado.
--
-- PRIMERA EJECUCION: deje el valor en 0. Crea y amplía el esquema, migra los
-- datos existentes a MenteMotion y muestra los diagnósticos.
--
-- CORTE DEFINITIVO: sólo cuando el diagnóstico final devuelva cero incidencias,
-- cambie el valor a 1 y vuelva a importar este mismo archivo. Entonces se aplican
-- NOT NULL institucionales y la unicidad estructural del email.
SET @CAMPUS_MT_APLICAR_ENDURECIMIENTO = 0;

-- ============================================================
-- DIAGNOSTICO PREVIO DE SOLO LECTURA
-- ============================================================
-- SOLO LECTURA. Para una instalación con las migraciones académicas actuales.
-- Devuelve conteos, no contraseñas, correos ni datos personales.
-- Un valor > 0 requiere revisar las filas antes de imponer relaciones estrictas.
SELECT 'emails_duplicados_normalizados' AS comprobacion, COUNT(*) AS incidencias
FROM (SELECT LOWER(TRIM(email)) FROM usuarios GROUP BY LOWER(TRIM(email)) HAVING COUNT(*)>1) d
UNION ALL SELECT 'emails_vacios',COUNT(*) FROM usuarios WHERE TRIM(email)=''
UNION ALL SELECT 'roles_sin_equivalencia',COUNT(*) FROM usuarios WHERE UPPER(TRIM(rol)) NOT IN ('ADMINISTRADOR','DOCENTE','ESTUDIANTE')
UNION ALL SELECT 'perfiles_sin_usuario',COUNT(*) FROM perfiles p LEFT JOIN usuarios u ON u.idUsuario=p.id_usuario WHERE u.idUsuario IS NULL
UNION ALL SELECT 'cursos_creador_sin_usuario',COUNT(*) FROM cursos c LEFT JOIN usuarios u ON u.idUsuario=c.creadoPor WHERE COALESCE(c.creadoPor,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'cursos_responsable_sin_usuario',COUNT(*) FROM cursos c LEFT JOIN usuarios u ON u.idUsuario=c.responsable WHERE COALESCE(c.responsable,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'secciones_sin_curso',COUNT(*) FROM secciones s LEFT JOIN cursos c ON c.idCurso=s.id_curso WHERE c.idCurso IS NULL
UNION ALL SELECT 'secciones_docente_sin_usuario',COUNT(*) FROM secciones s LEFT JOIN usuarios u ON u.idUsuario=s.docente WHERE COALESCE(s.docente,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'secciones_tutor_sin_usuario',COUNT(*) FROM secciones s LEFT JOIN usuarios u ON u.idUsuario=s.tutor WHERE COALESCE(s.tutor,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'secciones_creador_sin_usuario',COUNT(*) FROM secciones s LEFT JOIN usuarios u ON u.idUsuario=s.creadoPor WHERE COALESCE(s.creadoPor,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'lecciones_sin_seccion',COUNT(*) FROM lecciones l LEFT JOIN secciones s ON s.idSeccion=l.id_modulo WHERE s.idSeccion IS NULL
UNION ALL SELECT 'inscripciones_sin_curso',COUNT(*) FROM asignacioncursos a LEFT JOIN cursos c ON c.idCurso=a.id_seccion WHERE c.idCurso IS NULL
UNION ALL SELECT 'inscripciones_sin_usuario',COUNT(*) FROM asignacioncursos a LEFT JOIN usuarios u ON u.idUsuario=a.id_estudiante WHERE u.idUsuario IS NULL
UNION ALL SELECT 'entregas_sin_usuario',COUNT(*) FROM entregaslecciones e LEFT JOIN usuarios u ON u.idUsuario=e.id_estudiante WHERE u.idUsuario IS NULL
UNION ALL SELECT 'entregas_contexto_inconsistente',COUNT(*) FROM entregaslecciones e LEFT JOIN lecciones l ON l.idLeccion=e.id_leccion LEFT JOIN secciones s ON s.idSeccion=l.id_modulo WHERE l.idLeccion IS NULL OR s.idSeccion IS NULL OR e.id_seccion<>s.idSeccion OR e.id_curso<>s.id_curso
UNION ALL SELECT 'calificaciones_sin_usuario',COUNT(*) FROM calificaciones n LEFT JOIN usuarios u ON u.idUsuario=n.id_estudiante WHERE u.idUsuario IS NULL
UNION ALL SELECT 'calificaciones_contexto_inconsistente',COUNT(*) FROM calificaciones n LEFT JOIN lecciones l ON l.idLeccion=n.id_modulo LEFT JOIN secciones s ON s.idSeccion=l.id_modulo WHERE l.idLeccion IS NULL OR s.idSeccion IS NULL OR n.id_seccion<>s.idSeccion OR n.id_curso<>s.id_curso
UNION ALL SELECT 'evaluaciones_autor_sin_usuario',COUNT(*) FROM evaluaciones e LEFT JOIN usuarios u ON u.idUsuario=e.id_autor WHERE u.idUsuario IS NULL
UNION ALL SELECT 'evaluaciones_contexto_inconsistente',COUNT(*) FROM evaluaciones e LEFT JOIN secciones s ON s.idSeccion=e.id_seccion WHERE s.idSeccion IS NULL OR e.id_curso<>s.id_curso
UNION ALL SELECT 'evaluaciones_calificaciones_sin_usuario',COUNT(*) FROM evaluaciones_calificaciones ec LEFT JOIN usuarios u ON u.idUsuario=ec.id_estudiante WHERE u.idUsuario IS NULL
UNION ALL SELECT 'cierres_estudiante_sin_usuario',COUNT(*) FROM cierres_periodo_calificaciones cp LEFT JOIN usuarios u ON u.idUsuario=cp.id_estudiante WHERE u.idUsuario IS NULL
UNION ALL SELECT 'cierres_actualizador_sin_usuario',COUNT(*) FROM cierres_periodo_calificaciones cp LEFT JOIN usuarios u ON u.idUsuario=cp.actualizadoPor WHERE COALESCE(cp.actualizadoPor,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'periodos_seccion_cerrador_sin_usuario',COUNT(*) FROM periodos_seccion_estado pe LEFT JOIN usuarios u ON u.idUsuario=pe.cerradoPor WHERE COALESCE(pe.cerradoPor,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'periodos_seccion_reabridor_sin_usuario',COUNT(*) FROM periodos_seccion_estado pe LEFT JOIN usuarios u ON u.idUsuario=pe.reabiertoPor WHERE COALESCE(pe.reabiertoPor,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'asistencia_contexto_inconsistente',COUNT(*) FROM asistencia_clases a LEFT JOIN secciones s ON s.idSeccion=a.id_seccion WHERE s.idSeccion IS NULL OR a.id_curso<>s.id_curso
UNION ALL SELECT 'asistencia_creador_sin_usuario',COUNT(*) FROM asistencia_clases a LEFT JOIN usuarios u ON u.idUsuario=a.creadaPor WHERE u.idUsuario IS NULL
UNION ALL SELECT 'asistencia_estudiante_sin_usuario',COUNT(*) FROM asistencia_registros ar LEFT JOIN usuarios u ON u.idUsuario=ar.id_estudiante WHERE u.idUsuario IS NULL
UNION ALL SELECT 'asistencia_actualizador_sin_usuario',COUNT(*) FROM asistencia_registros ar LEFT JOIN usuarios u ON u.idUsuario=ar.actualizadoPor WHERE COALESCE(ar.actualizadoPor,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'actividades_contexto_inconsistente',COUNT(*) FROM actividades a LEFT JOIN secciones s ON s.idSeccion=a.id_seccion LEFT JOIN cursos c ON c.idCurso=a.id_curso WHERE (a.id_seccion IS NOT NULL AND (s.idSeccion IS NULL OR a.id_curso IS NULL OR a.id_curso<>s.id_curso)) OR (a.id_curso IS NOT NULL AND c.idCurso IS NULL)
UNION ALL SELECT 'actividades_autor_sin_usuario',COUNT(*) FROM actividades a LEFT JOIN usuarios u ON u.idUsuario=a.id_autor WHERE u.idUsuario IS NULL
UNION ALL SELECT 'actividades_intentos_sin_usuario',COUNT(*) FROM actividades_intentos ai LEFT JOIN usuarios u ON u.idUsuario=ai.id_usuario WHERE COALESCE(ai.id_usuario,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'posteos_autor_sin_usuario',COUNT(*) FROM posteos p LEFT JOIN usuarios u ON u.idUsuario=p.id_autor WHERE u.idUsuario IS NULL
UNION ALL SELECT 'recursos_creador_sin_usuario',COUNT(*) FROM recursoslecciones r LEFT JOIN usuarios u ON u.idUsuario=r.creadoPor WHERE u.idUsuario IS NULL
UNION ALL SELECT 'mensajes_sin_remitente',COUNT(*) FROM mensajes m LEFT JOIN usuarios u ON u.idUsuario=m.id_remitente WHERE u.idUsuario IS NULL
UNION ALL SELECT 'mensajes_sin_destinatario',COUNT(*) FROM mensajes m LEFT JOIN usuarios u ON u.idUsuario=m.id_destinatario WHERE u.idUsuario IS NULL
UNION ALL SELECT 'participantes_sin_mensaje',COUNT(*) FROM mensajes_participantes p LEFT JOIN mensajes m ON m.idMensaje=p.id_mensaje WHERE m.idMensaje IS NULL
UNION ALL SELECT 'participantes_sin_usuario',COUNT(*) FROM mensajes_participantes p LEFT JOIN usuarios u ON u.idUsuario=p.id_usuario WHERE u.idUsuario IS NULL
UNION ALL SELECT 'notificaciones_sin_usuario',COUNT(*) FROM notificaciones n LEFT JOIN usuarios u ON u.idUsuario=n.id_usuario WHERE u.idUsuario IS NULL
UNION ALL SELECT 'lecturas_notificacion_sin_usuario',COUNT(*) FROM notificaciones_lecturas nl LEFT JOIN usuarios u ON u.idUsuario=nl.id_usuario WHERE u.idUsuario IS NULL
UNION ALL SELECT 'historial_objetivo_sin_usuario',COUNT(*) FROM usuarios_historial h LEFT JOIN usuarios u ON u.idUsuario=h.id_usuario WHERE u.idUsuario IS NULL
UNION ALL SELECT 'historial_actor_sin_usuario',COUNT(*) FROM usuarios_historial h LEFT JOIN usuarios u ON u.idUsuario=h.id_usuario_accion WHERE u.idUsuario IS NULL
UNION ALL SELECT 'adjuntos_sin_mensaje',COUNT(*) FROM mensajes_adjuntos a LEFT JOIN mensajes m ON m.idMensaje=a.id_mensaje WHERE m.idMensaje IS NULL;

-- ============================================================
-- EXPANSION MULTIINSTITUCION
-- ============================================================
-- FASE 1: expansión compatible con el Campus anterior. MySQL 8.x.
-- Ejecutar con la base destino seleccionada, en mantenimiento y con backup.
-- No activa multi-tenancy. Las columnas institucionales permanecen NULLABLE
-- hasta desplegar los modelos que escriben el contexto y finalizar la migración.
-- DDL no transaccional: las comprobaciones permiten reanudar tras un fallo.

DELIMITER $$
DROP PROCEDURE IF EXISTS campus_mt_expandir$$
CREATE PROCEDURE campus_mt_expandir()
BEGIN
    DECLARE institucionInicial INT;
    DECLARE completada INT DEFAULT 0;

    IF DATABASE() IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Seleccione la base de Campus antes de migrar';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='usuarios')
       OR NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='cursos') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Faltan las tablas base usuarios o cursos';
    END IF;
    IF EXISTS (SELECT 1 FROM usuarios GROUP BY LOWER(TRIM(email)) HAVING COUNT(*)>1)
       OR EXISTS (SELECT 1 FROM usuarios WHERE TRIM(email)='') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Resolver emails duplicados o vacios sin borrar cuentas antes de migrar';
    END IF;

    CREATE TABLE IF NOT EXISTS campus_migraciones (
        codigo VARCHAR(100) NOT NULL PRIMARY KEY,
        fechaAplicacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    SELECT COUNT(*) INTO completada FROM campus_migraciones WHERE codigo='multi_institucion_01_expandir';

    CREATE TABLE IF NOT EXISTS instituciones (
        idInstitucion INT NOT NULL AUTO_INCREMENT,
        nombre VARCHAR(160) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        logo VARCHAR(255) NULL,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        fechaAlta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        fechaBaja DATETIME NULL,
        motivoBaja VARCHAR(255) NULL,
        configuracion JSON NULL,
        PRIMARY KEY (idInstitucion),
        UNIQUE KEY uq_instituciones_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS roles (
        idRol INT NOT NULL AUTO_INCREMENT,
        codigo VARCHAR(30) NOT NULL,
        nombre VARCHAR(80) NOT NULL,
        PRIMARY KEY (idRol), UNIQUE KEY uq_roles_codigo (codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS usuarios_instituciones (
        idUsuarioInstitucion INT NOT NULL AUTO_INCREMENT,
        id_usuario INT NOT NULL,
        id_institucion INT NOT NULL,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        fechaAlta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        fechaBaja DATETIME NULL,
        motivoBaja VARCHAR(255) NULL,
        PRIMARY KEY (idUsuarioInstitucion),
        UNIQUE KEY uq_usuario_institucion (id_usuario,id_institucion),
        KEY idx_membresia_institucion_estado (id_institucion,activo,id_usuario),
        CONSTRAINT fk_membresia_institucion FOREIGN KEY (id_institucion)
            REFERENCES instituciones(idInstitucion) ON DELETE RESTRICT
        -- FK a usuarios pendiente hasta convertir/validar el motor legacy MyISAM.
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS usuarios_instituciones_roles (
        id_usuario_institucion INT NOT NULL,
        id_rol INT NOT NULL,
        PRIMARY KEY (id_usuario_institucion,id_rol),
        KEY idx_membresia_rol (id_rol),
        CONSTRAINT fk_rol_membresia FOREIGN KEY (id_usuario_institucion)
            REFERENCES usuarios_instituciones(idUsuarioInstitucion) ON DELETE RESTRICT,
        CONSTRAINT fk_membresia_rol FOREIGN KEY (id_rol)
            REFERENCES roles(idRol) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='usuarios' AND column_name='esSuperAdmin') THEN
        ALTER TABLE usuarios ADD COLUMN esSuperAdmin TINYINT(1) NOT NULL DEFAULT 0;
    END IF;

    IF completada=0 THEN
        INSERT INTO instituciones(nombre,slug)
        SELECT 'MenteMotion','mentemotion'
        WHERE NOT EXISTS (SELECT 1 FROM instituciones WHERE slug='mentemotion');
        SELECT idInstitucion INTO institucionInicial FROM instituciones WHERE slug='mentemotion';

        INSERT INTO roles(codigo,nombre)
        SELECT 'ADMINISTRADOR','Administrador' WHERE NOT EXISTS (SELECT 1 FROM roles WHERE codigo='ADMINISTRADOR');
        INSERT INTO roles(codigo,nombre)
        SELECT 'DOCENTE','Docente' WHERE NOT EXISTS (SELECT 1 FROM roles WHERE codigo='DOCENTE');
        INSERT INTO roles(codigo,nombre)
        SELECT 'ESTUDIANTE','Estudiante' WHERE NOT EXISTS (SELECT 1 FROM roles WHERE codigo='ESTUDIANTE');

        -- No se promueve GESTOR ni se interpreta la columna secciones.tutor como rol TUTOR.
        -- No se conceden privilegios globales a administradores existentes.
        INSERT INTO usuarios_instituciones(id_usuario,id_institucion,activo,fechaAlta,fechaBaja,motivoBaja)
        SELECT u.idUsuario,institucionInicial,u.activo,u.fechaAlta,u.fechaBaja,u.motivoBaja
        FROM usuarios u
        WHERE NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=u.idUsuario AND ui.id_institucion=institucionInicial);

        INSERT INTO usuarios_instituciones_roles(id_usuario_institucion,id_rol)
        SELECT ui.idUsuarioInstitucion,r.idRol
        FROM usuarios u
        INNER JOIN usuarios_instituciones ui ON ui.id_usuario=u.idUsuario AND ui.id_institucion=institucionInicial
        INNER JOIN roles r ON BINARY r.codigo=BINARY UPPER(TRIM(u.rol)) AND r.codigo IN ('ADMINISTRADOR','DOCENTE','ESTUDIANTE')
        WHERE NOT EXISTS (SELECT 1 FROM usuarios_instituciones_roles ur WHERE ur.id_usuario_institucion=ui.idUsuarioInstitucion AND ur.id_rol=r.idRol);
    END IF;
END$$
CALL campus_mt_expandir()$$
DROP PROCEDURE campus_mt_expandir$$

DROP PROCEDURE IF EXISTS campus_mt_columna$$
CREATE PROCEDURE campus_mt_columna(IN tabla VARCHAR(64))
BEGIN
    -- Lista cerrada; no recibe identificadores desde peticiones web.
    IF tabla NOT IN ('cursos','mensajes','actividades','ciclos_lectivos','instrumentos_evaluacion','notificaciones','notificaciones_lecturas','usuarios_historial') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tabla no autorizada para expansion institucional';
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=tabla) THEN
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=tabla AND column_name='id_institucion') THEN
            SET @campus_mt_sql=CONCAT('ALTER TABLE `',tabla,'` ADD COLUMN id_institucion INT NULL');
            PREPARE campus_mt_stmt FROM @campus_mt_sql;
            EXECUTE campus_mt_stmt;
            DEALLOCATE PREPARE campus_mt_stmt;
        END IF;
        IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=tabla AND index_name='idx_institucion') THEN
            SET @campus_mt_sql=CONCAT('ALTER TABLE `',tabla,'` ADD INDEX idx_institucion (id_institucion)');
            PREPARE campus_mt_stmt FROM @campus_mt_sql;
            EXECUTE campus_mt_stmt;
            DEALLOCATE PREPARE campus_mt_stmt;
        END IF;
        IF NOT EXISTS (SELECT 1 FROM campus_migraciones WHERE codigo='multi_institucion_01_expandir') THEN
            SET @campus_mt_sql=CONCAT('UPDATE `',tabla,'` SET id_institucion=(SELECT idInstitucion FROM instituciones WHERE slug=''mentemotion'') WHERE id_institucion IS NULL');
            PREPARE campus_mt_stmt FROM @campus_mt_sql;
            EXECUTE campus_mt_stmt;
            DEALLOCATE PREPARE campus_mt_stmt;
        END IF;
    END IF;
END$$
CALL campus_mt_columna('cursos')$$
CALL campus_mt_columna('mensajes')$$
CALL campus_mt_columna('actividades')$$
CALL campus_mt_columna('ciclos_lectivos')$$
CALL campus_mt_columna('instrumentos_evaluacion')$$
CALL campus_mt_columna('notificaciones')$$
CALL campus_mt_columna('notificaciones_lecturas')$$
CALL campus_mt_columna('usuarios_historial')$$
DROP PROCEDURE campus_mt_columna$$
DELIMITER ;

INSERT INTO campus_migraciones(codigo)
SELECT 'multi_institucion_01_expandir'
WHERE NOT EXISTS (SELECT 1 FROM campus_migraciones WHERE codigo='multi_institucion_01_expandir');

-- Revisión obligatoria: estos usuarios conservan identidad y membresía,
-- pero no reciben un rol académico desconocido ni privilegios adicionales.
SELECT idUsuario,rol AS rol_legacy_sin_equivalencia
FROM usuarios WHERE UPPER(TRIM(rol)) NOT IN ('ADMINISTRADOR','DOCENTE','ESTUDIANTE');

-- ============================================================
-- ASISTENCIA
-- ============================================================
-- Fase 4: prepara asistencia para aplicar el contexto por curso y sección.
-- Es idempotente y no altera ni elimina registros existentes.
CREATE TABLE IF NOT EXISTS asistencia_clases (
  idClase INT NOT NULL AUTO_INCREMENT,
  id_seccion INT NOT NULL,
  id_curso INT NOT NULL,
  fechaClase DATE NOT NULL,
  tema VARCHAR(180) NULL,
  creadaPor INT NOT NULL,
  fechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(idClase),
  UNIQUE KEY uq_asistencia_fecha(id_seccion,fechaClase),
  KEY idx_asistencia_curso(id_curso)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asistencia_registros (
  idAsistencia INT NOT NULL AUTO_INCREMENT,
  id_clase INT NOT NULL,
  id_estudiante INT NOT NULL,
  estado VARCHAR(15) NOT NULL DEFAULT 'PRESENTE',
  observacion VARCHAR(255) NULL,
  actualizadoPor INT NULL,
  fechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(idAsistencia),
  UNIQUE KEY uq_asistencia_estudiante(id_clase,id_estudiante),
  KEY idx_asistencia_alumno(id_estudiante)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO campus_migraciones(codigo)
SELECT 'multi_institucion_02_asistencias'
WHERE NOT EXISTS (
  SELECT 1 FROM campus_migraciones WHERE codigo='multi_institucion_02_asistencias'
);

-- ============================================================
-- CATALOGOS DE CALIFICACION
-- ============================================================
-- Fase 4: ciclos e instrumentos pasan a ser catálogos por institución.
-- Permite repetir año y nombre en tenants distintos sin tocar los datos existentes.
DELIMITER $$
DROP PROCEDURE IF EXISTS campus_mt_catalogos_calificacion$$
CREATE PROCEDURE campus_mt_catalogos_calificacion()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM campus_migraciones WHERE codigo='multi_institucion_03_catalogos_calificacion') THEN
    IF EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='ciclos_lectivos' AND index_name='uq_ciclo_anio') THEN
      ALTER TABLE ciclos_lectivos DROP INDEX uq_ciclo_anio;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='ciclos_lectivos' AND index_name='uq_ciclo_institucion_anio') THEN
      ALTER TABLE ciclos_lectivos ADD UNIQUE KEY uq_ciclo_institucion_anio(id_institucion,anio);
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='instrumentos_evaluacion' AND index_name='uq_instrumento_nombre') THEN
      ALTER TABLE instrumentos_evaluacion DROP INDEX uq_instrumento_nombre;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='instrumentos_evaluacion' AND index_name='uq_instrumento_institucion_nombre') THEN
      ALTER TABLE instrumentos_evaluacion ADD UNIQUE KEY uq_instrumento_institucion_nombre(id_institucion,nombre);
    END IF;
  END IF;
END$$
CALL campus_mt_catalogos_calificacion()$$
DROP PROCEDURE campus_mt_catalogos_calificacion$$
DELIMITER ;

INSERT INTO campus_migraciones(codigo)
SELECT 'multi_institucion_03_catalogos_calificacion'
WHERE NOT EXISTS (SELECT 1 FROM campus_migraciones WHERE codigo='multi_institucion_03_catalogos_calificacion');

-- ============================================================
-- ENDURECIMIENTO OPCIONAL
-- ============================================================
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
        ) OR EXISTS (
            SELECT 1 FROM usuarios_historial h LEFT JOIN usuarios u ON u.idUsuario=h.id_usuario
            WHERE h.id_institucion IS NOT NULL AND COALESCE(h.id_usuario,0)>0
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=h.id_usuario AND ui.id_institucion=h.id_institucion)
        ) OR EXISTS (
            SELECT 1 FROM usuarios_historial h LEFT JOIN usuarios u ON u.idUsuario=h.id_usuario_accion
            WHERE h.id_institucion IS NOT NULL AND COALESCE(h.id_usuario_accion,0)>0
              AND COALESCE(u.esSuperAdmin,0)<>1
              AND NOT EXISTS (SELECT 1 FROM usuarios_instituciones ui WHERE ui.id_usuario=h.id_usuario_accion AND ui.id_institucion=h.id_institucion)
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
DROP PROCEDURE IF EXISTS campus_mt_endurecer_opcional$$
CREATE PROCEDURE campus_mt_endurecer_opcional()
BEGIN
    IF COALESCE(@CAMPUS_MT_APLICAR_ENDURECIMIENTO,0)=1 THEN
        CALL campus_mt_endurecer_contexto();
    ELSE
        SELECT 'Endurecimiento omitido: corrija el diagnóstico, cambie @CAMPUS_MT_APLICAR_ENDURECIMIENTO a 1 y reimporte este archivo.' AS estado_instalacion;
    END IF;
END$$
CALL campus_mt_endurecer_opcional()$$
DROP PROCEDURE campus_mt_endurecer_opcional$$
DROP PROCEDURE campus_mt_endurecer_contexto$$
DROP PROCEDURE campus_mt_exigir_contexto$$
DELIMITER ;

-- ============================================================
-- DIAGNOSTICO FINAL DE MEMBRESIAS Y RELACIONES
-- Todos los conteos deben quedar en cero antes del corte.
-- ============================================================
-- SOLO LECTURA. Ejecutar después de las migraciones 01, 02 y 03,
-- antes de 04_endurecer. Un valor mayor que cero requiere regularización.
-- No devuelve nombres, emails ni contenido académico.
SELECT 'membresias_sin_usuario' AS comprobacion, COUNT(*) AS incidencias
FROM usuarios_instituciones ui LEFT JOIN usuarios u ON u.idUsuario=ui.id_usuario
WHERE u.idUsuario IS NULL
UNION ALL SELECT 'cursos_responsable_sin_membresia', COUNT(*)
FROM cursos c LEFT JOIN usuarios u ON u.idUsuario=c.responsable
WHERE c.id_institucion IS NOT NULL AND COALESCE(c.responsable, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=c.responsable AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'secciones_docente_sin_membresia', COUNT(*)
FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=s.docente
WHERE c.id_institucion IS NOT NULL AND COALESCE(s.docente, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=s.docente AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'secciones_tutor_sin_membresia', COUNT(*)
FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=s.tutor
WHERE c.id_institucion IS NOT NULL AND COALESCE(s.tutor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=s.tutor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'inscripciones_estudiante_sin_membresia', COUNT(*)
FROM asignacioncursos a INNER JOIN cursos c ON c.idCurso=a.id_seccion LEFT JOIN usuarios u ON u.idUsuario=a.id_estudiante
WHERE c.id_institucion IS NOT NULL AND COALESCE(a.id_estudiante, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=a.id_estudiante AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'mensajes_remitente_sin_membresia', COUNT(*)
FROM mensajes m LEFT JOIN usuarios u ON u.idUsuario=m.id_remitente
WHERE m.id_institucion IS NOT NULL AND COALESCE(m.id_remitente, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=m.id_remitente AND ui.id_institucion=m.id_institucion
  )
UNION ALL SELECT 'mensajes_destinatario_sin_membresia', COUNT(*)
FROM mensajes m LEFT JOIN usuarios u ON u.idUsuario=m.id_destinatario
WHERE m.id_institucion IS NOT NULL AND COALESCE(m.id_destinatario, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=m.id_destinatario AND ui.id_institucion=m.id_institucion
  )
UNION ALL SELECT 'mensajes_participante_sin_membresia', COUNT(*)
FROM mensajes_participantes mp INNER JOIN mensajes m ON m.idMensaje=mp.id_mensaje LEFT JOIN usuarios u ON u.idUsuario=mp.id_usuario
WHERE m.id_institucion IS NOT NULL AND COALESCE(mp.id_usuario, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=mp.id_usuario AND ui.id_institucion=m.id_institucion
  )
UNION ALL SELECT 'notificaciones_usuario_sin_membresia', COUNT(*)
FROM notificaciones n LEFT JOIN usuarios u ON u.idUsuario=n.id_usuario
WHERE n.id_institucion IS NOT NULL AND COALESCE(n.id_usuario, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=n.id_usuario AND ui.id_institucion=n.id_institucion
  )
UNION ALL SELECT 'lecturas_notificacion_usuario_sin_membresia', COUNT(*)
FROM notificaciones_lecturas nl LEFT JOIN usuarios u ON u.idUsuario=nl.id_usuario
WHERE nl.id_institucion IS NOT NULL AND COALESCE(nl.id_usuario, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=nl.id_usuario AND ui.id_institucion=nl.id_institucion
  )
UNION ALL SELECT 'actividades_autor_sin_membresia', COUNT(*)
FROM actividades a LEFT JOIN usuarios u ON u.idUsuario=a.id_autor
WHERE a.id_institucion IS NOT NULL AND COALESCE(a.id_autor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=a.id_autor AND ui.id_institucion=a.id_institucion
  )
UNION ALL SELECT 'evaluaciones_autor_sin_membresia', COUNT(*)
FROM evaluaciones e INNER JOIN cursos c ON c.idCurso=e.id_curso LEFT JOIN usuarios u ON u.idUsuario=e.id_autor
WHERE c.id_institucion IS NOT NULL AND COALESCE(e.id_autor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=e.id_autor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'entregas_estudiante_sin_membresia', COUNT(*)
FROM entregaslecciones e INNER JOIN cursos c ON c.idCurso=e.id_curso LEFT JOIN usuarios u ON u.idUsuario=e.id_estudiante
WHERE c.id_institucion IS NOT NULL AND COALESCE(e.id_estudiante, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=e.id_estudiante AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'calificaciones_estudiante_sin_membresia', COUNT(*)
FROM calificaciones n INNER JOIN cursos c ON c.idCurso=n.id_curso LEFT JOIN usuarios u ON u.idUsuario=n.id_estudiante
WHERE c.id_institucion IS NOT NULL AND COALESCE(n.id_estudiante, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=n.id_estudiante AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'evaluaciones_calificaciones_estudiante_sin_membresia', COUNT(*)
FROM evaluaciones_calificaciones ec INNER JOIN evaluaciones e ON e.idEvaluacion=ec.id_evaluacion
INNER JOIN cursos c ON c.idCurso=e.id_curso LEFT JOIN usuarios u ON u.idUsuario=ec.id_estudiante
WHERE c.id_institucion IS NOT NULL AND COALESCE(ec.id_estudiante, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=ec.id_estudiante AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'cierres_periodo_estudiante_sin_membresia', COUNT(*)
FROM cierres_periodo_calificaciones cp INNER JOIN secciones s ON s.idSeccion=cp.id_seccion
INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=cp.id_estudiante
WHERE c.id_institucion IS NOT NULL AND COALESCE(cp.id_estudiante, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=cp.id_estudiante AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'cierres_periodo_actualizador_sin_membresia', COUNT(*)
FROM cierres_periodo_calificaciones cp INNER JOIN secciones s ON s.idSeccion=cp.id_seccion
INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=cp.actualizadoPor
WHERE c.id_institucion IS NOT NULL AND COALESCE(cp.actualizadoPor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=cp.actualizadoPor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'periodos_seccion_cerrador_sin_membresia', COUNT(*)
FROM periodos_seccion_estado pe INNER JOIN secciones s ON s.idSeccion=pe.id_seccion
INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=pe.cerradoPor
WHERE c.id_institucion IS NOT NULL AND COALESCE(pe.cerradoPor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=pe.cerradoPor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'periodos_seccion_reabridor_sin_membresia', COUNT(*)
FROM periodos_seccion_estado pe INNER JOIN secciones s ON s.idSeccion=pe.id_seccion
INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=pe.reabiertoPor
WHERE c.id_institucion IS NOT NULL AND COALESCE(pe.reabiertoPor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=pe.reabiertoPor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'asistencia_creador_sin_membresia', COUNT(*)
FROM asistencia_clases ac INNER JOIN cursos c ON c.idCurso=ac.id_curso LEFT JOIN usuarios u ON u.idUsuario=ac.creadaPor
WHERE c.id_institucion IS NOT NULL AND COALESCE(ac.creadaPor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=ac.creadaPor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'asistencia_estudiante_sin_membresia', COUNT(*)
FROM asistencia_registros ar INNER JOIN asistencia_clases ac ON ac.idClase=ar.id_clase
INNER JOIN cursos c ON c.idCurso=ac.id_curso LEFT JOIN usuarios u ON u.idUsuario=ar.id_estudiante
WHERE c.id_institucion IS NOT NULL AND COALESCE(ar.id_estudiante, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=ar.id_estudiante AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'asistencia_actualizador_sin_membresia', COUNT(*)
FROM asistencia_registros ar INNER JOIN asistencia_clases ac ON ac.idClase=ar.id_clase
INNER JOIN cursos c ON c.idCurso=ac.id_curso LEFT JOIN usuarios u ON u.idUsuario=ar.actualizadoPor
WHERE c.id_institucion IS NOT NULL AND COALESCE(ar.actualizadoPor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=ar.actualizadoPor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'posteos_autor_sin_membresia', COUNT(*)
FROM posteos p INNER JOIN cursos c ON c.idCurso=p.id_curso LEFT JOIN usuarios u ON u.idUsuario=p.id_autor
WHERE c.id_institucion IS NOT NULL AND COALESCE(p.id_autor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=p.id_autor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'recursos_creador_sin_membresia', COUNT(*)
FROM recursoslecciones r INNER JOIN lecciones l ON l.idLeccion=r.id_leccion
INNER JOIN secciones s ON s.idSeccion=l.id_modulo INNER JOIN cursos c ON c.idCurso=s.id_curso
LEFT JOIN usuarios u ON u.idUsuario=r.creadoPor
WHERE c.id_institucion IS NOT NULL AND COALESCE(r.creadoPor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=r.creadoPor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'historial_objetivo_sin_membresia', COUNT(*)
FROM usuarios_historial h LEFT JOIN usuarios u ON u.idUsuario=h.id_usuario
WHERE h.id_institucion IS NOT NULL AND COALESCE(h.id_usuario, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=h.id_usuario AND ui.id_institucion=h.id_institucion
  )
UNION ALL SELECT 'historial_actor_sin_membresia', COUNT(*)
FROM usuarios_historial h LEFT JOIN usuarios u ON u.idUsuario=h.id_usuario_accion
WHERE h.id_institucion IS NOT NULL AND COALESCE(h.id_usuario_accion, 0) > 0
  AND COALESCE(u.esSuperAdmin, 0)<>1
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=h.id_usuario_accion AND ui.id_institucion=h.id_institucion
  );
