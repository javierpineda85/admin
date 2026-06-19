ALTER TABLE actividades
  ADD COLUMN alcancePlantilla VARCHAR(20) NOT NULL DEFAULT 'personal' AFTER esPlantilla,
  ADD COLUMN destacadaPublica TINYINT(1) NOT NULL DEFAULT 0 AFTER alcancePlantilla;
