-- Asistencia diaria por curso. Conserva intacto el historial anterior por materia.
CREATE TABLE IF NOT EXISTS asistencia_curso_clases (
  idClase INT NOT NULL AUTO_INCREMENT,
  id_curso INT NOT NULL,
  fechaClase DATE NOT NULL,
  tema VARCHAR(180) NULL,
  creadaPor INT NOT NULL,
  fechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (idClase),
  UNIQUE KEY uq_asistencia_curso_fecha (id_curso, fechaClase)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asistencia_curso_registros (
  idAsistencia INT NOT NULL AUTO_INCREMENT,
  id_clase INT NOT NULL,
  id_estudiante INT NOT NULL,
  estado VARCHAR(15) NOT NULL DEFAULT 'PRESENTE',
  observacion VARCHAR(255) NULL,
  actualizadoPor INT NULL,
  fechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (idAsistencia),
  UNIQUE KEY uq_asistencia_curso_estudiante (id_clase, id_estudiante),
  KEY idx_asistencia_curso_alumno (id_estudiante),
  CONSTRAINT fk_asistencia_curso_clase FOREIGN KEY (id_clase) REFERENCES asistencia_curso_clases (idClase)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
