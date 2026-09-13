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
