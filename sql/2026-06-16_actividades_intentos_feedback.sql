ALTER TABLE actividades
  ADD COLUMN IF NOT EXISTS intentosPermitidos INT NOT NULL DEFAULT 1 AFTER puntajeMaximo;

ALTER TABLE actividades_preguntas
  ADD COLUMN IF NOT EXISTS explicacionError TEXT NULL AFTER pista;
