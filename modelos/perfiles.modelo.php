<?php
require_once('conexion.php');

class ModeloPerfiles
{
    static public function mdlObtenerPerfilPorUsuario($idUsuario)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT *,
                   DATE_FORMAT(fnacPerfil, '%d/%m/%Y') AS fnacFormateada
            FROM perfiles
            WHERE id_usuario = :id_usuario
            LIMIT 1
        ");
        $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /*EDITA UN PERFIL */
    static public function mdlEditarPerfil($datos)
    {
        $conexion = Conexion::conectar();
        $idUsuario = (int) ($datos["idUsuario"] ?? 0);

        $existe = $conexion->prepare("SELECT idPerfil FROM perfiles WHERE id_usuario = :id_usuario LIMIT 1");
        $existe->bindValue(":id_usuario", $idUsuario, PDO::PARAM_INT);
        $existe->execute();

        if (!$existe->fetch(PDO::FETCH_ASSOC)) {
            return self::mdlGuardarPerfil([
                "idUsuario" => $idUsuario,
                "dniPerfil" => $datos["dniPerfil"] ?? null,
                "telefonoPerfil" => $datos["telefonoPerfil"] ?? null,
                "fnacPerfil" => $datos["fnacPerfil"] ?? ($datos["fnac"] ?? null),
                "domicilioPerfil" => $datos["domicilioPerfil"] ?? null,
                "provinciaPerfil" => $datos["provinciaPerfil"] ?? null,
                "contenidoPerfil" => $datos["contenidoPerfil"] ?? '',
            ]);
        }

        $registro = $conexion->prepare("
            UPDATE perfiles
            SET dniPerfil = :dniPerfil,
                telefonoPerfil = :telefonoPerfil,
                fnacPerfil = :fnacPerfil,
                domicilioPerfil = :domicilioPerfil,
                provinciaPerfil = :provinciaPerfil,
                contenidoPerfil = :contenidoPerfil
            WHERE id_usuario = :id_usuario
        ");

        $dniPerfil = trim((string) ($datos["dniPerfil"] ?? ''));
        $fnacPerfil = trim((string) ($datos["fnacPerfil"] ?? ($datos["fnac"] ?? '')));

        $registro->bindValue(":id_usuario", $idUsuario, PDO::PARAM_INT);
        $registro->bindValue(":dniPerfil", $dniPerfil !== '' ? (int) $dniPerfil : null, $dniPerfil !== '' ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $registro->bindValue(":telefonoPerfil", trim((string) ($datos["telefonoPerfil"] ?? '')), PDO::PARAM_STR);
        $registro->bindValue(":fnacPerfil", $fnacPerfil !== '' ? $fnacPerfil : null, $fnacPerfil !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $registro->bindValue(":domicilioPerfil", trim((string) ($datos["domicilioPerfil"] ?? '')), PDO::PARAM_STR);
        $registro->bindValue(":provinciaPerfil", trim((string) ($datos["provinciaPerfil"] ?? '')), PDO::PARAM_STR);
        $registro->bindValue(":contenidoPerfil", (string) ($datos["contenidoPerfil"] ?? ''), PDO::PARAM_STR);

        return $registro->execute() ? "ok" : "error";
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
