<?php
require_once('conexion.php');
require_once __DIR__ . '/tenant.modelo.php';

class ModeloLecciones
{
    private static $tablaLeccionesPreparada = false;
    private static $tablaAdjuntosEntregasPreparada = false;

    private static function prepararTablaLecciones()
    {
        if (ModeloTenant::activo()) { ModeloTenant::id(); return; }
        if (self::$tablaLeccionesPreparada) {
            return;
        }

        $pdo = Conexion::conectar();

        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM lecciones LIKE 'estadoLeccion'");
            if (!$stmt || !$stmt->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec("ALTER TABLE lecciones ADD COLUMN estadoLeccion varchar(12) NOT NULL DEFAULT 'PUBLICADA' AFTER contenidoLeccion");
            }

            $stmt = $pdo->query("SHOW COLUMNS FROM lecciones LIKE 'fechaPublicacionLeccion'");
            if (!$stmt || !$stmt->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec("ALTER TABLE lecciones ADD COLUMN fechaPublicacionLeccion datetime NULL AFTER estadoLeccion");
            }

            $pdo->exec('ALTER TABLE lecciones MODIFY contenidoLeccion longtext NOT NULL');
        } catch (Exception $e) {
            // Mantiene compatibilidad si la base ya fue actualizada o el usuario no tiene permisos de ALTER.
        }

