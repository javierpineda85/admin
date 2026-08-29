<?php
require_once('modelos/lecciones.modelo.php');
require_once('controladores/notificaciones.controller.php');

class ControladorLecciones
{
    private static function puedeGestionarSeccion($idSeccion)
    {
        if (ControladorPermisos::esAdministrador()) {
            return true;
        }

        if (!ControladorPermisos::esDocente()) {
            return false;
        }

        $idDocente = (int) ($_SESSION['usuario']['id'] ?? 0);
        return self::crtSeccionAsignadaDocente((int) $idSeccion, $idDocente);
    }

    public static function crtBuscarSeccionPorId($idSeccion)
    {
        return ModeloLecciones::mdlBuscarSeccionPorId($idSeccion);
    }

    public static function crtSeccionAsignadaDocente($idSeccion, $idDocente)
    {
        return ModeloLecciones::mdlSeccionAsignadaDocente((int) $idSeccion, (int) $idDocente);
    }

    private static function incluirBorradores()
    {
        return ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente();
    }

    private static function normalizarEstadoLeccion($estado, $fallback = 'PUBLICADA')
    {
        $estado = strtoupper(trim((string) $estado));
        return in_array($estado, ['BORRADOR', 'PUBLICADA'], true) ? $estado : $fallback;
    }

