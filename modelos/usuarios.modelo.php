<?php

class ModeloUsuarios
{
    // Método para verificar las credenciales de inicio de sesión
    public function authenticate($email, $password)
    {
        // Aquí deberías realizar la lógica para verificar las credenciales en tu base de datos
        // Este es solo un ejemplo básico
        $storedPassword = $this->getStoredPasswordByUsername($email);
        if ($storedPassword !== null && password_verify($password, $storedPassword)) {
            // Las credenciales son válidas
            return true;
        } else {
            // Las credenciales no son válidas
            return false;
        }
    }

    // Método para obtener el hash de contraseña almacenado en la base de datos
    private function getStoredPasswordByUsername($username)
    {
        // Aquí deberías implementar la lógica para obtener el hash de contraseña de tu base de datos
        // Este es solo un ejemplo básico
        $users = [
            'john' => '$2y$10$jWQxRc0kLlNhvX52nVpPve.hGzsOR5M10KgIrNzJwXvT4aQxir9jC' // Ejemplo de hash de contraseña
        ];
        return isset($users[$username]) ? $users[$username] : null;
    }

    static public function mdlObtenerUsuarioPorEmail($email)
    {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlObtenerUsuarioPorId($idUsuario)
    {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE idUsuario = :idUsuario LIMIT 1");
        $stmt->bindParam(":idUsuario", $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlSeleccionarUsuarios($item, $valor)
    {
        if ($item === null || $valor === null) {
            $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE activo = 1 ORDER BY apellidoUsuario ASC, nombreUsuario ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE $item = :valor AND activo = 1 ORDER BY apellidoUsuario ASC, nombreUsuario ASC");
        $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlDestinatariosPermitidos($idUsuarioActual, $rolActual)
    {
        $rolActual = strtoupper(trim((string) $rolActual));

        if (in_array($rolActual, ['ADMINISTRADOR', 'DOCENTE'], true)) {
            $stmt = Conexion::conectar()->prepare("
                SELECT idUsuario, nombreUsuario, apellidoUsuario, email, rol
                FROM usuarios
                WHERE activo = 1
                  AND idUsuario <> :idUsuarioActual
                ORDER BY rol ASC, apellidoUsuario ASC, nombreUsuario ASC
            ");
            $stmt->bindParam(":idUsuarioActual", $idUsuarioActual, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = Conexion::conectar()->prepare("
            SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email, u.rol
            FROM usuarios u
            INNER JOIN asignacioncursos a1 ON a1.id_estudiante = :idUsuarioActual
            INNER JOIN asignacioncursos a2 ON a2.id_seccion = a1.id_seccion
            WHERE u.idUsuario = a2.id_estudiante
              AND u.activo = 1
              AND u.rol = 'ESTUDIANTE'
              AND u.idUsuario <> :idUsuarioActual
            ORDER BY u.apellidoUsuario ASC, u.nombreUsuario ASC
        ");
        $stmt->bindParam(":idUsuarioActual", $idUsuarioActual, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlCompartenCurso($idUsuario1, $idUsuario2)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT COUNT(*) AS total
            FROM asignacioncursos a1
            INNER JOIN asignacioncursos a2 ON a1.id_seccion = a2.id_seccion
            WHERE a1.id_estudiante = :idUsuario1
              AND a2.id_estudiante = :idUsuario2
        ");
        $stmt->bindParam(":idUsuario1", $idUsuario1, PDO::PARAM_INT);
        $stmt->bindParam(":idUsuario2", $idUsuario2, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($resultado) && (int) $resultado['total'] > 0;
    }

    static public function mdlActualizarPassword($idUsuario, $passwordHash)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE usuarios SET pass = :pass, resetPass = 0 WHERE idUsuario = :idUsuario");
        $stmt->bindParam(":pass", $passwordHash, PDO::PARAM_STR);
        $stmt->bindParam(":idUsuario", $idUsuario, PDO::PARAM_INT);

        return $stmt->execute() ? "ok" : "error";
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


    /*MODIFICAR USUARIO */
    static public function mdlModificarUsuario($tabla, $datos)
    {
        // Comenzamos construyendo la parte inicial de la consulta SQL
        $consulta = "UPDATE $tabla SET nombreUsuario = :nombreUsuario, apellidoUsuario = :apellidoUsuario, email = :email, rol = :rol";

        // Creamos un array para almacenar los valores que vamos a vincular en la consulta
        $valores = array(
            ":nombreUsuario" => $datos["nombreUsuario"],
            ":apellidoUsuario" => $datos["apellidoUsuario"],
            ":email" => $datos["emailUsuario"],
            ":rol" => $datos["rol"]
        );

        // Si el campo de contraseña está presente en los datos y no está vacío, lo incluimos en la consulta y en los valores a vincular
        if (isset($datos["passUsuario"]) && !empty($datos["passUsuario"])) {
            $consulta .= ", pass = :passUsuario_hashed"; // Agregamos el campo de contraseña a la consulta
            $consulta .=", resetPass = 1";
            $valores[":passUsuario_hashed"] = password_hash($datos["passUsuario"], PASSWORD_DEFAULT); // Hasheamos la contraseña y la añadimos al array de valores
        }

        // Agregamos la condición WHERE para identificar el usuario a actualizar
        $consulta .= " WHERE idUsuario = :idUsuario";

        // Preparamos y ejecutamos la consulta
        $registro = Conexion::conectar()->prepare($consulta);

        // Vinculamos los valores a la consulta
        foreach ($valores as $clave => $valor) {
            $registro->bindValue($clave, $valor, PDO::PARAM_STR);
        }

        // Vinculamos el ID del usuario
        $registro->bindValue(":idUsuario", $datos["idUsuario"], PDO::PARAM_INT);

        // Ejecutamos la consulta
        $registro->execute();

        // Verificamos si se realizó la actualización correctamente
        if ($registro->rowCount() > 0) {
            return "ok";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }

        // Cerramos el cursor y liberamos los recursos
        $registro->closeCursor();
        $registro = null;
    }
}
