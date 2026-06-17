CREATE TABLE IF NOT EXISTS actividades (
  idActividad INT NOT NULL AUTO_INCREMENT,
  tituloActividad VARCHAR(160) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  descripcionActividad TEXT NULL,
  tipoActividad VARCHAR(30) NOT NULL DEFAULT 'multiple_choice',
  visibilidad VARCHAR(20) NOT NULL DEFAULT 'privada',
  estadoActividad VARCHAR(15) NOT NULL DEFAULT 'BORRADOR',
  id_curso INT NULL,
  id_seccion INT NULL,
  id_autor INT NOT NULL,
  puntajeMaximo DECIMAL(6,2) NOT NULL DEFAULT 0,
  intentosPermitidos INT NOT NULL DEFAULT 1,
  permiteVisitantes TINYINT(1) NOT NULL DEFAULT 1,
  recursoExternoUrl VARCHAR(255) NULL,
  recursoExternoEmbed TEXT NULL,
  fechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fechaActualizacion DATETIME NULL,
  PRIMARY KEY (idActividad),
  UNIQUE KEY uq_actividades_slug (slug),
  KEY idx_actividades_contexto (id_curso, id_seccion, visibilidad, estadoActividad),
  KEY idx_actividades_autor (id_autor)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS actividades_preguntas (
  idPregunta INT NOT NULL AUTO_INCREMENT,
  id_actividad INT NOT NULL,
  tipoPregunta VARCHAR(30) NOT NULL,
  textoPregunta TEXT NOT NULL,
  respuestaCorrecta TEXT NULL,
  puntaje DECIMAL(6,2) NOT NULL DEFAULT 1,
  orden INT NOT NULL DEFAULT 1,
  pista TEXT NULL,
  explicacionError TEXT NULL,
  PRIMARY KEY (idPregunta),
  KEY idx_preguntas_actividad (id_actividad, orden)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS actividades_opciones (
  idOpcion INT NOT NULL AUTO_INCREMENT,
  id_pregunta INT NOT NULL,
  textoOpcion TEXT NOT NULL,
  esCorrecta TINYINT(1) NOT NULL DEFAULT 0,
  orden INT NOT NULL DEFAULT 1,
  PRIMARY KEY (idOpcion),
  KEY idx_opciones_pregunta (id_pregunta, orden)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS actividades_intentos (
  idIntento INT NOT NULL AUTO_INCREMENT,
  id_actividad INT NOT NULL,
  id_usuario INT NULL,
  nombreVisitante VARCHAR(120) NULL,
  emailVisitante VARCHAR(120) NULL,
  puntaje DECIMAL(6,2) NOT NULL DEFAULT 0,
  estadoIntento VARCHAR(15) NOT NULL DEFAULT 'ENTREGADO',
  fechaInicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fechaEntrega DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ipVisitante VARCHAR(45) NULL,
  PRIMARY KEY (idIntento),
  KEY idx_intentos_actividad (id_actividad, fechaEntrega),
  KEY idx_intentos_usuario (id_usuario)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS actividades_respuestas (
  idRespuesta INT NOT NULL AUTO_INCREMENT,
  id_intento INT NOT NULL,
  id_pregunta INT NOT NULL,
  id_opcion INT NULL,
  textoRespuesta TEXT NULL,
  esCorrecta TINYINT(1) NOT NULL DEFAULT 0,
  puntajeObtenido DECIMAL(6,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (idRespuesta),
  KEY idx_respuestas_intento (id_intento, id_pregunta)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
