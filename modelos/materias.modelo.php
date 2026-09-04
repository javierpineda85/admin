<?php
require_once('conexion.php');

class ModeloMaterias
{
    static public function mdlBuscarMateriaPorId($idSeccion)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT s.*,
                   CONCAT(creador.nombreUsuario, ' ', creador.apellidoUsuario) AS creadorNombre
            FROM secciones s
            LEFT JOIN usuarios creador ON creador.idUsuario = s.creadoPor
            WHERE s.idSeccion = :idSeccion
            LIMIT 1
        ");
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* GUARDAR MATERIA */
    static public function mdlGuardarMateria($tabla, $datos)
    {

        $registro = Conexion::conectar()->prepare("INSERT INTO $tabla (tituloSeccion, contenidoSeccion, id_curso, docente, tutor, bannerSeccion, colorInicioBanner, colorFinBanner, creadoPor) VALUES (:tituloSeccion, :contenidoSeccion, :id_curso, :docente, :tutor, :bannerSeccion, :colorInicioBanner, :colorFinBanner, :creadoPor)");

        $registro->bindParam(":tituloSeccion", $datos["tituloSeccion"], PDO::PARAM_STR);
        $registro->bindParam(":contenidoSeccion", $datos["contenidoSeccion"], PDO::PARAM_STR);
        $registro->bindParam(":id_curso", $datos["id_curso"], PDO::PARAM_INT);
        $registro->bindParam(":docente", $datos["docente"], PDO::PARAM_INT);
        $registro->bindParam(":tutor", $datos["tutor"], PDO::PARAM_INT);
        $registro->bindParam(":bannerSeccion", $datos["bannerSeccion"], PDO::PARAM_STR);
        $registro->bindParam(":colorInicioBanner", $datos["colorInicioBanner"], PDO::PARAM_STR);
        $registro->bindParam(":colorFinBanner", $datos["colorFinBanner"], PDO::PARAM_STR);
        $registro->bindParam(":creadoPor", $datos["creadoPor"], PDO::PARAM_INT);

        if ($registro->execute()) {
            return "ok";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }
        $registro->closeCursor();
        $registro = null;
    }

