<?php
require_once __DIR__ . '/conexion.php';

/** Predicados comunes de aislamiento. Los identificadores SQL son constantes del código. */
class ModeloTenant
{
    public static function activo()
    {
        return defined('INSTITUCIONES_CONTEXTO_ACTIVO') && INSTITUCIONES_CONTEXTO_ACTIVO === true;
    }

    public static function id()
    {
        if (!self::activo()) { return 0; }
        if (!class_exists('ControladorInstitucion', false)
            || !ControladorInstitucion::refrescar(false)
            || ControladorInstitucion::id() <= 0) {
            throw new RuntimeException('Acceso institucional denegado.');
        }
        return (int) ControladorInstitucion::id();
    }

    public static function cursos($alias = 'c')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return $alias . '.id_institucion = ' . self::id() . ' AND ' . self::sesionActiva();
    }

    private static function identificador($valor)
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/D', $valor)) {
            throw new InvalidArgumentException('Identificador SQL inválido.');
        }
    }

    /** Revalida también en la sentencia ejecutada, no solo al construirla. */
    public static function sesionActiva()
    {
        if (!self::activo()) { return '1=1'; }
        $id = self::id();
        $usuario = (int) ($_SESSION['usuario']['id'] ?? 0);
        return 'EXISTS (SELECT 1 FROM usuarios_instituciones acceso_ui
            INNER JOIN usuarios acceso_u ON acceso_u.idUsuario=acceso_ui.id_usuario AND acceso_u.activo=1
            INNER JOIN instituciones acceso_i ON acceso_i.idInstitucion=acceso_ui.id_institucion AND acceso_i.activo=1
            WHERE acceso_ui.id_usuario=' . $usuario . ' AND acceso_ui.id_institucion=' . $id . ' AND acceso_ui.activo=1)';
    }

    public static function cursoId($id)
    {
        return self::activo() ? 'EXISTS (SELECT 1 FROM cursos alcance_c WHERE alcance_c.idCurso=' . (int)$id . ' AND ' . self::cursos('alcance_c') . ')' : '1=1';
    }

    public static function seccionId($id)
    {
        return self::activo() ? 'EXISTS (SELECT 1 FROM secciones alcance_s WHERE alcance_s.idSeccion=' . (int)$id . ' AND ' . self::secciones('alcance_s') . ')' : '1=1';
    }

    public static function lecciones($alias = 'l')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return 'EXISTS (SELECT 1 FROM secciones alcance_ls INNER JOIN cursos alcance_lc ON alcance_lc.idCurso=alcance_ls.id_curso
            WHERE alcance_ls.idSeccion=' . $alias . '.id_modulo AND ' . self::cursos('alcance_lc') . ')';
    }

    public static function leccionId($id)
    {
        return self::activo() ? 'EXISTS (SELECT 1 FROM lecciones alcance_l WHERE alcance_l.idLeccion=' . (int)$id . ' AND ' . self::lecciones('alcance_l') . ')' : '1=1';
    }

    public static function hijoLeccion($alias)
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return 'EXISTS (SELECT 1 FROM lecciones alcance_hl WHERE alcance_hl.idLeccion=' . $alias . '.id_leccion AND ' . self::lecciones('alcance_hl') . ')';
    }

    /** Una referencia redundante incoherente nunca permite leer datos de otra institución. */
    public static function entregas($alias = 'e')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return 'EXISTS (SELECT 1 FROM lecciones coherencia_l INNER JOIN secciones coherencia_s ON coherencia_s.idSeccion=coherencia_l.id_modulo
            INNER JOIN cursos coherencia_c ON coherencia_c.idCurso=coherencia_s.id_curso WHERE coherencia_l.idLeccion=' . $alias . '.id_leccion
            AND coherencia_s.idSeccion=' . $alias . '.id_seccion AND coherencia_c.idCurso=' . $alias . '.id_curso
            AND ' . self::cursos('coherencia_c') . ')
            AND ' . self::usuarioRelacionadoInstitucion($alias . '.id_estudiante');
    }

    public static function relacionLeccion($idLeccion, $idSeccion, $idCurso)
    {
        if (!self::activo()) { return '1=1'; }
        return 'EXISTS (SELECT 1 FROM lecciones relacion_l INNER JOIN secciones relacion_s ON relacion_s.idSeccion=relacion_l.id_modulo
            INNER JOIN cursos relacion_c ON relacion_c.idCurso=relacion_s.id_curso
            WHERE relacion_l.idLeccion=' . (int)$idLeccion . ($idSeccion === null ? '' : ' AND relacion_s.idSeccion=' . (int)$idSeccion) . '
            AND relacion_c.idCurso=' . (int)$idCurso . ' AND ' . self::cursos('relacion_c') . ')';
    }

    public static function posteos($alias = 'p')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return 'EXISTS (SELECT 1 FROM lecciones post_l INNER JOIN secciones post_s ON post_s.idSeccion=post_l.id_modulo
            INNER JOIN cursos post_c ON post_c.idCurso=post_s.id_curso WHERE post_l.idLeccion=' . $alias . '.id_leccion
            AND post_c.idCurso=' . $alias . '.id_curso AND ' . self::cursos('post_c') . ')
            AND ' . self::usuarioRelacionadoInstitucion($alias . '.id_autor');
    }

    public static function calificaciones($alias = 'calificaciones')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return 'EXISTS (SELECT 1 FROM secciones nota_s INNER JOIN cursos nota_c ON nota_c.idCurso=nota_s.id_curso
            WHERE nota_s.idSeccion=' . $alias . '.id_seccion AND nota_c.idCurso=' . $alias . '.id_curso
            AND ' . self::cursos('nota_c') . ' AND (COALESCE(' . $alias . '.id_modulo,0)=0 OR EXISTS
                (SELECT 1 FROM lecciones nota_l WHERE nota_l.idLeccion=' . $alias . '.id_modulo AND nota_l.id_modulo=nota_s.idSeccion)))
            AND ' . self::usuarioRelacionadoInstitucion($alias . '.id_estudiante');
    }

    /** Acepta membresías históricas inactivas para conservar la trazabilidad académica del tenant. */
    private static function usuarioRelacionadoInstitucion($expresionUsuario)
    {
        self::identificador($expresionUsuario);
        return 'EXISTS (SELECT 1 FROM usuarios_instituciones relacion_ui
            WHERE relacion_ui.id_usuario=' . $expresionUsuario . '
            AND relacion_ui.id_institucion=' . self::id() . ')';
    }

    public static function escrituraCalificacion(array $datos)
    {
        if (!self::activo()) { return '1=1'; }
        $idLeccion = (int)($datos['id_modulo'] ?? 0);
        $relacion = $idLeccion > 0
            ? self::relacionLeccion($idLeccion, $datos['id_seccion'], $datos['id_curso'])
            : 'EXISTS (SELECT 1 FROM secciones nota_es INNER JOIN cursos nota_ec ON nota_ec.idCurso=nota_es.id_curso
                WHERE nota_es.idSeccion=' . (int)$datos['id_seccion'] . ' AND nota_ec.idCurso=' . (int)$datos['id_curso'] . '
                AND ' . self::cursos('nota_ec') . ')';
        return $relacion . ' AND EXISTS (SELECT 1 FROM asignacioncursos nota_a
            INNER JOIN usuarios nota_u ON nota_u.idUsuario=nota_a.id_estudiante
            WHERE nota_a.id_estudiante=' . (int)$datos['id_estudiante'] . '
            AND nota_a.id_seccion=' . (int)$datos['id_curso'] . " AND nota_a.estadoInscripcion='ACTIVA'
            AND " . self::usuarioConRol('nota_u.idUsuario', ['ESTUDIANTE']) . ')';
    }

    public static function exigirCalificacion(array $datos)
    {
        if (!self::activo()) { return; }
        $stmt = Conexion::conectar()->query('SELECT 1 WHERE ' . self::escrituraCalificacion($datos));
        if (!$stmt || !$stmt->fetchColumn()) { throw new RuntimeException('Acceso institucional denegado.'); }
    }

    public static function asistenciaClases($alias = 'ac')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return 'EXISTS (SELECT 1 FROM secciones asistencia_s
            INNER JOIN cursos asistencia_c ON asistencia_c.idCurso=asistencia_s.id_curso
            WHERE asistencia_s.idSeccion=' . $alias . '.id_seccion
            AND asistencia_c.idCurso=' . $alias . '.id_curso
            AND ' . self::cursos('asistencia_c') . ')';
    }

    public static function asistenciaRegistros($alias = 'ar')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return 'EXISTS (SELECT 1 FROM asistencia_clases asistencia_ac
            WHERE asistencia_ac.idClase=' . $alias . '.id_clase
            AND ' . self::asistenciaClases('asistencia_ac') . ')';
    }

    public static function ciclosLectivos($alias = 'cl')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return $alias . '.id_institucion=' . self::id() . ' AND ' . self::sesionActiva();
    }

    public static function instrumentosEvaluacion($alias = 'ie')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return $alias . '.id_institucion=' . self::id() . ' AND ' . self::sesionActiva();
    }

    public static function periodos($alias = 'p')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return 'EXISTS (SELECT 1 FROM ciclos_lectivos periodo_cl WHERE periodo_cl.idCicloLectivo=' . $alias . '.id_ciclo
            AND ' . self::ciclosLectivos('periodo_cl') . ')';
    }

    public static function evaluaciones($alias = 'ev')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return 'EXISTS (SELECT 1 FROM secciones evaluacion_s
            INNER JOIN cursos evaluacion_c ON evaluacion_c.idCurso=evaluacion_s.id_curso
            WHERE evaluacion_s.idSeccion=' . $alias . '.id_seccion
            AND evaluacion_c.idCurso=' . $alias . '.id_curso AND ' . self::cursos('evaluacion_c') . '
            AND ('. $alias . '.id_periodo IS NULL OR EXISTS (SELECT 1 FROM periodos_calificacion evaluacion_p
                WHERE evaluacion_p.idPeriodo=' . $alias . '.id_periodo AND evaluacion_p.id_ciclo=evaluacion_c.id_ciclo_lectivo
                AND ' . self::periodos('evaluacion_p') . '))
            AND (' . $alias . '.id_instrumento IS NULL OR EXISTS (SELECT 1 FROM instrumentos_evaluacion evaluacion_i
                WHERE evaluacion_i.idInstrumento=' . $alias . '.id_instrumento AND ' . self::instrumentosEvaluacion('evaluacion_i') . ')))';
    }

    public static function calificacionesEvaluacion($alias = 'ec')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return 'EXISTS (SELECT 1 FROM evaluaciones evaluacion_ec WHERE evaluacion_ec.idEvaluacion=' . $alias . '.id_evaluacion
            AND ' . self::evaluaciones('evaluacion_ec') . ')';
    }

    public static function cierresPeriodo($alias = 'cp')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return 'EXISTS (SELECT 1 FROM secciones cierre_s INNER JOIN cursos cierre_c ON cierre_c.idCurso=cierre_s.id_curso
            INNER JOIN periodos_calificacion cierre_p ON cierre_p.idPeriodo=' . $alias . '.id_periodo
            WHERE cierre_s.idSeccion=' . $alias . '.id_seccion AND cierre_p.id_ciclo=cierre_c.id_ciclo_lectivo
            AND ' . self::cursos('cierre_c') . ' AND ' . self::periodos('cierre_p') . ')';
    }

    public static function estadoPeriodoSeccion($alias = 'pe')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return 'EXISTS (SELECT 1 FROM secciones estado_s INNER JOIN cursos estado_c ON estado_c.idCurso=estado_s.id_curso
            INNER JOIN periodos_calificacion estado_p ON estado_p.idPeriodo=' . $alias . '.id_periodo
            WHERE estado_s.idSeccion=' . $alias . '.id_seccion AND estado_p.id_ciclo=estado_c.id_ciclo_lectivo
            AND ' . self::cursos('estado_c') . ' AND ' . self::periodos('estado_p') . ')';
    }

    public static function actividades($alias = 'a')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return $alias . '.id_institucion=' . self::id() . ' AND ' . self::sesionActiva() . '
            AND ((' . $alias . '.id_curso IS NULL AND ' . $alias . '.id_seccion IS NULL)
                OR EXISTS (SELECT 1 FROM cursos actividad_c WHERE actividad_c.idCurso=' . $alias . '.id_curso
                    AND ' . self::cursos('actividad_c') . ' AND (' . $alias . '.id_seccion IS NULL OR EXISTS
                        (SELECT 1 FROM secciones actividad_s WHERE actividad_s.idSeccion=' . $alias . '.id_seccion
                            AND actividad_s.id_curso=actividad_c.idCurso))))';
    }

    /** Alcance deliberadamente público: no requiere membresía, pero sí tenant activo y relaciones coherentes. */
    public static function actividadesPublicas($alias = 'a')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return 'EXISTS (SELECT 1 FROM instituciones actividad_i WHERE actividad_i.idInstitucion=' . $alias . '.id_institucion AND actividad_i.activo=1)
            AND ((' . $alias . '.id_curso IS NULL AND ' . $alias . '.id_seccion IS NULL)
                OR EXISTS (SELECT 1 FROM cursos actividad_pc WHERE actividad_pc.idCurso=' . $alias . '.id_curso
                    AND actividad_pc.id_institucion=' . $alias . '.id_institucion
                    AND (' . $alias . '.id_seccion IS NULL OR EXISTS (SELECT 1 FROM secciones actividad_ps
                        WHERE actividad_ps.idSeccion=' . $alias . '.id_seccion AND actividad_ps.id_curso=actividad_pc.idCurso))))';
    }

    public static function preguntasActividad($alias = 'ap', $publica = false)
    {
        self::identificador($alias);
        $alcance = $publica ? self::actividadesPublicas('pregunta_a') : self::actividades('pregunta_a');
        return 'EXISTS (SELECT 1 FROM actividades pregunta_a WHERE pregunta_a.idActividad=' . $alias . '.id_actividad AND ' . $alcance
            . ($publica ? " AND pregunta_a.estadoActividad='PUBLICADA' AND pregunta_a.visibilidad IN ('publica','oculta')" : '') . ')';
    }

    public static function intentosActividad($alias = 'ai', $publica = false)
    {
        self::identificador($alias);
        $alcance = $publica ? self::actividadesPublicas('intento_a') : self::actividades('intento_a');
        return 'EXISTS (SELECT 1 FROM actividades intento_a WHERE intento_a.idActividad=' . $alias . '.id_actividad AND ' . $alcance . ')';
    }

    public static function mensajes($alias = 'm')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return $alias . '.id_institucion=' . self::id() . ' AND ' . self::sesionActiva();
    }

    public static function mensajeId($idMensaje)
    {
        return self::activo()
            ? 'EXISTS (SELECT 1 FROM mensajes mensaje_tenant WHERE mensaje_tenant.idMensaje=' . (int)$idMensaje . ' AND ' . self::mensajes('mensaje_tenant') . ')'
            : '1=1';
    }

    public static function participanteMensaje($alias = 'mp')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return 'EXISTS (SELECT 1 FROM mensajes mensaje_p WHERE mensaje_p.idMensaje=' . $alias . '.id_mensaje AND ' . self::mensajes('mensaje_p') . ')';
    }

    public static function notificaciones($alias = 'n')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return $alias . '.id_institucion=' . self::id() . ' AND ' . self::sesionActiva();
    }

    public static function lecturasNotificaciones($alias = 'nl')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return $alias . '.id_institucion=' . self::id() . ' AND ' . self::sesionActiva();
    }

    public static function exigirParticipanteMensaje($idMensaje, $idUsuario)
    {
        if (!self::activo()) { return; }
        $stmt=Conexion::conectar()->prepare('SELECT 1 FROM mensajes_participantes mp INNER JOIN mensajes m ON m.idMensaje=mp.id_mensaje
            WHERE mp.id_mensaje=? AND mp.id_usuario=? AND mp.eliminado=0 AND ' . self::mensajes('m'));
        $stmt->execute([(int)$idMensaje,(int)$idUsuario]);
        if(!$stmt->fetchColumn()){throw new RuntimeException('Acceso institucional denegado.');}
    }

    public static function adjuntosEntrega($alias = 'entregaslecciones_adjuntos')
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($alias);
        return 'EXISTS (SELECT 1 FROM entregaslecciones adj_e WHERE adj_e.idEntregaLeccion=' . $alias . '.id_entrega AND ' . self::entregas('adj_e') . ')';
    }

    public static function secciones($alias = 's')
    {
        self::identificador($alias);
        return self::activo()
            ? 'EXISTS (SELECT 1 FROM cursos tenant_c WHERE tenant_c.idCurso = ' . $alias . '.id_curso AND ' . self::cursos('tenant_c') . ')'
            : '1=1';
    }

    public static function exigirCurso($idCurso)
    {
        if (!self::activo()) { return; }
        $stmt = Conexion::conectar()->prepare('SELECT 1 FROM cursos c WHERE c.idCurso = ? AND ' . self::cursos());
        $stmt->execute([(int) $idCurso]);
        if (!$stmt->fetchColumn()) { throw new RuntimeException('Acceso institucional denegado.'); }
    }

    public static function exigirSeccion($idSeccion)
    {
        if (!self::activo()) { return; }
        $stmt = Conexion::conectar()->prepare('SELECT 1 FROM secciones s WHERE s.idSeccion = ? AND ' . self::secciones());
        $stmt->execute([(int) $idSeccion]);
        if (!$stmt->fetchColumn()) { throw new RuntimeException('Acceso institucional denegado.'); }
    }

    public static function exigirLeccion($idLeccion, $idSeccion = null, $idCurso = null)
    {
        if (!self::activo()) { return; }
        $stmt = Conexion::conectar()->prepare('SELECT l.id_modulo, s.id_curso FROM lecciones l
            INNER JOIN secciones s ON s.idSeccion=l.id_modulo
            INNER JOIN cursos c ON c.idCurso=s.id_curso
            WHERE l.idLeccion=? AND ' . self::cursos());
        $stmt->execute([(int) $idLeccion]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila || ($idSeccion !== null && (int)$fila['id_modulo'] !== (int)$idSeccion)
            || ($idCurso !== null && (int)$fila['id_curso'] !== (int)$idCurso)) {
            throw new RuntimeException('Acceso institucional denegado.');
        }
    }

    public static function exigirRecurso($idRecurso)
    {
        if (!self::activo()) { return; }
        $stmt = Conexion::conectar()->prepare('SELECT id_leccion FROM recursoslecciones WHERE idRecursoLeccion=?');
        $stmt->execute([(int)$idRecurso]);
        self::exigirLeccion((int)$stmt->fetchColumn());
    }

    public static function exigirEntrega($idEntrega)
    {
        if (!self::activo()) { return; }
        $stmt = Conexion::conectar()->prepare('SELECT id_leccion,id_seccion,id_curso FROM entregaslecciones WHERE idEntregaLeccion=?');
        $stmt->execute([(int)$idEntrega]);
        $fila=$stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila) { throw new RuntimeException('Acceso institucional denegado.'); }
        self::exigirLeccion($fila['id_leccion'],$fila['id_seccion'],$fila['id_curso']);
    }

    public static function exigirClaseAsistencia($idClase, $idSeccion = null)
    {
        if (!self::activo()) { return; }
        $stmt = Conexion::conectar()->prepare('SELECT ac.id_seccion FROM asistencia_clases ac
            WHERE ac.idClase=? AND ' . self::asistenciaClases('ac'));
        $stmt->execute([(int)$idClase]);
        $seccion = $stmt->fetchColumn();
        if ($seccion === false || ($idSeccion !== null && (int)$seccion !== (int)$idSeccion)) {
            throw new RuntimeException('Acceso institucional denegado.');
        }
    }

    public static function exigirPeriodoSeccion($idPeriodo, $idSeccion)
    {
        if (!self::activo()) { return; }
        self::exigirSeccion($idSeccion);
        $stmt = Conexion::conectar()->prepare('SELECT 1 FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso
            INNER JOIN periodos_calificacion p ON p.idPeriodo=? AND p.id_ciclo=c.id_ciclo_lectivo
            WHERE s.idSeccion=? AND ' . self::cursos('c') . ' AND ' . self::periodos('p'));
        $stmt->execute([(int)$idPeriodo, (int)$idSeccion]);
        if (!$stmt->fetchColumn()) { throw new RuntimeException('Acceso institucional denegado.'); }
    }

    public static function periodoSeccionId($idPeriodo, $idSeccion)
    {
        if (!self::activo()) { return '1=1'; }
        return 'EXISTS (SELECT 1 FROM secciones periodo_s INNER JOIN cursos periodo_c ON periodo_c.idCurso=periodo_s.id_curso
            INNER JOIN periodos_calificacion periodo_p ON periodo_p.idPeriodo=' . (int)$idPeriodo . ' AND periodo_p.id_ciclo=periodo_c.id_ciclo_lectivo
            WHERE periodo_s.idSeccion=' . (int)$idSeccion . ' AND ' . self::cursos('periodo_c') . '
            AND ' . self::periodos('periodo_p') . ')';
    }

    public static function exigirInstrumentoEvaluacion($idInstrumento)
    {
        if (!self::activo()) { return; }
        $stmt = Conexion::conectar()->prepare('SELECT 1 FROM instrumentos_evaluacion ie WHERE ie.idInstrumento=? AND ' . self::instrumentosEvaluacion('ie'));
        $stmt->execute([(int)$idInstrumento]);
        if (!$stmt->fetchColumn()) { throw new RuntimeException('Acceso institucional denegado.'); }
    }

    public static function exigirSeccionCurso($idSeccion, $idCurso)
    {
        if (!self::activo()) { return; }
        $stmt = Conexion::conectar()->prepare('SELECT 1 FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso
            WHERE s.idSeccion=? AND c.idCurso=? AND ' . self::cursos('c'));
        $stmt->execute([(int)$idSeccion,(int)$idCurso]);
        if (!$stmt->fetchColumn()) { throw new RuntimeException('Acceso institucional denegado.'); }
    }

    public static function exigirEvaluacion($idEvaluacion)
    {
        if (!self::activo()) { return; }
        $stmt = Conexion::conectar()->prepare('SELECT 1 FROM evaluaciones ev WHERE ev.idEvaluacion=? AND ' . self::evaluaciones('ev'));
        $stmt->execute([(int)$idEvaluacion]);
        if (!$stmt->fetchColumn()) { throw new RuntimeException('Acceso institucional denegado.'); }
    }

    public static function exigirActividad($idActividad)
    {
        if (!self::activo()) { return; }
        $stmt=Conexion::conectar()->prepare('SELECT 1 FROM actividades a WHERE a.idActividad=? AND '.self::actividades('a'));
        $stmt->execute([(int)$idActividad]);
        if(!$stmt->fetchColumn()){throw new RuntimeException('Acceso institucional denegado.');}
    }

    public static function actividadId($idActividad)
    {
        return self::activo() ? 'EXISTS (SELECT 1 FROM actividades actividad_id WHERE actividad_id.idActividad='.(int)$idActividad.' AND '.self::actividades('actividad_id').')' : '1=1';
    }

    public static function actividadPublicaId($idActividad)
    {
        if (!self::activo()) { return '1=1'; }
        return 'EXISTS (SELECT 1 FROM actividades actividad_publica WHERE actividad_publica.idActividad='.(int)$idActividad.
            " AND actividad_publica.estadoActividad='PUBLICADA' AND actividad_publica.visibilidad IN ('publica','oculta')
            AND actividad_publica.permiteVisitantes=1 AND ".self::actividadesPublicas('actividad_publica').')';
    }

    public static function exigirActividadPublica($idActividad)
    {
        if(!self::activo()){return;}
        $stmt=Conexion::conectar()->query('SELECT 1 WHERE '.self::actividadPublicaId($idActividad));
        if(!$stmt||!$stmt->fetchColumn()){throw new RuntimeException('Acceso institucional denegado.');}
    }

    public static function exigirInscripcion($idEstudiante, $idCurso)
    {
        if (!self::activo()) { return; }
        self::exigirCurso($idCurso);
        self::exigirUsuario($idEstudiante, ['ESTUDIANTE']);
        $stmt=Conexion::conectar()->prepare("SELECT 1 FROM asignacioncursos WHERE id_estudiante=? AND id_seccion=? AND estadoInscripcion='ACTIVA'");
        $stmt->execute([(int)$idEstudiante,(int)$idCurso]);
        if (!$stmt->fetchColumn()) { throw new RuntimeException('Inscripción institucional no válida.'); }
    }

    public static function escrituraEntrega(array $datos)
    {
        if (!self::activo()) { return '1=1'; }
        return self::relacionLeccion($datos['id_leccion'],$datos['id_seccion'],$datos['id_curso']) . '
            AND EXISTS (SELECT 1 FROM asignacioncursos entrega_a INNER JOIN usuarios entrega_u ON entrega_u.idUsuario=entrega_a.id_estudiante
            WHERE entrega_a.id_estudiante=' . (int)$datos['id_estudiante'] . ' AND entrega_a.id_seccion=' . (int)$datos['id_curso'] . " AND entrega_a.estadoInscripcion='ACTIVA' AND " . self::usuarioConRol('entrega_u.idUsuario',['ESTUDIANTE']) . ')';
    }

    public static function usuarioConRol($columna, array $roles)
    {
        if (!self::activo()) { return '1=1'; }
        self::identificador($columna);
        $permitidos = ['ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE'];
        if (!$roles || array_diff($roles, $permitidos)) { throw new InvalidArgumentException('Rol inválido.'); }
        $lista = "'" . implode("','", $roles) . "'";
        return 'EXISTS (SELECT 1 FROM usuarios_instituciones tenant_ui
            INNER JOIN instituciones tenant_i ON tenant_i.idInstitucion=tenant_ui.id_institucion AND tenant_i.activo=1
            INNER JOIN usuarios tenant_u ON tenant_u.idUsuario=tenant_ui.id_usuario AND tenant_u.activo=1
            INNER JOIN usuarios_instituciones_roles tenant_ur ON tenant_ur.id_usuario_institucion=tenant_ui.idUsuarioInstitucion
            INNER JOIN roles tenant_r ON tenant_r.idRol=tenant_ur.id_rol
            WHERE tenant_ui.id_usuario=' . $columna . ' AND tenant_ui.activo=1
            AND tenant_ui.id_institucion=' . self::id() . ' AND tenant_r.codigo IN (' . $lista . '))';
    }

    public static function exigirUsuario($idUsuario, array $roles)
    {
        if (!self::activo()) { return; }
        $stmt = Conexion::conectar()->prepare('SELECT 1 FROM usuarios u WHERE u.idUsuario=? AND ' . self::usuarioConRol('u.idUsuario', $roles));
        $stmt->execute([(int) $idUsuario]);
        if (!$stmt->fetchColumn()) { throw new RuntimeException('Membresía o rol institucional no válido.'); }
    }

    public static function usuarioIdConRol($idUsuario, array $roles)
    {
        return self::activo() ? 'EXISTS (SELECT 1 FROM usuarios rol_u WHERE rol_u.idUsuario=' . (int)$idUsuario . ' AND ' . self::usuarioConRol('rol_u.idUsuario',$roles) . ')' : '1=1';
    }
}
