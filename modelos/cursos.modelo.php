<?php
require_once('conexion.php');

class ModeloCursos
{
    /*SELECCIONAR CURSO */
    static public function mdlSeleccionarCursos($tabla, $item, $valor)
    {

        if ($item != null && $valor != null) {
            /*Traer un solo item */

            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = '$valor' ");
            //var_dump($stmt);exit;
            $stmt->execute();
            return $stmt->fetch();
            $stmt->closeCursor();

            $stmt = null;

        } else if ($item == 'count') {
            /*CONTAR TOTAL DE REGISTROS */
            $stmt = Conexion::conectar()->prepare("SELECT count(*) as totalCursos FROM $tabla ");
            $stmt->execute();
            return $stmt->fetch();
            $stmt->closeCursor();

            $stmt = null;
        } else {

            /*Traer todos los registros */
            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla ORDER BY nombreCurso ASC ");
            //var_dump($stmt);exit;
            $stmt->execute();
            return $stmt->fetchAll();
            $stmt->closeCursor();
        }
    }

    /* GUARDAR CURSO */
    static public function mdlGuardarCurso($tabla, $datos)
    {

        $registro = Conexion::conectar()->prepare("INSERT INTO $tabla(nombreCurso, contenidoCurso, estado, fechaInicioCurso, fechaFinCurso, horarioCurso) VALUES(:nombreCurso, :contenidoCurso, :estado, :fechaInicioCurso, :fechaFinCurso, :horarioCurso)");

        $registro->bindParam(":nombreCurso", $datos["nombreCurso"], PDO::PARAM_STR);
        $registro->bindParam(":contenidoCurso", $datos["contenidoCurso"], PDO::PARAM_STR);
        $registro->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
        $registro->bindParam(":fechaInicioCurso", $datos["fechaInicioCurso"], PDO::PARAM_STR);
        $registro->bindParam(":fechaFinCurso", $datos["fechaFinCurso"], PDO::PARAM_STR);
        $registro->bindParam(":horarioCurso", $datos["horarioCurso"], PDO::PARAM_STR);

        if ($registro->execute()) {
            return "ok";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }

        $registro->closeCursor();
        $registro = null;
    }
}
