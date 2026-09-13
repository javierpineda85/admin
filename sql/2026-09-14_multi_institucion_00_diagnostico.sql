-- SOLO LECTURA. Para una instalación con las migraciones académicas actuales.
-- Devuelve conteos, no contraseñas, correos ni datos personales.
-- Un valor > 0 requiere revisar las filas antes de imponer relaciones estrictas.
SELECT 'emails_duplicados_normalizados' AS comprobacion, COUNT(*) AS incidencias
FROM (SELECT LOWER(TRIM(email)) FROM usuarios GROUP BY LOWER(TRIM(email)) HAVING COUNT(*)>1) d
UNION ALL SELECT 'emails_vacios',COUNT(*) FROM usuarios WHERE TRIM(email)=''
UNION ALL SELECT 'roles_sin_equivalencia',COUNT(*) FROM usuarios WHERE UPPER(TRIM(rol)) NOT IN ('ADMINISTRADOR','DOCENTE','ESTUDIANTE')
UNION ALL SELECT 'secciones_sin_curso',COUNT(*) FROM secciones s LEFT JOIN cursos c ON c.idCurso=s.id_curso WHERE c.idCurso IS NULL
UNION ALL SELECT 'lecciones_sin_seccion',COUNT(*) FROM lecciones l LEFT JOIN secciones s ON s.idSeccion=l.id_modulo WHERE s.idSeccion IS NULL
UNION ALL SELECT 'inscripciones_sin_curso',COUNT(*) FROM asignacioncursos a LEFT JOIN cursos c ON c.idCurso=a.id_seccion WHERE c.idCurso IS NULL
UNION ALL SELECT 'inscripciones_sin_usuario',COUNT(*) FROM asignacioncursos a LEFT JOIN usuarios u ON u.idUsuario=a.id_estudiante WHERE u.idUsuario IS NULL
UNION ALL SELECT 'entregas_contexto_inconsistente',COUNT(*) FROM entregaslecciones e LEFT JOIN lecciones l ON l.idLeccion=e.id_leccion LEFT JOIN secciones s ON s.idSeccion=l.id_modulo WHERE l.idLeccion IS NULL OR s.idSeccion IS NULL OR e.id_seccion<>s.idSeccion OR e.id_curso<>s.id_curso
UNION ALL SELECT 'calificaciones_contexto_inconsistente',COUNT(*) FROM calificaciones n LEFT JOIN lecciones l ON l.idLeccion=n.id_modulo LEFT JOIN secciones s ON s.idSeccion=l.id_modulo WHERE l.idLeccion IS NULL OR s.idSeccion IS NULL OR n.id_seccion<>s.idSeccion OR n.id_curso<>s.id_curso
UNION ALL SELECT 'evaluaciones_contexto_inconsistente',COUNT(*) FROM evaluaciones e LEFT JOIN secciones s ON s.idSeccion=e.id_seccion WHERE s.idSeccion IS NULL OR e.id_curso<>s.id_curso
UNION ALL SELECT 'asistencia_contexto_inconsistente',COUNT(*) FROM asistencia_clases a LEFT JOIN secciones s ON s.idSeccion=a.id_seccion WHERE s.idSeccion IS NULL OR a.id_curso<>s.id_curso
UNION ALL SELECT 'actividades_contexto_inconsistente',COUNT(*) FROM actividades a LEFT JOIN secciones s ON s.idSeccion=a.id_seccion LEFT JOIN cursos c ON c.idCurso=a.id_curso WHERE (a.id_seccion IS NOT NULL AND (s.idSeccion IS NULL OR a.id_curso IS NULL OR a.id_curso<>s.id_curso)) OR (a.id_curso IS NOT NULL AND c.idCurso IS NULL)
UNION ALL SELECT 'mensajes_sin_remitente',COUNT(*) FROM mensajes m LEFT JOIN usuarios u ON u.idUsuario=m.id_remitente WHERE u.idUsuario IS NULL
UNION ALL SELECT 'participantes_sin_mensaje',COUNT(*) FROM mensajes_participantes p LEFT JOIN mensajes m ON m.idMensaje=p.id_mensaje WHERE m.idMensaje IS NULL
UNION ALL SELECT 'adjuntos_sin_mensaje',COUNT(*) FROM mensajes_adjuntos a LEFT JOIN mensajes m ON m.idMensaje=a.id_mensaje WHERE m.idMensaje IS NULL;
