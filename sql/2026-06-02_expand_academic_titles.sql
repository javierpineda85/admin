ALTER TABLE cursos
  MODIFY nombreCurso VARCHAR(120) NOT NULL,
  MODIFY contenidoCurso TEXT NOT NULL;

ALTER TABLE secciones
  MODIFY tituloSeccion VARCHAR(140) NOT NULL,
  MODIFY contenidoSeccion TEXT NULL;
