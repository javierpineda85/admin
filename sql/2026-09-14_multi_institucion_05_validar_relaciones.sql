-- SOLO LECTURA. Ejecutar después de las migraciones 01, 02 y 03,
-- antes de 04_endurecer. Un valor mayor que cero requiere regularización.
-- No devuelve nombres, emails ni contenido académico.
SELECT 'cursos_responsable_sin_membresia' AS comprobacion, COUNT(*) AS incidencias
FROM cursos c INNER JOIN usuarios u ON u.idUsuario=c.responsable
WHERE c.id_institucion IS NOT NULL AND COALESCE(c.responsable, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=c.responsable AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'secciones_docente_sin_membresia', COUNT(*)
FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso INNER JOIN usuarios u ON u.idUsuario=s.docente
WHERE c.id_institucion IS NOT NULL AND COALESCE(s.docente, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=s.docente AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'secciones_tutor_sin_membresia', COUNT(*)
FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso INNER JOIN usuarios u ON u.idUsuario=s.tutor
WHERE c.id_institucion IS NOT NULL AND COALESCE(s.tutor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=s.tutor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'inscripciones_estudiante_sin_membresia', COUNT(*)
FROM asignacioncursos a INNER JOIN cursos c ON c.idCurso=a.id_seccion INNER JOIN usuarios u ON u.idUsuario=a.id_estudiante
WHERE c.id_institucion IS NOT NULL AND COALESCE(a.id_estudiante, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=a.id_estudiante AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'mensajes_remitente_sin_membresia', COUNT(*)
FROM mensajes m INNER JOIN usuarios u ON u.idUsuario=m.id_remitente
WHERE m.id_institucion IS NOT NULL AND COALESCE(m.id_remitente, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=m.id_remitente AND ui.id_institucion=m.id_institucion
  )
UNION ALL SELECT 'mensajes_participante_sin_membresia', COUNT(*)
FROM mensajes_participantes mp INNER JOIN mensajes m ON m.idMensaje=mp.id_mensaje INNER JOIN usuarios u ON u.idUsuario=mp.id_usuario
WHERE m.id_institucion IS NOT NULL AND COALESCE(mp.id_usuario, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=mp.id_usuario AND ui.id_institucion=m.id_institucion
  )
UNION ALL SELECT 'actividades_autor_sin_membresia', COUNT(*)
FROM actividades a INNER JOIN usuarios u ON u.idUsuario=a.id_autor
WHERE a.id_institucion IS NOT NULL AND COALESCE(a.id_autor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=a.id_autor AND ui.id_institucion=a.id_institucion
  )
UNION ALL SELECT 'evaluaciones_autor_sin_membresia', COUNT(*)
FROM evaluaciones e INNER JOIN cursos c ON c.idCurso=e.id_curso INNER JOIN usuarios u ON u.idUsuario=e.id_autor
WHERE c.id_institucion IS NOT NULL AND COALESCE(e.id_autor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=e.id_autor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'entregas_estudiante_sin_membresia', COUNT(*)
FROM entregaslecciones e INNER JOIN cursos c ON c.idCurso=e.id_curso INNER JOIN usuarios u ON u.idUsuario=e.id_estudiante
WHERE c.id_institucion IS NOT NULL AND COALESCE(e.id_estudiante, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=e.id_estudiante AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'calificaciones_estudiante_sin_membresia', COUNT(*)
FROM calificaciones n INNER JOIN cursos c ON c.idCurso=n.id_curso INNER JOIN usuarios u ON u.idUsuario=n.id_estudiante
WHERE c.id_institucion IS NOT NULL AND COALESCE(n.id_estudiante, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=n.id_estudiante AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'asistencia_creador_sin_membresia', COUNT(*)
FROM asistencia_clases ac INNER JOIN cursos c ON c.idCurso=ac.id_curso INNER JOIN usuarios u ON u.idUsuario=ac.creadaPor
WHERE c.id_institucion IS NOT NULL AND COALESCE(ac.creadaPor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=ac.creadaPor AND ui.id_institucion=c.id_institucion
  );
