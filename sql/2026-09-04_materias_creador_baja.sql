-- Autoría y baja reversible de materias/secciones.
ALTER TABLE secciones
  ADD COLUMN creadoPor INT NULL DEFAULT NULL AFTER colorFinBanner,
  ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER creadoPor,
  ADD COLUMN fechaBaja DATETIME NULL DEFAULT NULL AFTER activo,
  ADD COLUMN motivoBaja VARCHAR(255) NULL DEFAULT NULL AFTER fechaBaja,
  ADD COLUMN usuarioBaja INT NULL DEFAULT NULL AFTER motivoBaja,
  ADD INDEX idx_secciones_creado_por (creadoPor),
  ADD INDEX idx_secciones_activo (activo);
