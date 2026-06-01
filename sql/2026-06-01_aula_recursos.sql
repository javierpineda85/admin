-- Tabla de recursos para lecciones
CREATE TABLE IF NOT EXISTS recursoslecciones (
  idRecursoLeccion INT NOT NULL AUTO_INCREMENT,
  id_leccion INT NOT NULL,
  tipoRecurso VARCHAR(10) NOT NULL,
  tituloRecurso VARCHAR(120) NOT NULL,
  urlRecurso VARCHAR(255) NOT NULL,
  creadoPor INT NOT NULL,
  fechaRecurso TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (idRecursoLeccion),
  KEY id_leccion (id_leccion, tipoRecurso),
  KEY creadoPor (creadoPor)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
