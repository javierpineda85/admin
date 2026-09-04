-- Permite que el Campus conserve la pertenencia de los cursos creados por docentes.
ALTER TABLE cursos
  ADD COLUMN creadoPor INT NULL DEFAULT NULL AFTER horarioCurso,
  ADD INDEX idx_cursos_creado_por (creadoPor);
