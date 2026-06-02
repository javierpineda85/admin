ALTER TABLE cursos
  MODIFY nombreCurso VARCHAR(120) NOT NULL,
  MODIFY contenidoCurso TEXT NOT NULL;

ALTER TABLE calificaciones
  ADD COLUMN devolucion TEXT NULL AFTER calificacion;

ALTER TABLE secciones
  MODIFY tituloSeccion VARCHAR(140) NOT NULL,
  MODIFY contenidoSeccion TEXT NULL,
  ADD COLUMN bannerSeccion VARCHAR(255) NULL AFTER tutor,
  ADD COLUMN colorInicioBanner VARCHAR(20) NULL DEFAULT '#0f172a' AFTER bannerSeccion,
  ADD COLUMN colorFinBanner VARCHAR(20) NULL DEFAULT '#1d4ed8' AFTER colorInicioBanner;
