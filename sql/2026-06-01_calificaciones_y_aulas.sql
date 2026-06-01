ALTER TABLE lecciones
  ADD COLUMN IF NOT EXISTS tipoLeccion VARCHAR(12) NOT NULL DEFAULT 'MATERIAL' AFTER nombreLeccion;

ALTER TABLE posteos
  ADD COLUMN IF NOT EXISTS id_leccion INT NULL AFTER id_curso;

ALTER TABLE posteos
  ADD COLUMN IF NOT EXISTS tipoPosteo VARCHAR(12) NOT NULL DEFAULT 'FORO' AFTER id_leccion;

ALTER TABLE calificaciones
  ADD UNIQUE KEY uq_calificacion (id_estudiante, id_seccion, id_modulo);

CREATE TABLE IF NOT EXISTS entregaslecciones (
  idEntregaLeccion INT NOT NULL AUTO_INCREMENT,
  id_leccion INT NOT NULL,
  id_seccion INT NOT NULL,
  id_curso INT NOT NULL,
  id_estudiante INT NOT NULL,
  urlArchivo VARCHAR(255) NOT NULL,
  comentarioEntrega TINYTEXT NULL,
  fechaEntrega TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estadoEntrega VARCHAR(15) NOT NULL DEFAULT 'ENTREGADA',
  PRIMARY KEY (idEntregaLeccion),
  UNIQUE KEY uq_entrega (id_leccion, id_estudiante),
  KEY id_seccion (id_seccion, id_curso, id_estudiante)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
