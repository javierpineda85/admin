<?php
require_once('modelos/lecciones.modelo.php');

class ControladorLecciones
{
    public static function crtBuscarSeccionPorId($idSeccion)
    {
        return ModeloLecciones::mdlBuscarSeccionPorId($idSeccion);
    }

    public static function crtBuscarLeccionesPorSeccion($idSeccion)
    {
        return ModeloLecciones::mdlBuscarLeccionesPorSeccion($idSeccion);
    }

    public static function crtBuscarRecursosPorLeccion($idLeccion)
    {
        return ModeloLecciones::mdlBuscarRecursosPorLeccion($idLeccion);
    }

    public static function crtGuardarLeccion()
    {
        if (!isset($_POST['nombreLeccion'], $_POST['id_modulo'])) {
            return null;
        }

        if (!(ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente())) {
            $_SESSION['error_message'] = 'No tenés permisos para crear lecciones.';
            return 'denied';
        }

        $nombreLeccion = trim((string) $_POST['nombreLeccion']);
        $contenidoLeccion = trim((string) ($_POST['contenidoLeccion'] ?? ''));
        $idModulo = (int) $_POST['id_modulo'];

        if ($nombreLeccion === '' || $idModulo <= 0) {
            $_SESSION['error_message'] = 'Completá el nombre de la lección y la sección asociada.';
            return 'error';
        }

        $respuesta = ModeloLecciones::mdlGuardarLeccion('lecciones', [
            'nombreLeccion' => $nombreLeccion,
            'contenidoLeccion' => $contenidoLeccion,
            'id_modulo' => $idModulo,
        ]);

        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Lección creada correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudo crear la lección.';
        }

        return $respuesta;
    }

    public static function crtGuardarRecursoLeccion()
    {
        if (!isset($_POST['id_leccion'], $_POST['tipoRecurso'], $_POST['tituloRecurso'])) {
            return null;
        }

        if (!(ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente())) {
            $_SESSION['error_message'] = 'No tenés permisos para agregar recursos.';
            return 'denied';
        }

        $idLeccion = (int) $_POST['id_leccion'];
        $tipoRecurso = strtoupper(trim((string) $_POST['tipoRecurso']));
        $tituloRecurso = trim((string) $_POST['tituloRecurso']);
        $creadoPor = (int) ($_SESSION['usuario']['id'] ?? 0);

        if ($idLeccion <= 0 || $tituloRecurso === '') {
            $_SESSION['error_message'] = 'Completá el título del recurso.';
            return 'error';
        }

        if (!in_array($tipoRecurso, ['ARCHIVO', 'ENLACE'], true)) {
            $_SESSION['error_message'] = 'El tipo de recurso no es válido.';
            return 'error';
        }

        $urlRecurso = '';

        if ($tipoRecurso === 'ARCHIVO') {
            if (empty($_FILES['archivoRecurso']['name']) || ($_FILES['archivoRecurso']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $_SESSION['error_message'] = 'Subí un archivo válido para adjuntar.';
                return 'error';
            }

            $urlRecurso = self::subirArchivo($_FILES['archivoRecurso']);

            if ($urlRecurso === '') {
                $_SESSION['error_message'] = 'No se pudo guardar el archivo adjunto.';
                return 'error';
            }
        } else {
            $urlRecurso = trim((string) ($_POST['urlRecurso'] ?? ''));

            if ($urlRecurso === '') {
                $_SESSION['error_message'] = 'Pegá el enlace del recurso.';
                return 'error';
            }

            if (!filter_var($urlRecurso, FILTER_VALIDATE_URL)) {
                $urlRecurso = 'https://' . ltrim($urlRecurso, '/');

                if (!filter_var($urlRecurso, FILTER_VALIDATE_URL)) {
                    $_SESSION['error_message'] = 'El enlace no tiene un formato válido.';
                    return 'error';
                }
            }
        }

        $respuesta = ModeloLecciones::mdlGuardarRecursoLeccion('recursoslecciones', [
            'id_leccion' => $idLeccion,
            'tipoRecurso' => $tipoRecurso,
            'tituloRecurso' => $tituloRecurso,
            'urlRecurso' => $urlRecurso,
            'creadoPor' => $creadoPor,
        ]);

        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Recurso agregado correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudo guardar el recurso.';
        }

        return $respuesta;
    }

    private static function subirArchivo(array $archivo)
    {
        $directorio = __DIR__ . '/../uploads/lecciones/';

        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            return '';
        }

        $nombreOriginal = (string) ($archivo['name'] ?? '');
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $extensionesPermitidas = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'zip'];

        if ($extension === '' || !in_array($extension, $extensionesPermitidas, true)) {
            return '';
        }

        $nombreSeguro = 'leccion_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $rutaDestino = $directorio . $nombreSeguro;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            return '';
        }

        return 'uploads/lecciones/' . $nombreSeguro;
    }
}
