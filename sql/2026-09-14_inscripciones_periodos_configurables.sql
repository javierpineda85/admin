-- Inscripciones históricas y modalidades de calificación por curso.
ALTER TABLE asignacioncursos ADD COLUMN IF NOT EXISTS estadoInscripcion VARCHAR(15) NOT NULL DEFAULT 'ACTIVA';
ALTER TABLE asignacioncursos ADD COLUMN IF NOT EXISTS fechaAlta DATETIME NULL;
ALTER TABLE asignacioncursos ADD COLUMN IF NOT EXISTS fechaBaja DATETIME NULL;
ALTER TABLE asignacioncursos ADD COLUMN IF NOT EXISTS motivoBaja VARCHAR(255) NULL;
UPDATE asignacioncursos SET fechaAlta=COALESCE(fechaAlta,NOW()) WHERE fechaAlta IS NULL;

ALTER TABLE cursos ADD COLUMN IF NOT EXISTS modalidadCalificacion VARCHAR(20) NOT NULL DEFAULT 'DOS_TRAMOS';
ALTER TABLE cursos ADD COLUMN IF NOT EXISTS intensificacionActiva TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS periodos_seccion_estado (
  idPeriodoSeccion INT NOT NULL AUTO_INCREMENT,
  id_periodo INT NOT NULL,
  id_seccion INT NOT NULL,
  estado VARCHAR(15) NOT NULL DEFAULT 'ABIERTO',
  fechaCierre DATETIME NULL,
  cerradoPor INT NULL,
  fechaReapertura DATETIME NULL,
  reabiertoPor INT NULL,
  motivoReapertura VARCHAR(255) NULL,
  PRIMARY KEY (idPeriodoSeccion),
  UNIQUE KEY uq_periodo_seccion (id_periodo,id_seccion)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO periodos_seccion_estado
  (id_periodo,id_seccion,estado,fechaCierre,cerradoPor,fechaReapertura,reabiertoPor,motivoReapertura)
SELECT relaciones.id_periodo,relaciones.id_seccion,p.estado,p.fechaCierre,p.cerradoPor,p.fechaReapertura,p.reabiertoPor,p.motivoReapertura
FROM (SELECT id_periodo,id_seccion FROM evaluaciones WHERE id_periodo IS NOT NULL
      UNION SELECT id_periodo,id_seccion FROM cierres_periodo_calificaciones) relaciones
INNER JOIN periodos_calificacion p ON p.idPeriodo=relaciones.id_periodo;
