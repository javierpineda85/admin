<?php
require_once('conexion.php');

class ModeloCalificaciones
{
    public static function mdlGuardarCalificacion($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            'INSERT INTO calificaciones (id_estudiante, id_seccion, id_modulo, id_curso, calificacion)
             VALUES (:id_estudiante, :id_seccion, :id_modulo, :id_curso, :calificacion)
             ON DUPLICATE KEY UPDATE
                id_curso = VALUES(id_curso),
                calificacion = VALUES(calificacion)'
        );
        $stmt->bindValue(':id_estudiante', (int) $datos['id_estudiante'], PDO::PARAM_INT);
        $stmt->bindValue(':id_seccion', (int) $datos['id_seccion'], PDO::PARAM_INT);
        $stmt->bindValue(':id_modulo', (int) $datos['id_modulo'], PDO::PARAM_INT);
        $stmt->bindValue(':id_curso', (int) $datos['id_curso'], PDO::PARAM_INT);
        $stmt->bindValue(':calificacion', (int) $datos['calificacion'], PDO::PARAM_INT);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlCalificacionesPorSeccion($idSeccion)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT c.idCalificacion, c.id_estudiante, c.id_seccion, c.id_modulo, c.id_curso, c.calificacion,
                    l.nombreLeccion, l.tipoLeccion,
                    u.nombreUsuario, u.apellidoUsuario, u.email
             FROM calificaciones c
             INNER JOIN usuarios u ON u.idUsuario = c.id_estudiante
             LEFT JOIN lecciones l ON l.idLeccion = c.id_modulo
             WHERE c.id_seccion = :idSeccion
             ORDER BY c.idCalificacion DESC'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCalificacionesPorEstudiante($idSeccion, $idEstudiante)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT c.idCalificacion, c.id_estudiante, c.id_seccion, c.id_modulo, c.id_curso, c.calificacion,
                    l.nombreLeccion, l.tipoLeccion
             FROM calificaciones c
             LEFT JOIN lecciones l ON l.idLeccion = c.id_modulo
             WHERE c.id_seccion = :idSeccion
               AND c.id_estudiante = :idEstudiante
             ORDER BY c.idCalificacion DESC'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
