-- SOLO LECTURA. Ejecutar después de las migraciones 01, 02 y 03,
-- antes de 04_endurecer. Un valor mayor que cero requiere regularización.
-- No devuelve nombres, emails ni contenido académico.
SELECT 'membresias_sin_usuario' AS comprobacion, COUNT(*) AS incidencias
FROM usuarios_instituciones ui LEFT JOIN usuarios u ON u.idUsuario=ui.id_usuario
WHERE u.idUsuario IS NULL
UNION ALL SELECT 'cursos_responsable_sin_membresia', COUNT(*)
FROM cursos c LEFT JOIN usuarios u ON u.idUsuario=c.responsable
WHERE c.id_institucion IS NOT NULL AND COALESCE(c.responsable, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=c.responsable AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'secciones_docente_sin_membresia', COUNT(*)
FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=s.docente
WHERE c.id_institucion IS NOT NULL AND COALESCE(s.docente, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=s.docente AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'secciones_tutor_sin_membresia', COUNT(*)
FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=s.tutor
WHERE c.id_institucion IS NOT NULL AND COALESCE(s.tutor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=s.tutor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'inscripciones_estudiante_sin_membresia', COUNT(*)
FROM asignacioncursos a INNER JOIN cursos c ON c.idCurso=a.id_seccion LEFT JOIN usuarios u ON u.idUsuario=a.id_estudiante
WHERE c.id_institucion IS NOT NULL AND COALESCE(a.id_estudiante, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=a.id_estudiante AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'mensajes_remitente_sin_membresia', COUNT(*)
FROM mensajes m LEFT JOIN usuarios u ON u.idUsuario=m.id_remitente
WHERE m.id_institucion IS NOT NULL AND COALESCE(m.id_remitente, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=m.id_remitente AND ui.id_institucion=m.id_institucion
  )
UNION ALL SELECT 'mensajes_destinatario_sin_membresia', COUNT(*)
FROM mensajes m LEFT JOIN usuarios u ON u.idUsuario=m.id_destinatario
WHERE m.id_institucion IS NOT NULL AND COALESCE(m.id_destinatario, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=m.id_destinatario AND ui.id_institucion=m.id_institucion
  )
UNION ALL SELECT 'mensajes_participante_sin_membresia', COUNT(*)
FROM mensajes_participantes mp INNER JOIN mensajes m ON m.idMensaje=mp.id_mensaje LEFT JOIN usuarios u ON u.idUsuario=mp.id_usuario
WHERE m.id_institucion IS NOT NULL AND COALESCE(mp.id_usuario, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=mp.id_usuario AND ui.id_institucion=m.id_institucion
  )
UNION ALL SELECT 'notificaciones_usuario_sin_membresia', COUNT(*)
FROM notificaciones n LEFT JOIN usuarios u ON u.idUsuario=n.id_usuario
WHERE n.id_institucion IS NOT NULL AND COALESCE(n.id_usuario, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=n.id_usuario AND ui.id_institucion=n.id_institucion
  )
UNION ALL SELECT 'lecturas_notificacion_usuario_sin_membresia', COUNT(*)
FROM notificaciones_lecturas nl LEFT JOIN usuarios u ON u.idUsuario=nl.id_usuario
WHERE nl.id_institucion IS NOT NULL AND COALESCE(nl.id_usuario, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=nl.id_usuario AND ui.id_institucion=nl.id_institucion
  )
UNION ALL SELECT 'actividades_autor_sin_membresia', COUNT(*)
FROM actividades a LEFT JOIN usuarios u ON u.idUsuario=a.id_autor
WHERE a.id_institucion IS NOT NULL AND COALESCE(a.id_autor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=a.id_autor AND ui.id_institucion=a.id_institucion
  )
UNION ALL SELECT 'evaluaciones_autor_sin_membresia', COUNT(*)
FROM evaluaciones e INNER JOIN cursos c ON c.idCurso=e.id_curso LEFT JOIN usuarios u ON u.idUsuario=e.id_autor
WHERE c.id_institucion IS NOT NULL AND COALESCE(e.id_autor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=e.id_autor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'entregas_estudiante_sin_membresia', COUNT(*)
FROM entregaslecciones e INNER JOIN cursos c ON c.idCurso=e.id_curso LEFT JOIN usuarios u ON u.idUsuario=e.id_estudiante
WHERE c.id_institucion IS NOT NULL AND COALESCE(e.id_estudiante, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=e.id_estudiante AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'calificaciones_estudiante_sin_membresia', COUNT(*)
FROM calificaciones n INNER JOIN cursos c ON c.idCurso=n.id_curso LEFT JOIN usuarios u ON u.idUsuario=n.id_estudiante
WHERE c.id_institucion IS NOT NULL AND COALESCE(n.id_estudiante, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=n.id_estudiante AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'evaluaciones_calificaciones_estudiante_sin_membresia', COUNT(*)
FROM evaluaciones_calificaciones ec INNER JOIN evaluaciones e ON e.idEvaluacion=ec.id_evaluacion
INNER JOIN cursos c ON c.idCurso=e.id_curso LEFT JOIN usuarios u ON u.idUsuario=ec.id_estudiante
WHERE c.id_institucion IS NOT NULL AND COALESCE(ec.id_estudiante, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=ec.id_estudiante AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'cierres_periodo_estudiante_sin_membresia', COUNT(*)
FROM cierres_periodo_calificaciones cp INNER JOIN secciones s ON s.idSeccion=cp.id_seccion
INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=cp.id_estudiante
WHERE c.id_institucion IS NOT NULL AND COALESCE(cp.id_estudiante, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=cp.id_estudiante AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'cierres_periodo_actualizador_sin_membresia', COUNT(*)
FROM cierres_periodo_calificaciones cp INNER JOIN secciones s ON s.idSeccion=cp.id_seccion
INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=cp.actualizadoPor
WHERE c.id_institucion IS NOT NULL AND COALESCE(cp.actualizadoPor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=cp.actualizadoPor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'periodos_seccion_cerrador_sin_membresia', COUNT(*)
FROM periodos_seccion_estado pe INNER JOIN secciones s ON s.idSeccion=pe.id_seccion
INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=pe.cerradoPor
WHERE c.id_institucion IS NOT NULL AND COALESCE(pe.cerradoPor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=pe.cerradoPor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'periodos_seccion_reabridor_sin_membresia', COUNT(*)
FROM periodos_seccion_estado pe INNER JOIN secciones s ON s.idSeccion=pe.id_seccion
INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=pe.reabiertoPor
WHERE c.id_institucion IS NOT NULL AND COALESCE(pe.reabiertoPor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=pe.reabiertoPor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'asistencia_creador_sin_membresia', COUNT(*)
FROM asistencia_clases ac INNER JOIN cursos c ON c.idCurso=ac.id_curso LEFT JOIN usuarios u ON u.idUsuario=ac.creadaPor
WHERE c.id_institucion IS NOT NULL AND COALESCE(ac.creadaPor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=ac.creadaPor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'asistencia_estudiante_sin_membresia', COUNT(*)
FROM asistencia_registros ar INNER JOIN asistencia_clases ac ON ac.idClase=ar.id_clase
INNER JOIN cursos c ON c.idCurso=ac.id_curso LEFT JOIN usuarios u ON u.idUsuario=ar.id_estudiante
WHERE c.id_institucion IS NOT NULL AND COALESCE(ar.id_estudiante, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=ar.id_estudiante AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'asistencia_actualizador_sin_membresia', COUNT(*)
FROM asistencia_registros ar INNER JOIN asistencia_clases ac ON ac.idClase=ar.id_clase
INNER JOIN cursos c ON c.idCurso=ac.id_curso LEFT JOIN usuarios u ON u.idUsuario=ar.actualizadoPor
WHERE c.id_institucion IS NOT NULL AND COALESCE(ar.actualizadoPor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=ar.actualizadoPor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'posteos_autor_sin_membresia', COUNT(*)
FROM posteos p INNER JOIN cursos c ON c.idCurso=p.id_curso LEFT JOIN usuarios u ON u.idUsuario=p.id_autor
WHERE c.id_institucion IS NOT NULL AND COALESCE(p.id_autor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=p.id_autor AND ui.id_institucion=c.id_institucion
  )
UNION ALL SELECT 'recursos_creador_sin_membresia', COUNT(*)
FROM recursoslecciones r INNER JOIN lecciones l ON l.idLeccion=r.id_leccion
INNER JOIN secciones s ON s.idSeccion=l.id_modulo INNER JOIN cursos c ON c.idCurso=s.id_curso
LEFT JOIN usuarios u ON u.idUsuario=r.creadoPor
WHERE c.id_institucion IS NOT NULL AND COALESCE(r.creadoPor, 0) > 0
  AND NOT EXISTS (
      SELECT 1 FROM usuarios_instituciones ui
      WHERE ui.id_usuario=r.creadoPor AND ui.id_institucion=c.id_institucion
  );
