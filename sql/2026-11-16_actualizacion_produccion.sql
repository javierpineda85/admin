-- Campus 5.2026.11.16: script único de actualización para producción.
-- Ejecutar sobre un respaldo reciente y con una cuenta que pueda crear procedimientos temporales.
-- Requiere el esquema académico y multiinstitución ya desplegado.
-- Es idempotente: puede ejecutarse más de una vez sin duplicar columnas, índices ni roles.

DELIMITER $$

DROP PROCEDURE IF EXISTS campus_20261116_actualizacion$$
CREATE PROCEDURE campus_20261116_actualizacion()
BEGIN
  -- Actividades: tarjetas arrastrables y multimedia.
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

  -- Calificaciones de tareas por período.
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'lecciones' AND column_name = 'id_periodo') THEN
    ALTER TABLE lecciones ADD COLUMN id_periodo INT NULL AFTER id_modulo;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'lecciones' AND index_name = 'idx_lecciones_periodo') THEN
    ALTER TABLE lecciones ADD KEY idx_lecciones_periodo (id_modulo, id_periodo);
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'calificaciones' AND column_name = 'id_periodo') THEN
    ALTER TABLE calificaciones ADD COLUMN id_periodo INT NULL AFTER id_curso;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'calificaciones' AND index_name = 'idx_calificaciones_periodo') THEN
    ALTER TABLE calificaciones ADD KEY idx_calificaciones_periodo (id_seccion, id_periodo, id_estudiante);
  END IF;
END$$

CALL campus_20261116_actualizacion()$$
DROP PROCEDURE campus_20261116_actualizacion$$

DELIMITER ;

-- Asigna las notas históricas de trabajos al período que coincida con su fecha.
-- Las filas sin fechas de período permanecen sin modificar: el Campus las completa al abrir la materia.
UPDATE calificaciones n
INNER JOIN lecciones l ON l.idLeccion = n.id_modulo
INNER JOIN secciones s ON s.idSeccion = n.id_seccion AND l.id_modulo = s.idSeccion
INNER JOIN cursos c ON c.idCurso = s.id_curso AND n.id_curso = c.idCurso
LEFT JOIN periodos_calificacion p ON p.id_ciclo = c.id_ciclo_lectivo
  AND p.tipo = 'REGULAR'
  AND p.fechaInicio IS NOT NULL AND p.fechaFin IS NOT NULL
  AND DATE(n.fechaCalificacion) BETWEEN p.fechaInicio AND p.fechaFin
SET n.id_periodo = COALESCE(l.id_periodo, p.idPeriodo)
WHERE n.id_periodo IS NULL;

-- Rol institucional de consulta integral para coordinación académica.
INSERT INTO roles (codigo, nombre)
SELECT 'COORDINADOR', 'Coordinador académico'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE codigo = 'COORDINADOR');

-- Los avisos de calificaciones no requieren migración: reutilizan la tabla notificaciones existente.
