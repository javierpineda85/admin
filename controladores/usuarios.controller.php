<?php

require_once('modelos/usuarios.modelo.php');
require_once('modelos/perfiles.modelo.php');

class ControladorUsuarios
{
    private static function contextoInstitucionalActivo()
    {
        return class_exists('ControladorInstitucion', false) && ControladorInstitucion::activo();
    }

    private static function rolesFormulario()
    {
        $entrada = $_POST['roles'] ?? ($_POST['rol'] ?? []);
        $entrada = is_array($entrada) ? $entrada : [$entrada];
        $permitidos = ['ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE'];
        $roles = [];
        foreach ($entrada as $rol) {
            $rol = strtoupper(trim((string) $rol));
            if (in_array($rol, $permitidos, true)) { $roles[$rol] = true; }
        }
        return array_keys($roles);
    }

    private static function eliminarImagenProcesada($ruta)
    {
        $ruta = str_replace('\\', '/', trim((string) $ruta));
        if (!str_starts_with($ruta, 'usuarios/')) { return; }
        $archivo = realpath(__DIR__ . '/../img/' . $ruta);
        $raiz = realpath(__DIR__ . '/../img/usuarios');
        if ($archivo && $raiz && is_file($archivo) && strpos($archivo, $raiz . DIRECTORY_SEPARATOR) === 0) {
            unlink($archivo);
        }
    }

    private static function registrarHistorialSeguro(array $datos)
    {
        try {
            return ModeloUsuarios::mdlRegistrarHistorial($datos) === 'ok';
        } catch (Throwable $e) {
            error_log('No se pudo registrar el historial de usuario: ' . get_class($e));
            return false;
        }
    }

    private static function procesarImagenUsuario($campo = 'imgUsuario', $nombreBase = 'usuario')
    {
        if (empty($_FILES[$campo]['name'])) {
            return '';
        }

        if ((int) ($_FILES[$campo]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['error_message'] = 'No se pudo subir la imagen del usuario.';
            return false;
        }

        $carpeta = __DIR__ . '/../img/usuarios/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        $original = basename((string) $_FILES[$campo]['name']);
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $nombreArchivo = $nombreBase . '_' . uniqid('', true) . ($extension !== '' ? '.' . $extension : '');
        $rutaCompleta = $carpeta . $nombreArchivo;

        if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $rutaCompleta)) {
            $_SESSION['error_message'] = 'No se pudo guardar la imagen del usuario.';
            return false;
        }

