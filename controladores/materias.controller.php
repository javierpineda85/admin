<?php
require('modelos/materias.modelo.php');


class ControladorMaterias
{
    private static function eliminarArchivoLocal($ruta)
    {
        $ruta = trim((string) $ruta);
        if ($ruta === '') {
            return;
        }

        $rutaFisica = __DIR__ . '/../img/' . ltrim($ruta, '/');
        if (is_file($rutaFisica)) {
            @unlink($rutaFisica);
        }
    }

    private static function subirBannerSeccion(array $archivo)
    {
        $directorio = __DIR__ . '/../img/secciones/';

        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            return '';
        }

        $nombreOriginal = (string) ($archivo['name'] ?? '');
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if ($extension === '' || !in_array($extension, $extensionesPermitidas, true)) {
            return '';
        }

        $nombreSeguro = 'banner_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $rutaDestino = $directorio . $nombreSeguro;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            return '';
        }

        return 'secciones/' . $nombreSeguro;
    }

    static public function crtBuscarMateriaPorId($idSeccion)
    {
        return ModeloMaterias::mdlBuscarMateriaPorId((int) $idSeccion);
    }

    static public function crtGuardarMateria()
    {
        if (isset($_POST["tituloSeccion"])) {
            if (!ControladorPermisos::esAdministrador()) {
                $_SESSION['error_message'] = 'Solo el administrador puede crear materias.';
                return 'denied';
            }

            $tabla = "secciones";
            $bannerSeccion = '';
            $materia = null;

            $datos = array(
                "tituloSeccion" => $_POST["tituloSeccion"],
                "contenidoSeccion" => $_POST["contenidoSeccion"],
                "id_curso" => $_POST["id_curso"],
                "docente" => $_POST["docente"],
                "tutor" => $_POST["tutor"],
                "bannerSeccion" => '',
                "colorInicioBanner" => trim((string) ($_POST["colorInicioBanner"] ?? '#0f172a')),
                "colorFinBanner" => trim((string) ($_POST["colorFinBanner"] ?? '#1d4ed8')),
            );

            if (!empty($_FILES['bannerSeccion']['name']) && ($_FILES['bannerSeccion']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $bannerSeccion = self::subirBannerSeccion($_FILES['bannerSeccion']);
                if ($bannerSeccion === '') {
                    $_SESSION['error_message'] = 'No se pudo guardar el banner de la seccion.';
                    return 'error';
                }
            }

            $datos['bannerSeccion'] = $bannerSeccion;

            $respuesta = ModeloMaterias::mdlGuardarMateria($tabla, $datos);
            if ($respuesta === 'ok') {
                $_SESSION['success_message'] = 'Materia creada exitosamente';
            } else {
                $_SESSION['error_message'] = 'No se pudo crear la materia';
            }
            return $respuesta;
        }
    }

    static public function crtModificarMateria()
    {
        if (isset($_POST["tituloSeccion"], $_POST["idSeccion"])) {
            $tabla = "secciones";
            $materiaActual = self::crtBuscarMateriaPorId((int) $_POST["idSeccion"]);

            if (!$materiaActual) {
                $_SESSION['error_message'] = 'La materia no existe.';
                return 'error';
            }

            if (!ControladorPermisos::esAdministrador()) {
                $idDocente = (int) ($_SESSION['usuario']['id'] ?? 0);
                $esDocenteAsignado = ControladorLecciones::crtSeccionAsignadaDocente((int) $_POST["idSeccion"], $idDocente);
                if (!$esDocenteAsignado) {
                    $_SESSION['error_message'] = 'No podes editar una materia donde no estas asignado.';
                    return 'denied';
                }
            }

            $bannerSeccion = $materiaActual['bannerSeccion'] ?? '';

            if (!empty($_FILES['bannerSeccion']['name']) && ($_FILES['bannerSeccion']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $nuevoBanner = self::subirBannerSeccion($_FILES['bannerSeccion']);
                if ($nuevoBanner === '') {
                    $_SESSION['error_message'] = 'No se pudo actualizar el banner de la seccion.';
                    return 'error';
                }
                if (!empty($bannerSeccion)) {
                    self::eliminarArchivoLocal($bannerSeccion);
                }
                $bannerSeccion = $nuevoBanner;
            }

            $datos = array(
                "idSeccion" => (int) $_POST["idSeccion"],
                "tituloSeccion" => $_POST["tituloSeccion"],
                "contenidoSeccion" => $_POST["contenidoSeccion"],
                "id_curso" => $_POST["id_curso"],
                "docente" => $_POST["docente"],
                "tutor" => $_POST["tutor"],
                "bannerSeccion" => $bannerSeccion,
                "colorInicioBanner" => trim((string) ($_POST["colorInicioBanner"] ?? '#0f172a')),
                "colorFinBanner" => trim((string) ($_POST["colorFinBanner"] ?? '#1d4ed8')),
            );

            $respuesta = ModeloMaterias::mdlModificarMateria($tabla, $datos);
            if ($respuesta === 'ok') {
                $_SESSION['success_message'] = 'Materia modificada exitosamente';
            } else {
                $_SESSION['error_message'] = 'No se pudo modificar la materia';
            }
            return $respuesta;
        }
    }

    static public function crtBuscarMateriaXcurso($item, $valor)
    {

        $respuesta = ModeloMaterias::mdlBuscarMateriaXcurso($item, $valor);
        return $respuesta;

        exit;
    }

    static public function crtBuscarMateriasPorDocente($idDocente)
    {
        return ModeloMaterias::mdlBuscarMateriasPorDocente((int) $idDocente);
    }
}
