-- SOLO LECTURA. Para una instalación con las migraciones académicas actuales.
-- Devuelve conteos, no contraseñas, correos ni datos personales.
-- Un valor > 0 requiere revisar las filas antes de imponer relaciones estrictas.
SELECT 'emails_duplicados_normalizados' AS comprobacion, COUNT(*) AS incidencias
FROM (SELECT LOWER(TRIM(email)) FROM usuarios GROUP BY LOWER(TRIM(email)) HAVING COUNT(*)>1) d
UNION ALL SELECT 'emails_vacios',COUNT(*) FROM usuarios WHERE TRIM(email)=''
UNION ALL SELECT 'roles_sin_equivalencia',COUNT(*) FROM usuarios WHERE UPPER(TRIM(rol)) NOT IN ('ADMINISTRADOR','DOCENTE','ESTUDIANTE')
UNION ALL SELECT 'perfiles_sin_usuario',COUNT(*) FROM perfiles p LEFT JOIN usuarios u ON u.idUsuario=p.id_usuario WHERE u.idUsuario IS NULL
UNION ALL SELECT 'cursos_creador_sin_usuario',COUNT(*) FROM cursos c LEFT JOIN usuarios u ON u.idUsuario=c.creadoPor WHERE COALESCE(c.creadoPor,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'cursos_responsable_sin_usuario',COUNT(*) FROM cursos c LEFT JOIN usuarios u ON u.idUsuario=c.responsable WHERE COALESCE(c.responsable,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'secciones_sin_curso',COUNT(*) FROM secciones s LEFT JOIN cursos c ON c.idCurso=s.id_curso WHERE c.idCurso IS NULL
UNION ALL SELECT 'secciones_docente_sin_usuario',COUNT(*) FROM secciones s LEFT JOIN usuarios u ON u.idUsuario=s.docente WHERE COALESCE(s.docente,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'secciones_tutor_sin_usuario',COUNT(*) FROM secciones s LEFT JOIN usuarios u ON u.idUsuario=s.tutor WHERE COALESCE(s.tutor,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'secciones_creador_sin_usuario',COUNT(*) FROM secciones s LEFT JOIN usuarios u ON u.idUsuario=s.creadoPor WHERE COALESCE(s.creadoPor,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'lecciones_sin_seccion',COUNT(*) FROM lecciones l LEFT JOIN secciones s ON s.idSeccion=l.id_modulo WHERE s.idSeccion IS NULL
UNION ALL SELECT 'inscripciones_sin_curso',COUNT(*) FROM asignacioncursos a LEFT JOIN cursos c ON c.idCurso=a.id_seccion WHERE c.idCurso IS NULL
UNION ALL SELECT 'inscripciones_sin_usuario',COUNT(*) FROM asignacioncursos a LEFT JOIN usuarios u ON u.idUsuario=a.id_estudiante WHERE u.idUsuario IS NULL
UNION ALL SELECT 'entregas_sin_usuario',COUNT(*) FROM entregaslecciones e LEFT JOIN usuarios u ON u.idUsuario=e.id_estudiante WHERE u.idUsuario IS NULL
UNION ALL SELECT 'entregas_contexto_inconsistente',COUNT(*) FROM entregaslecciones e LEFT JOIN lecciones l ON l.idLeccion=e.id_leccion LEFT JOIN secciones s ON s.idSeccion=l.id_modulo WHERE l.idLeccion IS NULL OR s.idSeccion IS NULL OR e.id_seccion<>s.idSeccion OR e.id_curso<>s.id_curso
UNION ALL SELECT 'calificaciones_sin_usuario',COUNT(*) FROM calificaciones n LEFT JOIN usuarios u ON u.idUsuario=n.id_estudiante WHERE u.idUsuario IS NULL
UNION ALL SELECT 'calificaciones_contexto_inconsistente',COUNT(*) FROM calificaciones n LEFT JOIN lecciones l ON l.idLeccion=n.id_modulo LEFT JOIN secciones s ON s.idSeccion=l.id_modulo WHERE l.idLeccion IS NULL OR s.idSeccion IS NULL OR n.id_seccion<>s.idSeccion OR n.id_curso<>s.id_curso
UNION ALL SELECT 'evaluaciones_autor_sin_usuario',COUNT(*) FROM evaluaciones e LEFT JOIN usuarios u ON u.idUsuario=e.id_autor WHERE u.idUsuario IS NULL
UNION ALL SELECT 'evaluaciones_contexto_inconsistente',COUNT(*) FROM evaluaciones e LEFT JOIN secciones s ON s.idSeccion=e.id_seccion WHERE s.idSeccion IS NULL OR e.id_curso<>s.id_curso
UNION ALL SELECT 'evaluaciones_calificaciones_sin_usuario',COUNT(*) FROM evaluaciones_calificaciones ec LEFT JOIN usuarios u ON u.idUsuario=ec.id_estudiante WHERE u.idUsuario IS NULL
UNION ALL SELECT 'cierres_estudiante_sin_usuario',COUNT(*) FROM cierres_periodo_calificaciones cp LEFT JOIN usuarios u ON u.idUsuario=cp.id_estudiante WHERE u.idUsuario IS NULL
UNION ALL SELECT 'cierres_actualizador_sin_usuario',COUNT(*) FROM cierres_periodo_calificaciones cp LEFT JOIN usuarios u ON u.idUsuario=cp.actualizadoPor WHERE COALESCE(cp.actualizadoPor,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'periodos_seccion_cerrador_sin_usuario',COUNT(*) FROM periodos_seccion_estado pe LEFT JOIN usuarios u ON u.idUsuario=pe.cerradoPor WHERE COALESCE(pe.cerradoPor,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'periodos_seccion_reabridor_sin_usuario',COUNT(*) FROM periodos_seccion_estado pe LEFT JOIN usuarios u ON u.idUsuario=pe.reabiertoPor WHERE COALESCE(pe.reabiertoPor,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'asistencia_contexto_inconsistente',COUNT(*) FROM asistencia_clases a LEFT JOIN secciones s ON s.idSeccion=a.id_seccion WHERE s.idSeccion IS NULL OR a.id_curso<>s.id_curso
UNION ALL SELECT 'asistencia_creador_sin_usuario',COUNT(*) FROM asistencia_clases a LEFT JOIN usuarios u ON u.idUsuario=a.creadaPor WHERE u.idUsuario IS NULL
UNION ALL SELECT 'asistencia_estudiante_sin_usuario',COUNT(*) FROM asistencia_registros ar LEFT JOIN usuarios u ON u.idUsuario=ar.id_estudiante WHERE u.idUsuario IS NULL
UNION ALL SELECT 'asistencia_actualizador_sin_usuario',COUNT(*) FROM asistencia_registros ar LEFT JOIN usuarios u ON u.idUsuario=ar.actualizadoPor WHERE COALESCE(ar.actualizadoPor,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'actividades_contexto_inconsistente',COUNT(*) FROM actividades a LEFT JOIN secciones s ON s.idSeccion=a.id_seccion LEFT JOIN cursos c ON c.idCurso=a.id_curso WHERE (a.id_seccion IS NOT NULL AND (s.idSeccion IS NULL OR a.id_curso IS NULL OR a.id_curso<>s.id_curso)) OR (a.id_curso IS NOT NULL AND c.idCurso IS NULL)
UNION ALL SELECT 'actividades_autor_sin_usuario',COUNT(*) FROM actividades a LEFT JOIN usuarios u ON u.idUsuario=a.id_autor WHERE u.idUsuario IS NULL
UNION ALL SELECT 'actividades_intentos_sin_usuario',COUNT(*) FROM actividades_intentos ai LEFT JOIN usuarios u ON u.idUsuario=ai.id_usuario WHERE COALESCE(ai.id_usuario,0)>0 AND u.idUsuario IS NULL
UNION ALL SELECT 'posteos_autor_sin_usuario',COUNT(*) FROM posteos p LEFT JOIN usuarios u ON u.idUsuario=p.id_autor WHERE u.idUsuario IS NULL
UNION ALL SELECT 'recursos_creador_sin_usuario',COUNT(*) FROM recursoslecciones r LEFT JOIN usuarios u ON u.idUsuario=r.creadoPor WHERE u.idUsuario IS NULL
UNION ALL SELECT 'mensajes_sin_remitente',COUNT(*) FROM mensajes m LEFT JOIN usuarios u ON u.idUsuario=m.id_remitente WHERE u.idUsuario IS NULL
UNION ALL SELECT 'mensajes_sin_destinatario',COUNT(*) FROM mensajes m LEFT JOIN usuarios u ON u.idUsuario=m.id_destinatario WHERE u.idUsuario IS NULL
UNION ALL SELECT 'participantes_sin_mensaje',COUNT(*) FROM mensajes_participantes p LEFT JOIN mensajes m ON m.idMensaje=p.id_mensaje WHERE m.idMensaje IS NULL
UNION ALL SELECT 'participantes_sin_usuario',COUNT(*) FROM mensajes_participantes p LEFT JOIN usuarios u ON u.idUsuario=p.id_usuario WHERE u.idUsuario IS NULL
UNION ALL SELECT 'notificaciones_sin_usuario',COUNT(*) FROM notificaciones n LEFT JOIN usuarios u ON u.idUsuario=n.id_usuario WHERE u.idUsuario IS NULL
UNION ALL SELECT 'lecturas_notificacion_sin_usuario',COUNT(*) FROM notificaciones_lecturas nl LEFT JOIN usuarios u ON u.idUsuario=nl.id_usuario WHERE u.idUsuario IS NULL
UNION ALL SELECT 'historial_objetivo_sin_usuario',COUNT(*) FROM usuarios_historial h LEFT JOIN usuarios u ON u.idUsuario=h.id_usuario WHERE u.idUsuario IS NULL
UNION ALL SELECT 'historial_actor_sin_usuario',COUNT(*) FROM usuarios_historial h LEFT JOIN usuarios u ON u.idUsuario=h.id_usuario_accion WHERE u.idUsuario IS NULL
UNION ALL SELECT 'adjuntos_sin_mensaje',COUNT(*) FROM mensajes_adjuntos a LEFT JOIN mensajes m ON m.idMensaje=a.id_mensaje WHERE m.idMensaje IS NULL;
