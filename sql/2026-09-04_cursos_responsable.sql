-- Separa la autoria historica del responsable actual del curso.
ALTER TABLE cursos
  ADD COLUMN responsable INT NULL DEFAULT NULL AFTER creadoPor,
  ADD INDEX idx_cursos_responsable (responsable);

UPDATE cursos
SET responsable = creadoPor
WHERE responsable IS NULL;
