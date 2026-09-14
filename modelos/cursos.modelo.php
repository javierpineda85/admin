<?php
require_once('conexion.php');
require_once __DIR__ . '/tenant.modelo.php';

class ModeloCursos
{
    private static $estructuraAcademicaPreparada = false;
    public static function prepararEstructuraAcademica()
    {
        // El esquema multiinstitución se prepara por migración, nunca con DDL en una petición.
        if (ModeloTenant::activo()) { ModeloTenant::id(); return; }
        if (self::$estructuraAcademicaPreparada) { return; }
        $pdo=Conexion::conectar();
        foreach (["ALTER TABLE cursos ADD COLUMN modalidadCalificacion VARCHAR(20) NOT NULL DEFAULT 'DOS_TRAMOS'","ALTER TABLE cursos ADD COLUMN intensificacionActiva TINYINT(1) NOT NULL DEFAULT 1","ALTER TABLE asignacioncursos ADD COLUMN estadoInscripcion VARCHAR(15) NOT NULL DEFAULT 'ACTIVA'","ALTER TABLE asignacioncursos ADD COLUMN fechaAlta DATETIME NULL","ALTER TABLE asignacioncursos ADD COLUMN fechaBaja DATETIME NULL","ALTER TABLE asignacioncursos ADD COLUMN motivoBaja VARCHAR(255) NULL"] as $sql) {
            try {$pdo->exec($sql);} catch(PDOException $e) {if((int)($e->errorInfo[1]??0)!==1060){throw $e;}}
        }
        $pdo->exec('UPDATE asignacioncursos SET fechaAlta=COALESCE(fechaAlta,NOW()) WHERE fechaAlta IS NULL');
        self::$estructuraAcademicaPreparada=true;
    }
    private static function duplicarArchivoLocal($ruta, array &$archivosCreados)
    {
        $ruta = trim((string) $ruta);
        if ($ruta === '' || preg_match('~^https?://~i', $ruta)) {
            return $ruta;
        }

        $origen = realpath(__DIR__ . '/../' . ltrim(str_replace('\\', '/', $ruta), '/'));
        $directorioPermitido = realpath(__DIR__ . '/../uploads/lecciones');
        if (!$origen || !$directorioPermitido || strpos($origen, $directorioPermitido . DIRECTORY_SEPARATOR) !== 0 || !is_file($origen)) {
            return $ruta;
        }

        $extension = pathinfo($origen, PATHINFO_EXTENSION);
        $nombre = 'copia_' . date('YmdHis') . '_' . bin2hex(random_bytes(5)) . ($extension !== '' ? '.' . $extension : '');
        $destino = $directorioPermitido . DIRECTORY_SEPARATOR . $nombre;
        if (copy($origen, $destino)) {
            $archivosCreados[] = $destino;
            return 'uploads/lecciones/' . $nombre;
        }
        if (ModeloTenant::activo()) { throw new RuntimeException('No se pudo copiar el archivo institucional.'); }
        return $ruta;
    }

