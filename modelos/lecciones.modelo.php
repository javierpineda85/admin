<?php
require_once('conexion.php');

class ModeloLecciones
{
    public static function mdlBuscarSeccionPorId($idSeccion)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor, c.nombreCurso, c.estado, c.fechaInicioCurso, c.fechaFinCurso, c.horarioCurso, u.nombreUsuario, u.apellidoUsuario
             FROM secciones s
             INNER JOIN cursos c ON c.idCurso = s.id_curso
             INNER JOIN usuarios u ON u.idUsuario = s.docente
             WHERE s.idSeccion = :idSeccion
             LIMIT 1'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlBuscarLeccionesPorSeccion($idSeccion)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT l.idLeccion, l.nombreLeccion, l.contenidoLeccion, l.id_modulo, COUNT(r.idRecursoLeccion) AS totalRecursos
             FROM lecciones l
             LEFT JOIN recursoslecciones r ON r.id_leccion = l.idLeccion
             WHERE l.id_modulo = :idSeccion
             GROUP BY l.idLeccion, l.nombreLeccion, l.contenidoLeccion, l.id_modulo
             ORDER BY l.idLeccion ASC'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarLeccionPorId($idLeccion)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT * FROM lecciones WHERE idLeccion = :idLeccion LIMIT 1'
        );
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
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

    public static function mdlGuardarLeccion($tabla, $datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO $tabla (nombreLeccion, contenidoLeccion, id_modulo) VALUES (:nombreLeccion, :contenidoLeccion, :id_modulo)"
        );

        $stmt->bindValue(':nombreLeccion', $datos['nombreLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':contenidoLeccion', $datos['contenidoLeccion'], PDO::PARAM_STR);
        $stmt->bindValue(':id_modulo', (int) $datos['id_modulo'], PDO::PARAM_INT);

        if ($stmt->execute()) {
            return 'ok';
        }

        return 'error';
    }

    public static function mdlGuardarRecursoLeccion($tabla, $datos)
    {
        $stmt = Conexion::conectar()->prepare(
            "INSERT INTO $tabla (id_leccion, tipoRecurso, tituloRecurso, urlRecurso, creadoPor) VALUES (:id_leccion, :tipoRecurso, :tituloRecurso, :urlRecurso, :creadoPor)"
        );

        $stmt->bindValue(':id_leccion', (int) $datos['id_leccion'], PDO::PARAM_INT);
        $stmt->bindValue(':tipoRecurso', $datos['tipoRecurso'], PDO::PARAM_STR);
        $stmt->bindValue(':tituloRecurso', $datos['tituloRecurso'], PDO::PARAM_STR);
        $stmt->bindValue(':urlRecurso', $datos['urlRecurso'], PDO::PARAM_STR);
        $stmt->bindValue(':creadoPor', (int) $datos['creadoPor'], PDO::PARAM_INT);

        if ($stmt->execute()) {
            return 'ok';
        }

        return 'error';
    }
}
