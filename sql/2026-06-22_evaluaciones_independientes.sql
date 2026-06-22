CREATE TABLE IF NOT EXISTS evaluaciones (
  idEvaluacion INT NOT NULL AUTO_INCREMENT,
  id_seccion INT NOT NULL,
  id_curso INT NOT NULL,
  id_autor INT NOT NULL,
  temaEvaluacion VARCHAR(180) NOT NULL,
  fechaEvaluacion DATE NOT NULL,
  fechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (idEvaluacion),
  KEY idx_evaluaciones_seccion (id_seccion, fechaEvaluacion),
  KEY idx_evaluaciones_autor (id_autor)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evaluaciones_calificaciones (
  idEvaluacionCalificacion INT NOT NULL AUTO_INCREMENT,
  id_evaluacion INT NOT NULL,
  id_estudiante INT NOT NULL,
  calificacion DECIMAL(5,2) NOT NULL,
  devolucion TEXT NULL,
  fechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (idEvaluacionCalificacion),
  UNIQUE KEY uq_evaluacion_estudiante (id_evaluacion, id_estudiante),
  KEY idx_evaluaciones_calificaciones_estudiante (id_estudiante)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
