ALTER TABLE usuarios
  ADD COLUMN ultimaConexion datetime DEFAULT NULL AFTER fechaAlta;