    static public function mdlModificarMateria($tabla, $datos)
    {
        $registro = Conexion::conectar()->prepare("
            UPDATE $tabla
            SET tituloSeccion = :tituloSeccion,
                contenidoSeccion = :contenidoSeccion,
                id_curso = :id_curso,
                docente = :docente,
                tutor = :tutor,
                bannerSeccion = :bannerSeccion,
                colorInicioBanner = :colorInicioBanner,
                colorFinBanner = :colorFinBanner
            WHERE idSeccion = :idSeccion
        ");

        $registro->bindParam(":tituloSeccion", $datos["tituloSeccion"], PDO::PARAM_STR);
        $registro->bindParam(":contenidoSeccion", $datos["contenidoSeccion"], PDO::PARAM_STR);
        $registro->bindParam(":id_curso", $datos["id_curso"], PDO::PARAM_INT);
        $registro->bindParam(":docente", $datos["docente"], PDO::PARAM_INT);
        $registro->bindParam(":tutor", $datos["tutor"], PDO::PARAM_INT);
        $registro->bindParam(":bannerSeccion", $datos["bannerSeccion"], PDO::PARAM_STR);
        $registro->bindParam(":colorInicioBanner", $datos["colorInicioBanner"], PDO::PARAM_STR);
        $registro->bindParam(":colorFinBanner", $datos["colorFinBanner"], PDO::PARAM_STR);
        $registro->bindParam(":idSeccion", $datos["idSeccion"], PDO::PARAM_INT);

        if ($registro->execute()) {
            return "ok";
        }

        return "error";
    }


      
    /*SELECCIONAR O LISTAR MATERIAS */
    static public function mdlBuscarMateriaXcurso($item, $valor)
    {
        if ($item == 'join-1-curso') {

            /* JOIN con usuarios y 1 curso en especifico */
            $stmt = Conexion::conectar()->prepare("SELECT idSeccion, tituloSeccion, contenidoSeccion,id_curso, docente, tutor, cursos.nombreCurso, usuarios.nombreUsuario , usuarios.apellidoUsuario FROM secciones JOIN cursos ON secciones.id_curso = cursos.idCurso JOIN usuarios ON secciones.docente = usuarios.idUsuario WHERE secciones.id_curso= $valor ORDER BY tituloSeccion ASC");

            $stmt->execute();
            return $stmt->fetchAll();
            $stmt->closeCursor();
            $stmt = null;
        }
    }

    static public function mdlBuscarMateriasPorDocente($idDocente)
    {
        return self::mdlListarMateriasGestion((int) $idDocente);
    }

    static public function mdlListarMateriasGestion($idDocente = 0)
    {
        $filtroDocente = (int) $idDocente > 0
            ? ' WHERE (s.docente = :idDocente OR s.tutor = :idDocente) AND s.activo = 1 AND c.activo = 1'
            : '';

        $stmt = Conexion::conectar()->prepare("
            SELECT s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor,
                   s.bannerSeccion, s.colorInicioBanner, s.colorFinBanner, s.creadoPor, s.activo,
                   c.nombreCurso,
                   u.nombreUsuario, u.apellidoUsuario,
                   tutor.nombreUsuario AS nombreTutor, tutor.apellidoUsuario AS apellidoTutor,
                   COUNT(DISTINCT l.idLeccion) AS totalLecciones,
                   COUNT(DISTINCT CASE WHEN l.tipoLeccion = 'TAREA' THEN l.idLeccion END) AS totalTareas
            FROM secciones s
            INNER JOIN cursos c ON s.id_curso = c.idCurso
            INNER JOIN usuarios u ON s.docente = u.idUsuario
            LEFT JOIN usuarios tutor ON tutor.idUsuario = s.tutor
            LEFT JOIN lecciones l ON l.id_modulo = s.idSeccion
            " . $filtroDocente . "
            GROUP BY s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor,
                     s.bannerSeccion, s.colorInicioBanner, s.colorFinBanner,
                     c.nombreCurso, u.nombreUsuario, u.apellidoUsuario,
                     tutor.nombreUsuario, tutor.apellidoUsuario
            ORDER BY c.nombreCurso ASC, s.tituloSeccion ASC
        ");

        if ((int) $idDocente > 0) {
            $stmt->bindValue(':idDocente', (int) $idDocente, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlCambiarEstadoActivoMateria($idSeccion, $activo, $motivo, $idAdministrador)
    {
        $stmt = Conexion::conectar()->prepare("
            UPDATE secciones
            SET activo = :activo,
                fechaBaja = :fechaBaja,
                motivoBaja = :motivoBaja,
                usuarioBaja = :usuarioBaja
            WHERE idSeccion = :idSeccion
        ");
        $esActiva = (int) $activo === 1;
        $stmt->bindValue(':activo', $esActiva ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':fechaBaja', $esActiva ? null : date('Y-m-d H:i:s'), $esActiva ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':motivoBaja', $esActiva ? null : trim((string) $motivo), $esActiva ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':usuarioBaja', $esActiva ? null : (int) $idAdministrador, $esActiva ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);

        return $stmt->execute() ? 'ok' : 'error';
    }

    static public function mdlDependenciasMateria($idSeccion)
    {
        $relaciones = [
            'lecciones' => 'id_modulo',
            'calificaciones' => 'id_seccion',
            'entregaslecciones' => 'id_seccion',
            'evaluaciones' => 'id_seccion',
            'actividades' => 'id_seccion',
        ];
        $pdo = Conexion::conectar();
        $dependencias = [];

        foreach ($relaciones as $tabla => $columna) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM {$tabla} WHERE {$columna} = :idSeccion");
                $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
                $stmt->execute();
                $total = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
                if ($total > 0) {
                    $dependencias[$tabla] = $total;
                }
            } catch (PDOException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) !== 1146) {
                    throw $e;
                }
            }
        }

        return $dependencias;
    }

    static public function mdlEliminarMateria($idSeccion)
    {
        $stmt = Conexion::conectar()->prepare('DELETE FROM secciones WHERE idSeccion = :idSeccion');
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);

        return $stmt->execute() && $stmt->rowCount() === 1 ? 'ok' : 'error';
    }
}
