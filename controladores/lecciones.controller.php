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

    public static function crtBuscarPostsPorLeccion($idLeccion)
    {
        return ModeloLecciones::mdlBuscarPostsPorLeccion($idLeccion);
    }

    public static function crtBuscarEntregasPorLeccion($idLeccion)
    {
        return ModeloLecciones::mdlBuscarEntregasPorLeccion($idLeccion);
    }

    public static function crtBuscarEstudiantesCurso($idCurso)
    {
        return ModeloLecciones::mdlBuscarEstudiantesCurso($idCurso);
    }

    public static function crtResumenSeccion($idSeccion)
    {
        return ModeloLecciones::mdlResumenSeccion($idSeccion);
    }

    public static function crtResumenEstudianteSeccion($idSeccion, $idEstudiante)
    {
        return ModeloLecciones::mdlResumenEstudianteSeccion($idSeccion, $idEstudiante);
    }

    public static function crtBuscarLeccionPorId($idLeccion)
    {
        return ModeloLecciones::mdlBuscarLeccionPorId($idLeccion);
    }

    public static function crtBuscarRecursoPorId($idRecurso)
    {
        return ModeloLecciones::mdlBuscarRecursoPorId($idRecurso);
    }

    public static function crtBuscarEntregaPorLeccionEstudiante($idLeccion, $idEstudiante)
    {
        return ModeloLecciones::mdlBuscarEntregaPorIdLeccionYEstudiante($idLeccion, $idEstudiante);
    }

    public static function crtProcesarAcciones()
    {
        $accion = trim((string) ($_POST['accion'] ?? ''));

        if ($accion === '') {
            return null;
        }

        switch ($accion) {
            case 'crear_leccion':
                return self::crtGuardarLeccion();
            case 'actualizar_leccion':
                return self::crtActualizarLeccion();
            case 'eliminar_leccion':
                return self::crtEliminarLeccion();
            case 'crear_recurso':
                return self::crtGuardarRecursoLeccion();
            case 'actualizar_recurso':
                return self::crtActualizarRecursoLeccion();
            case 'eliminar_recurso':
                return self::crtEliminarRecursoLeccion();
            case 'crear_post':
                return self::crtGuardarPostLeccion();
            case 'entregar_tarea':
                return self::crtGuardarEntregaLeccion();
        }

        return null;
    }

    public static function crtGuardarLeccion()
    {
        if (!isset($_POST['nombreLeccion'], $_POST['id_modulo'], $_POST['tipoLeccion'])) {
            return null;
        }

        if (!(ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente())) {
            $_SESSION['error_message'] = 'No tenes permisos para crear lecciones.';
            return 'denied';
        }

        $nombreLeccion = trim((string) $_POST['nombreLeccion']);
        $contenidoLeccion = trim((string) ($_POST['contenidoLeccion'] ?? ''));
        $tipoLeccion = strtoupper(trim((string) $_POST['tipoLeccion']));
        $idModulo = (int) $_POST['id_modulo'];

        if ($nombreLeccion === '' || $idModulo <= 0) {
            $_SESSION['error_message'] = 'Completa el nombre de la leccion y la seccion asociada.';
            return 'error';
        }

        if (!in_array($tipoLeccion, ['MATERIAL', 'TAREA', 'PREGUNTA'], true)) {
            $_SESSION['error_message'] = 'El tipo de leccion no es valido.';
            return 'error';
        }

        $respuesta = ModeloLecciones::mdlGuardarLeccion('lecciones', [
            'nombreLeccion' => $nombreLeccion,
            'tipoLeccion' => $tipoLeccion,
            'contenidoLeccion' => $contenidoLeccion,
            'id_modulo' => $idModulo,
        ]);

        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Leccion creada correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudo crear la leccion.';
        }

        return $respuesta;
    }

    public static function crtActualizarLeccion()
    {
        if (!isset($_POST['idLeccion'], $_POST['nombreLeccion'], $_POST['tipoLeccion'])) {
            return null;
        }

        if (!(ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente())) {
            $_SESSION['error_message'] = 'No tenes permisos para editar lecciones.';
            return 'denied';
        }

        $idLeccion = (int) $_POST['idLeccion'];
        $nombreLeccion = trim((string) $_POST['nombreLeccion']);
        $contenidoLeccion = trim((string) ($_POST['contenidoLeccion'] ?? ''));
        $tipoLeccion = strtoupper(trim((string) $_POST['tipoLeccion']));

        if ($idLeccion <= 0 || $nombreLeccion === '') {
            $_SESSION['error_message'] = 'No se pudo actualizar la leccion.';
            return 'error';
        }

        if (!in_array($tipoLeccion, ['MATERIAL', 'TAREA', 'PREGUNTA'], true)) {
            $_SESSION['error_message'] = 'El tipo de leccion no es valido.';
            return 'error';
        }

        $respuesta = ModeloLecciones::mdlActualizarLeccion('lecciones', [
            'idLeccion' => $idLeccion,
            'nombreLeccion' => $nombreLeccion,
            'tipoLeccion' => $tipoLeccion,
            'contenidoLeccion' => $contenidoLeccion,
        ]);

        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Leccion actualizada correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudo actualizar la leccion.';
        }

        return $respuesta;
    }

    public static function crtEliminarLeccion()
    {
        if (!isset($_POST['idLeccion'])) {
            return null;
        }

        if (!(ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente())) {
            $_SESSION['error_message'] = 'No tenes permisos para eliminar lecciones.';
            return 'denied';
        }

        $idLeccion = (int) $_POST['idLeccion'];
        $leccion = self::crtBuscarLeccionPorId($idLeccion);

        if (!$leccion) {
            $_SESSION['error_message'] = 'La leccion no existe.';
            return 'error';
        }

        $recursos = self::crtBuscarRecursosPorLeccion($idLeccion);
        $entregas = ModeloLecciones::mdlBuscarEntregasPorLeccion($idLeccion);

        foreach ($recursos as $recurso) {
            self::eliminarArchivoLocal((string) $recurso['urlRecurso']);
        }

        foreach ($entregas as $entrega) {
            self::eliminarArchivoLocal((string) $entrega['urlArchivo']);
        }

        $respuesta = ModeloLecciones::mdlEliminarLeccion($idLeccion);

        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Leccion eliminada correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudo eliminar la leccion.';
        }

        return $respuesta;
    }

    public static function crtGuardarRecursoLeccion()
    {
        if (!isset($_POST['id_leccion'], $_POST['tipoRecurso'], $_POST['tituloRecurso'])) {
            return null;
        }

        if (!(ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente())) {
            $_SESSION['error_message'] = 'No tenes permisos para agregar recursos.';
            return 'denied';
        }

        $idLeccion = (int) $_POST['id_leccion'];
        $tipoRecurso = strtoupper(trim((string) $_POST['tipoRecurso']));
        $tituloRecurso = trim((string) $_POST['tituloRecurso']);
        $creadoPor = (int) ($_SESSION['usuario']['id'] ?? 0);

        if ($idLeccion <= 0 || $tituloRecurso === '') {
            $_SESSION['error_message'] = 'Completa el titulo del recurso.';
            return 'error';
        }

        if (!in_array($tipoRecurso, ['ARCHIVO', 'ENLACE'], true)) {
            $_SESSION['error_message'] = 'El tipo de recurso no es valido.';
            return 'error';
        }

        $urlRecurso = '';

        if ($tipoRecurso === 'ARCHIVO') {
            if (empty($_FILES['archivoRecurso']['name']) || ($_FILES['archivoRecurso']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $_SESSION['error_message'] = 'Subi un archivo valido para adjuntar.';
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
                $_SESSION['error_message'] = 'Pega el enlace del recurso.';
                return 'error';
            }

            if (!filter_var($urlRecurso, FILTER_VALIDATE_URL)) {
                $urlRecurso = 'https://' . ltrim($urlRecurso, '/');

                if (!filter_var($urlRecurso, FILTER_VALIDATE_URL)) {
                    $_SESSION['error_message'] = 'El enlace no tiene un formato valido.';
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

    public static function crtActualizarRecursoLeccion()
    {
        if (!isset($_POST['idRecursoLeccion'], $_POST['tipoRecurso'], $_POST['tituloRecurso'], $_POST['id_leccion'])) {
            return null;
        }

        if (!(ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente())) {
            $_SESSION['error_message'] = 'No tenes permisos para editar recursos.';
            return 'denied';
        }

        $idRecurso = (int) $_POST['idRecursoLeccion'];
        $idLeccion = (int) $_POST['id_leccion'];
        $tipoRecurso = strtoupper(trim((string) $_POST['tipoRecurso']));
        $tituloRecurso = trim((string) $_POST['tituloRecurso']);
        $recurso = self::crtBuscarRecursoPorId($idRecurso);

        if (!$recurso || $idLeccion <= 0 || $tituloRecurso === '') {
            $_SESSION['error_message'] = 'No se pudo actualizar el recurso.';
            return 'error';
        }

        if (!in_array($tipoRecurso, ['ARCHIVO', 'ENLACE'], true)) {
            $_SESSION['error_message'] = 'El tipo de recurso no es valido.';
            return 'error';
        }

        $urlRecurso = trim((string) ($_POST['urlRecurso'] ?? $recurso['urlRecurso']));

        if ($tipoRecurso === 'ARCHIVO' && !empty($_FILES['archivoRecurso']['name']) && ($_FILES['archivoRecurso']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            self::eliminarArchivoLocal((string) $recurso['urlRecurso']);
            $urlRecurso = self::subirArchivo($_FILES['archivoRecurso']);
        } elseif ($tipoRecurso === 'ENLACE' && $urlRecurso !== '' && !filter_var($urlRecurso, FILTER_VALIDATE_URL)) {
            $urlRecurso = 'https://' . ltrim($urlRecurso, '/');
        }

        if ($urlRecurso === '' || ($tipoRecurso === 'ENLACE' && !filter_var($urlRecurso, FILTER_VALIDATE_URL))) {
            $_SESSION['error_message'] = 'El recurso no tiene una URL valida.';
            return 'error';
        }

        $respuesta = ModeloLecciones::mdlActualizarRecursoLeccion([
            'idRecursoLeccion' => $idRecurso,
            'tipoRecurso' => $tipoRecurso,
            'tituloRecurso' => $tituloRecurso,
            'urlRecurso' => $urlRecurso,
        ]);

        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Recurso actualizado correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudo actualizar el recurso.';
        }

        return $respuesta;
    }

    public static function crtEliminarRecursoLeccion()
    {
        if (!isset($_POST['idRecursoLeccion'])) {
            return null;
        }

        if (!(ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente())) {
            $_SESSION['error_message'] = 'No tenes permisos para eliminar recursos.';
            return 'denied';
        }

        $idRecurso = (int) $_POST['idRecursoLeccion'];
        $recurso = self::crtBuscarRecursoPorId($idRecurso);

        if (!$recurso) {
            $_SESSION['error_message'] = 'El recurso no existe.';
            return 'error';
        }

        self::eliminarArchivoLocal((string) $recurso['urlRecurso']);

        $respuesta = ModeloLecciones::mdlEliminarRecursoLeccion($idRecurso);

        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Recurso eliminado correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudo eliminar el recurso.';
        }

        return $respuesta;
    }

    public static function crtGuardarPostLeccion()
    {
        if (!isset($_POST['id_leccion'], $_POST['contenidoPosteo'])) {
            return null;
        }

        $leccion = self::crtBuscarLeccionPorId((int) $_POST['id_leccion']);

        if (!$leccion || strtoupper((string) ($leccion['tipoLeccion'] ?? '')) !== 'PREGUNTA') {
            $_SESSION['error_message'] = 'La discusion solo esta disponible en lecciones tipo pregunta.';
            return 'error';
        }

        if (ControladorPermisos::esEstudiante()) {
            $idEstudiante = (int) ($_SESSION['usuario']['id'] ?? 0);
            $idCurso = (int) ($_POST['id_curso'] ?? 0);
            if ($idCurso <= 0 || !ModeloLecciones::mdlEstudianteEnCurso($idEstudiante, $idCurso)) {
                $_SESSION['error_message'] = 'No perteneces a este curso.';
                return 'denied';
            }
        }

        $contenido = trim((string) $_POST['contenidoPosteo']);
        if ($contenido === '') {
            $_SESSION['error_message'] = 'Escribi un mensaje para participar.';
            return 'error';
        }

        $respuesta = ModeloLecciones::mdlGuardarPostLeccion([
            'id_autor' => (int) ($_SESSION['usuario']['id'] ?? 0),
            'contenidoPosteo' => $contenido,
            'fechaPosteo' => date('Y-m-d H:i:s'),
            'id_curso' => (int) ($_POST['id_curso'] ?? 0),
            'id_leccion' => (int) $leccion['idLeccion'],
        ]);

        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Mensaje publicado correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudo publicar el mensaje.';
        }

        return $respuesta;
    }

    public static function crtGuardarEntregaLeccion()
    {
        if (!isset($_POST['id_leccion'], $_POST['id_seccion'], $_POST['id_curso'])) {
            return null;
        }

        if (!ControladorPermisos::esEstudiante()) {
            $_SESSION['error_message'] = 'Solo los estudiantes pueden entregar tareas.';
            return 'denied';
        }

        $idEstudiante = (int) ($_SESSION['usuario']['id'] ?? 0);
        if (!ModeloLecciones::mdlEstudianteEnCurso($idEstudiante, (int) $_POST['id_curso'])) {
            $_SESSION['error_message'] = 'No perteneces a este curso.';
            return 'denied';
        }

        $leccion = self::crtBuscarLeccionPorId((int) $_POST['id_leccion']);
        if (!$leccion || strtoupper((string) ($leccion['tipoLeccion'] ?? '')) !== 'TAREA') {
            $_SESSION['error_message'] = 'La entrega solo esta disponible en lecciones tipo tarea.';
            return 'error';
        }

        if (empty($_FILES['archivoEntrega']['name']) || ($_FILES['archivoEntrega']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['error_message'] = 'Subi un archivo valido para entregar la tarea.';
            return 'error';
        }

        $urlArchivo = self::subirArchivo($_FILES['archivoEntrega']);
        if ($urlArchivo === '') {
            $_SESSION['error_message'] = 'No se pudo guardar la entrega.';
            return 'error';
        }

        $respuesta = ModeloLecciones::mdlGuardarEntregaLeccion([
            'id_leccion' => (int) $_POST['id_leccion'],
            'id_seccion' => (int) $_POST['id_seccion'],
            'id_curso' => (int) $_POST['id_curso'],
            'id_estudiante' => (int) ($_SESSION['usuario']['id'] ?? 0),
            'urlArchivo' => $urlArchivo,
            'comentarioEntrega' => trim((string) ($_POST['comentarioEntrega'] ?? '')),
            'fechaEntrega' => date('Y-m-d H:i:s'),
            'estadoEntrega' => 'ENTREGADA',
        ]);

        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Entrega enviada correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudo registrar la entrega.';
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

    private static function eliminarArchivoLocal($ruta)
    {
        $ruta = trim((string) $ruta);

        if ($ruta === '' || str_starts_with($ruta, 'http://') || str_starts_with($ruta, 'https://')) {
            return;
        }

        $rutaFisica = __DIR__ . '/../' . ltrim($ruta, '/');
        if (is_file($rutaFisica)) {
            @unlink($rutaFisica);
        }
    }
}
