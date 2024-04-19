<?php

class ModeloUsuarios
{


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


    /*MODIFICAR USUARIO */
    static public function mdlModificarUsuario($tabla, $datos)
    {
        $registro = Conexion::conectar()->prepare("UPDATE $tabla set nombreUsuario = :nombreUsuario, apellidoUsuario = :apellidoUsuario, email = :email, rol = :rol WHERE idUsuario = :idUsuario");
        
        $registro->bindParam(":idUsuario", $datos["idUsuario"],PDO::PARAM_INT);
        $registro->bindParam(":nombreUsuario", $datos["nombreUsuario"], PDO::PARAM_STR);
        $registro->bindParam(":apellidoUsuario", $datos["apellidoUsuario"], PDO::PARAM_STR);
        $registro->bindParam(":email", $datos["emailUsuario"], PDO::PARAM_STR);
        $registro->bindParam(":rol", $datos["rol"], PDO::PARAM_STR);
    
        // Si la contraseña está presente en los datos, la actualizamos en la consulta SQL
        if (isset($datos["passUsuario"])) {
            $registro->bindValue(":passUsuario", $datos["passUsuario"], PDO::PARAM_STR);
        }
    
        $registro->execute();
    
        if ($registro->rowCount() > 0) {
            return "ok";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }
    
        $registro->closeCursor();
        $registro = null;
    }
    
}
