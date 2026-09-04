-- Baja reversible de cursos administrada exclusivamente desde Campus.
ALTER TABLE cursos
  ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER creadoPor,
  ADD COLUMN fechaBaja DATETIME NULL DEFAULT NULL AFTER activo,
  ADD COLUMN motivoBaja VARCHAR(255) NULL DEFAULT NULL AFTER fechaBaja,
  ADD COLUMN usuarioBaja INT NULL DEFAULT NULL AFTER motivoBaja,
  ADD INDEX idx_cursos_activo (activo);
