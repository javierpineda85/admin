DELIMITER $$
DROP PROCEDURE IF EXISTS campus_actividades_arrastrar_soltar$$
CREATE PROCEDURE campus_actividades_arrastrar_soltar()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'actividades_opciones' AND column_name = 'tipoArrastre') THEN
    ALTER TABLE actividades_opciones ADD COLUMN tipoArrastre VARCHAR(20) NULL AFTER orden;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'actividades_opciones' AND column_name = 'claveArrastre') THEN
    ALTER TABLE actividades_opciones ADD COLUMN claveArrastre INT NULL AFTER tipoArrastre;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'actividades_opciones' AND column_name = 'imagenOpcion') THEN
    ALTER TABLE actividades_opciones ADD COLUMN imagenOpcion VARCHAR(255) NULL AFTER claveArrastre;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'actividades_opciones' AND column_name = 'audioOpcion') THEN
    ALTER TABLE actividades_opciones ADD COLUMN audioOpcion VARCHAR(255) NULL AFTER imagenOpcion;
  END IF;
END$$
CALL campus_actividades_arrastrar_soltar()$$
DROP PROCEDURE campus_actividades_arrastrar_soltar$$
DELIMITER ;
