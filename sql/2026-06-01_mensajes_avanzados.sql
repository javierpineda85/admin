-- Mensajes avanzados: participantes, adjuntos, papelera y lectura

ALTER TABLE mensajes MODIFY contenidoMensaje LONGTEXT NOT NULL;
ALTER TABLE mensajes ENGINE=InnoDB;

DROP TABLE IF EXISTS mensajes_participantes;
CREATE TABLE IF NOT EXISTS mensajes_participantes (
  idMensajeParticipante int NOT NULL AUTO_INCREMENT,
  id_mensaje int NOT NULL,
  id_usuario int NOT NULL,
  rolParticipante varchar(15) NOT NULL,
  leido tinyint(1) NOT NULL DEFAULT '0',
  fechaLeido datetime DEFAULT NULL,
  enPapelera tinyint(1) NOT NULL DEFAULT '0',
  fechaPapelera datetime DEFAULT NULL,
  eliminado tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (idMensajeParticipante),
  KEY idx_mensaje_usuario (id_mensaje, id_usuario),
  KEY idx_usuario_box (id_usuario, rolParticipante, leido, enPapelera, eliminado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS mensajes_adjuntos;
CREATE TABLE IF NOT EXISTS mensajes_adjuntos (
  idAdjunto int NOT NULL AUTO_INCREMENT,
  id_mensaje int NOT NULL,
  nombreOriginal varchar(255) NOT NULL,
  nombreGuardado varchar(255) NOT NULL,
  rutaArchivo varchar(255) NOT NULL,
  mimeType varchar(100) DEFAULT NULL,
  tamanoArchivo int DEFAULT NULL,
  fechaAdjunto timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (idAdjunto),
  KEY idx_mensaje (id_mensaje)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
