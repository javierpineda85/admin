<?php
require_once('conexion.php');

class ModeloPerfiles
{
    /*EDITA UN PERFIL */
    static public function mdlEditarPerfil($datos)
    {

        $registro = Conexion::conectar()->prepare("UPDATE perfiles SET fnacPerfil = :fnacPerfil, domicilioPerfil = :domicilioPerfil, contenidoPerfil = :contenidoPerfil WHERE id_usuario= :id_usuario");

        $registro->bindParam(":id_usuario", $datos["idUsuario"], PDO::PARAM_INT);
        $registro->bindParam(":fnacPerfil", $datos["fnac"], PDO::PARAM_STR);
        $registro->bindParam(":domicilioPerfil", $datos["domicilioPerfil"], PDO::PARAM_STR);
        $registro->bindParam(":contenidoPerfil", $datos["contenidoPerfil"], PDO::PARAM_STR);

        if ($registro->execute()) {
            return "ok";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }

        $registro->closeCursor();
        $registro = null;
    }

    /*INSERTA UN PERFIL */
    static public function mdlGuardarPerfil($datos)
    {

        $registro = Conexion::conectar()->prepare("INSERT INTO perfiles (id_usuario, dniPerfil, telefonoPerfil, fnacPerfil, domicilioPerfil, provinciaPerfil, contenidoPerfil) VALUES (:id_usuario, :dniPerfil, :telefonoPerfil, :fnacPerfil, :domicilioPerfil, :provinciaPerfil, :contenidoPerfil)");

        $registro->bindParam(":id_usuario", $datos["idUsuario"], PDO::PARAM_INT);
        $registro->bindParam(":dniPerfil", $datos["dniPerfil"], PDO::PARAM_INT);
        $registro->bindParam(":telefonoPerfil", $datos["telefonoPerfil"], PDO::PARAM_STR);
        $registro->bindParam(":fnacPerfil", $datos["fnacPerfil"], PDO::PARAM_STR);
        $registro->bindParam(":domicilioPerfil", $datos["domicilioPerfil"], PDO::PARAM_STR);
        $registro->bindParam(":provinciaPerfil", $datos["provinciaPerfil"], PDO::PARAM_STR);
        $registro->bindParam(":contenidoPerfil", $datos["contenidoPerfil"], PDO::PARAM_STR);

        if ($registro->execute()) {

            return "ok";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }

        $registro->closeCursor();
        $registro = null;
    }
}
