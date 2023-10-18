<?php
require_once('conexion.php');

class ModeloPerfiles
{
    /*GUARDA UN PERFIL */
    static public function mdlGuardarPerfil($datos)
    {
        $registro = Conexion::conectar()->prepare("INSERT INTO perfiles (id_usuario, fnacPerfil, domicilioPerfil, contenidoPerfil) VALUES (:id_usuario, :fnacPerfil, :domicilioPerfil, :contenidoPerfil)");

        $registro->bindParam(":id_usuario", $datos["id_usuario"], PDO::PARAM_INT);
        $registro->bindParam(":fnacPefil", $datos["fnacPefil"], PDO::PARAM_STR);
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

    /*BUSCAR UN PERFIL */
    static public function mdlSeleccionarPerfil($item, $valor)
    {

        $stmt = Conexion::conectar()->prepare("SELECT *,DATE_FORMAT(fnacPerfil, '%d/%m/%Y') AS fnac FROM perfiles WHERE $item = '$valor' ");
        $stmt->execute();
        return $stmt->fetchAll();
        $stmt->closeCursor();

        $stmt = null;
    }
}
