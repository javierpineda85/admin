<?php
require_once('conexion.php');

class ModeloMaterias
{

    /* GUARDAR MATERIA */
    static public function mdlGuardarMateria($tabla, $datos)
    {

        $registro = Conexion::conectar()->prepare("INSERT INTO $tabla (tituloSeccion, contenidoSeccion, id_curso, docente, tutor) VALUES (:tituloSeccion, :contenidoSeccion, :id_curso, :docente, :tutor)");

        $registro->bindParam(":tituloSeccion", $datos["tituloSeccion"], PDO::PARAM_STR);
        $registro->bindParam(":contenidoSeccion", $datos["contenidoSeccion"], PDO::PARAM_STR);
        $registro->bindParam(":id_curso", $datos["id_curso"], PDO::PARAM_INT);
        $registro->bindParam(":docente", $datos["docente"], PDO::PARAM_INT);
        $registro->bindParam(":tutor", $datos["tutor"], PDO::PARAM_INT);

        if ($registro->execute()) {
            return "ok";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }
        $registro->closeCursor();
        $registro = null;
    }

    /*SELECCIONAR O LISTAR MATERIAS */
    static public function mdlSeleccionarMateria($tabla, $item, $valor)
    {
       
        /*Busca una materia especifica? */
        if ($item != null && $valor != null) {

            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = '$valor' ");

            $stmt->execute();
            return $stmt->fetchAll();
            $stmt->closeCursor();
            $stmt = null;

        } else if ($item == 'join') {

            /* JOIN con usuarios y cursos */
            $stmt = Conexion::conectar()->prepare("SELECT idSeccion, tituloSeccion, contenidoSeccion,id_curso, docente, tutor, cursos.nombreCurso, usuarios.nombreUsuario , usuarios.apellidoUsuario FROM secciones JOIN cursos ON secciones.id_curso = cursos.idCurso JOIN usuarios ON secciones.docente = usuarios.idUsuario  ORDER BY tituloSeccion ASC");

            $stmt->execute();
            return $stmt->fetchAll();
            $stmt->closeCursor();
            $stmt = null;

        } else if($item == 'count'){

            /*TOTAL de registros */
            $stmt = Conexion::conectar()->prepare("SELECT count(*) as totalMaterias FROM $tabla ");
            $stmt->execute();
            return $stmt->fetchAll();
            $stmt->closeCursor();
        }else  {

            /*Trae todas las materias? */
            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla ORDER BY tituloSeccion ");
            $stmt->execute();
            return $stmt->fetchAll();
            $stmt->closeCursor();
        }
    }
}
