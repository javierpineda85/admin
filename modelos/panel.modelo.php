<?php
require_once('conexion.php');
require_once('mensajes.modelo.php');
require_once('notificaciones.modelo.php');

class ModeloPanel
{
    private static $tablaLecturasPreparada = false;

    private static function normalizarRol($rol)
    {
        return strtoupper(trim((string) $rol));
    }

    private static function contar($sql, array $params = [])
    {
        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $clave => $valor) {
            $tipo = is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($clave, $valor, $tipo);
        }
        $stmt->execute();
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($fila['total'] ?? 0);
    }

    private static function listar($sql, array $params = [])
    {
        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($params as $clave => $valor) {
            $tipo = is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($clave, $valor, $tipo);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function tarjetasPorRol($rol, $idUsuario)
    {
        $rol = self::normalizarRol($rol);

        if ($rol === 'ADMINISTRADOR') {
            $usuariosActivos = self::contar('SELECT COUNT(*) AS total FROM usuarios WHERE activo = 1');
            $usuariosConectados = self::contar('SELECT COUNT(*) AS total FROM usuarios WHERE activo = 1 AND ultimaConexion >= (NOW() - INTERVAL 60 MINUTE)');
            $cursos = self::contar('SELECT COUNT(*) AS total FROM cursos');
            $secciones = self::contar('SELECT COUNT(*) AS total FROM secciones');
            $mensajes = self::contar(
                'SELECT COUNT(*) AS total
                 FROM mensajes_participantes
                 WHERE id_usuario = :idUsuario
                   AND rolParticipante = "DESTINATARIO"
                   AND leido = 0
                   AND enPapelera = 0
                   AND eliminado = 0',
                [':idUsuario' => $idUsuario]
            );
            $pendientes = self::contar(
                'SELECT COUNT(*) AS total
                 FROM entregaslecciones e
                 LEFT JOIN calificaciones c
                   ON c.id_estudiante = e.id_estudiante
                  AND c.id_seccion = e.id_seccion
                  AND c.id_modulo = e.id_leccion
                 WHERE c.idCalificacion IS NULL'
            );

            return [
                [
                    'label' => 'Usuarios activos',
                    'value' => $usuariosActivos,
                    'note' => 'Cuentas habilitadas',
                    'icon' => 'fas fa-users',
                    'class' => 'bg-info',
                ],
                [
                    'label' => 'Conectados 60m',
                    'value' => $usuariosConectados,
                    'note' => 'Última conexión',
                    'icon' => 'fas fa-signal',
                    'class' => 'bg-dark',
                ],
                [
                    'label' => 'Cursos',
                    'value' => $cursos,
                    'note' => 'Oferta académica',
                    'icon' => 'fas fa-layer-group',
                    'class' => 'bg-primary',
                ],
                [
                    'label' => 'Secciones',
                    'value' => $secciones,
                    'note' => 'Aulas publicadas',
                    'icon' => 'fas fa-chalkboard-teacher',
                    'class' => 'bg-success',
                ],
                [
                    'label' => 'Mensajes',
                    'value' => $mensajes,
                    'note' => 'Recibidos en tu bandeja',
                    'icon' => 'fas fa-comments',
                    'class' => 'bg-warning',
                ],
                [
                    'label' => 'Pendientes',
                    'value' => $pendientes,
                    'note' => 'Entregas sin calificar',
                    'icon' => 'fas fa-clipboard-check',
                    'class' => 'bg-danger',
                ],
            ];
        }

        if ($rol === 'DOCENTE') {
            $secciones = self::contar(
                'SELECT COUNT(DISTINCT s.idSeccion) AS total
                 FROM secciones s
                 WHERE s.docente = :idUsuario OR s.tutor = :idUsuario',
                [':idUsuario' => $idUsuario]
            );
            $lecciones = self::contar(
                'SELECT COUNT(DISTINCT l.idLeccion) AS total
                 FROM lecciones l
                 INNER JOIN secciones s ON s.idSeccion = l.id_modulo
                 WHERE s.docente = :idUsuario OR s.tutor = :idUsuario',
                [':idUsuario' => $idUsuario]
            );
            $pendientes = self::contar(
                'SELECT COUNT(*) AS total
                 FROM entregaslecciones e
                 INNER JOIN secciones s ON s.idSeccion = e.id_seccion
                 LEFT JOIN calificaciones c
                   ON c.id_estudiante = e.id_estudiante
                  AND c.id_seccion = e.id_seccion
                  AND c.id_modulo = e.id_leccion
                 WHERE (s.docente = :idUsuario OR s.tutor = :idUsuario)
                   AND c.idCalificacion IS NULL',
                [':idUsuario' => $idUsuario]
            );
            $mensajes = self::contar(
                'SELECT COUNT(*) AS total
                 FROM mensajes_participantes
                 WHERE id_usuario = :idUsuario
                   AND rolParticipante = "DESTINATARIO"
                   AND leido = 0
                   AND enPapelera = 0
                   AND eliminado = 0',
                [':idUsuario' => $idUsuario]
            );
            $promedio = self::listar(
                'SELECT COALESCE(ROUND(AVG(c.calificacion), 2), 0) AS total
                 FROM calificaciones c
                 INNER JOIN secciones s ON s.idSeccion = c.id_seccion
                 WHERE s.docente = :idUsuario OR s.tutor = :idUsuario',
                [':idUsuario' => $idUsuario]
            );

            return [
                [
                    'label' => 'Secciones a cargo',
                    'value' => $secciones,
                    'note' => 'Aulas activas',
                    'icon' => 'fas fa-chalkboard',
                    'class' => 'bg-primary',
                ],
                [
                    'label' => 'Lecciones',
                    'value' => $lecciones,
                    'note' => 'Materiales y actividades',
                    'icon' => 'fas fa-book-open',
                    'class' => 'bg-success',
                ],
                [
                    'label' => 'Pendientes',
                    'value' => $pendientes,
                    'note' => 'Entregas por corregir',
                    'icon' => 'fas fa-hourglass-half',
                    'class' => 'bg-warning',
                ],
                [
                    'label' => 'Mensajes',
                    'value' => $mensajes,
                    'note' => 'Bandeja de entrada',
                    'icon' => 'fas fa-comments',
                    'class' => 'bg-info',
                ],
                [
                    'label' => 'Promedio',
                    'value' => $promedio[0]['total'] ?? 0,
                    'note' => 'Notas registradas',
                    'icon' => 'fas fa-chart-line',
                    'class' => 'bg-danger',
                ],
            ];
        }

        $cursosAsignados = self::contar(
            'SELECT COUNT(DISTINCT a.id_seccion) AS total
             FROM asignacioncursos a
             WHERE a.id_estudiante = :idUsuario',
            [':idUsuario' => $idUsuario]
        );
        $lecciones = self::contar(
            'SELECT COUNT(DISTINCT l.idLeccion) AS total
             FROM lecciones l
             INNER JOIN asignacioncursos a ON a.id_seccion = l.id_modulo
             WHERE a.id_estudiante = :idUsuario',
            [':idUsuario' => $idUsuario]
        );
        $entregas = self::contar(
            'SELECT COUNT(*) AS total
             FROM entregaslecciones
             WHERE id_estudiante = :idUsuario',
            [':idUsuario' => $idUsuario]
        );
        $mensajes = self::contar(
            'SELECT COUNT(*) AS total
             FROM mensajes_participantes
             WHERE id_usuario = :idUsuario
               AND rolParticipante = "DESTINATARIO"
               AND leido = 0
               AND enPapelera = 0
               AND eliminado = 0',
            [':idUsuario' => $idUsuario]
        );
        $promedio = self::listar(
            'SELECT COALESCE(ROUND(AVG(calificacion), 2), 0) AS total
             FROM calificaciones
             WHERE id_estudiante = :idUsuario',
            [':idUsuario' => $idUsuario]
        );

        return [
            [
                'label' => 'Cursos',
                'value' => $cursosAsignados,
                'note' => 'Aulas inscriptas',
                'icon' => 'fas fa-layer-group',
                'class' => 'bg-primary',
            ],
            [
                'label' => 'Lecciones',
                'value' => $lecciones,
                'note' => 'Contenido disponible',
                'icon' => 'fas fa-book-open',
                'class' => 'bg-success',
            ],
            [
                'label' => 'Entregas',
                'value' => $entregas,
                'note' => 'Trabajos enviados',
                'icon' => 'fas fa-file-upload',
                'class' => 'bg-warning',
            ],
            [
                'label' => 'Mensajes',
                'value' => $mensajes,
                'note' => 'Conversaciones activas',
                'icon' => 'fas fa-comments',
                'class' => 'bg-info',
            ],
            [
                'label' => 'Promedio',
                'value' => $promedio[0]['total'] ?? 0,
                'note' => 'Rendimiento general',
                'icon' => 'fas fa-star',
                'class' => 'bg-danger',
            ],
        ];
    }

    private static function actividadMensajes($idUsuario, $limite = 3)
    {
        return self::listar(
            'SELECT m.idMensaje, m.contenidoMensaje, m.fechaMensaje,
                    u.nombreUsuario, u.apellidoUsuario, u.imgUsuario
             FROM mensajes_participantes mp
             INNER JOIN mensajes m ON m.idMensaje = mp.id_mensaje
             INNER JOIN usuarios u ON u.idUsuario = m.id_remitente
             WHERE mp.id_usuario = :idUsuario
              AND mp.rolParticipante = "DESTINATARIO"
              AND mp.leido = 0
              AND mp.eliminado = 0
             ORDER BY mp.leido ASC, m.fechaMensaje DESC
             LIMIT ' . (int) $limite,
            [':idUsuario' => $idUsuario]
        );
    }

    private static function actividadPosteos($idUsuario, $rol, $limite = 2)
    {
        $rol = self::normalizarRol($rol);
        if ($rol === 'ADMINISTRADOR') {
            return self::listar(
                'SELECT p.idPosteo, p.contenidoPosteo, p.fechaPosteo, p.id_curso,
                        l.nombreLeccion, s.tituloSeccion,
                        u.nombreUsuario, u.apellidoUsuario
                 FROM posteos p
                 INNER JOIN usuarios u ON u.idUsuario = p.id_autor
                 LEFT JOIN lecciones l ON l.idLeccion = p.id_leccion
                 LEFT JOIN secciones s ON s.idSeccion = l.id_modulo
                 ORDER BY p.fechaPosteo DESC
                 LIMIT ' . (int) $limite
            );
        }

        if ($rol === 'DOCENTE') {
            return self::listar(
                'SELECT DISTINCT p.idPosteo, p.contenidoPosteo, p.fechaPosteo, p.id_curso,
                        l.nombreLeccion, s.tituloSeccion,
                        u.nombreUsuario, u.apellidoUsuario
                 FROM posteos p
                 INNER JOIN usuarios u ON u.idUsuario = p.id_autor
                 LEFT JOIN lecciones l ON l.idLeccion = p.id_leccion
                 LEFT JOIN secciones s ON s.idSeccion = l.id_modulo
                 WHERE s.docente = :idUsuario OR s.tutor = :idUsuario
                 ORDER BY p.fechaPosteo DESC
                 LIMIT ' . (int) $limite,
                [':idUsuario' => $idUsuario]
            );
        }

        return self::listar(
            'SELECT DISTINCT p.idPosteo, p.contenidoPosteo, p.fechaPosteo, p.id_curso,
                    l.nombreLeccion, s.tituloSeccion,
                    u.nombreUsuario, u.apellidoUsuario
             FROM posteos p
             INNER JOIN usuarios u ON u.idUsuario = p.id_autor
             LEFT JOIN lecciones l ON l.idLeccion = p.id_leccion
             LEFT JOIN secciones s ON s.idSeccion = l.id_modulo
             INNER JOIN asignacioncursos a ON a.id_seccion = s.idSeccion
             WHERE a.id_estudiante = :idUsuario
             ORDER BY p.fechaPosteo DESC
             LIMIT ' . (int) $limite,
            [':idUsuario' => $idUsuario]
        );
    }

    private static function actividadEntregas($idUsuario, $rol, $limite = 2)
    {
        $rol = self::normalizarRol($rol);

        if ($rol === 'ESTUDIANTE') {
            return self::listar(
                'SELECT e.idEntregaLeccion, e.fechaEntrega, e.urlArchivo,
                        l.nombreLeccion, s.tituloSeccion
                 FROM entregaslecciones e
                 LEFT JOIN lecciones l ON l.idLeccion = e.id_leccion
                 LEFT JOIN secciones s ON s.idSeccion = e.id_seccion
                 WHERE e.id_estudiante = :idUsuario
                 ORDER BY e.fechaEntrega DESC
                 LIMIT ' . (int) $limite,
                [':idUsuario' => $idUsuario]
            );
        }

        if ($rol === 'DOCENTE') {
            return self::listar(
                'SELECT e.idEntregaLeccion, e.id_estudiante, e.fechaEntrega, e.urlArchivo,
                        l.nombreLeccion, s.tituloSeccion,
                        u.nombreUsuario, u.apellidoUsuario
                 FROM entregaslecciones e
                 INNER JOIN secciones s ON s.idSeccion = e.id_seccion
                 INNER JOIN usuarios u ON u.idUsuario = e.id_estudiante
                 LEFT JOIN lecciones l ON l.idLeccion = e.id_leccion
                 WHERE s.docente = :idUsuario OR s.tutor = :idUsuario
                 ORDER BY e.fechaEntrega DESC
                 LIMIT ' . (int) $limite,
                [':idUsuario' => $idUsuario]
            );
        }

        return self::listar(
            'SELECT e.idEntregaLeccion, e.id_estudiante, e.fechaEntrega, e.urlArchivo,
                    l.nombreLeccion, s.tituloSeccion,
                    u.nombreUsuario, u.apellidoUsuario
             FROM entregaslecciones e
             INNER JOIN secciones s ON s.idSeccion = e.id_seccion
             INNER JOIN usuarios u ON u.idUsuario = e.id_estudiante
             LEFT JOIN lecciones l ON l.idLeccion = e.id_leccion
             ORDER BY e.fechaEntrega DESC
             LIMIT ' . (int) $limite
        );
    }

    private static function entregasPendientes($idUsuario, $rol)
    {
        $rol = self::normalizarRol($rol);

        if (!in_array($rol, ['ADMINISTRADOR', 'DOCENTE'], true)) {
            return [];
        }

        $filtroDocente = $rol === 'DOCENTE'
            ? ' AND (s.docente = :idUsuario OR s.tutor = :idUsuario)'
            : '';
        $params = $rol === 'DOCENTE' ? [':idUsuario' => (int) $idUsuario] : [];

        return self::listar(
            'SELECT e.idEntregaLeccion, e.id_leccion, e.id_seccion, e.id_curso,
                    e.id_estudiante, e.urlArchivo, e.comentarioEntrega, e.fechaEntrega,
                    l.nombreLeccion, s.tituloSeccion, cursos.nombreCurso,
                    u.nombreUsuario, u.apellidoUsuario
             FROM entregaslecciones e
             INNER JOIN lecciones l ON l.idLeccion = e.id_leccion
             INNER JOIN secciones s ON s.idSeccion = e.id_seccion
             INNER JOIN cursos ON cursos.idCurso = e.id_curso
             INNER JOIN usuarios u ON u.idUsuario = e.id_estudiante
             LEFT JOIN calificaciones c
               ON c.id_estudiante = e.id_estudiante
              AND c.id_seccion = e.id_seccion
              AND c.id_modulo = e.id_leccion
             WHERE e.estadoEntrega = "ENTREGADA"
               AND c.idCalificacion IS NULL' . $filtroDocente . '
             ORDER BY e.fechaEntrega ASC, s.tituloSeccion ASC, u.apellidoUsuario ASC',
            $params
        );
    }

    private static function actividadCalificaciones($idUsuario, $rol, $limite = 2)
    {
        $rol = self::normalizarRol($rol);

        if ($rol === 'ESTUDIANTE') {
            return self::listar(
                'SELECT c.idCalificacion, c.calificacion, c.id_seccion, c.id_modulo,
                        l.nombreLeccion, s.tituloSeccion
                 FROM calificaciones c
                 LEFT JOIN lecciones l ON l.idLeccion = c.id_modulo
                 LEFT JOIN secciones s ON s.idSeccion = c.id_seccion
                 WHERE c.id_estudiante = :idUsuario
                 ORDER BY c.idCalificacion DESC
                 LIMIT ' . (int) $limite,
                [':idUsuario' => $idUsuario]
            );
        }

        if ($rol === 'DOCENTE') {
            return self::listar(
                'SELECT c.idCalificacion, c.calificacion, c.id_seccion, c.id_modulo,
                        l.nombreLeccion, s.tituloSeccion,
                        u.nombreUsuario, u.apellidoUsuario
                 FROM calificaciones c
                 INNER JOIN secciones s ON s.idSeccion = c.id_seccion
                 LEFT JOIN lecciones l ON l.idLeccion = c.id_modulo
                 INNER JOIN usuarios u ON u.idUsuario = c.id_estudiante
                 WHERE s.docente = :idUsuario OR s.tutor = :idUsuario
                 ORDER BY c.idCalificacion DESC
                 LIMIT ' . (int) $limite,
                [':idUsuario' => $idUsuario]
            );
        }

        return self::listar(
            'SELECT c.idCalificacion, c.calificacion, c.id_seccion, c.id_modulo,
                    l.nombreLeccion, s.tituloSeccion,
                    u.nombreUsuario, u.apellidoUsuario
             FROM calificaciones c
             INNER JOIN secciones s ON s.idSeccion = c.id_seccion
             LEFT JOIN lecciones l ON l.idLeccion = c.id_modulo
             INNER JOIN usuarios u ON u.idUsuario = c.id_estudiante
             ORDER BY c.idCalificacion DESC
             LIMIT ' . (int) $limite
        );
    }

    private static function actividadNotificaciones($idUsuario, $limite = 5)
    {
        return ModeloNotificaciones::mdlListarNotificacionesUsuario((int) $idUsuario, (int) $limite);
    }

    private static function prepararTablaLecturas()
    {
        if (self::$tablaLecturasPreparada) {
            return;
        }

        try {
            Conexion::conectar()->exec('
                CREATE TABLE IF NOT EXISTS notificaciones_lecturas (
                    idNotificacionLectura int NOT NULL AUTO_INCREMENT,
                    id_usuario int NOT NULL,
                    claveNotificacion varchar(120) NOT NULL,
                    fechaLectura timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (idNotificacionLectura),
                    UNIQUE KEY idx_usuario_clave (id_usuario, claveNotificacion)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
            ');
        } catch (Exception $e) {
            // Si la base no permite DDL, la campana sigue mostrando actividad sin bloquear la app.
        }

        self::$tablaLecturasPreparada = true;
    }

    private static function clavesLeidas($idUsuario)
    {
        self::prepararTablaLecturas();

        try {
            $stmt = Conexion::conectar()->prepare('
                SELECT claveNotificacion
                FROM notificaciones_lecturas
                WHERE id_usuario = :idUsuario
            ');
            $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
            $stmt->execute();

            return array_fill_keys(array_map(static function ($fila) {
                return (string) ($fila['claveNotificacion'] ?? '');
            }, $stmt->fetchAll(PDO::FETCH_ASSOC)), true);
        } catch (Exception $e) {
            return [];
        }
    }

    private static function actividadNormalizada(array $items, $idUsuario)
    {
        $actividad = [];
        $leidas = self::clavesLeidas($idUsuario);

        foreach ($items as $item) {
            $notificacion = null;

            if (isset($item['fechaEntrega'])) {
                $notificacion = [
                    'clave' => 'entrega:' . (int) ($item['idEntregaLeccion'] ?? 0),
                    'titulo' => 'Entrega registrada',
                    'detalle' => trim(($item['nombreUsuario'] ?? '') . ' ' . ($item['apellidoUsuario'] ?? '')) . ' envio ' . ($item['nombreLeccion'] ?? 'una actividad'),
                    'fecha' => (string) $item['fechaEntrega'],
                    'icon' => 'fas fa-file-upload',
                    'class' => 'bg-warning',
                    'orden' => strtotime((string) $item['fechaEntrega']) ?: 0,
                ];
            } elseif (isset($item['fechaPosteo'])) {
                $notificacion = [
                    'clave' => 'posteo:' . (int) ($item['idPosteo'] ?? 0),
                    'titulo' => 'Nuevo aporte',
                    'detalle' => trim(($item['nombreUsuario'] ?? '') . ' ' . ($item['apellidoUsuario'] ?? '')) . ': ' . ($item['contenidoPosteo'] ?? ''),
                    'fecha' => (string) $item['fechaPosteo'],
                    'icon' => 'fas fa-comments',
                    'class' => 'bg-primary',
                    'orden' => strtotime((string) $item['fechaPosteo']) ?: 0,
                ];
            } elseif (isset($item['idCalificacion'])) {
                $notificacion = [
                    'clave' => 'calificacion:' . (int) ($item['idCalificacion'] ?? 0),
                    'titulo' => 'Calificacion actualizada',
                    'detalle' => trim(($item['nombreUsuario'] ?? '') . ' ' . ($item['apellidoUsuario'] ?? '')) . ' obtuvo ' . (int) ($item['calificacion'] ?? 0) . ' puntos en ' . ($item['nombreLeccion'] ?? 'una actividad'),
                    'fecha' => 'Reciente',
                    'icon' => 'fas fa-star',
                    'class' => 'bg-success',
                    'orden' => (int) ($item['idCalificacion'] ?? 0),
                ];
            } elseif (isset($item['idNotificacion'])) {
                $tipo = (string) ($item['tipoNotificacion'] ?? '');
                $notificacion = [
                    'clave' => 'notificacion:' . (int) ($item['idNotificacion'] ?? 0),
                    'titulo' => (string) ($item['tituloNotificacion'] ?? 'Nueva publicacion'),
                    'detalle' => (string) ($item['detalleNotificacion'] ?? ''),
                    'fecha' => (string) ($item['fechaNotificacion'] ?? 'Reciente'),
                    'icon' => $tipo === 'ACTIVIDAD_PUBLICADA' ? 'fas fa-tasks' : 'fas fa-book-open',
                    'class' => $tipo === 'ACTIVIDAD_PUBLICADA' ? 'bg-info' : 'bg-success',
                    'orden' => strtotime((string) ($item['fechaNotificacion'] ?? '')) ?: (int) ($item['idNotificacion'] ?? 0),
                ];
            }

            if (!$notificacion || $notificacion['clave'] === '') {
                continue;
            }

            $notificacion['leida'] = isset($leidas[$notificacion['clave']]);
            $actividad[] = $notificacion;
        }

        usort($actividad, static function ($a, $b) {
            return ($b['orden'] ?? 0) <=> ($a['orden'] ?? 0);
        });

        return $actividad;
    }

    public static function mdlMarcarNotificacionLeida($idUsuario, $clave)
    {
        self::prepararTablaLecturas();
        $clave = trim((string) $clave);

        if ((int) $idUsuario <= 0 || $clave === '') {
            return 'error';
        }

        try {
            $stmt = Conexion::conectar()->prepare('
                INSERT IGNORE INTO notificaciones_lecturas (id_usuario, claveNotificacion)
                VALUES (:idUsuario, :clave)
            ');
            $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
            $stmt->bindValue(':clave', $clave, PDO::PARAM_STR);
            return $stmt->execute() ? 'ok' : 'error';
        } catch (Exception $e) {
            return 'error';
        }
    }

    public static function mdlMarcarNotificacionesLeidas($idUsuario, array $claves)
    {
        $respuesta = 'ok';

        foreach ($claves as $clave) {
            if (self::mdlMarcarNotificacionLeida($idUsuario, $clave) !== 'ok') {
                $respuesta = 'error';
            }
        }

        return $respuesta;
    }

    public static function mdlResumenDashboard($idUsuario, $rol)
    {
        $idUsuario = (int) $idUsuario;
        $rol = self::normalizarRol($rol);
        $tarjetas = self::tarjetasPorRol($rol, $idUsuario);
        $actividad = [];

        foreach (array_merge(
            self::actividadMensajes($idUsuario, 2),
            self::actividadEntregas($idUsuario, $rol, 2),
            self::actividadPosteos($idUsuario, $rol, 2),
            self::actividadCalificaciones($idUsuario, $rol, 2)
        ) as $item) {
            $orden = 0;
            if (isset($item['fechaMensaje'])) {
                $orden = strtotime((string) $item['fechaMensaje']) ?: 0;
                $actividad[] = [
                    'titulo' => 'Nuevo mensaje',
                    'detalle' => trim(($item['nombreUsuario'] ?? '') . ' ' . ($item['apellidoUsuario'] ?? '')) . ': ' . ($item['contenidoMensaje'] ?? ''),
                    'fecha' => (string) $item['fechaMensaje'],
                    'icon' => 'fas fa-comments',
                    'class' => 'bg-info',
                    'orden' => $orden,
                ];
                continue;
            }

            if (isset($item['fechaEntrega'])) {
                $orden = strtotime((string) $item['fechaEntrega']) ?: 0;
                $actividad[] = [
                    'titulo' => 'Entrega registrada',
                    'detalle' => trim(($item['nombreUsuario'] ?? '') . ' ' . ($item['apellidoUsuario'] ?? '')) . ' envió ' . ($item['nombreLeccion'] ?? 'una actividad'),
                    'fecha' => (string) $item['fechaEntrega'],
                    'icon' => 'fas fa-file-upload',
                    'class' => 'bg-warning',
                    'orden' => $orden,
                ];
                continue;
            }

            if (isset($item['fechaPosteo'])) {
                $orden = strtotime((string) $item['fechaPosteo']) ?: 0;
                $actividad[] = [
                    'titulo' => 'Nuevo aporte',
                    'detalle' => trim(($item['nombreUsuario'] ?? '') . ' ' . ($item['apellidoUsuario'] ?? '')) . ': ' . ($item['contenidoPosteo'] ?? ''),
                    'fecha' => (string) $item['fechaPosteo'],
                    'icon' => 'fas fa-comments',
                    'class' => 'bg-primary',
                    'orden' => $orden,
                ];
                continue;
            }

            if (isset($item['idCalificacion'])) {
                $orden = (int) $item['idCalificacion'];
                $actividad[] = [
                    'titulo' => 'Calificación actualizada',
                    'detalle' => trim(($item['nombreUsuario'] ?? '') . ' ' . ($item['apellidoUsuario'] ?? '')) . ' obtuvo ' . (int) ($item['calificacion'] ?? 0) . ' puntos en ' . ($item['nombreLeccion'] ?? 'una actividad'),
                    'fecha' => 'Reciente',
                    'icon' => 'fas fa-star',
                    'class' => 'bg-success',
                    'orden' => $orden,
                ];
            }
        }

        usort($actividad, static function ($a, $b) {
            return ($b['orden'] ?? 0) <=> ($a['orden'] ?? 0);
        });

        return [
            'tarjetas' => $tarjetas,
            'actividad' => array_slice($actividad, 0, 5),
            'pendientes' => self::entregasPendientes($idUsuario, $rol),
            'rol' => $rol,
        ];
    }

    public static function mdlIndicadoresCabecera($idUsuario, $rol)
    {
        $idUsuario = (int) $idUsuario;
        $rol = self::normalizarRol($rol);
        $mensajes = ModeloMensajes::mdlContarMensajesNoLeidos($idUsuario);

        $notificaciones = 0;
        if ($rol === 'ADMINISTRADOR') {
            $notificaciones += self::contar(
                'SELECT COUNT(*) AS total
                 FROM entregaslecciones e
                 LEFT JOIN calificaciones c
                   ON c.id_estudiante = e.id_estudiante
                  AND c.id_seccion = e.id_seccion
                  AND c.id_modulo = e.id_leccion
                 WHERE c.idCalificacion IS NULL'
            );
        } elseif ($rol === 'DOCENTE') {
            $notificaciones += self::contar(
                'SELECT COUNT(*) AS total
                 FROM entregaslecciones e
                 INNER JOIN secciones s ON s.idSeccion = e.id_seccion
                 LEFT JOIN calificaciones c
                   ON c.id_estudiante = e.id_estudiante
                  AND c.id_seccion = e.id_seccion
                  AND c.id_modulo = e.id_leccion
                 WHERE (s.docente = :idUsuario OR s.tutor = :idUsuario)
                   AND c.idCalificacion IS NULL',
                [':idUsuario' => $idUsuario]
            );
        } elseif ($rol === 'ESTUDIANTE') {
            $notificaciones += self::contar(
                'SELECT COUNT(*) AS total
                 FROM calificaciones
                 WHERE id_estudiante = :idUsuario',
                [':idUsuario' => $idUsuario]
            );
        }

        if ($rol === 'ADMINISTRADOR') {
            $notificaciones += self::contar(
                'SELECT COUNT(*) AS total
                 FROM posteos p
                 WHERE p.fechaPosteo >= (NOW() - INTERVAL 7 DAY)'
            );
        } elseif ($rol === 'DOCENTE') {
            $notificaciones += self::contar(
                'SELECT COUNT(*) AS total
                 FROM posteos p
                 LEFT JOIN lecciones l ON l.idLeccion = p.id_leccion
                 LEFT JOIN secciones s ON s.idSeccion = l.id_modulo
                 WHERE (s.docente = :idUsuario OR s.tutor = :idUsuario)
                   AND p.fechaPosteo >= (NOW() - INTERVAL 7 DAY)',
                [':idUsuario' => $idUsuario]
            );
        } elseif ($rol === 'ESTUDIANTE') {
            $notificaciones += self::contar(
                'SELECT COUNT(*) AS total
                 FROM posteos p
                 INNER JOIN asignacioncursos a ON a.id_seccion = p.id_curso
                 WHERE a.id_estudiante = :idUsuario
                   AND p.fechaPosteo >= (NOW() - INTERVAL 7 DAY)',
                [':idUsuario' => $idUsuario]
            );
        }

        $mensajesRecientes = self::actividadMensajes($idUsuario, 5);
        $actividadReciente = array_slice(self::actividadNormalizada(array_merge(
            self::actividadNotificaciones($idUsuario, 10),
            self::actividadEntregas($idUsuario, $rol, 10),
            self::actividadPosteos($idUsuario, $rol, 10),
            self::actividadCalificaciones($idUsuario, $rol, 10)
        ), $idUsuario), 0, 10);
        $notificaciones = count(array_filter($actividadReciente, static function ($notificacion) {
            return empty($notificacion['leida']);
        }));

        return [
            'mensajes' => $mensajes,
            'notificaciones' => $notificaciones,
            'mensajesRecientes' => $mensajesRecientes,
            'actividadReciente' => $actividadReciente,
        ];
    }
}