    /** Compensa inserciones parciales en instalaciones cuyas tablas académicas aún son MyISAM. */
    private static function limpiarDuplicacionFallida(PDO $pdo, $idCurso, array $archivosCreados)
    {
        $idCurso = (int) $idCurso;
        $filasCompensadas = true;
        if ($idCurso > 0) {
            $consultas = [
                'DELETE o FROM actividades_opciones o INNER JOIN actividades_preguntas p ON p.idPregunta=o.id_pregunta INNER JOIN actividades a ON a.idActividad=p.id_actividad WHERE a.id_curso=?',
                'DELETE p FROM actividades_preguntas p INNER JOIN actividades a ON a.idActividad=p.id_actividad WHERE a.id_curso=?',
                'DELETE FROM actividades WHERE id_curso=?',
                'DELETE r FROM recursoslecciones r INNER JOIN lecciones l ON l.idLeccion=r.id_leccion INNER JOIN secciones s ON s.idSeccion=l.id_modulo WHERE s.id_curso=?',
                'DELETE a FROM archivoslecciones a INNER JOIN lecciones l ON l.idLeccion=a.id_leccion INNER JOIN secciones s ON s.idSeccion=l.id_modulo WHERE s.id_curso=?',
                'DELETE l FROM lecciones l INNER JOIN secciones s ON s.idSeccion=l.id_modulo WHERE s.id_curso=?',
                'DELETE FROM secciones WHERE id_curso=?',
                'DELETE FROM cursos WHERE idCurso=?',
            ];
            foreach ($consultas as $sql) {
                try {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$idCurso]);
                } catch (Throwable $e) {
                    $filasCompensadas = false;
                    error_log('No se pudo compensar una fila de la duplicación fallida: ' . $e->getMessage());
                }
            }
        }
        if (!$filasCompensadas) { return; }
        foreach (array_unique($archivosCreados) as $archivo) {
            if (is_file($archivo) && !@unlink($archivo)) {
                error_log('No se pudo retirar un archivo de la duplicación fallida: ' . $archivo);
            }
        }
    }

    static public function mdlDuplicarCurso($idCursoOrigen, array $datos)
    {
        ModeloTenant::exigirCurso($idCursoOrigen);
        $institucion = ModeloTenant::activo() ? ModeloTenant::id() : 0;
        if ($institucion) { self::validarDuplicacionInstitucional($idCursoOrigen, $datos['idUsuario']); }
        self::prepararEstructuraAcademica();
        $pdo = Conexion::conectar();
        $idCursoNuevo = 0;
        $archivosCreados = [];

        try {
            $cursoStmt = $pdo->prepare('SELECT * FROM cursos WHERE idCurso = :idCurso AND ' . ModeloTenant::cursos('cursos') . ' LIMIT 1');
            $cursoStmt->execute([':idCurso' => (int) $idCursoOrigen]);
            $curso = $cursoStmt->fetch(PDO::FETCH_ASSOC);
            if (!$curso) {
                return 0;
            }

            $insertCurso = $pdo->prepare('INSERT INTO cursos
                (' . ($institucion ? 'id_institucion,' : '') . 'nombreCurso, contenidoCurso, estado, fechaInicioCurso, fechaFinCurso, horarioCurso, creadoPor, responsable, modalidadCalificacion, intensificacionActiva)
                SELECT ' . ($institucion ? $institucion . ',' : '') . ':nombre, :contenido, :estado, :inicio, :fin, :horario, :creador, :responsable, :modalidad, :intensificacion
                WHERE ' . ModeloTenant::cursoId($idCursoOrigen));
            $insertCurso->execute([
                ':nombre' => $datos['nombreCurso'],
                ':contenido' => $curso['contenidoCurso'],
                ':estado' => $curso['estado'],
                ':inicio' => $datos['fechaInicioCurso'],
                ':fin' => $datos['fechaFinCurso'],
                ':horario' => $curso['horarioCurso'],
                ':creador' => (int) $datos['idUsuario'],
                ':responsable' => (int) $datos['idUsuario'],
                ':modalidad' => $curso['modalidadCalificacion'] ?? 'DOS_TRAMOS',
                ':intensificacion' => (int) ($curso['intensificacionActiva'] ?? 1),
            ]);
            if ($insertCurso->rowCount() !== 1) { throw new RuntimeException('Acceso institucional denegado.'); }
            $idCursoNuevo = (int) $pdo->lastInsertId();

            $secciones = $pdo->prepare('SELECT * FROM secciones WHERE id_curso = :idCurso AND ' . ModeloTenant::secciones('secciones') . ' ORDER BY idSeccion');
            $secciones->execute([':idCurso' => (int) $idCursoOrigen]);
            foreach ($secciones->fetchAll(PDO::FETCH_ASSOC) as $seccion) {
                if ($institucion) {
                    ModeloTenant::exigirUsuario($seccion['docente'], ['DOCENTE','ADMINISTRADOR']);
                    if ((int)$seccion['tutor'] > 0) { ModeloTenant::exigirUsuario($seccion['tutor'], ['DOCENTE','ADMINISTRADOR']); }
                }
                $insertSeccion = $pdo->prepare('INSERT INTO secciones
                    (tituloSeccion, contenidoSeccion, id_curso, docente, tutor, bannerSeccion, colorInicioBanner, colorFinBanner, creadoPor)
                    SELECT :titulo, :contenido, :curso, :docente, :tutor, :banner, :colorInicio, :colorFin, :creador WHERE ' . ModeloTenant::cursoId($idCursoNuevo));
                $insertSeccion->execute([
                    ':titulo' => $seccion['tituloSeccion'], ':contenido' => $seccion['contenidoSeccion'],
                    ':curso' => $idCursoNuevo, ':docente' => $seccion['docente'], ':tutor' => $seccion['tutor'],
                    ':banner' => $seccion['bannerSeccion'], ':colorInicio' => $seccion['colorInicioBanner'],
                    ':colorFin' => $seccion['colorFinBanner'], ':creador' => (int) $datos['idUsuario'],
                ]);
                $idSeccionNueva = (int) $pdo->lastInsertId();

                $lecciones = $pdo->prepare('SELECT * FROM lecciones WHERE id_modulo = :idSeccion AND ' . ModeloTenant::lecciones('lecciones') . ' ORDER BY idLeccion');
                $lecciones->execute([':idSeccion' => (int) $seccion['idSeccion']]);
                foreach ($lecciones->fetchAll(PDO::FETCH_ASSOC) as $leccion) {
                    $insertLeccion = $pdo->prepare('INSERT INTO lecciones
                        (nombreLeccion, tipoLeccion, contenidoLeccion, estadoLeccion, fechaPublicacionLeccion, id_modulo)
                        SELECT :nombre, :tipo, :contenido, "BORRADOR", NULL, :seccion WHERE ' . ModeloTenant::seccionId($idSeccionNueva));
                    $insertLeccion->execute([
                        ':nombre' => $leccion['nombreLeccion'], ':tipo' => $leccion['tipoLeccion'],
                        ':contenido' => $leccion['contenidoLeccion'], ':seccion' => $idSeccionNueva,
                    ]);
                    $idLeccionNueva = (int) $pdo->lastInsertId();

                    $recursos = $pdo->prepare('SELECT * FROM recursoslecciones WHERE id_leccion = :idLeccion AND ' . ModeloTenant::hijoLeccion('recursoslecciones') . ' ORDER BY idRecursoLeccion');
                    $recursos->execute([':idLeccion' => (int) $leccion['idLeccion']]);
                    foreach ($recursos->fetchAll(PDO::FETCH_ASSOC) as $recurso) {
                        $insertRecurso = $pdo->prepare('INSERT INTO recursoslecciones
                            (id_leccion, tipoRecurso, tituloRecurso, urlRecurso, creadoPor)
                            SELECT :leccion, :tipo, :titulo, :url, :creador WHERE ' . ModeloTenant::leccionId($idLeccionNueva));
                        $insertRecurso->execute([
                            ':leccion' => $idLeccionNueva, ':tipo' => $recurso['tipoRecurso'],
                            ':titulo' => $recurso['tituloRecurso'],
                            ':url' => self::duplicarArchivoLocal($recurso['urlRecurso'], $archivosCreados),
                            ':creador' => (int) $datos['idUsuario'],
                        ]);
                    }
                    if ($institucion) {
                        $archivos=$pdo->prepare('SELECT tipoArchivo,urlArchivo FROM archivoslecciones WHERE id_leccion=? AND ' . ModeloTenant::hijoLeccion('archivoslecciones'));
                        $archivos->execute([(int)$leccion['idLeccion']]);
                        foreach ($archivos->fetchAll(PDO::FETCH_ASSOC) as $archivo) {
                            $pdo->prepare('INSERT INTO archivoslecciones(id_leccion,tipoArchivo,urlArchivo) SELECT ?,?,? WHERE ' . ModeloTenant::leccionId($idLeccionNueva))
                                ->execute([$idLeccionNueva,$archivo['tipoArchivo'],self::duplicarArchivoLocal($archivo['urlArchivo'], $archivosCreados)]);
                        }
                    }
                }

                try {
                    $actividades = $pdo->prepare('SELECT * FROM actividades WHERE id_curso = :idCurso AND id_seccion = :idSeccion AND COALESCE(esPlantilla, 0) = 0 AND ' . ($institucion ? 'id_institucion=' . $institucion . ' AND ' . ModeloTenant::sesionActiva() : '1=1') . ' ORDER BY idActividad');
                    $actividades->execute([':idCurso' => (int) $idCursoOrigen, ':idSeccion' => (int) $seccion['idSeccion']]);
                    foreach ($actividades->fetchAll(PDO::FETCH_ASSOC) as $actividad) {
                        $slug = substr($actividad['slug'], 0, 155) . '-copia-' . $idCursoNuevo . '-' . bin2hex(random_bytes(3));
                        $insertActividad = $pdo->prepare('INSERT INTO actividades
                            (' . ($institucion ? 'id_institucion,' : '') . 'tituloActividad, slug, descripcionActividad, tipoActividad, visibilidad, estadoActividad,
                             id_curso, id_seccion, id_autor, puntajeMaximo, intentosPermitidos, permiteVisitantes,
                             esPlantilla, alcancePlantilla, destacadaPublica, id_actividad_origen, recursoExternoUrl, recursoExternoEmbed)
                            SELECT ' . ($institucion ? $institucion . ',' : '') . ':titulo, :slug, :descripcion, :tipo, "privada", "BORRADOR", :curso, :seccion,
                             :autor, :puntaje, :intentos, :visitantes, 0, "personal", 0, :origen, :url, :embed WHERE ' . ModeloTenant::seccionId($idSeccionNueva));
                        $insertActividad->execute([
                            ':titulo' => $actividad['tituloActividad'], ':slug' => $slug,
                            ':descripcion' => $actividad['descripcionActividad'], ':tipo' => $actividad['tipoActividad'],
                            ':curso' => $idCursoNuevo, ':seccion' => $idSeccionNueva, ':autor' => (int) $datos['idUsuario'],
                            ':puntaje' => $actividad['puntajeMaximo'], ':intentos' => $actividad['intentosPermitidos'],
                            ':visitantes' => $actividad['permiteVisitantes'], ':origen' => (int) $actividad['idActividad'],
                            ':url' => $actividad['recursoExternoUrl'], ':embed' => $actividad['recursoExternoEmbed'],
                        ]);
                        $idActividadNueva = (int) $pdo->lastInsertId();

                        $preguntas = $pdo->prepare('SELECT * FROM actividades_preguntas WHERE id_actividad = :id ORDER BY orden, idPregunta');
                        $preguntas->execute([':id' => (int) $actividad['idActividad']]);
                        foreach ($preguntas->fetchAll(PDO::FETCH_ASSOC) as $pregunta) {
                            $insertPregunta = $pdo->prepare('INSERT INTO actividades_preguntas
                                (id_actividad, tipoPregunta, textoPregunta, codigoBase, lenguajeCodigo, variantesCodigo,
                                 respuestaCorrecta, puntaje, orden, pista, explicacionError)
                                VALUES (:actividad, :tipo, :texto, :codigo, :lenguaje, :variantes, :respuesta, :puntaje, :orden, :pista, :explicacion)');
                            $insertPregunta->execute([
                                ':actividad' => $idActividadNueva, ':tipo' => $pregunta['tipoPregunta'], ':texto' => $pregunta['textoPregunta'],
                                ':codigo' => $pregunta['codigoBase'], ':lenguaje' => $pregunta['lenguajeCodigo'], ':variantes' => $pregunta['variantesCodigo'],
                                ':respuesta' => $pregunta['respuestaCorrecta'], ':puntaje' => $pregunta['puntaje'], ':orden' => $pregunta['orden'],
                                ':pista' => $pregunta['pista'], ':explicacion' => $pregunta['explicacionError'],
                            ]);
                            $idPreguntaNueva = (int) $pdo->lastInsertId();
                            $opciones = $pdo->prepare('SELECT * FROM actividades_opciones WHERE id_pregunta = :id ORDER BY orden, idOpcion');
                            $opciones->execute([':id' => (int) $pregunta['idPregunta']]);
                            foreach ($opciones->fetchAll(PDO::FETCH_ASSOC) as $opcion) {
                                $pdo->prepare('INSERT INTO actividades_opciones (id_pregunta, textoOpcion, esCorrecta, orden) VALUES (?, ?, ?, ?)')
                                    ->execute([$idPreguntaNueva, $opcion['textoOpcion'], $opcion['esCorrecta'], $opcion['orden']]);
                            }
                        }
                    }
                } catch (PDOException $e) {
                    if ((int) ($e->errorInfo[1] ?? 0) !== 1146) {
                        throw $e;
                    }
                }
            }

            return $idCursoNuevo;
        } catch (Throwable $e) {
            self::limpiarDuplicacionFallida($pdo, $idCursoNuevo, $archivosCreados);
            error_log('Error al duplicar curso: ' . $e->getMessage());
            return 0;
        }
    }
    static public function mdlBuscarCursoPorId($idCurso)
    {
        self::prepararEstructuraAcademica();
        $stmt = Conexion::conectar()->prepare("
            SELECT c.*,
                   DATE_FORMAT(c.fechaInicioCurso, '%d/%m/%Y') AS fInicio,
                   DATE_FORMAT(c.fechaFinCurso, '%d/%m/%Y') AS fFin,
                   CONCAT(u.nombreUsuario, ' ', u.apellidoUsuario) AS creadorNombre
            FROM cursos c
            LEFT JOIN usuarios u ON u.idUsuario = c.creadoPor
            WHERE c.idCurso = :idCurso AND " . ModeloTenant::cursos() . "
            LIMIT 1
        ");
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    static public function mdlListarCursos()
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT c.*,
                   DATE_FORMAT(c.fechaInicioCurso, '%d/%m/%Y') AS fInicio,
                   DATE_FORMAT(c.fechaFinCurso, '%d/%m/%Y') AS fFin,
                   COUNT(DISTINCT s.idSeccion) AS totalSecciones,
                   COUNT(DISTINCT l.idLeccion) AS totalLecciones
            FROM cursos c
            LEFT JOIN secciones s ON s.id_curso = c.idCurso
            LEFT JOIN lecciones l ON l.id_modulo = s.idSeccion
            WHERE " . ModeloTenant::cursos() . "
            GROUP BY c.idCurso, c.nombreCurso, c.contenidoCurso, c.estado,
                     c.fechaInicioCurso, c.fechaFinCurso, c.horarioCurso
            ORDER BY c.nombreCurso ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlCursosPorEstudiante($idEstudiante)
    {
        ModeloTenant::exigirUsuario($idEstudiante, ['ESTUDIANTE']);
        self::prepararEstructuraAcademica();
        $stmt = Conexion::conectar()->prepare("
            SELECT DISTINCT c.*,
                   DATE_FORMAT(c.fechaInicioCurso, '%d/%m/%Y') AS fInicio,
                   DATE_FORMAT(c.fechaFinCurso, '%d/%m/%Y') AS fFin,
                   COUNT(DISTINCT s.idSeccion) AS totalSecciones,
                   COUNT(DISTINCT l.idLeccion) AS totalLecciones
            FROM asignacioncursos a
            INNER JOIN cursos c ON c.idCurso = a.id_seccion
            LEFT JOIN secciones s ON s.id_curso = c.idCurso
            LEFT JOIN lecciones l ON l.id_modulo = s.idSeccion
            WHERE a.id_estudiante = :idEstudiante AND a.estadoInscripcion = 'ACTIVA'
              AND " . ModeloTenant::cursos() . "
              AND c.activo = 1
            GROUP BY c.idCurso, c.nombreCurso, c.contenidoCurso, c.estado, c.fechaInicioCurso, c.fechaFinCurso, c.horarioCurso
            ORDER BY c.nombreCurso ASC
        ");
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlCursosPorDocente($idDocente)
    {
        ModeloTenant::exigirUsuario($idDocente, ['DOCENTE', 'ADMINISTRADOR']);
        $stmt = Conexion::conectar()->prepare("
            SELECT DISTINCT c.*,
                   DATE_FORMAT(c.fechaInicioCurso, '%d/%m/%Y') AS fInicio,
                   DATE_FORMAT(c.fechaFinCurso, '%d/%m/%Y') AS fFin,
                   COUNT(DISTINCT s.idSeccion) AS totalSecciones,
                   COUNT(DISTINCT l.idLeccion) AS totalLecciones
            FROM cursos c
            LEFT JOIN secciones s ON c.idCurso = s.id_curso
            LEFT JOIN lecciones l ON l.id_modulo = s.idSeccion
            WHERE (c.responsable = :idDocente
               OR s.docente = :idDocente
               OR s.tutor = :idDocente)
              AND " . ModeloTenant::cursos() . "
              AND c.activo = 1
            GROUP BY c.idCurso, c.nombreCurso, c.contenidoCurso, c.estado, c.fechaInicioCurso, c.fechaFinCurso, c.horarioCurso, c.creadoPor, c.responsable
            ORDER BY c.nombreCurso ASC
        ");
        $stmt->bindValue(':idDocente', (int) $idDocente, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlDocentePuedeGestionarCurso($idCurso, $idDocente)
    {
        ModeloTenant::exigirCurso($idCurso);
        ModeloTenant::exigirUsuario($idDocente, ['DOCENTE', 'ADMINISTRADOR']);
        $stmt = Conexion::conectar()->prepare("
            SELECT COUNT(*) AS total
            FROM cursos c
            WHERE c.idCurso = :idCurso
              AND " . ModeloTenant::cursos() . "
              AND c.responsable = :idDocente
              AND c.activo = 1
        ");
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->bindValue(':idDocente', (int) $idDocente, PDO::PARAM_INT);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0) > 0;
    }

    static public function mdlDocenteVinculadoCurso($idCurso, $idDocente)
    {
        ModeloTenant::exigirCurso($idCurso);
        ModeloTenant::exigirUsuario($idDocente, ['DOCENTE', 'ADMINISTRADOR']);
        $stmt = Conexion::conectar()->prepare("
            SELECT COUNT(*) AS total
            FROM cursos c
            LEFT JOIN secciones s ON s.id_curso = c.idCurso AND s.activo = 1
            WHERE c.idCurso = :idCurso
              AND " . ModeloTenant::cursos() . "
              AND c.activo = 1
              AND (c.responsable = :idDocente OR s.docente = :idDocente OR s.tutor = :idDocente)
        ");
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->bindValue(':idDocente', (int) $idDocente, PDO::PARAM_INT);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0) > 0;
    }

    static public function mdlEstudianteInscriptoCurso($idEstudiante, $idCurso)
    {
        ModeloTenant::exigirCurso($idCurso);
        ModeloTenant::exigirUsuario($idEstudiante, ['ESTUDIANTE']);
        self::prepararEstructuraAcademica();
        $stmt = Conexion::conectar()->prepare("
            SELECT COUNT(*) AS total
            FROM asignacioncursos
            WHERE id_estudiante = :idEstudiante
              AND id_seccion = :idCurso AND estadoInscripcion = 'ACTIVA'
              AND " . ModeloTenant::cursoId($idCurso) . "
        ");
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();

        return (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0)) > 0;
    }

    static public function mdlSeccionesPorCurso($idCurso)
    {
        ModeloTenant::exigirCurso($idCurso);
        $stmt = Conexion::conectar()->prepare("
            SELECT s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor, s.activo,
                   c.nombreCurso,
                   u.nombreUsuario, u.apellidoUsuario,
                   COUNT(DISTINCT l.idLeccion) AS totalLecciones,
                   SUM(CASE WHEN l.tipoLeccion = 'TAREA' THEN 1 ELSE 0 END) AS totalTareas,
                   SUM(CASE WHEN l.tipoLeccion = 'MATERIAL' THEN 1 ELSE 0 END) AS totalMateriales,
                   SUM(CASE WHEN l.tipoLeccion = 'PREGUNTA' THEN 1 ELSE 0 END) AS totalPreguntas
            FROM secciones s
            INNER JOIN cursos c ON c.idCurso = s.id_curso
            INNER JOIN usuarios u ON u.idUsuario = s.docente
            LEFT JOIN lecciones l ON l.id_modulo = s.idSeccion
            WHERE s.id_curso = :idCurso
              AND " . ModeloTenant::cursos() . "
            GROUP BY s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor,
                     c.nombreCurso, u.nombreUsuario, u.apellidoUsuario
            ORDER BY s.tituloSeccion ASC
        ");
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlSeccionesPorCursoParaDocente($idCurso, $idDocente)
    {
        ModeloTenant::exigirCurso($idCurso);
        ModeloTenant::exigirUsuario($idDocente, ['DOCENTE', 'ADMINISTRADOR']);
        $stmt = Conexion::conectar()->prepare("
            SELECT s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor, s.activo,
                   c.nombreCurso,
                   u.nombreUsuario, u.apellidoUsuario,
                   COUNT(DISTINCT l.idLeccion) AS totalLecciones,
                   SUM(CASE WHEN l.tipoLeccion = 'TAREA' THEN 1 ELSE 0 END) AS totalTareas,
                   SUM(CASE WHEN l.tipoLeccion = 'MATERIAL' THEN 1 ELSE 0 END) AS totalMateriales,
                   SUM(CASE WHEN l.tipoLeccion = 'PREGUNTA' THEN 1 ELSE 0 END) AS totalPreguntas
            FROM secciones s
            INNER JOIN cursos c ON c.idCurso = s.id_curso
            INNER JOIN usuarios u ON u.idUsuario = s.docente
            LEFT JOIN lecciones l ON l.id_modulo = s.idSeccion
            WHERE s.id_curso = :idCurso
              AND " . ModeloTenant::cursos() . "
              AND (s.docente = :idDocente OR s.tutor = :idDocente)
              AND s.activo = 1
            GROUP BY s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor,
                     c.nombreCurso, u.nombreUsuario, u.apellidoUsuario
            ORDER BY s.tituloSeccion ASC
        ");
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->bindValue(':idDocente', (int) $idDocente, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* GUARDAR CURSO */
    static public function mdlGuardarCurso($tabla, $datos)
    {
        if ($tabla !== 'cursos') { throw new InvalidArgumentException('Tabla inválida.'); }
        if (ModeloTenant::activo()) {
            $institucion = ModeloTenant::id();
            ModeloTenant::exigirUsuario($datos['responsable'], ['DOCENTE', 'ADMINISTRADOR']);
            ModeloTenant::exigirUsuario($datos['creadoPor'], ['DOCENTE', 'ADMINISTRADOR']);
            $stmt = Conexion::conectar()->prepare('INSERT INTO cursos
                (id_institucion,nombreCurso,contenidoCurso,estado,fechaInicioCurso,fechaFinCurso,horarioCurso,creadoPor,responsable,modalidadCalificacion,intensificacionActiva)
                SELECT ?,?,?,?,?,?,?,?,?,?,? WHERE ' . ModeloTenant::sesionActiva() . ' AND ' . ModeloTenant::usuarioIdConRol($datos['responsable'], ['DOCENTE','ADMINISTRADOR']) . ' AND ' . ModeloTenant::usuarioIdConRol($datos['creadoPor'], ['DOCENTE','ADMINISTRADOR']));
            return $stmt->execute([$institucion,$datos['nombreCurso'],$datos['contenidoCurso'],$datos['estado'],
                $datos['fechaInicioCurso'],$datos['fechaFinCurso'],$datos['horarioCurso'],$datos['creadoPor'],
                $datos['responsable'],$datos['modalidadCalificacion'],(int)$datos['intensificacionActiva']]) ? 'ok' : 'error';
        }
        self::prepararEstructuraAcademica();
        $registro = Conexion::conectar()->prepare("INSERT INTO $tabla(nombreCurso, contenidoCurso, estado, fechaInicioCurso, fechaFinCurso, horarioCurso, creadoPor, responsable, modalidadCalificacion, intensificacionActiva) VALUES(:nombreCurso, :contenidoCurso, :estado, :fechaInicioCurso, :fechaFinCurso, :horarioCurso, :creadoPor, :responsable, :modalidadCalificacion, :intensificacionActiva)");

        $registro->bindParam(":nombreCurso", $datos["nombreCurso"], PDO::PARAM_STR);
        $registro->bindParam(":contenidoCurso", $datos["contenidoCurso"], PDO::PARAM_STR);
        $registro->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
        $registro->bindParam(":fechaInicioCurso", $datos["fechaInicioCurso"], PDO::PARAM_STR);
        $registro->bindParam(":fechaFinCurso", $datos["fechaFinCurso"], PDO::PARAM_STR);
        $registro->bindParam(":horarioCurso", $datos["horarioCurso"], PDO::PARAM_STR);
        $registro->bindParam(":creadoPor", $datos["creadoPor"], PDO::PARAM_INT);
        $registro->bindParam(":responsable", $datos["responsable"], PDO::PARAM_INT);
        $registro->bindValue(":modalidadCalificacion",$datos['modalidadCalificacion'],PDO::PARAM_STR);
        $registro->bindValue(":intensificacionActiva",(int)$datos['intensificacionActiva'],PDO::PARAM_INT);

        if ($registro->execute()) {
            return "ok";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }

        $registro->closeCursor();
        $registro = null;
    }

    /*EDITAR CURSO */

    static public function mdlModificarCurso($tabla, $datos)
    {
        if ($tabla !== 'cursos') { throw new InvalidArgumentException('Tabla inválida.'); }
        ModeloTenant::exigirCurso($datos['idCurso']);
        self::prepararEstructuraAcademica();
        $registro = Conexion::conectar()->prepare("UPDATE $tabla SET nombreCurso=:nombreCurso, contenidoCurso=:contenidoCurso, estado=:estado, fechaInicioCurso=:fechaInicioCurso, fechaFinCurso=:fechaFinCurso, horarioCurso=:horarioCurso, modalidadCalificacion=:modalidadCalificacion, intensificacionActiva=:intensificacionActiva WHERE idCurso=:idCurso AND " . ModeloTenant::cursos('cursos'));
        $registro->bindParam(":idCurso", $datos["idCurso"], PDO::PARAM_INT);
        $registro->bindParam(":nombreCurso", $datos["nombreCurso"], PDO::PARAM_STR);
        $registro->bindParam(":contenidoCurso", $datos["contenidoCurso"], PDO::PARAM_STR);
        $registro->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
        $registro->bindParam(":fechaInicioCurso", $datos["fechaInicioCurso"], PDO::PARAM_STR);
        $registro->bindParam(":fechaFinCurso", $datos["fechaFinCurso"], PDO::PARAM_STR);
        $registro->bindParam(":horarioCurso", $datos["horarioCurso"], PDO::PARAM_STR);
        $registro->bindValue(":modalidadCalificacion",$datos['modalidadCalificacion'],PDO::PARAM_STR);
        $registro->bindValue(":intensificacionActiva",(int)$datos['intensificacionActiva'],PDO::PARAM_INT);

        if ($registro->execute()) {
            return "ok";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }

        $registro->closeCursor();
        $registro = null;
    }

    static public function mdlCursoTieneCalificaciones($idCurso)
    {
        ModeloTenant::exigirCurso($idCurso);
        $stmt=Conexion::conectar()->prepare('SELECT (SELECT COUNT(*) FROM calificaciones WHERE id_curso=:curso1)+(SELECT COUNT(*) FROM evaluaciones_calificaciones ec INNER JOIN evaluaciones e ON e.idEvaluacion=ec.id_evaluacion WHERE e.id_curso=:curso2)+(SELECT COUNT(*) FROM cierres_periodo_calificaciones cp INNER JOIN secciones s ON s.idSeccion=cp.id_seccion WHERE s.id_curso=:curso3) total');
        $stmt->execute([':curso1'=>(int)$idCurso,':curso2'=>(int)$idCurso,':curso3'=>(int)$idCurso]);return (int)($stmt->fetchColumn()?:0)>0;
    }

    static public function mdlCambiarEstadoActivoCurso($idCurso, $activo, $motivo, $idAdministrador)
    {
        ModeloTenant::exigirCurso($idCurso);
        $stmt = Conexion::conectar()->prepare("
            UPDATE cursos
            SET activo = :activo,
                fechaBaja = :fechaBaja,
                motivoBaja = :motivoBaja,
                usuarioBaja = :usuarioBaja
            WHERE idCurso = :idCurso AND " . ModeloTenant::cursos('cursos') . "
        ");
        $esActivo = (int) $activo === 1;
        $stmt->bindValue(':activo', $esActivo ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':fechaBaja', $esActivo ? null : date('Y-m-d H:i:s'), $esActivo ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':motivoBaja', $esActivo ? null : trim((string) $motivo), $esActivo ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':usuarioBaja', $esActivo ? null : (int) $idAdministrador, $esActivo ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);

        return $stmt->execute() ? 'ok' : 'error';
    }

    static public function mdlActualizarResponsableCurso($idCurso, $idResponsable)
    {
        ModeloTenant::exigirCurso($idCurso);
        if ((int) $idResponsable > 0) { ModeloTenant::exigirUsuario($idResponsable, ['DOCENTE', 'ADMINISTRADOR']); }
        $stmt = Conexion::conectar()->prepare('UPDATE cursos SET responsable = :idResponsable WHERE idCurso = :idCurso AND ' . ModeloTenant::cursos('cursos') . ' AND ' . ((int)$idResponsable > 0 ? ModeloTenant::usuarioIdConRol($idResponsable,['DOCENTE','ADMINISTRADOR']) : '1=1'));
        $idResponsable = (int) $idResponsable;
        $stmt->bindValue(':idResponsable', $idResponsable > 0 ? $idResponsable : null, $idResponsable > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);

        return $stmt->execute() ? 'ok' : 'error';
    }

    static public function mdlDependenciasCurso($idCurso)
    {
        ModeloTenant::exigirCurso($idCurso);
        $relaciones = [
            'secciones' => 'id_curso',
            'asignacioncursos' => 'id_seccion',
            'calificaciones' => 'id_curso',
            'entregaslecciones' => 'id_curso',
            'posteos' => 'id_curso',
            'evaluaciones' => 'id_curso',
            'actividades' => 'id_curso',
        ];
        $pdo = Conexion::conectar();
        $dependencias = [];

        foreach ($relaciones as $tabla => $columna) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM {$tabla} WHERE {$columna} = :idCurso");
                $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
                $stmt->execute();
                $total = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
                if ($total > 0) {
                    $dependencias[$tabla] = $total;
                }
            } catch (PDOException $e) {
                // Algunas instalaciones antiguas aun no tienen todas las tablas opcionales.
                if ((int) ($e->errorInfo[1] ?? 0) !== 1146) {
                    throw $e;
                }
            }
        }

        return $dependencias;
    }

    static public function mdlEliminarCurso($idCurso)
    {
        ModeloTenant::exigirCurso($idCurso);
        $stmt = Conexion::conectar()->prepare('DELETE FROM cursos WHERE idCurso = :idCurso AND ' . ModeloTenant::cursos('cursos'));
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);

        return $stmt->execute() && $stmt->rowCount() === 1 ? 'ok' : 'error';
    }

    /* ASIGNAR CURSO */
    static public function mdlEstudiantesDisponiblesCurso($idCurso)
    {
        ModeloTenant::exigirCurso($idCurso);
        $rolEstudiante = ModeloTenant::activo() ? ModeloTenant::usuarioConRol('u.idUsuario', ['ESTUDIANTE']) : "u.rol = 'ESTUDIANTE'";
        self::prepararEstructuraAcademica();
        $stmt = Conexion::conectar()->prepare("
            SELECT u.idUsuario,u.nombreUsuario,u.apellidoUsuario,u.email,u.imgUsuario,u.activo
            FROM usuarios u
            WHERE " . $rolEstudiante . "
              AND u.activo = 1
              AND NOT EXISTS (
                  SELECT 1
                  FROM asignacioncursos a
                  WHERE a.id_estudiante = u.idUsuario
                    AND a.id_seccion = :idCurso AND a.estadoInscripcion='ACTIVA'
              )
            ORDER BY u.apellidoUsuario ASC, u.nombreUsuario ASC
        ");
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    private static function validarDuplicacionInstitucional($idCurso, $idUsuario)
    {
        if ((int)$idUsuario !== (int)($_SESSION['usuario']['id'] ?? 0)) { throw new RuntimeException('Identidad institucional no válida.'); }
        ModeloTenant::exigirUsuario($idUsuario, ['DOCENTE','ADMINISTRADOR']);
        $pdo=Conexion::conectar();
        $stmt=$pdo->prepare('SELECT docente,tutor FROM secciones WHERE id_curso=? AND ' . ModeloTenant::secciones('secciones'));
        $stmt->execute([(int)$idCurso]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $seccion) {
            ModeloTenant::exigirUsuario($seccion['docente'],['DOCENTE','ADMINISTRADOR']);
            if ((int)$seccion['tutor'] > 0) { ModeloTenant::exigirUsuario($seccion['tutor'],['DOCENTE','ADMINISTRADOR']); }
        }
        // Preflight antes de crear filas: nunca copiar un archivo cuya atribución sea ambigua.
        $referenciasAjenas=[];
        $consultas=[
            'SELECT r.urlRecurso FROM recursoslecciones r WHERE NOT (' . ModeloTenant::hijoLeccion('r') . ')',
            'SELECT a.urlArchivo FROM archivoslecciones a WHERE NOT (' . ModeloTenant::hijoLeccion('a') . ')',
            'SELECT e.urlArchivo FROM entregaslecciones e WHERE NOT (' . ModeloTenant::entregas() . ')',
            'SELECT a.rutaArchivo FROM entregaslecciones_adjuntos a WHERE NOT (' . ModeloTenant::adjuntosEntrega('a') . ')',
        ];
        foreach ($consultas as $sql) {
            foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $rutaAjena) {
                if (trim((string)$rutaAjena)==='' || preg_match('~^https?://~i',(string)$rutaAjena)) { continue; }
                $canon=realpath(__DIR__ . '/../' . ltrim(str_replace('\\','/',(string)$rutaAjena),'/'));
                if ($canon) { $referenciasAjenas[strtolower($canon)]=true; }
            }
        }
        $stmt=$pdo->prepare('SELECT r.urlRecurso FROM recursoslecciones r INNER JOIN lecciones l ON l.idLeccion=r.id_leccion
            INNER JOIN secciones s ON s.idSeccion=l.id_modulo WHERE s.id_curso=?
            UNION SELECT a.urlArchivo FROM archivoslecciones a INNER JOIN lecciones l ON l.idLeccion=a.id_leccion
            INNER JOIN secciones s ON s.idSeccion=l.id_modulo WHERE s.id_curso=?');
        $stmt->execute([(int)$idCurso,(int)$idCurso]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $ruta) {
            $ruta=trim((string)$ruta);
            if ($ruta === '' || preg_match('~^https?://~i',$ruta)) { continue; }
            $raiz=realpath(__DIR__ . '/../uploads/lecciones');
            $archivo=realpath(__DIR__ . '/../' . ltrim(str_replace('\\','/',$ruta),'/'));
            if (!$raiz || !$archivo || !is_file($archivo) || strpos($archivo,$raiz . DIRECTORY_SEPARATOR)!==0) {
                throw new RuntimeException('Archivo institucional ausente o fuera del directorio permitido.');
            }
            if (isset($referenciasAjenas[strtolower($archivo)])) { throw new RuntimeException('Archivo institucional con referencias ambiguas.'); }
        }
    }

    public static function mdlInscripcionesCurso($idCurso, $estado = 'ACTIVA')
    {
        ModeloTenant::exigirCurso($idCurso);
        if (!in_array($estado, ['ACTIVA','BAJA'], true)) { return []; }
        $filtro = ModeloTenant::cursoId($idCurso);
        if ($estado === 'ACTIVA') {
            $filtro .= ' AND ' . (ModeloTenant::activo()
                ? ModeloTenant::usuarioConRol('u.idUsuario',['ESTUDIANTE']) : "u.rol='ESTUDIANTE'");
        }
        $stmt=Conexion::conectar()->prepare('SELECT a.*,u.idUsuario,u.nombreUsuario,u.apellidoUsuario,u.email,u.imgUsuario
            FROM asignacioncursos a INNER JOIN usuarios u ON u.idUsuario=a.id_estudiante
            WHERE a.id_seccion=? AND a.estadoInscripcion=? AND ' . $filtro . ' ORDER BY u.apellidoUsuario,u.nombreUsuario');
        $stmt->execute([(int)$idCurso,$estado]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlAsignarCurso($tabla, $datos)
    {
        if ($tabla !== 'asignacioncursos') { throw new InvalidArgumentException('Tabla inválida.'); }
        ModeloTenant::exigirCurso($datos['idCurso']);
        ModeloTenant::exigirUsuario($datos['idUsuario'], ['ESTUDIANTE']);
        $rolEstudiante = ModeloTenant::activo() ? ModeloTenant::usuarioConRol('u.idUsuario', ['ESTUDIANTE']) : "u.rol = 'ESTUDIANTE'";
        self::prepararEstructuraAcademica();
        $filtroInscripcion = ModeloTenant::cursoId($datos['idCurso']);
        if (ModeloTenant::activo()) {
            $filtroInscripcion .= ' AND ' . ModeloTenant::usuarioConRol('asignacioncursos.id_estudiante', ['ESTUDIANTE']);
        }
        $reactivar=Conexion::conectar()->prepare("UPDATE $tabla SET estadoInscripcion='ACTIVA',fechaAlta=NOW(),fechaBaja=NULL,motivoBaja=NULL WHERE id_estudiante=:idUsuario AND id_seccion=:idCurso AND estadoInscripcion='BAJA' AND " . $filtroInscripcion);
        $reactivar->execute([':idUsuario'=>(int)$datos['idUsuario'],':idCurso'=>(int)$datos['idCurso']]);
        if($reactivar->rowCount()===1){return 'ok';}
        $registro = Conexion::conectar()->prepare("
            INSERT INTO $tabla (id_estudiante,id_seccion,estadoInscripcion,fechaAlta)
            SELECT u.idUsuario, :idCurso, 'ACTIVA', NOW()
            FROM usuarios u
            WHERE u.idUsuario = :idUsuario
              AND " . ModeloTenant::cursoId($datos['idCurso']) . "
              AND " . $rolEstudiante . "
              AND u.activo = 1
              AND NOT EXISTS (
                SELECT 1
                FROM $tabla
                WHERE id_estudiante = :idUsuarioExiste
                  AND id_seccion = :idCursoExiste
            )
        ");

        $registro->bindParam(":idCurso", $datos["idCurso"], PDO::PARAM_INT);
        $registro->bindParam(":idUsuario", $datos["idUsuario"], PDO::PARAM_INT);
        $registro->bindParam(":idCursoExiste", $datos["idCurso"], PDO::PARAM_INT);
        $registro->bindParam(":idUsuarioExiste", $datos["idUsuario"], PDO::PARAM_INT);

     
        if ($registro->execute()) {
            return $registro->rowCount() === 1 ? "ok" : "exists";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }

        $registro->closeCursor();
        $registro = null;
        
    }

    static public function mdlQuitarEstudianteCurso($idCurso, $idUsuario)
    {
        ModeloTenant::exigirCurso($idCurso);
        self::prepararEstructuraAcademica();
        $registro = Conexion::conectar()->prepare("
            UPDATE asignacioncursos SET estadoInscripcion='BAJA',fechaBaja=NOW(),motivoBaja=:motivo
            WHERE id_seccion = :idCurso
              AND " . ModeloTenant::cursoId($idCurso) . "
              AND id_estudiante = :idUsuario AND estadoInscripcion='ACTIVA'
        ");
        $registro->bindValue(":idCurso", (int) $idCurso, PDO::PARAM_INT);
        $registro->bindValue(":idUsuario", (int) $idUsuario, PDO::PARAM_INT);
        $registro->bindValue(":motivo",trim((string)($_POST['motivoBaja']??'')),PDO::PARAM_STR);

        return $registro->execute() ? "ok" : "error";
    }
}
