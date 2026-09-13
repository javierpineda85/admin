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
