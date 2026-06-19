ALTER TABLE actividades
  ADD COLUMN esPlantilla TINYINT(1) NOT NULL DEFAULT 0 AFTER permiteVisitantes,
  ADD COLUMN id_actividad_origen INT NULL AFTER esPlantilla;
