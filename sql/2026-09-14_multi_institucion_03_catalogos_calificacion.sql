-- Fase 4: ciclos e instrumentos pasan a ser catálogos por institución.
-- Permite repetir año y nombre en tenants distintos sin tocar los datos existentes.
DELIMITER $$
DROP PROCEDURE IF EXISTS campus_mt_catalogos_calificacion$$
CREATE PROCEDURE campus_mt_catalogos_calificacion()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM campus_migraciones WHERE codigo='multi_institucion_03_catalogos_calificacion') THEN
    IF EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='ciclos_lectivos' AND index_name='uq_ciclo_anio') THEN
      ALTER TABLE ciclos_lectivos DROP INDEX uq_ciclo_anio;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='ciclos_lectivos' AND index_name='uq_ciclo_institucion_anio') THEN
      ALTER TABLE ciclos_lectivos ADD UNIQUE KEY uq_ciclo_institucion_anio(id_institucion,anio);
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='instrumentos_evaluacion' AND index_name='uq_instrumento_nombre') THEN
      ALTER TABLE instrumentos_evaluacion DROP INDEX uq_instrumento_nombre;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='instrumentos_evaluacion' AND index_name='uq_instrumento_institucion_nombre') THEN
      ALTER TABLE instrumentos_evaluacion ADD UNIQUE KEY uq_instrumento_institucion_nombre(id_institucion,nombre);
    END IF;
  END IF;
END$$
CALL campus_mt_catalogos_calificacion()$$
DROP PROCEDURE campus_mt_catalogos_calificacion$$
DELIMITER ;

INSERT INTO campus_migraciones(codigo)
SELECT 'multi_institucion_03_catalogos_calificacion'
WHERE NOT EXISTS (SELECT 1 FROM campus_migraciones WHERE codigo='multi_institucion_03_catalogos_calificacion');
