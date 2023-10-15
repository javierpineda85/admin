<?php
require_once('conexion.php');

class ModeloUsuarios
{
    /*SELECCIONAR USUARIO - LISTADO */
    static public function mdlSeleccionarUsuario($tabla, $item, $valor)
    {

        if ($item != null && $valor != null) {

            /*Un usuario especifico */
            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = '$valor' ");
            //var_dump($stmt);exit;
            $stmt->execute();
            return $stmt->fetchAll();
            $stmt->closeCursor();

            $stmt = null;
        } else if($item=='count'){

            /*TOTAL DE USUARIOS */
            $stmt = Conexion::conectar()->prepare("SELECT count(*) as totalUsuarios FROM $tabla");
            $stmt->execute();
            return $stmt->fetchAll();
            $stmt->closeCursor();
        } else {
            /*TODOS los usuarios  */
            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla ORDER BY rol ");
            $stmt->execute();
            return $stmt->fetchAll();
            $stmt->closeCursor();
        }
    }


    /*INSERTAR USUARIO */
    static public function mdlGuardarUsuario($tabla, $datos)
    {
        $registro = Conexion::conectar()->prepare("INSERT INTO $tabla (nombreUsuario, apellidoUsuario, email, pass, resetPass, imgUsuario, activo, rol) VALUES (:nombreUsuario, :apellidoUsuario, :email, :pass, :resetPass, :imgUsuario, :activo, :rol)");

        $registro->bindParam(":nombreUsuario", $datos["nombreUsuario"], PDO::PARAM_STR);
        $registro->bindParam(":apellidoUsuario", $datos["apellidoUsuario"], PDO::PARAM_STR);
        $registro->bindParam(":email", $datos["email"], PDO::PARAM_STR);
        $registro->bindParam(":pass", $datos["pass"], PDO::PARAM_STR);
        $registro->bindParam(":resetPass", $datos["resetPass"], PDO::PARAM_INT);
        $registro->bindParam(":imgUsuario", $datos["imgUsuario"], PDO::PARAM_STR);
        $registro->bindParam(":activo", $datos["activo"], PDO::PARAM_INT);
        $registro->bindParam(":rol", $datos["rol"], PDO::PARAM_STR);

        if ($registro->execute()) {
            return "ok";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }

        $registro->closeCursor();
        $registro = null;
    }
}