        self::$tablaLeccionesPreparada = true;
    }

    private static function prepararTablaAdjuntosEntregas()
    {
        if (ModeloTenant::activo()) { ModeloTenant::id(); return; }
        if (self::$tablaAdjuntosEntregasPreparada) {
            return;
        }

        $pdo = Conexion::conectar();

        try {
            $estadoTabla = $pdo->query("SHOW TABLE STATUS LIKE 'entregaslecciones'");
            $datosTabla = $estadoTabla ? $estadoTabla->fetch(PDO::FETCH_ASSOC) : null;
            if ($datosTabla && strtoupper((string) ($datosTabla['Engine'] ?? '')) !== 'INNODB') {
                $pdo->exec('ALTER TABLE entregaslecciones ENGINE=InnoDB');
            }

            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS entregaslecciones_adjuntos (
                    idAdjuntoEntrega INT NOT NULL AUTO_INCREMENT,
                    id_entrega INT NOT NULL,
                    nombreOriginal VARCHAR(255) NOT NULL,
                    rutaArchivo VARCHAR(255) NOT NULL,
                    mimeType VARCHAR(100) DEFAULT NULL,
                    tamanoArchivo INT DEFAULT NULL,
                    fechaAdjunto TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (idAdjuntoEntrega),
                    KEY idx_entrega (id_entrega)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );
        } catch (Exception $e) {
            // La migracion SQL permite preparar la tabla cuando el usuario web no tiene permisos DDL.
        }

        self::$tablaAdjuntosEntregasPreparada = true;
    }

    private static function tablaAdjuntosEntregasDisponible(PDO $pdo)
    {
        try {
            $tablaAdjuntos = $pdo->query("SHOW TABLES LIKE 'entregaslecciones_adjuntos'");
            return $tablaAdjuntos && $tablaAdjuntos->fetchColumn() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    private static function entregasSoportanTransacciones(PDO $pdo)
    {
        try {
            $estadoTabla = $pdo->query("SHOW TABLE STATUS LIKE 'entregaslecciones'");
            $datosTabla = $estadoTabla ? $estadoTabla->fetch(PDO::FETCH_ASSOC) : null;
            return $datosTabla && strtoupper((string) ($datosTabla['Engine'] ?? '')) === 'INNODB';
        } catch (Exception $e) {
            return false;
        }
    }

    public static function mdlBuscarSeccionPorId($idSeccion)
    {
        ModeloTenant::exigirSeccion($idSeccion);
        $stmt = Conexion::conectar()->prepare(
            'SELECT s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.bannerSeccion, s.colorInicioBanner, s.colorFinBanner, s.id_curso, s.docente, s.tutor,
                    s.creadoPor, s.activo, s.fechaBaja, s.motivoBaja,
                    c.nombreCurso, c.estado, c.fechaInicioCurso, c.fechaFinCurso, c.horarioCurso,
                    u.nombreUsuario, u.apellidoUsuario,
                    tutor.nombreUsuario AS nombreTutor, tutor.apellidoUsuario AS apellidoTutor,
                    CONCAT(creador.nombreUsuario, CHAR(32), creador.apellidoUsuario) AS creadorNombre
             FROM secciones s
             INNER JOIN cursos c ON c.idCurso = s.id_curso
             INNER JOIN usuarios u ON u.idUsuario = s.docente
             LEFT JOIN usuarios tutor ON tutor.idUsuario = s.tutor
             LEFT JOIN usuarios creador ON creador.idUsuario = s.creadoPor
             WHERE s.idSeccion = :idSeccion AND ' . ModeloTenant::cursos() . '
             LIMIT 1'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlSeccionAsignadaDocente($idSeccion, $idDocente)
    {
        ModeloTenant::exigirSeccion($idSeccion);
        ModeloTenant::exigirUsuario($idDocente, ['DOCENTE', 'ADMINISTRADOR']);
        $stmt = Conexion::conectar()->prepare(
            'SELECT COUNT(*) AS total
             FROM secciones
             WHERE idSeccion = :idSeccion
               AND (docente = :idDocente OR tutor = :idDocente) AND ' . ModeloTenant::secciones('secciones')
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idDocente', (int) $idDocente, PDO::PARAM_INT);
        $stmt->execute();
        return (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0)) > 0;
    }

    public static function mdlBuscarLeccionPorId($idLeccion)
    {
        ModeloTenant::exigirLeccion($idLeccion);
        self::prepararTablaLecciones();

        $stmt = Conexion::conectar()->prepare(
            'SELECT * FROM lecciones WHERE idLeccion = :idLeccion AND ' . ModeloTenant::lecciones('lecciones') . ' LIMIT 1'
        );
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlBuscarLeccionesPorSeccion($idSeccion, $incluirBorradores = true)
    {
        ModeloTenant::exigirSeccion($idSeccion);
        self::prepararTablaLecciones();

        $filtroEstado = $incluirBorradores ? '' : ' AND l.estadoLeccion = "PUBLICADA" AND (l.fechaPublicacionLeccion IS NULL OR l.fechaPublicacionLeccion <= NOW())';
        $stmt = Conexion::conectar()->prepare(
            'SELECT l.idLeccion, l.nombreLeccion, l.tipoLeccion, l.contenidoLeccion, l.estadoLeccion, l.fechaPublicacionLeccion, l.id_modulo,
                    COUNT(DISTINCT r.idRecursoLeccion) AS totalRecursos,
                    COUNT(DISTINCT e.idEntregaLeccion) AS totalEntregas,
                    COUNT(DISTINCT p.idPosteo) AS totalPosts
             FROM lecciones l
             LEFT JOIN recursoslecciones r ON r.id_leccion = l.idLeccion
             LEFT JOIN entregaslecciones e ON e.id_leccion = l.idLeccion AND ' . ModeloTenant::entregas() . '
             LEFT JOIN posteos p ON p.id_leccion = l.idLeccion AND ' . ModeloTenant::posteos() . '
             WHERE l.id_modulo = :idSeccion
             ' . $filtroEstado . ' AND ' . ModeloTenant::lecciones() . '
             GROUP BY l.idLeccion, l.nombreLeccion, l.tipoLeccion, l.contenidoLeccion, l.estadoLeccion, l.fechaPublicacionLeccion, l.id_modulo
             ORDER BY l.idLeccion ASC'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlResumenSeccion($idSeccion, $incluirBorradores = true)
    {
        ModeloTenant::exigirSeccion($idSeccion);
        self::prepararTablaLecciones();

        $filtroEstado = $incluirBorradores ? '' : ' AND estadoLeccion = "PUBLICADA" AND (fechaPublicacionLeccion IS NULL OR fechaPublicacionLeccion <= NOW())';
        $filtroEstadoAlias = $incluirBorradores ? '' : ' AND l.estadoLeccion = "PUBLICADA" AND (l.fechaPublicacionLeccion IS NULL OR l.fechaPublicacionLeccion <= NOW())';
        $filtroEstado .= ' AND ' . ModeloTenant::lecciones('lecciones');
        $filtroEstadoAlias .= ' AND ' . ModeloTenant::lecciones();
        $stmt = Conexion::conectar()->prepare(
            'SELECT
                COUNT(*) AS totalLecciones,
                SUM(CASE WHEN tipoLeccion = "MATERIAL" THEN 1 ELSE 0 END) AS totalMateriales,
                SUM(CASE WHEN tipoLeccion = "TAREA" THEN 1 ELSE 0 END) AS totalTareas,
                SUM(CASE WHEN tipoLeccion = "PREGUNTA" THEN 1 ELSE 0 END) AS totalPreguntas,
                SUM(CASE WHEN estadoLeccion = "BORRADOR" THEN 1 ELSE 0 END) AS totalBorradores,
                SUM(CASE WHEN estadoLeccion = "PUBLICADA" AND (fechaPublicacionLeccion IS NULL OR fechaPublicacionLeccion <= NOW()) THEN 1 ELSE 0 END) AS totalPublicadas,
                SUM(CASE WHEN estadoLeccion = "PUBLICADA" AND fechaPublicacionLeccion > NOW() THEN 1 ELSE 0 END) AS totalProgramadas
             FROM lecciones
             WHERE id_modulo = :idSeccion' . $filtroEstado
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        $resumen = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt = Conexion::conectar()->prepare(
            'SELECT COUNT(*) AS totalRecursos
             FROM recursoslecciones r
             INNER JOIN lecciones l ON l.idLeccion = r.id_leccion
             WHERE l.id_modulo = :idSeccion' . $filtroEstadoAlias
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        $resumen['totalRecursos'] = (int) (($stmt->fetch(PDO::FETCH_ASSOC)['totalRecursos'] ?? 0));

        $stmt = Conexion::conectar()->prepare(
            'SELECT COUNT(*) AS totalEntregas
             FROM entregaslecciones
             WHERE id_seccion = :idSeccion AND ' . ModeloTenant::entregas('entregaslecciones')
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        $resumen['totalEntregas'] = (int) (($stmt->fetch(PDO::FETCH_ASSOC)['totalEntregas'] ?? 0));

        $stmt = Conexion::conectar()->prepare(
            'SELECT COALESCE(ROUND(AVG(calificacion), 2), 0) AS promedioNotas
             FROM calificaciones
             WHERE id_seccion = :idSeccion AND ' . ModeloTenant::calificaciones()
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        $resumen['promedioNotas'] = (float) (($stmt->fetch(PDO::FETCH_ASSOC)['promedioNotas'] ?? 0));

        $stmt = Conexion::conectar()->prepare(
            'SELECT COUNT(*) AS pendientes
             FROM entregaslecciones e
             LEFT JOIN calificaciones c
               ON c.id_estudiante = e.id_estudiante
              AND c.id_seccion = e.id_seccion
              AND c.id_modulo = e.id_leccion
              AND ' . ModeloTenant::calificaciones('c') . '
             WHERE e.id_seccion = :idSeccion
               AND c.idCalificacion IS NULL AND ' . ModeloTenant::entregas()
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        $resumen['pendientesCalificar'] = (int) (($stmt->fetch(PDO::FETCH_ASSOC)['pendientes'] ?? 0));

        return $resumen;
    }

    public static function mdlResumenEstudianteSeccion($idSeccion, $idEstudiante)
    {
        ModeloTenant::exigirSeccion($idSeccion);
        ModeloTenant::exigirUsuario($idEstudiante, ['ESTUDIANTE']);
        self::prepararTablaLecciones();

        $stmt = Conexion::conectar()->prepare(
            'SELECT
                COUNT(*) AS totalEntregas,
                SUM(CASE WHEN estadoEntrega = "ENTREGADA" THEN 1 ELSE 0 END) AS entregadas,
                SUM(CASE WHEN estadoEntrega = "PENDIENTE" THEN 1 ELSE 0 END) AS pendientes
             FROM entregaslecciones
             WHERE id_seccion = :idSeccion
               AND id_estudiante = :idEstudiante AND ' . ModeloTenant::entregas('entregaslecciones')
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        $resumen = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt = Conexion::conectar()->prepare(
            'SELECT COALESCE(ROUND(AVG(calificacion), 2), 0) AS promedioNotas
             FROM calificaciones
             WHERE id_seccion = :idSeccion
               AND id_estudiante = :idEstudiante AND ' . ModeloTenant::calificaciones()
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        $resumen['promedioNotas'] = (float) (($stmt->fetch(PDO::FETCH_ASSOC)['promedioNotas'] ?? 0));

        $stmt = Conexion::conectar()->prepare(
            'SELECT COUNT(*) AS totalPosts
             FROM posteos
             WHERE id_leccion IN (
                SELECT idLeccion FROM lecciones
                WHERE id_modulo = :idSeccion
                  AND estadoLeccion = "PUBLICADA"
                  AND (fechaPublicacionLeccion IS NULL OR fechaPublicacionLeccion <= NOW())
             )
               AND id_autor = :idEstudiante AND ' . ModeloTenant::posteos('posteos')
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        $resumen['totalPosts'] = (int) (($stmt->fetch(PDO::FETCH_ASSOC)['totalPosts'] ?? 0));

        return $resumen;
    }

    public static function mdlBuscarRecursosPorLeccion($idLeccion)
    {
        ModeloTenant::exigirLeccion($idLeccion);
        $stmt = Conexion::conectar()->prepare(
            'SELECT idRecursoLeccion, id_leccion, tipoRecurso, tituloRecurso, urlRecurso, creadoPor, fechaRecurso
             FROM recursoslecciones
             WHERE id_leccion = :idLeccion
               AND ' . ModeloTenant::hijoLeccion('recursoslecciones') . '
             ORDER BY idRecursoLeccion ASC'
        );
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarRecursoPorId($idRecurso)
    {
        ModeloTenant::exigirRecurso($idRecurso);
        $stmt = Conexion::conectar()->prepare(
            'SELECT idRecursoLeccion, id_leccion, tipoRecurso, tituloRecurso, urlRecurso, creadoPor, fechaRecurso
             FROM recursoslecciones
             WHERE idRecursoLeccion = :idRecurso
               AND ' . ModeloTenant::hijoLeccion('recursoslecciones') . '
             LIMIT 1'
        );
        $stmt->bindValue(':idRecurso', (int) $idRecurso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlBuscarPostsPorLeccion($idLeccion)
    {
        ModeloTenant::exigirLeccion($idLeccion);
        $stmt = Conexion::conectar()->prepare(
            'SELECT p.idPosteo, p.id_autor, p.contenidoPosteo, p.fechaPosteo,
                    u.nombreUsuario, u.apellidoUsuario, u.imgUsuario
             FROM posteos p
             INNER JOIN usuarios u ON u.idUsuario = p.id_autor
             WHERE p.id_leccion = :idLeccion
               AND ' . ModeloTenant::posteos() . '
             ORDER BY p.fechaPosteo ASC'
        );
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarEntregaPorLeccionEstudiante($idLeccion, $idEstudiante)
    {
        ModeloTenant::exigirLeccion($idLeccion);
        ModeloTenant::exigirUsuario($idEstudiante, ['ESTUDIANTE']);
        $stmt = Conexion::conectar()->prepare(
            'SELECT idEntregaLeccion, id_leccion, id_seccion, id_curso, id_estudiante, urlArchivo, comentarioEntrega, fechaEntrega, estadoEntrega
             FROM entregaslecciones
             WHERE id_leccion = :idLeccion
               AND id_estudiante = :idEstudiante
               AND ' . ModeloTenant::entregas('entregaslecciones') . '
             LIMIT 1'
        );
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlBuscarEntregasPorLeccion($idLeccion)
    {
        ModeloTenant::exigirLeccion($idLeccion);
        $stmt = Conexion::conectar()->prepare(
            'SELECT e.idEntregaLeccion, e.id_leccion, e.id_seccion, e.id_curso, e.id_estudiante, e.urlArchivo, e.comentarioEntrega, e.fechaEntrega, e.estadoEntrega,
                    u.nombreUsuario, u.apellidoUsuario, u.email
             FROM entregaslecciones e
             INNER JOIN usuarios u ON u.idUsuario = e.id_estudiante
             WHERE e.id_leccion = :idLeccion
               AND ' . ModeloTenant::entregas() . '
             ORDER BY e.fechaEntrega DESC'
        );
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarAdjuntosPorEntrega($idEntregaLeccion)
    {
        ModeloTenant::exigirEntrega($idEntregaLeccion);
        self::prepararTablaAdjuntosEntregas();

        try {
            $stmt = Conexion::conectar()->prepare(
                'SELECT idAdjuntoEntrega, id_entrega, nombreOriginal, rutaArchivo, mimeType, tamanoArchivo, fechaAdjunto
                 FROM entregaslecciones_adjuntos
                 WHERE id_entrega = :idEntregaLeccion
                   AND ' . ModeloTenant::adjuntosEntrega() . '
                 ORDER BY idAdjuntoEntrega ASC'
            );
            $stmt->bindValue(':idEntregaLeccion', (int) $idEntregaLeccion, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public static function mdlBuscarEntregaPorId($idEntrega)
    {
        ModeloTenant::exigirEntrega($idEntrega);
        $stmt=Conexion::conectar()->prepare('SELECT idEntregaLeccion,id_leccion,id_seccion,id_curso,id_estudiante,urlArchivo,comentarioEntrega,fechaEntrega,estadoEntrega
            FROM entregaslecciones e WHERE e.idEntregaLeccion=:idEntrega AND '.ModeloTenant::entregas('e').' LIMIT 1');
        $stmt->bindValue(':idEntrega',(int)$idEntrega,PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)?:null;
    }

    public static function mdlBuscarAdjuntoEntregaPorId($idAdjunto)
    {
        $stmt=Conexion::conectar()->prepare('SELECT a.idAdjuntoEntrega,a.id_entrega,a.nombreOriginal,a.rutaArchivo,a.mimeType,a.tamanoArchivo
            FROM entregaslecciones_adjuntos a WHERE a.idAdjuntoEntrega=:idAdjunto AND '.ModeloTenant::adjuntosEntrega('a').' LIMIT 1');
        $stmt->bindValue(':idAdjunto',(int)$idAdjunto,PDO::PARAM_INT);
        $stmt->execute();
        $adjunto=$stmt->fetch(PDO::FETCH_ASSOC)?:null;
        if($adjunto){ModeloTenant::exigirEntrega((int)$adjunto['id_entrega']);}
        return $adjunto;
    }

    public static function mdlBuscarEstudiantesCurso($idCurso)
    {
        ModeloTenant::exigirCurso($idCurso);
        $rolEstudiante = ModeloTenant::activo() ? ModeloTenant::usuarioConRol('u.idUsuario', ['ESTUDIANTE']) : 'u.rol = "ESTUDIANTE"';
        $stmt = Conexion::conectar()->prepare(
            'SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email
             FROM asignacioncursos a
             INNER JOIN usuarios u ON u.idUsuario = a.id_estudiante
             WHERE a.id_seccion = :idCurso
               AND ' . ModeloTenant::cursoId($idCurso) . '
               AND a.estadoInscripcion = "ACTIVA"
               AND ' . $rolEstudiante . '
             ORDER BY u.apellidoUsuario ASC, u.nombreUsuario ASC'
        );
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlEstudianteEnCurso($idEstudiante, $idCurso)
    {
        ModeloTenant::exigirCurso($idCurso);
        ModeloTenant::exigirUsuario($idEstudiante, ['ESTUDIANTE']);
        $stmt = Conexion::conectar()->prepare(
            'SELECT COUNT(*) AS total
             FROM asignacioncursos
             WHERE id_estudiante = :idEstudiante
               AND id_seccion = :idCurso AND estadoInscripcion = "ACTIVA" AND ' . ModeloTenant::cursoId($idCurso)
        );
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();
        return (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0)) > 0;
    }

    public static function mdlBuscarLeccionesProgramadasVencidas($idSeccion = 0)
    {
        self::prepararTablaLecciones();

        $filtroSeccion = (int) $idSeccion > 0 ? ' AND l.id_modulo = :idSeccion' : '';
        $filtroSeccion .= ' AND ' . ModeloTenant::cursos();
        $stmt = Conexion::conectar()->prepare(
            'SELECT l.*, s.idSeccion, s.tituloSeccion, s.id_curso, c.nombreCurso
             FROM lecciones l
             INNER JOIN secciones s ON s.idSeccion = l.id_modulo
             INNER JOIN cursos c ON c.idCurso = s.id_curso
             WHERE l.estadoLeccion = "PUBLICADA"
               AND l.fechaPublicacionLeccion IS NOT NULL
               AND l.fechaPublicacionLeccion <= NOW()' . $filtroSeccion
        );

        if ((int) $idSeccion > 0) {
            $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlGuardarLeccion($tabla, $datos)
    {
        if ($tabla !== 'lecciones') { throw new InvalidArgumentException('Tabla inválida.'); }
        ModeloTenant::exigirSeccion($datos['id_modulo']);
        self::prepararTablaLecciones();

        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO $tabla (nombreLeccion, tipoLeccion, contenidoLeccion, estadoLeccion, fechaPublicacionLeccion, id_modulo)
             SELECT :nombreLeccion, :tipoLeccion, :contenidoLeccion, :estadoLeccion, :fechaPublicacionLeccion, :id_modulo WHERE " . ModeloTenant::seccionId($datos['id_modulo'])
        );
        $stmt->bindValue(':nombreLeccion', $datos['nombreLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':tipoLeccion', $datos['tipoLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':contenidoLeccion', $datos['contenidoLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':estadoLeccion', $datos['estadoLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':fechaPublicacionLeccion', $datos['fechaPublicacionLeccion'] ?: null, $datos['fechaPublicacionLeccion'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':id_modulo', (int) $datos['id_modulo'], PDO::PARAM_INT);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlActualizarLeccion($tabla, $datos)
    {
        if ($tabla !== 'lecciones') { throw new InvalidArgumentException('Tabla inválida.'); }
        ModeloTenant::exigirLeccion($datos['idLeccion']);
        self::prepararTablaLecciones();

        $stmt = Conexion::conectar()->prepare(
            "UPDATE $tabla
             SET nombreLeccion = :nombreLeccion,
                 tipoLeccion = :tipoLeccion,
                 contenidoLeccion = :contenidoLeccion,
                 estadoLeccion = :estadoLeccion,
                 fechaPublicacionLeccion = :fechaPublicacionLeccion
             WHERE idLeccion = :idLeccion AND " . ModeloTenant::lecciones('lecciones')
        );
        $stmt->bindValue(':idLeccion', (int) $datos['idLeccion'], PDO::PARAM_INT);
        $stmt->bindValue(':nombreLeccion', $datos['nombreLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':tipoLeccion', $datos['tipoLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':contenidoLeccion', $datos['contenidoLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':estadoLeccion', $datos['estadoLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':fechaPublicacionLeccion', $datos['fechaPublicacionLeccion'] ?: null, $datos['fechaPublicacionLeccion'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlEliminarLeccion($idLeccion)
    {
        ModeloTenant::exigirLeccion($idLeccion);
        if (ModeloTenant::activo()) {
            foreach (['entregaslecciones' => ModeloTenant::entregas('entregaslecciones'),
                'posteos' => ModeloTenant::posteos('posteos'), 'calificaciones' => ModeloTenant::calificaciones()] as $tabla => $coherencia) {
                $columna = $tabla === 'calificaciones' ? 'id_modulo' : 'id_leccion';
                $revision = Conexion::conectar()->prepare("SELECT COUNT(*) FROM $tabla WHERE $columna=? AND NOT ($coherencia)");
                $revision->execute([(int)$idLeccion]);
                if ((int)$revision->fetchColumn() > 0) {
                    throw new RuntimeException('La lección institucional tiene referencias inconsistentes; deben regularizarse antes de eliminarla.');
                }
            }
        }
        $pdo = Conexion::conectar();
        self::prepararTablaAdjuntosEntregas();
        $pdo->prepare('DELETE FROM recursoslecciones WHERE id_leccion = :idLeccion AND ' . ModeloTenant::hijoLeccion('recursoslecciones'))->execute([':idLeccion' => (int) $idLeccion]);
        $pdo->prepare('DELETE FROM posteos WHERE id_leccion = :idLeccion AND ' . ModeloTenant::posteos('posteos'))->execute([':idLeccion' => (int) $idLeccion]);
        try {
            $pdo->prepare(
                'DELETE a
                 FROM entregaslecciones_adjuntos a
                 INNER JOIN entregaslecciones e ON e.idEntregaLeccion = a.id_entrega
                 WHERE e.id_leccion = :idLeccion AND ' . ModeloTenant::entregas()
            )->execute([':idLeccion' => (int) $idLeccion]);
        } catch (Exception $e) {
            // Compatibilidad con instalaciones que todavia no ejecutaron la migracion de adjuntos.
        }
        $pdo->prepare('DELETE FROM entregaslecciones WHERE id_leccion = :idLeccion AND ' . ModeloTenant::entregas('entregaslecciones'))->execute([':idLeccion' => (int) $idLeccion]);
        $pdo->prepare('DELETE FROM calificaciones WHERE id_modulo = :idLeccion AND ' . ModeloTenant::calificaciones())->execute([':idLeccion' => (int) $idLeccion]);

        $stmt = $pdo->prepare('DELETE FROM lecciones WHERE idLeccion = :idLeccion AND ' . ModeloTenant::lecciones('lecciones'));
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlGuardarRecursoLeccion($tabla, $datos)
    {
        if ($tabla !== 'recursoslecciones') { throw new InvalidArgumentException('Tabla inválida.'); }
        ModeloTenant::exigirLeccion($datos['id_leccion']);
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO $tabla (id_leccion, tipoRecurso, tituloRecurso, urlRecurso, creadoPor)
             SELECT :id_leccion, :tipoRecurso, :tituloRecurso, :urlRecurso, :creadoPor WHERE " . ModeloTenant::leccionId($datos['id_leccion'])
        );
        $stmt->bindValue(':id_leccion', (int) $datos['id_leccion'], PDO::PARAM_INT);
        $stmt->bindValue(':tipoRecurso', $datos['tipoRecurso'], PDO::PARAM_STR);
        $stmt->bindValue(':tituloRecurso', $datos['tituloRecurso'], PDO::PARAM_STR);
        $stmt->bindValue(':urlRecurso', $datos['urlRecurso'], PDO::PARAM_STR);
        $stmt->bindValue(':creadoPor', (int) $datos['creadoPor'], PDO::PARAM_INT);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlActualizarRecursoLeccion($datos)
    {
        ModeloTenant::exigirRecurso($datos['idRecursoLeccion']);
        $stmt = Conexion::conectar()->prepare(
            'UPDATE recursoslecciones
             SET tipoRecurso = :tipoRecurso,
                 tituloRecurso = :tituloRecurso,
                 urlRecurso = :urlRecurso
             WHERE idRecursoLeccion = :idRecursoLeccion AND ' . ModeloTenant::hijoLeccion('recursoslecciones')
        );
        $stmt->bindValue(':idRecursoLeccion', (int) $datos['idRecursoLeccion'], PDO::PARAM_INT);
        $stmt->bindValue(':tipoRecurso', $datos['tipoRecurso'], PDO::PARAM_STR);
        $stmt->bindValue(':tituloRecurso', $datos['tituloRecurso'], PDO::PARAM_STR);
        $stmt->bindValue(':urlRecurso', $datos['urlRecurso'], PDO::PARAM_STR);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlEliminarRecursoLeccion($idRecursoLeccion)
    {
        ModeloTenant::exigirRecurso($idRecursoLeccion);
        $stmt = Conexion::conectar()->prepare(
            'DELETE FROM recursoslecciones WHERE idRecursoLeccion = :idRecursoLeccion AND ' . ModeloTenant::hijoLeccion('recursoslecciones')
        );
        $stmt->bindValue(':idRecursoLeccion', (int) $idRecursoLeccion, PDO::PARAM_INT);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlGuardarPostLeccion($datos)
    {
        ModeloTenant::exigirLeccion($datos['id_leccion'], null, $datos['id_curso']);
        ModeloTenant::exigirUsuario($datos['id_autor'], ['ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE']);
        $stmt = Conexion::conectar()->prepare(
            'INSERT INTO posteos (id_autor, contenidoPosteo, fechaPosteo, id_curso, id_leccion)
             SELECT :id_autor, :contenidoPosteo, :fechaPosteo, :id_curso, :id_leccion WHERE ' . ModeloTenant::relacionLeccion($datos['id_leccion'],null,$datos['id_curso']) . ' AND ' . ModeloTenant::usuarioIdConRol($datos['id_autor'],['ADMINISTRADOR','DOCENTE','ESTUDIANTE'])
        );
        $stmt->bindValue(':id_autor', (int) $datos['id_autor'], PDO::PARAM_INT);
        $stmt->bindValue(':contenidoPosteo', $datos['contenidoPosteo'], PDO::PARAM_STR);
        $stmt->bindValue(':fechaPosteo', $datos['fechaPosteo'], PDO::PARAM_STR);
        $stmt->bindValue(':id_curso', (int) $datos['id_curso'], PDO::PARAM_INT);
        $stmt->bindValue(':id_leccion', (int) $datos['id_leccion'], PDO::PARAM_INT);
        if(!$stmt->execute()){return 'error';}
        return !ModeloTenant::activo() || $stmt->rowCount()===1 ? 'ok' : 'error';
    }

    public static function mdlGuardarEntregaLeccion($datos)
    {
        ModeloTenant::exigirLeccion($datos['id_leccion'], $datos['id_seccion'], $datos['id_curso']);
        ModeloTenant::exigirInscripcion($datos['id_estudiante'], $datos['id_curso']);
        $stmt = Conexion::conectar()->prepare(
            'INSERT INTO entregaslecciones
                (id_leccion, id_seccion, id_curso, id_estudiante, urlArchivo, comentarioEntrega, fechaEntrega, estadoEntrega)
             SELECT
                :id_leccion, :id_seccion, :id_curso, :id_estudiante, :urlArchivo, :comentarioEntrega, :fechaEntrega, :estadoEntrega
             WHERE ' . ModeloTenant::escrituraEntrega($datos) . ' ON DUPLICATE KEY UPDATE
                id_seccion = VALUES(id_seccion),
                id_curso = VALUES(id_curso),
                urlArchivo = VALUES(urlArchivo),
                comentarioEntrega = VALUES(comentarioEntrega),
                fechaEntrega = VALUES(fechaEntrega),
                estadoEntrega = VALUES(estadoEntrega)'
        );
        $stmt->bindValue(':id_leccion', (int) $datos['id_leccion'], PDO::PARAM_INT);
        $stmt->bindValue(':id_seccion', (int) $datos['id_seccion'], PDO::PARAM_INT);
        $stmt->bindValue(':id_curso', (int) $datos['id_curso'], PDO::PARAM_INT);
        $stmt->bindValue(':id_estudiante', (int) $datos['id_estudiante'], PDO::PARAM_INT);
        $stmt->bindValue(':urlArchivo', $datos['urlArchivo'], PDO::PARAM_STR);
        $stmt->bindValue(':comentarioEntrega', $datos['comentarioEntrega'], PDO::PARAM_STR);
        $stmt->bindValue(':fechaEntrega', $datos['fechaEntrega'], PDO::PARAM_STR);
        $stmt->bindValue(':estadoEntrega', $datos['estadoEntrega'], PDO::PARAM_STR);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlGuardarEntregaConAdjuntos($datos, array $adjuntos, $reemplazarAdjuntos)
    {
        ModeloTenant::exigirLeccion($datos['id_leccion'], $datos['id_seccion'], $datos['id_curso']);
        ModeloTenant::exigirInscripcion($datos['id_estudiante'], $datos['id_curso']);
        self::prepararTablaAdjuntosEntregas();
        $pdo = Conexion::conectar();

        if (
            !self::tablaAdjuntosEntregasDisponible($pdo)
            || !self::entregasSoportanTransacciones($pdo)
        ) {
            return false;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO entregaslecciones
                    (id_leccion, id_seccion, id_curso, id_estudiante, urlArchivo, comentarioEntrega, fechaEntrega, estadoEntrega)
                 SELECT
                    :id_leccion, :id_seccion, :id_curso, :id_estudiante, :urlArchivo, :comentarioEntrega, :fechaEntrega, :estadoEntrega
                 WHERE ' . ModeloTenant::escrituraEntrega($datos) . ' ON DUPLICATE KEY UPDATE
                    id_seccion = VALUES(id_seccion),
                    id_curso = VALUES(id_curso),
                    urlArchivo = VALUES(urlArchivo),
                    comentarioEntrega = VALUES(comentarioEntrega),
                    fechaEntrega = VALUES(fechaEntrega),
                    estadoEntrega = VALUES(estadoEntrega)'
            );
            $stmt->bindValue(':id_leccion', (int) $datos['id_leccion'], PDO::PARAM_INT);
            $stmt->bindValue(':id_seccion', (int) $datos['id_seccion'], PDO::PARAM_INT);
            $stmt->bindValue(':id_curso', (int) $datos['id_curso'], PDO::PARAM_INT);
            $stmt->bindValue(':id_estudiante', (int) $datos['id_estudiante'], PDO::PARAM_INT);
            $stmt->bindValue(':urlArchivo', $datos['urlArchivo'], PDO::PARAM_STR);
            $stmt->bindValue(':comentarioEntrega', $datos['comentarioEntrega'], PDO::PARAM_STR);
            $stmt->bindValue(':fechaEntrega', $datos['fechaEntrega'], PDO::PARAM_STR);
            $stmt->bindValue(':estadoEntrega', $datos['estadoEntrega'], PDO::PARAM_STR);
            $stmt->execute();

            $buscarEntrega = $pdo->prepare(
                'SELECT idEntregaLeccion
                 FROM entregaslecciones
                 WHERE id_leccion = :idLeccion
                   AND id_estudiante = :idEstudiante
                   AND ' . ModeloTenant::entregas('entregaslecciones') . '
                 LIMIT 1'
            );
            $buscarEntrega->bindValue(':idLeccion', (int) $datos['id_leccion'], PDO::PARAM_INT);
            $buscarEntrega->bindValue(':idEstudiante', (int) $datos['id_estudiante'], PDO::PARAM_INT);
            $buscarEntrega->execute();
            $idEntregaLeccion = (int) ($buscarEntrega->fetchColumn() ?: 0);

            if ($idEntregaLeccion <= 0) {
                throw new RuntimeException('No se pudo recuperar la entrega guardada.');
            }

            if ($reemplazarAdjuntos) {
                $eliminar = $pdo->prepare(
                    'DELETE FROM entregaslecciones_adjuntos WHERE id_entrega = :idEntregaLeccion AND ' . ModeloTenant::adjuntosEntrega()
                );
                $eliminar->bindValue(':idEntregaLeccion', $idEntregaLeccion, PDO::PARAM_INT);
                $eliminar->execute();

                $insertar = $pdo->prepare(
                    'INSERT INTO entregaslecciones_adjuntos
                        (id_entrega, nombreOriginal, rutaArchivo, mimeType, tamanoArchivo)
                     SELECT
                        :id_entrega, :nombreOriginal, :rutaArchivo, :mimeType, :tamanoArchivo
                     WHERE EXISTS (SELECT 1 FROM entregaslecciones adj_e WHERE adj_e.idEntregaLeccion=' . $idEntregaLeccion . ' AND ' . ModeloTenant::entregas('adj_e') . ')'
                );

                foreach ($adjuntos as $adjunto) {
                    $insertar->bindValue(':id_entrega', $idEntregaLeccion, PDO::PARAM_INT);
                    $insertar->bindValue(':nombreOriginal', $adjunto['nombreOriginal'], PDO::PARAM_STR);
                    $insertar->bindValue(':rutaArchivo', $adjunto['rutaArchivo'], PDO::PARAM_STR);
                    $insertar->bindValue(':mimeType', $adjunto['mimeType'] ?: null, $adjunto['mimeType'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
                    $insertar->bindValue(':tamanoArchivo', (int) $adjunto['tamanoArchivo'], PDO::PARAM_INT);
                    $insertar->execute();
                }
            }

            $pdo->commit();
            return $idEntregaLeccion;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return false;
        }
    }

    public static function mdlBuscarEntregaPorIdLeccionYEstudiante($idLeccion, $idEstudiante)
    {
        ModeloTenant::exigirLeccion($idLeccion);
        ModeloTenant::exigirUsuario($idEstudiante, ['ESTUDIANTE']);
        $stmt = Conexion::conectar()->prepare(
            'SELECT idEntregaLeccion, id_leccion, id_seccion, id_curso, id_estudiante, urlArchivo, comentarioEntrega, fechaEntrega, estadoEntrega
             FROM entregaslecciones
             WHERE id_leccion = :idLeccion
               AND id_estudiante = :idEstudiante
               AND ' . ModeloTenant::entregas('entregaslecciones') . ' LIMIT 1'
        );
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlEliminarEntregaLeccion($idEntregaLeccion)
    {
        ModeloTenant::exigirEntrega($idEntregaLeccion);
        self::prepararTablaAdjuntosEntregas();
        $pdo = Conexion::conectar();

        try {
            $pdo->beginTransaction();

            if (self::tablaAdjuntosEntregasDisponible($pdo)) {
                $stmt = $pdo->prepare(
                    'DELETE FROM entregaslecciones_adjuntos WHERE id_entrega = :idEntregaLeccion AND ' . ModeloTenant::adjuntosEntrega()
                );
                $stmt->bindValue(':idEntregaLeccion', (int) $idEntregaLeccion, PDO::PARAM_INT);
                $stmt->execute();
            }

            $stmt = $pdo->prepare(
                'DELETE FROM entregaslecciones WHERE idEntregaLeccion = :idEntregaLeccion AND ' . ModeloTenant::entregas('entregaslecciones')
            );
            $stmt->bindValue(':idEntregaLeccion', (int) $idEntregaLeccion, PDO::PARAM_INT);
            $stmt->execute();

            $pdo->commit();
            return 'ok';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return 'error';
        }
    }
}
