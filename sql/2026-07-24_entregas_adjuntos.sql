-- Adjuntos multiples para las entregas de tareas

ALTER TABLE entregaslecciones ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS entregaslecciones_adjuntos (
  idAdjuntoEntrega INT NOT NULL AUTO_INCREMENT,
  id_entrega INT NOT NULL,
  nombreOriginal VARCHAR(255) NOT NULL,
  rutaArchivo VARCHAR(255) NOT NULL,
  mimeType VARCHAR(100) DEFAULT NULL,
  tamanoArchivo INT DEFAULT NULL,
  fechaAdjunto TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (idAdjuntoEntrega),
  KEY idx_entrega (id_entrega)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
