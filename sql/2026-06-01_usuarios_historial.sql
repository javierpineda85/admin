CREATE TABLE IF NOT EXISTS usuarios_historial (
  idHistorial int NOT NULL AUTO_INCREMENT,
  id_usuario int NOT NULL,
  accion varchar(30) NOT NULL,
  detalle varchar(255) NOT NULL,
  id_usuario_accion int NOT NULL,
  fechaEvento datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (idHistorial),
  KEY id_usuario (id_usuario, id_usuario_accion, accion)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
