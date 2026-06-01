<?php
require_once('conexion.php');

class ModeloPanel
{
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
                    'note' => 'Última conexión reciente',
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
                    u.nombreUsuario, u.apellidoUsuario
             FROM mensajes_participantes mp
             INNER JOIN mensajes m ON m.idMensaje = mp.id_mensaje
             INNER JOIN usuarios u ON u.idUsuario = m.id_remitente
             WHERE mp.id_usuario = :idUsuario
               AND mp.rolParticipante = "DESTINATARIO"
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

    public static function mdlResumenDashboard($idUsuario, $rol)
    {
        $idUsuario = (int) $idUsuario;
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
            'rol' => self::normalizarRol($rol),
        ];
    }

    public static function mdlIndicadoresCabecera($idUsuario, $rol)
    {
        $idUsuario = (int) $idUsuario;
        $rol = self::normalizarRol($rol);
        $mensajes = self::contar(
            'SELECT COUNT(*) AS total
             FROM mensajes
             WHERE id_destinatario = :idUsuario',
            [':idUsuario' => $idUsuario]
        );

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
        $actividadReciente = array_slice(array_merge(
            self::actividadEntregas($idUsuario, $rol, 3),
            self::actividadPosteos($idUsuario, $rol, 3),
            self::actividadCalificaciones($idUsuario, $rol, 3)
        ), 0, 5);

        return [
            'mensajes' => $mensajes,
            'notificaciones' => $notificaciones,
            'mensajesRecientes' => $mensajesRecientes,
            'actividadReciente' => $actividadReciente,
        ];
    }
}
