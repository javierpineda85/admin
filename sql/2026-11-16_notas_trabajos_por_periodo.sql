-- Fase 1: notas de trabajos por período y planillas exportables.
-- Ejecutar primero sobre una copia de respaldo de la base seleccionada.
-- No elimina datos ni modifica notas existentes.

DELIMITER $$
DROP PROCEDURE IF EXISTS campus_notas_trabajo_periodo$$
CREATE PROCEDURE campus_notas_trabajo_periodo()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='lecciones' AND column_name='id_periodo') THEN
    ALTER TABLE lecciones ADD COLUMN id_periodo INT NULL AFTER id_modulo;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='lecciones' AND index_name='idx_lecciones_periodo') THEN
    ALTER TABLE lecciones ADD KEY idx_lecciones_periodo (id_modulo,id_periodo);
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='calificaciones' AND column_name='id_periodo') THEN
    ALTER TABLE calificaciones ADD COLUMN id_periodo INT NULL AFTER id_curso;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='calificaciones' AND index_name='idx_calificaciones_periodo') THEN
    ALTER TABLE calificaciones ADD KEY idx_calificaciones_periodo (id_seccion,id_periodo,id_estudiante);
  END IF;
END$$
CALL campus_notas_trabajo_periodo()$$
DROP PROCEDURE campus_notas_trabajo_periodo$$
DELIMITER ;

-- Las notas ya cargadas se asignan según la fecha de calificación siempre que
-- el período del ciclo tenga fechaInicio y fechaFin configuradas. Al ingresar
-- a una materia, la aplicación también completa las pendientes distribuyéndolas
-- cronológicamente entre los períodos regulares del curso si esas fechas faltan.
UPDATE calificaciones n
INNER JOIN lecciones l ON l.idLeccion=n.id_modulo
INNER JOIN secciones s ON s.idSeccion=n.id_seccion AND l.id_modulo=s.idSeccion
INNER JOIN cursos c ON c.idCurso=s.id_curso AND n.id_curso=c.idCurso
LEFT JOIN periodos_calificacion p ON p.id_ciclo=c.id_ciclo_lectivo
  AND p.tipo='REGULAR'
  AND p.fechaInicio IS NOT NULL AND p.fechaFin IS NOT NULL
  AND DATE(n.fechaCalificacion) BETWEEN p.fechaInicio AND p.fechaFin
SET n.id_periodo=COALESCE(l.id_periodo,p.idPeriodo)
WHERE n.id_periodo IS NULL;