        return 'usuarios/' . $nombreArchivo;
    }

    public static function crtSeleccionarUsuario($item, $valor)
    {
        return ModeloUsuarios::mdlSeleccionarUsuarios($item, $valor);
    }

    public static function crtUsuariosDocentesAsignables()
    {
        return ModeloUsuarios::mdlUsuariosDocentesAsignables();
    }

    public static function crtDestinatariosPermitidos()
    {
        $idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
        $rolActual = ControladorPermisos::rolActual();

        return ModeloUsuarios::mdlDestinatariosPermitidos($idUsuarioActual, $rolActual);
    }

    public static function crtUsuarioActual()
    {
        $idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
        if ($idUsuarioActual <= 0) {
            return null;
        }

        return ModeloUsuarios::mdlObtenerUsuarioCompleto($idUsuarioActual);
    }

    public static function crtUsuarioCompleto($idUsuario)
    {
        return ModeloUsuarios::mdlObtenerUsuarioCompleto((int) $idUsuario);
    }

    public static function crtPuedeVerPerfilEnSeccion($idUsuarioPerfil, $idSeccion)
    {
        $idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
        $idUsuarioPerfil = (int) $idUsuarioPerfil;
        $idSeccion = (int) $idSeccion;

        if ($idUsuarioActual <= 0 || $idUsuarioPerfil <= 0 || $idSeccion <= 0) {
            return false;
        }

        $seccion = ControladorLecciones::crtBuscarSeccionPorId($idSeccion);
        if (!$seccion) {
            return false;
        }

        $idCurso = (int) ($seccion['id_curso'] ?? 0);
        $puedeEntrar = ControladorPermisos::tieneRol('ADMINISTRADOR')
            || (ControladorPermisos::tieneRol('DOCENTE') && ControladorLecciones::crtSeccionAsignadaDocente($idSeccion, $idUsuarioActual))
            || (ControladorPermisos::tieneRol('ESTUDIANTE') && ControladorCursos::crtEstudianteInscriptoCurso($idUsuarioActual, $idCurso));

        if (!$puedeEntrar) {
            return false;
        }

        $esDocenteOTutor = in_array($idUsuarioPerfil, [
            (int) ($seccion['docente'] ?? 0),
            (int) ($seccion['tutor'] ?? 0),
        ], true);

        return $esDocenteOTutor
            || ControladorCursos::crtEstudianteInscriptoCurso($idUsuarioPerfil, $idCurso);
    }

    public static function rutaImagenUsuario($rutaImagen, $fallback = 'user2-160x160.jpg')
    {
        $rutaImagen = trim((string) $rutaImagen);
        $fallback = trim((string) $fallback) !== '' ? trim((string) $fallback) : 'user2-160x160.jpg';

        $candidatos = [];
        if ($rutaImagen !== '') {
            $rutaLimpia = ltrim($rutaImagen, './');
            $candidatos[] = $rutaLimpia;

            if (str_starts_with($rutaLimpia, 'img/')) {
                $candidatos[] = substr($rutaLimpia, 4);
            }

            if (!str_starts_with($rutaLimpia, 'usuarios/')) {
                $candidatos[] = 'usuarios/' . basename($rutaLimpia);
            }

            $base = basename($rutaLimpia);
            if ($base !== '') {
                $candidatos[] = 'usuarios/' . $base;
                $candidatos[] = $base;
            }
        }

        $candidatos[] = $fallback;

        foreach (array_unique($candidatos) as $candidato) {
            $normalizado = ltrim((string) $candidato, './');
            $rutaFisica = __DIR__ . '/../img/' . $normalizado;
            if (is_file($rutaFisica)) {
                return $normalizado;
            }

            $base = basename($normalizado);
            if ($base !== '') {
                $coincidencias = glob(__DIR__ . '/../img/usuarios/' . $base . '*') ?: [];
                if (!empty($coincidencias)) {
                    return 'usuarios/' . basename($coincidencias[0]);
                }
            }
        }

        return $fallback;
    }

    public static function crtRelacionesAcademicas($idUsuario)
    {
        return ModeloUsuarios::mdlRelacionesAcademicas((int) $idUsuario);
    }

    public static function crtHistorialUsuario($idUsuario)
    {
        if ((int) $idUsuario !== (int) ($_SESSION['usuario']['id'] ?? 0)) {
            return [];
        }
        return ModeloUsuarios::mdlHistorialUsuario((int) $idUsuario);
    }

    public static function crtUsuariosConectadosRecientes($minutos = 60)
    {
        return ModeloUsuarios::mdlUsuariosConectadosRecientes((int) $minutos);
    }

    public static function crtUsuariosNoConectadosRecientes($minutos = 60)
    {
        return ModeloUsuarios::mdlUsuariosNoConectadosRecientes((int) $minutos);
    }

    public static function crtContarUsuariosConectadosRecientes($minutos = 60)
    {
        return ModeloUsuarios::mdlContarUsuariosConectadosRecientes((int) $minutos);
    }

    public static function crtGuardarUsuario()
    {
        $nombreUsuario = trim((string) ($_POST["nombreUsuario"] ?? ''));
        $apellidoUsuario = trim((string) ($_POST["apellidoUsuario"] ?? ''));
        $emailUsuario = trim((string) ($_POST["emailUsuario"] ?? ($_POST["email"] ?? '')));
        $passUsuario = (string) ($_POST["passUsuario"] ?? ($_POST["pass"] ?? ''));
        $rolUsuario = trim((string) ($_POST["rol"] ?? ''));

        if (self::contextoInstitucionalActivo()) {
            $roles = self::rolesFormulario();
            if ($nombreUsuario === '' || $apellidoUsuario === '' || !filter_var($emailUsuario, FILTER_VALIDATE_EMAIL) || !$roles) {
                return null;
            }
            $imagen = self::procesarImagenUsuario('imgUsuario', 'alta');
            if ($imagen === false) { return false; }
            try {
                $resultado = ModeloUsuarios::mdlCrearOMatricularUsuarioInstitucional([
                    'nombreUsuario'=>$nombreUsuario,'apellidoUsuario'=>$apellidoUsuario,'email'=>$emailUsuario,
                    'pass'=>$passUsuario,'imgUsuario'=>$imagen,
                ], [
                    'dniPerfil'=>$_POST['dniPerfil']??null,'telefonoPerfil'=>$_POST['telefonoPerfil']??null,
                    'fnacPerfil'=>$_POST['fnacPerfil']??null,'domicilioPerfil'=>$_POST['domicilioPerfil']??null,
                    'provinciaPerfil'=>$_POST['provinciaPerfil']??null,'contenidoPerfil'=>$_POST['contenidoPerfil']??'',
                ], $roles);
            } catch (Throwable $e) {
                if ($imagen !== '') { self::eliminarImagenProcesada($imagen); }
                $_SESSION['error_message'] = $e->getMessage();
                return false;
            }
            if (empty($resultado['identidadNueva']) && $imagen !== '') { self::eliminarImagenProcesada($imagen); }
            self::registrarHistorialSeguro([
                'id_usuario'=>(int)$resultado['idUsuario'],'accion'=>'ALTA_INSTITUCIONAL',
                'detalle'=>'Se habilitó la membresía en la institución activa con roles: '.implode(', ',$roles).'.',
                'id_usuario_accion'=>(int)($_SESSION['usuario']['id']??0),'fechaEvento'=>date('Y-m-d H:i:s'),
            ]);
            $_SESSION['success_message'] = !empty($resultado['identidadNueva'])
                ? 'Identidad global y membresía institucional creadas correctamente'
                : 'La identidad existente fue incorporada a la institución';
            return 'ok';
        }

        if ($nombreUsuario === '' || $apellidoUsuario === '' || $emailUsuario === '' || $passUsuario === '' || $rolUsuario === '') {
            return null;
        }

        try {
            $conexion = Conexion::conectar();
            if (!$conexion->inTransaction()) {
                $conexion->beginTransaction();
            }

            $tabla = "usuarios";
            $datosUsuario = [
                "nombreUsuario" => $nombreUsuario,
                "apellidoUsuario" => $apellidoUsuario,
                "email" => $emailUsuario,
                "pass" => password_hash($passUsuario, PASSWORD_DEFAULT),
                "resetPass" => 1,
                "activo" => 1,
                "rol" => $rolUsuario,
                "fechaAlta" => date('Y-m-d H:i:s'),
            ];

            $imagen = self::procesarImagenUsuario('imgUsuario', 'alta');
            if ($imagen === false) {
                $conexion->rollBack();
                return false;
            }
            $datosUsuario['imgUsuario'] = $imagen !== '' ? $imagen : '';

            $respuestaUsuario = ModeloUsuarios::mdlGuardarUsuario($tabla, $datosUsuario);
            if ($respuestaUsuario !== 'ok') {
                $conexion->rollBack();
                $_SESSION['error_message'] = 'No se pudo crear el usuario';
                return false;
            }

            $idNuevoUsuario = (int) $conexion->lastInsertId();

            $datosPerfil = [
                "idUsuario" => $idNuevoUsuario,
                "dniPerfil" => $_POST['dniPerfil'] ?? null,
                "telefonoPerfil" => $_POST['telefonoPerfil'] ?? null,
                "fnacPerfil" => $_POST['fnacPerfil'] ?? null,
                "domicilioPerfil" => $_POST['domicilioPerfil'] ?? null,
                "provinciaPerfil" => $_POST['provinciaPerfil'] ?? null,
                "contenidoPerfil" => $_POST['contenidoPerfil'] ?? '',
            ];

            $respuestaPerfil = ModeloPerfiles::mdlGuardarPerfil($datosPerfil);
            if ($respuestaPerfil !== 'ok') {
                $conexion->rollBack();
                $_SESSION['error_message'] = 'No se pudo crear el perfil del usuario';
                return false;
            }

            ModeloUsuarios::mdlRegistrarHistorial([
                'id_usuario' => $idNuevoUsuario,
                'accion' => 'ALTA',
                'detalle' => 'Se creó el usuario y su perfil inicial.',
                'id_usuario_accion' => (int) ($_SESSION['usuario']['id'] ?? 0),
                'fechaEvento' => date('Y-m-d H:i:s'),
            ]);

            $conexion->commit();
            $_SESSION['success_message'] = 'Usuario y perfil creados exitosamente';
            return 'ok';
        } catch (Exception $e) {
            if ($conexion instanceof PDO && $conexion->inTransaction()) {
                $conexion->rollBack();
            }

            $_SESSION['error_message'] = $e->getMessage();
            return false;
        }
    }

    public static function crtModificarUsuario()
    {
        if (!isset($_POST["nombreUsuario"], $_POST["apellidoUsuario"], $_POST["emailUsuario"])
            || (!isset($_POST['rol']) && !isset($_POST['roles']))) {
            return null;
        }

        $roles = self::rolesFormulario();

        $tabla = "usuarios";
        $datos = [
            "idUsuario" => (int) ($_POST["idUsuario"] ?? 0),
            "nombreUsuario" => trim((string) $_POST["nombreUsuario"]),
            "apellidoUsuario" => trim((string) $_POST["apellidoUsuario"]),
            "emailUsuario" => trim((string) $_POST["emailUsuario"]),
            "rol" => $roles[0] ?? '',
            "roles" => $roles,
        ];

        if ($datos['idUsuario'] <= 0 || !self::crtUsuarioCompleto($datos['idUsuario'])) {
            $_SESSION['error_message'] = 'No podés modificar un usuario ajeno a la institución activa.';
            return false;
        }

        if (self::contextoInstitucionalActivo()) {
            if (!$roles) {
                $_SESSION['error_message'] = 'Seleccioná al menos un rol institucional.';
                return false;
            }
            try {
                $respuesta = ModeloUsuarios::mdlModificarUsuario($tabla, $datos);
                if ($respuesta === 'ok') {
                    self::registrarHistorialSeguro([
                        'id_usuario'=>$datos['idUsuario'],'accion'=>'ROLES_INSTITUCIONALES',
                        'detalle'=>'Se actualizaron los roles de la membresía activa: '.implode(', ',$roles).'.',
                        'id_usuario_accion'=>(int)($_SESSION['usuario']['id']??0),'fechaEvento'=>date('Y-m-d H:i:s'),
                    ]);
                    $_SESSION['success_message'] = 'Roles institucionales actualizados correctamente';
                }
                return $respuesta;
            } catch (Throwable $e) {
                $_SESSION['error_message'] = $e->getMessage();
                return false;
            }
        }

        $imagen = self::procesarImagenUsuario('imgUsuario', 'perfil');
        if ($imagen === false) {
            return false;
        }
        if ($imagen !== '') {
            $datos['imgUsuario'] = $imagen;
        }

        if (!empty($_POST["passUsuario"])) {
            $datos["passUsuario"] = (string) $_POST["passUsuario"];
        }

        $respuesta = ModeloUsuarios::mdlModificarUsuario($tabla, $datos);
        if ($respuesta === 'ok') {
            $datosPerfil = [
                "idUsuario" => $datos['idUsuario'],
                "dniPerfil" => $_POST['dniPerfil'] ?? null,
                "telefonoPerfil" => $_POST['telefonoPerfil'] ?? null,
                "fnacPerfil" => $_POST['fnacPerfil'] ?? null,
                "domicilioPerfil" => $_POST['domicilioPerfil'] ?? null,
                "provinciaPerfil" => $_POST['provinciaPerfil'] ?? null,
                "contenidoPerfil" => $_POST['contenidoPerfil'] ?? '',
            ];

            $respuestaPerfil = ModeloPerfiles::mdlEditarPerfil($datosPerfil);
            if ($respuestaPerfil !== 'ok') {
                $_SESSION['error_message'] = 'No se pudo modificar el perfil del usuario';
                return false;
            }

            ModeloUsuarios::mdlRegistrarHistorial([
                'id_usuario' => $datos['idUsuario'],
                'accion' => 'MODIFICACION',
                'detalle' => 'Se actualizaron datos de cuenta o perfil.',
                'id_usuario_accion' => (int) ($_SESSION['usuario']['id'] ?? 0),
                'fechaEvento' => date('Y-m-d H:i:s'),
            ]);
            $_SESSION['success_message'] = 'Usuario modificado exitosamente';
        } else {
            $_SESSION['error_message'] = 'No se pudo modificar el usuario';
        }

        return $respuesta;
    }

    public static function crtDarBajaUsuario()
    {
        if (!isset($_POST['accion_usuario']) || $_POST['accion_usuario'] !== 'baja_usuario') {
            return null;
        }

        $datos = [
            'idUsuario' => (int) ($_POST['idUsuario'] ?? 0),
            'fechaBaja' => !empty($_POST['fechaBaja']) ? $_POST['fechaBaja'] . ' 00:00:00' : date('Y-m-d H:i:s'),
            'motivoBaja' => trim((string) ($_POST['motivoBaja'] ?? '')),
            'usuarioBaja' => (int) ($_SESSION['usuario']['id'] ?? 0),
        ];

        if ($datos['idUsuario'] <= 0 || $datos['motivoBaja'] === '') {
            $_SESSION['error_message'] = 'Completa la fecha y el motivo de baja.';
            return false;
        }

        if (!self::crtUsuarioCompleto($datos['idUsuario'])) {
            $_SESSION['error_message'] = 'No podés dar de baja un usuario ajeno a la institución activa.';
            return false;
        }

        $institucional = self::contextoInstitucionalActivo();
        try {
            $respuesta = ModeloUsuarios::mdlDarBajaUsuario($datos);
        } catch (Throwable $e) {
            $_SESSION['error_message'] = $e->getMessage();
            return false;
        }
        if ($respuesta === 'ok') {
            $historial = [
                'id_usuario' => $datos['idUsuario'],
                'accion' => $institucional ? 'BAJA_INSTITUCIONAL' : 'BAJA',
                'detalle' => ($institucional ? 'Membresía institucional dada de baja. Motivo: ' : 'Usuario dado de baja. Motivo: ') . $datos['motivoBaja'],
                'id_usuario_accion' => (int) ($_SESSION['usuario']['id'] ?? 0),
                'fechaEvento' => date('Y-m-d H:i:s'),
            ];
            if ($institucional) { self::registrarHistorialSeguro($historial); }
            else { ModeloUsuarios::mdlRegistrarHistorial($historial); }
            $_SESSION['success_message'] = $institucional ? 'Membresía institucional dada de baja correctamente' : 'Usuario dado de baja correctamente';
        } else {
            $_SESSION['error_message'] = 'No se pudo dar de baja al usuario';
        }

        return $respuesta;
    }

    public static function crtReactivarUsuario($idUsuario)
    {
        if ((int) $idUsuario <= 0 || !self::crtUsuarioCompleto((int) $idUsuario)) {
            $_SESSION['error_message'] = 'No podés reactivar un usuario ajeno a la institución activa.';
            return false;
        }
        $institucional = self::contextoInstitucionalActivo();
        try {
            $respuesta = ModeloUsuarios::mdlReactivarUsuario((int) $idUsuario);
        } catch (Throwable $e) {
            $_SESSION['error_message'] = $e->getMessage();
            return false;
        }
        if ($respuesta === 'ok') {
            $historial = [
                'id_usuario' => (int) $idUsuario,
                'accion' => $institucional ? 'REACTIVACION_INSTITUCIONAL' : 'REACTIVACION',
                'detalle' => $institucional ? 'Membresía reactivada en la institución activa.' : 'Usuario reactivado desde el listado de inactivos.',
                'id_usuario_accion' => (int) ($_SESSION['usuario']['id'] ?? 0),
                'fechaEvento' => date('Y-m-d H:i:s'),
            ];
            if ($institucional) { self::registrarHistorialSeguro($historial); }
            else { ModeloUsuarios::mdlRegistrarHistorial($historial); }
            $_SESSION['success_message'] = $institucional ? 'Membresía institucional reactivada correctamente' : 'Usuario reactivado correctamente';
        } else {
            $_SESSION['error_message'] = 'No se pudo reactivar al usuario';
        }

        return $respuesta;
    }
}
