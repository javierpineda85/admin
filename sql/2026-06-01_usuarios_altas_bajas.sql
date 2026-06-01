ALTER TABLE usuarios
  ADD COLUMN fechaAlta datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER rol,
  ADD COLUMN fechaBaja datetime DEFAULT NULL AFTER fechaAlta,
  ADD COLUMN motivoBaja varchar(255) DEFAULT NULL AFTER fechaBaja,
  ADD COLUMN usuarioBaja int DEFAULT NULL AFTER motivoBaja;
