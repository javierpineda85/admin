CREATE TABLE IF NOT EXISTS ciclos_lectivos (
  idCicloLectivo INT NOT NULL AUTO_INCREMENT, nombre VARCHAR(80) NOT NULL, anio SMALLINT NOT NULL,
  fechaInicio DATE NULL, fechaFin DATE NULL, activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (idCicloLectivo), UNIQUE KEY uq_ciclo_anio (anio)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS periodos_calificacion (
  idPeriodo INT NOT NULL AUTO_INCREMENT, id_ciclo INT NOT NULL, nombre VARCHAR(100) NOT NULL,
  tipo VARCHAR(30) NOT NULL DEFAULT 'REGULAR', orden INT NOT NULL DEFAULT 1,
  fechaInicio DATE NULL, fechaFin DATE NULL, estado VARCHAR(15) NOT NULL DEFAULT 'ABIERTO',
  fechaCierre DATETIME NULL, cerradoPor INT NULL, fechaReapertura DATETIME NULL,
  reabiertoPor INT NULL, motivoReapertura VARCHAR(255) NULL,
  PRIMARY KEY (idPeriodo), UNIQUE KEY uq_periodo_ciclo_nombre (id_ciclo, nombre), KEY idx_periodo_ciclo (id_ciclo, orden)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS instrumentos_evaluacion (
  idInstrumento INT NOT NULL AUTO_INCREMENT, nombre VARCHAR(100) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1, orden INT NOT NULL DEFAULT 1,
  PRIMARY KEY (idInstrumento), UNIQUE KEY uq_instrumento_nombre (nombre)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE cursos ADD COLUMN IF NOT EXISTS id_ciclo_lectivo INT NULL AFTER responsable;
ALTER TABLE evaluaciones
  ADD COLUMN IF NOT EXISTS id_periodo INT NULL AFTER id_curso,
  ADD COLUMN IF NOT EXISTS id_instrumento INT NULL AFTER id_periodo;
ALTER TABLE evaluaciones_calificaciones
  MODIFY calificacion DECIMAL(5,2) NULL,
  ADD COLUMN IF NOT EXISTS estadoAsistencia VARCHAR(15) NOT NULL DEFAULT 'PRESENTE' AFTER calificacion;

CREATE TABLE IF NOT EXISTS cierres_periodo_calificaciones (
  idCierrePeriodo INT NOT NULL AUTO_INCREMENT,
  id_periodo INT NOT NULL,
  id_seccion INT NOT NULL,
  id_estudiante INT NOT NULL,
  promedioCalculado DECIMAL(5,2) NULL,
  calificacionCierre DECIMAL(5,2) NULL,
  confirmada TINYINT(1) NOT NULL DEFAULT 0,
  fechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizadoPor INT NULL,
  PRIMARY KEY (idCierrePeriodo),
  UNIQUE KEY uq_cierre_periodo_estudiante (id_periodo, id_seccion, id_estudiante),
  KEY idx_cierre_periodo_seccion (id_periodo, id_seccion)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
