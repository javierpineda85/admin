ALTER TABLE actividades_preguntas
  ADD COLUMN lenguajeCodigo VARCHAR(30) NOT NULL DEFAULT 'plaintext' AFTER codigoBase,
  ADD COLUMN variantesCodigo TEXT NULL AFTER lenguajeCodigo;