    private static function normalizarFechaPublicacion($fecha)
    {
        $fecha = trim((string) $fecha);
        if ($fecha === '') {
            return null;
        }

        $fecha = str_replace('T', ' ', $fecha);
        $timestamp = strtotime($fecha);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    private static function leccionDisponibleParaEstudiantes($estado, $fechaPublicacion)
    {
        if (strtoupper((string) $estado) !== 'PUBLICADA') {
            return false;
        }

        $fechaPublicacion = trim((string) $fechaPublicacion);
        return $fechaPublicacion === '' || strtotime($fechaPublicacion) <= time();
    }

    public static function crtProcesarLeccionesProgramadas($idSeccion = 0)
    {
        foreach (ModeloLecciones::mdlBuscarLeccionesProgramadasVencidas((int) $idSeccion) as $leccion) {
            ControladorNotificaciones::crtNotificarLeccionPublicada($leccion, $leccion);
        }
    }

    public static function crtBuscarLeccionesPorSeccion($idSeccion)
    {
        return ModeloLecciones::mdlBuscarLeccionesPorSeccion($idSeccion, self::incluirBorradores());
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
        $entregas = ModeloLecciones::mdlBuscarEntregasPorLeccion($idLeccion);

        foreach ($entregas as &$entrega) {
            $entrega = self::completarAdjuntosEntrega($entrega);
        }
        unset($entrega);

        return $entregas;
    }

    public static function crtBuscarEstudiantesCurso($idCurso)
    {
        return ModeloLecciones::mdlBuscarEstudiantesCurso($idCurso);
    }

    public static function crtResumenSeccion($idSeccion)
    {
        return ModeloLecciones::mdlResumenSeccion($idSeccion, self::incluirBorradores());
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
        $entrega = ModeloLecciones::mdlBuscarEntregaPorIdLeccionYEstudiante($idLeccion, $idEstudiante);
        return $entrega ? self::completarAdjuntosEntrega($entrega) : null;
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
            case 'cancelar_entrega':
                return self::crtCancelarEntregaLeccion();
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
        $estadoLeccion = self::normalizarEstadoLeccion($_POST['estadoLeccion'] ?? 'PUBLICADA');
        $fechaPublicacionLeccion = self::normalizarFechaPublicacion($_POST['fechaPublicacionLeccion'] ?? '');
        $idModulo = (int) $_POST['id_modulo'];

        if ($nombreLeccion === '' || $idModulo <= 0) {
            $_SESSION['error_message'] = 'Completa el nombre de la leccion y la seccion asociada.';
            return 'error';
        }

        if (!in_array($tipoLeccion, ['MATERIAL', 'TAREA', 'PREGUNTA'], true)) {
            $_SESSION['error_message'] = 'El tipo de leccion no es valido.';
            return 'error';
        }

        if (!self::puedeGestionarSeccion($idModulo)) {
            $_SESSION['error_message'] = 'No podes editar una materia donde no estas asignado.';
            return 'denied';
        }

        $respuesta = ModeloLecciones::mdlGuardarLeccion('lecciones', [
            'nombreLeccion' => $nombreLeccion,
            'tipoLeccion' => $tipoLeccion,
            'contenidoLeccion' => $contenidoLeccion,
            'estadoLeccion' => $estadoLeccion,
            'fechaPublicacionLeccion' => $fechaPublicacionLeccion,
            'id_modulo' => $idModulo,
        ]);

        if ($respuesta === 'ok') {
            $idLeccionNueva = (int) Conexion::conectar()->lastInsertId();
            $recursoInicial = self::procesarRecursoInicial($idLeccionNueva);
            if ($recursoInicial === false) {
                ModeloLecciones::mdlEliminarLeccion($idLeccionNueva);
                $_SESSION['error_message'] = 'No se pudo guardar el recurso inicial.';
                return 'error';
            }
            if (self::leccionDisponibleParaEstudiantes($estadoLeccion, $fechaPublicacionLeccion)) {
                $seccion = self::crtBuscarSeccionPorId($idModulo);
                $leccionNueva = self::crtBuscarLeccionPorId($idLeccionNueva);
                if ($seccion && $leccionNueva) {
                    ControladorNotificaciones::crtNotificarLeccionPublicada($leccionNueva, $seccion);
                }
            }
            $_SESSION['success_message'] = self::mensajeEstadoLeccion($estadoLeccion, $fechaPublicacionLeccion);
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
        $leccion = self::crtBuscarLeccionPorId($idLeccion);
        $estadoActual = strtoupper((string) ($leccion['estadoLeccion'] ?? 'PUBLICADA'));
        $fechaActual = (string) ($leccion['fechaPublicacionLeccion'] ?? '');
        $estadoLeccion = self::normalizarEstadoLeccion($_POST['estadoLeccion'] ?? $estadoActual, $estadoActual ?: 'PUBLICADA');
        $fechaPublicacionLeccion = self::normalizarFechaPublicacion($_POST['fechaPublicacionLeccion'] ?? '');

        if ($idLeccion <= 0 || $nombreLeccion === '' || !$leccion) {
            $_SESSION['error_message'] = 'No se pudo actualizar la leccion.';
            return 'error';
        }

        if (!in_array($tipoLeccion, ['MATERIAL', 'TAREA', 'PREGUNTA'], true)) {
            $_SESSION['error_message'] = 'El tipo de leccion no es valido.';
            return 'error';
        }

        if (!self::puedeGestionarSeccion((int) ($leccion['id_modulo'] ?? 0))) {
            $_SESSION['error_message'] = 'No podes editar una materia donde no estas asignado.';
            return 'denied';
        }

        $respuesta = ModeloLecciones::mdlActualizarLeccion('lecciones', [
            'idLeccion' => $idLeccion,
            'nombreLeccion' => $nombreLeccion,
            'tipoLeccion' => $tipoLeccion,
            'contenidoLeccion' => $contenidoLeccion,
            'estadoLeccion' => $estadoLeccion,
            'fechaPublicacionLeccion' => $fechaPublicacionLeccion,
        ]);

        if ($respuesta === 'ok') {
            $estabaDisponible = self::leccionDisponibleParaEstudiantes($estadoActual, $fechaActual);
            $quedaDisponible = self::leccionDisponibleParaEstudiantes($estadoLeccion, $fechaPublicacionLeccion);
            if (!$estabaDisponible && $quedaDisponible) {
                $seccion = self::crtBuscarSeccionPorId((int) ($leccion['id_modulo'] ?? 0));
                $leccionActualizada = self::crtBuscarLeccionPorId($idLeccion);
                if ($seccion && $leccionActualizada) {
                    ControladorNotificaciones::crtNotificarLeccionPublicada($leccionActualizada, $seccion);
                }
            }
            $_SESSION['success_message'] = self::mensajeEstadoLeccion($estadoLeccion, $fechaPublicacionLeccion);
        } else {
            $_SESSION['error_message'] = 'No se pudo actualizar la leccion.';
        }

        return $respuesta;
    }

    private static function mensajeEstadoLeccion($estadoLeccion, $fechaPublicacionLeccion)
    {
        if ($estadoLeccion === 'BORRADOR') {
            return 'Leccion guardada como borrador.';
        }

        if ($fechaPublicacionLeccion && strtotime((string) $fechaPublicacionLeccion) > time()) {
            return 'Leccion programada correctamente.';
        }

        return 'Leccion publicada correctamente.';
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

        if (!self::puedeGestionarSeccion((int) ($leccion['id_modulo'] ?? 0))) {
            $_SESSION['error_message'] = 'No podes editar una materia donde no estas asignado.';
            return 'denied';
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
        if (!isset($_POST['id_leccion'])) {
            return null;
        }

        if (!(ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente())) {
            $_SESSION['error_message'] = 'No tenes permisos para agregar recursos.';
            return 'denied';
        }

        $idLeccion = (int) $_POST['id_leccion'];
        $leccion = self::crtBuscarLeccionPorId($idLeccion);

        if ($idLeccion <= 0 || !$leccion) {
            $_SESSION['error_message'] = 'No se pudo encontrar la leccion.';
            return 'error';
        }

        if (!self::puedeGestionarSeccion((int) ($leccion['id_modulo'] ?? 0))) {
            $_SESSION['error_message'] = 'No podes editar una materia donde no estas asignado.';
            return 'denied';
        }

        $respuesta = self::procesarRecursosFormulario($idLeccion, [
            'titulo' => 'tituloRecurso',
            'archivo' => 'archivoRecurso',
            'titulosArchivos' => 'titulosRecursoArchivo',
            'url' => 'urlRecurso',
            'urls' => 'urlsRecurso',
            'titulosUrls' => 'titulosRecursoUrl',
        ]);

        if ($respuesta === false) {
            $_SESSION['error_message'] = 'No se pudo guardar uno o mas recursos.';
            return 'error';
        }

        $totalRecursos = count($respuesta);
        if ($totalRecursos > 0) {
            $_SESSION['success_message'] = $totalRecursos === 1
                ? 'Recurso agregado correctamente.'
                : $totalRecursos . ' recursos agregados correctamente.';
        } else {
            $_SESSION['error_message'] = 'Agrega al menos un archivo o enlace.';
            return 'error';
        }

        return 'ok';
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
        $leccion = self::crtBuscarLeccionPorId($idLeccion);

        if (!$recurso || $idLeccion <= 0 || $tituloRecurso === '' || !$leccion) {
            $_SESSION['error_message'] = 'No se pudo actualizar el recurso.';
            return 'error';
        }

        if (!self::puedeGestionarSeccion((int) ($leccion['id_modulo'] ?? 0))) {
            $_SESSION['error_message'] = 'No podes editar una materia donde no estas asignado.';
            return 'denied';
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
        $leccion = $recurso ? self::crtBuscarLeccionPorId((int) ($recurso['id_leccion'] ?? 0)) : null;

        if (!$recurso || !$leccion) {
            $_SESSION['error_message'] = 'El recurso no existe.';
            return 'error';
        }

        if (!self::puedeGestionarSeccion((int) ($leccion['id_modulo'] ?? 0))) {
            $_SESSION['error_message'] = 'No podes editar una materia donde no estas asignado.';
            return 'denied';
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

        $entregaAnterior = self::crtBuscarEntregaPorLeccionEstudiante((int) $_POST['id_leccion'], $idEstudiante);
        $urlArchivo = (string) ($entregaAnterior['urlArchivo'] ?? '');
        $comentarioEntrega = trim((string) ($_POST['comentarioEntrega'] ?? ''));

        $archivosSeleccionados = self::normalizarArchivos('archivoEntrega');
        $adjuntosNuevos = [];

        foreach ($archivosSeleccionados as $archivo) {
            if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                self::eliminarAdjuntosLocales($adjuntosNuevos);
                $_SESSION['error_message'] = 'Uno de los archivos no se pudo cargar. La entrega no fue modificada.';
                return 'error';
            }

            $rutaArchivo = self::subirArchivo($archivo);
            if ($rutaArchivo === '') {
                self::eliminarAdjuntosLocales($adjuntosNuevos);
                $_SESSION['error_message'] = 'Uno de los archivos no tiene un formato valido. La entrega no fue modificada.';
                return 'error';
            }

            $adjuntosNuevos[] = [
                'nombreOriginal' => basename((string) ($archivo['name'] ?? 'Archivo')),
                'rutaArchivo' => $rutaArchivo,
                'mimeType' => trim((string) ($archivo['type'] ?? '')),
                'tamanoArchivo' => (int) ($archivo['size'] ?? 0),
            ];
        }

        if (!empty($adjuntosNuevos)) {
            $urlArchivo = (string) $adjuntosNuevos[0]['rutaArchivo'];
        }

        if ($urlArchivo === '' && $comentarioEntrega === '') {
            $_SESSION['error_message'] = 'Adjunta al menos un archivo o escribe un comentario con tu entrega.';
            return 'error';
        }

        $idEntregaGuardada = ModeloLecciones::mdlGuardarEntregaConAdjuntos([
            'id_leccion' => (int) $_POST['id_leccion'],
            'id_seccion' => (int) $_POST['id_seccion'],
            'id_curso' => (int) $_POST['id_curso'],
            'id_estudiante' => (int) ($_SESSION['usuario']['id'] ?? 0),
            'urlArchivo' => $urlArchivo,
            'comentarioEntrega' => $comentarioEntrega,
            'fechaEntrega' => date('Y-m-d H:i:s'),
            'estadoEntrega' => 'ENTREGADA',
        ], $adjuntosNuevos, !empty($archivosSeleccionados));

        if ($idEntregaGuardada !== false) {
            if (!empty($adjuntosNuevos) && $entregaAnterior) {
                self::eliminarArchivosEntrega($entregaAnterior);
            }

            $cantidadArchivos = !empty($adjuntosNuevos)
                ? count($adjuntosNuevos)
                : count((array) ($entregaAnterior['adjuntos'] ?? []));
            if ($cantidadArchivos > 0) {
                $_SESSION['success_message'] = 'Entrega enviada correctamente con ' . $cantidadArchivos . ' archivo' . ($cantidadArchivos === 1 ? '.' : 's.');
            } else {
                $_SESSION['success_message'] = 'Entrega enviada correctamente con tu comentario o enlace.';
            }
            return 'ok';
        } else {
            self::eliminarAdjuntosLocales($adjuntosNuevos);
            $_SESSION['error_message'] = 'No se pudo registrar la entrega.';
        }

        return 'error';
    }

    public static function crtCancelarEntregaLeccion()
    {
        if (!isset($_POST['id_leccion'], $_POST['id_seccion'], $_POST['id_curso'])) {
            return null;
        }

        if (!ControladorPermisos::esEstudiante()) {
            $_SESSION['error_message'] = 'Solo los estudiantes pueden cancelar entregas.';
            return 'denied';
        }

        $idEstudiante = (int) ($_SESSION['usuario']['id'] ?? 0);
        $idLeccion = (int) $_POST['id_leccion'];
        $idSeccion = (int) $_POST['id_seccion'];

        $calificacion = ControladorCalificaciones::crtCalificacionPorLeccionYEstudiante($idSeccion, $idLeccion, $idEstudiante);
        if (!empty($calificacion)) {
            $_SESSION['error_message'] = 'No podes cancelar una entrega que ya tiene nota.';
            return 'denied';
        }

        $entrega = self::crtBuscarEntregaPorLeccionEstudiante($idLeccion, $idEstudiante);
        if (!$entrega) {
            $_SESSION['error_message'] = 'No encontramos una entrega para cancelar.';
            return 'error';
        }

        $respuesta = ModeloLecciones::mdlEliminarEntregaLeccion((int) $entrega['idEntregaLeccion']);

        if ($respuesta === 'ok') {
            self::eliminarArchivosEntrega($entrega);
            $_SESSION['success_message'] = 'Entrega cancelada correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudo cancelar la entrega.';
        }

        return $respuesta;
    }

    private static function completarAdjuntosEntrega(array $entrega)
    {
        $adjuntos = ModeloLecciones::mdlBuscarAdjuntosPorEntrega((int) ($entrega['idEntregaLeccion'] ?? 0));

        if (empty($adjuntos) && !empty($entrega['urlArchivo'])) {
            $adjuntos[] = [
                'idAdjuntoEntrega' => 0,
                'id_entrega' => (int) ($entrega['idEntregaLeccion'] ?? 0),
                'nombreOriginal' => 'Archivo entregado',
                'rutaArchivo' => (string) $entrega['urlArchivo'],
                'mimeType' => null,
                'tamanoArchivo' => null,
                'fechaAdjunto' => $entrega['fechaEntrega'] ?? null,
            ];
        }

        $entrega['adjuntos'] = $adjuntos;
        return $entrega;
    }

    private static function eliminarAdjuntosLocales(array $adjuntos)
    {
        foreach ($adjuntos as $adjunto) {
            self::eliminarArchivoLocal((string) ($adjunto['rutaArchivo'] ?? ''));
        }
    }

    private static function eliminarArchivosEntrega(array $entrega)
    {
        $rutas = [(string) ($entrega['urlArchivo'] ?? '')];

        foreach ((array) ($entrega['adjuntos'] ?? []) as $adjunto) {
            $rutas[] = (string) ($adjunto['rutaArchivo'] ?? '');
        }

        foreach (array_unique(array_filter($rutas)) as $ruta) {
            self::eliminarArchivoLocal($ruta);
        }
    }

    private static function subirArchivo(array $archivo)
    {
        $directorio = __DIR__ . '/../uploads/lecciones/';

        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            return '';
        }

        $nombreOriginal = (string) ($archivo['name'] ?? '');
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $extensionesPermitidas = [
            'pdf', 'doc', 'docx', 'odt', 'xls', 'xlsx', 'ods', 'csv',
            'ppt', 'pptx', 'odp', 'jpg', 'jpeg', 'png', 'gif', 'webp',
            'txt', 'zip', 'rar', '7z', 'mp3', 'mp4'
        ];

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

    private static function normalizarArchivos($nombreCampo)
    {
        if (empty($_FILES[$nombreCampo]['name'])) {
            return [];
        }

        $archivo = $_FILES[$nombreCampo];
        if (!is_array($archivo['name'])) {
            return [$archivo];
        }

        $archivos = [];
        foreach ($archivo['name'] as $indice => $nombre) {
            if ((string) $nombre === '') {
                continue;
            }

            $archivos[] = [
                'name' => $nombre,
                'type' => $archivo['type'][$indice] ?? '',
                'tmp_name' => $archivo['tmp_name'][$indice] ?? '',
                'error' => $archivo['error'][$indice] ?? UPLOAD_ERR_NO_FILE,
                'size' => $archivo['size'][$indice] ?? 0,
            ];
        }

        return $archivos;
    }

    private static function normalizarUrls($campoSimple, $campoMultiple = '')
    {
        $valores = [];

        if ($campoMultiple !== '' && isset($_POST[$campoMultiple])) {
            $entradaMultiple = $_POST[$campoMultiple];
            $valores = is_array($entradaMultiple) ? $entradaMultiple : preg_split('/\r\n|\r|\n/', (string) $entradaMultiple);
        }

        if (isset($_POST[$campoSimple])) {
            $entradaSimple = $_POST[$campoSimple];
            $valores = array_merge($valores, is_array($entradaSimple) ? $entradaSimple : preg_split('/\r\n|\r|\n/', (string) $entradaSimple));
        }

        return array_values(array_filter(array_map(static function ($url) {
            return trim((string) $url);
        }, $valores), static function ($url) {
            return $url !== '';
        }));
    }

    private static function normalizarUrlRecurso($url)
    {
        $url = trim((string) $url);

        if ($url === '') {
            return '';
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $url = 'https://' . ltrim($url, '/');
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }

    private static function tituloDesdeUrl($url)
    {
        return parse_url($url, PHP_URL_HOST) ?: 'Enlace de la leccion';
    }

    private static function tituloRecursoParaCarga($tituloBase, $tituloAutomatico, array &$titulosUsados)
    {
        $tituloBase = trim((string) $tituloBase);
        $tituloAutomatico = trim((string) $tituloAutomatico);

        if ($tituloAutomatico === '') {
            $tituloAutomatico = 'Recurso';
        }

        if ($tituloBase === '') {
            $titulo = $tituloAutomatico;
        } else {
            $titulo = $tituloBase . ' - ' . $tituloAutomatico;
        }

        $tituloOriginal = $titulo;
        $numero = 2;
        $clave = strtolower($titulo);

        while (isset($titulosUsados[$clave])) {
            $titulo = $tituloOriginal . ' (' . $numero . ')';
            $clave = strtolower($titulo);
            $numero++;
        }

        $titulosUsados[$clave] = true;
        return $titulo;
    }

    private static function guardarRecursoLeccion($idLeccion, $tipo, $titulo, $url)
    {
        return ModeloLecciones::mdlGuardarRecursoLeccion('recursoslecciones', [
            'id_leccion' => (int) $idLeccion,
            'tipoRecurso' => $tipo,
            'tituloRecurso' => $titulo,
            'urlRecurso' => $url,
            'creadoPor' => (int) ($_SESSION['usuario']['id'] ?? 0),
        ]);
    }

    private static function procesarRecursosFormulario($idLeccion, array $campos)
    {
        $tituloBase = trim((string) ($_POST[$campos['titulo']] ?? ''));
        $archivos = self::normalizarArchivos($campos['archivo']);
        $urls = self::normalizarUrls($campos['url'], $campos['urls'] ?? '');
        $titulosArchivos = isset($campos['titulosArchivos'], $_POST[$campos['titulosArchivos']])
            ? (array) $_POST[$campos['titulosArchivos']]
            : [];
        $titulosUrls = isset($campos['titulosUrls'], $_POST[$campos['titulosUrls']])
            ? (array) $_POST[$campos['titulosUrls']]
            : [];
        $recursosGuardados = [];
        $titulosUsados = [];

        foreach ($archivos as $indiceArchivo => $archivo) {
            if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                return false;
            }

            $urlRecurso = self::subirArchivo($archivo);
            if ($urlRecurso === '') {
                return false;
            }

            $nombreOriginal = basename((string) ($archivo['name'] ?? 'Recurso adjunto'));
            $tituloIndividual = trim((string) ($titulosArchivos[$indiceArchivo] ?? ''));
            $titulo = self::tituloRecursoParaCarga(
                $tituloBase,
                $tituloIndividual !== '' ? $tituloIndividual : $nombreOriginal,
                $titulosUsados
            );

            $respuesta = self::guardarRecursoLeccion($idLeccion, 'ARCHIVO', $titulo, $urlRecurso);
            if ($respuesta !== 'ok') {
                self::eliminarArchivoLocal($urlRecurso);
                return false;
            }

            $recursosGuardados[] = $urlRecurso;
        }

        foreach ($urls as $indiceUrl => $url) {
            $urlRecurso = self::normalizarUrlRecurso($url);
            if ($urlRecurso === '') {
                return false;
            }

            $titulo = self::tituloRecursoParaCarga(
                $tituloBase,
                trim((string) ($titulosUrls[$indiceUrl] ?? '')) ?: self::tituloDesdeUrl($urlRecurso),
                $titulosUsados
            );
            $respuesta = self::guardarRecursoLeccion($idLeccion, 'ENLACE', $titulo, $urlRecurso);
            if ($respuesta !== 'ok') {
                return false;
            }

            $recursosGuardados[] = $urlRecurso;
        }

        return $recursosGuardados;
    }

    private static function procesarRecursoInicial($idLeccion)
    {
        return self::procesarRecursosFormulario($idLeccion, [
            'titulo' => 'tituloRecursoInicial',
            'archivo' => 'archivoRecursoInicial',
            'titulosArchivos' => 'titulosRecursoInicialArchivo',
            'url' => 'urlRecursoInicial',
            'urls' => 'urlsRecursoInicial',
            'titulosUrls' => 'titulosRecursoInicialUrl',
        ]);
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
