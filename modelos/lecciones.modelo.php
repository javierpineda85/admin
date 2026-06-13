<?php
require_once('conexion.php');

class ModeloLecciones
{
    private static $tablaLeccionesPreparada = false;

    private static function prepararTablaLecciones()
    {
        if (self::$tablaLeccionesPreparada) {
            return;
        }

        $pdo = Conexion::conectar();

        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM lecciones LIKE 'estadoLeccion'");
            if (!$stmt || !$stmt->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec("ALTER TABLE lecciones ADD COLUMN estadoLeccion varchar(12) NOT NULL DEFAULT 'PUBLICADA' AFTER contenidoLeccion");
            }

            $pdo->exec('ALTER TABLE lecciones MODIFY contenidoLeccion longtext NOT NULL');
        } catch (Exception $e) {
            // Mantiene compatibilidad si la base ya fue actualizada o el usuario no tiene permisos de ALTER.
        }

        self::$tablaLeccionesPreparada = true;
    }

    public static function mdlBuscarSeccionPorId($idSeccion)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.bannerSeccion, s.colorInicioBanner, s.colorFinBanner, s.id_curso, s.docente, s.tutor,
                    c.nombreCurso, c.estado, c.fechaInicioCurso, c.fechaFinCurso, c.horarioCurso,
                    u.nombreUsuario, u.apellidoUsuario,
                    tutor.nombreUsuario AS nombreTutor, tutor.apellidoUsuario AS apellidoTutor
             FROM secciones s
             INNER JOIN cursos c ON c.idCurso = s.id_curso
             INNER JOIN usuarios u ON u.idUsuario = s.docente
             LEFT JOIN usuarios tutor ON tutor.idUsuario = s.tutor
             WHERE s.idSeccion = :idSeccion
             LIMIT 1'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlSeccionAsignadaDocente($idSeccion, $idDocente)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT COUNT(*) AS total
             FROM secciones
             WHERE idSeccion = :idSeccion
               AND (docente = :idDocente OR tutor = :idDocente)'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idDocente', (int) $idDocente, PDO::PARAM_INT);
        $stmt->execute();
        return (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0)) > 0;
    }

    public static function mdlBuscarLeccionPorId($idLeccion)
    {
        self::prepararTablaLecciones();

        $stmt = Conexion::conectar()->prepare(
            'SELECT * FROM lecciones WHERE idLeccion = :idLeccion LIMIT 1'
        );
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlBuscarLeccionesPorSeccion($idSeccion, $incluirBorradores = true)
    {
        self::prepararTablaLecciones();

        $filtroEstado = $incluirBorradores ? '' : ' AND l.estadoLeccion = "PUBLICADA"';
        $stmt = Conexion::conectar()->prepare(
            'SELECT l.idLeccion, l.nombreLeccion, l.tipoLeccion, l.contenidoLeccion, l.estadoLeccion, l.id_modulo,
                    COUNT(DISTINCT r.idRecursoLeccion) AS totalRecursos,
                    COUNT(DISTINCT e.idEntregaLeccion) AS totalEntregas,
                    COUNT(DISTINCT p.idPosteo) AS totalPosts
             FROM lecciones l
             LEFT JOIN recursoslecciones r ON r.id_leccion = l.idLeccion
             LEFT JOIN entregaslecciones e ON e.id_leccion = l.idLeccion
             LEFT JOIN posteos p ON p.id_leccion = l.idLeccion
             WHERE l.id_modulo = :idSeccion
             ' . $filtroEstado . '
             GROUP BY l.idLeccion, l.nombreLeccion, l.tipoLeccion, l.contenidoLeccion, l.estadoLeccion, l.id_modulo
             ORDER BY l.idLeccion ASC'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlResumenSeccion($idSeccion, $incluirBorradores = true)
    {
        self::prepararTablaLecciones();

        $filtroEstado = $incluirBorradores ? '' : ' AND estadoLeccion = "PUBLICADA"';
        $filtroEstadoAlias = $incluirBorradores ? '' : ' AND l.estadoLeccion = "PUBLICADA"';
        $stmt = Conexion::conectar()->prepare(
            'SELECT
                COUNT(*) AS totalLecciones,
                SUM(CASE WHEN tipoLeccion = "MATERIAL" THEN 1 ELSE 0 END) AS totalMateriales,
                SUM(CASE WHEN tipoLeccion = "TAREA" THEN 1 ELSE 0 END) AS totalTareas,
                SUM(CASE WHEN tipoLeccion = "PREGUNTA" THEN 1 ELSE 0 END) AS totalPreguntas,
                SUM(CASE WHEN estadoLeccion = "BORRADOR" THEN 1 ELSE 0 END) AS totalBorradores,
                SUM(CASE WHEN estadoLeccion = "PUBLICADA" THEN 1 ELSE 0 END) AS totalPublicadas
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
             WHERE id_seccion = :idSeccion'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        $resumen['totalEntregas'] = (int) (($stmt->fetch(PDO::FETCH_ASSOC)['totalEntregas'] ?? 0));

        $stmt = Conexion::conectar()->prepare(
            'SELECT COALESCE(ROUND(AVG(calificacion), 2), 0) AS promedioNotas
             FROM calificaciones
             WHERE id_seccion = :idSeccion'
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
             WHERE e.id_seccion = :idSeccion
               AND c.idCalificacion IS NULL'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        $resumen['pendientesCalificar'] = (int) (($stmt->fetch(PDO::FETCH_ASSOC)['pendientes'] ?? 0));

        return $resumen;
    }

    public static function mdlResumenEstudianteSeccion($idSeccion, $idEstudiante)
    {
        self::prepararTablaLecciones();

        $stmt = Conexion::conectar()->prepare(
            'SELECT
                COUNT(*) AS totalEntregas,
                SUM(CASE WHEN estadoEntrega = "ENTREGADA" THEN 1 ELSE 0 END) AS entregadas,
                SUM(CASE WHEN estadoEntrega = "PENDIENTE" THEN 1 ELSE 0 END) AS pendientes
             FROM entregaslecciones
             WHERE id_seccion = :idSeccion
               AND id_estudiante = :idEstudiante'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        $resumen = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt = Conexion::conectar()->prepare(
            'SELECT COALESCE(ROUND(AVG(calificacion), 2), 0) AS promedioNotas
             FROM calificaciones
             WHERE id_seccion = :idSeccion
               AND id_estudiante = :idEstudiante'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        $resumen['promedioNotas'] = (float) (($stmt->fetch(PDO::FETCH_ASSOC)['promedioNotas'] ?? 0));

        $stmt = Conexion::conectar()->prepare(
            'SELECT COUNT(*) AS totalPosts
             FROM posteos
             WHERE id_leccion IN (
                SELECT idLeccion FROM lecciones WHERE id_modulo = :idSeccion AND estadoLeccion = "PUBLICADA"
             )
               AND id_autor = :idEstudiante'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        $resumen['totalPosts'] = (int) (($stmt->fetch(PDO::FETCH_ASSOC)['totalPosts'] ?? 0));

        return $resumen;
    }

    public static function mdlBuscarRecursosPorLeccion($idLeccion)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT idRecursoLeccion, id_leccion, tipoRecurso, tituloRecurso, urlRecurso, creadoPor, fechaRecurso
             FROM recursoslecciones
             WHERE id_leccion = :idLeccion
             ORDER BY idRecursoLeccion ASC'
        );
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarRecursoPorId($idRecurso)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT idRecursoLeccion, id_leccion, tipoRecurso, tituloRecurso, urlRecurso, creadoPor, fechaRecurso
             FROM recursoslecciones
             WHERE idRecursoLeccion = :idRecurso
             LIMIT 1'
        );
        $stmt->bindValue(':idRecurso', (int) $idRecurso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlBuscarPostsPorLeccion($idLeccion)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT p.idPosteo, p.id_autor, p.contenidoPosteo, p.fechaPosteo,
                    u.nombreUsuario, u.apellidoUsuario, u.imgUsuario
             FROM posteos p
             INNER JOIN usuarios u ON u.idUsuario = p.id_autor
             WHERE p.id_leccion = :idLeccion
             ORDER BY p.fechaPosteo ASC'
        );
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarEntregaPorLeccionEstudiante($idLeccion, $idEstudiante)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT idEntregaLeccion, id_leccion, id_seccion, id_curso, id_estudiante, urlArchivo, comentarioEntrega, fechaEntrega, estadoEntrega
             FROM entregaslecciones
             WHERE id_leccion = :idLeccion
               AND id_estudiante = :idEstudiante
             LIMIT 1'
        );
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlBuscarEntregasPorLeccion($idLeccion)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT e.idEntregaLeccion, e.id_leccion, e.id_seccion, e.id_curso, e.id_estudiante, e.urlArchivo, e.comentarioEntrega, e.fechaEntrega, e.estadoEntrega,
                    u.nombreUsuario, u.apellidoUsuario, u.email
             FROM entregaslecciones e
             INNER JOIN usuarios u ON u.idUsuario = e.id_estudiante
             WHERE e.id_leccion = :idLeccion
             ORDER BY e.fechaEntrega DESC'
        );
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarEstudiantesCurso($idCurso)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email
             FROM asignacioncursos a
             INNER JOIN usuarios u ON u.idUsuario = a.id_estudiante
             WHERE a.id_seccion = :idCurso
               AND u.rol = "ESTUDIANTE"
             ORDER BY u.apellidoUsuario ASC, u.nombreUsuario ASC'
        );
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlEstudianteEnCurso($idEstudiante, $idCurso)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT COUNT(*) AS total
             FROM asignacioncursos
             WHERE id_estudiante = :idEstudiante
               AND id_seccion = :idCurso'
        );
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();
        return (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0)) > 0;
    }

    public static function mdlGuardarLeccion($tabla, $datos)
    {
        self::prepararTablaLecciones();

        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO $tabla (nombreLeccion, tipoLeccion, contenidoLeccion, estadoLeccion, id_modulo)
             VALUES (:nombreLeccion, :tipoLeccion, :contenidoLeccion, :estadoLeccion, :id_modulo)"
        );
        $stmt->bindValue(':nombreLeccion', $datos['nombreLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':tipoLeccion', $datos['tipoLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':contenidoLeccion', $datos['contenidoLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':estadoLeccion', $datos['estadoLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':id_modulo', (int) $datos['id_modulo'], PDO::PARAM_INT);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlActualizarLeccion($tabla, $datos)
    {
        self::prepararTablaLecciones();

        $stmt = Conexion::conectar()->prepare(
            "UPDATE $tabla
             SET nombreLeccion = :nombreLeccion,
                 tipoLeccion = :tipoLeccion,
                 contenidoLeccion = :contenidoLeccion,
                 estadoLeccion = :estadoLeccion
             WHERE idLeccion = :idLeccion"
        );
        $stmt->bindValue(':idLeccion', (int) $datos['idLeccion'], PDO::PARAM_INT);
        $stmt->bindValue(':nombreLeccion', $datos['nombreLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':tipoLeccion', $datos['tipoLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':contenidoLeccion', $datos['contenidoLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':estadoLeccion', $datos['estadoLeccion'], PDO::PARAM_STR);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlEliminarLeccion($idLeccion)
    {
        $pdo = Conexion::conectar();
        $pdo->prepare('DELETE FROM recursoslecciones WHERE id_leccion = :idLeccion')->execute([':idLeccion' => (int) $idLeccion]);
        $pdo->prepare('DELETE FROM posteos WHERE id_leccion = :idLeccion')->execute([':idLeccion' => (int) $idLeccion]);
        $pdo->prepare('DELETE FROM entregaslecciones WHERE id_leccion = :idLeccion')->execute([':idLeccion' => (int) $idLeccion]);
        $pdo->prepare('DELETE FROM calificaciones WHERE id_modulo = :idLeccion')->execute([':idLeccion' => (int) $idLeccion]);

        $stmt = $pdo->prepare('DELETE FROM lecciones WHERE idLeccion = :idLeccion');
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlGuardarRecursoLeccion($tabla, $datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO $tabla (id_leccion, tipoRecurso, tituloRecurso, urlRecurso, creadoPor)
             VALUES (:id_leccion, :tipoRecurso, :tituloRecurso, :urlRecurso, :creadoPor)"
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
        $stmt = Conexion::conectar()->prepare(
            'UPDATE recursoslecciones
             SET tipoRecurso = :tipoRecurso,
                 tituloRecurso = :tituloRecurso,
                 urlRecurso = :urlRecurso
             WHERE idRecursoLeccion = :idRecursoLeccion'
        );
        $stmt->bindValue(':idRecursoLeccion', (int) $datos['idRecursoLeccion'], PDO::PARAM_INT);
        $stmt->bindValue(':tipoRecurso', $datos['tipoRecurso'], PDO::PARAM_STR);
        $stmt->bindValue(':tituloRecurso', $datos['tituloRecurso'], PDO::PARAM_STR);
        $stmt->bindValue(':urlRecurso', $datos['urlRecurso'], PDO::PARAM_STR);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlEliminarRecursoLeccion($idRecursoLeccion)
    {
        $stmt = Conexion::conectar()->prepare(
            'DELETE FROM recursoslecciones WHERE idRecursoLeccion = :idRecursoLeccion'
        );
        $stmt->bindValue(':idRecursoLeccion', (int) $idRecursoLeccion, PDO::PARAM_INT);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlGuardarPostLeccion($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            'INSERT INTO posteos (id_autor, contenidoPosteo, fechaPosteo, id_curso, id_leccion)
             VALUES (:id_autor, :contenidoPosteo, :fechaPosteo, :id_curso, :id_leccion)'
        );
        $stmt->bindValue(':id_autor', (int) $datos['id_autor'], PDO::PARAM_INT);
        $stmt->bindValue(':contenidoPosteo', $datos['contenidoPosteo'], PDO::PARAM_STR);
        $stmt->bindValue(':fechaPosteo', $datos['fechaPosteo'], PDO::PARAM_STR);
        $stmt->bindValue(':id_curso', (int) $datos['id_curso'], PDO::PARAM_INT);
        $stmt->bindValue(':id_leccion', (int) $datos['id_leccion'], PDO::PARAM_INT);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlGuardarEntregaLeccion($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            'INSERT INTO entregaslecciones
                (id_leccion, id_seccion, id_curso, id_estudiante, urlArchivo, comentarioEntrega, fechaEntrega, estadoEntrega)
             VALUES
                (:id_leccion, :id_seccion, :id_curso, :id_estudiante, :urlArchivo, :comentarioEntrega, :fechaEntrega, :estadoEntrega)
             ON DUPLICATE KEY UPDATE
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

    public static function mdlBuscarEntregaPorIdLeccionYEstudiante($idLeccion, $idEstudiante)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT idEntregaLeccion, id_leccion, id_seccion, id_curso, id_estudiante, urlArchivo, comentarioEntrega, fechaEntrega, estadoEntrega
             FROM entregaslecciones
             WHERE id_leccion = :idLeccion
               AND id_estudiante = :idEstudiante
             LIMIT 1'
        );
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlEliminarEntregaLeccion($idEntregaLeccion)
    {
        $stmt = Conexion::conectar()->prepare(
            'DELETE FROM entregaslecciones WHERE idEntregaLeccion = :idEntregaLeccion'
        );
        $stmt->bindValue(':idEntregaLeccion', (int) $idEntregaLeccion, PDO::PARAM_INT);
        return $stmt->execute() ? 'ok' : 'error';
    }
}
