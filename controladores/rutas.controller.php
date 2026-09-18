<?php

class RutasController
{
    public static function rutasInstitucionales()
    {
        return ['seleccionar-institucion', 'sin-acceso-institucional', 'institucion-preparada'];
    }

    private static function rutasPublicas()
    {
        return ['login', 'forgot', 'actividad-publica'];
    }

    private static function mapaRutas()
    {
        return [
            // Usuario / perfil
            'crear-usuario'   => 'usuario/crear-usuario.php',
            'listado-usuarios'=> 'usuario/listado-usuarios.php',
            'usuarios-inactivos'=> 'usuario/usuarios-inactivos.php',
            'usuarios-no-conectados'=> 'usuario/usuarios-no-conectados.php',
            'perfil-usuario'  => 'usuario/perfil-usuario.php',
            'perfil-publico'  => 'usuario/perfil-publico.php',
            'editar-usuario'  => 'usuario/editar-usuario.php',
            'editar-perfil'   => 'usuario/editar-perfil.php',

            // Cursos
            'crear-curso'     => 'cursos/crear-curso.php',
            'listado-cursos'  => 'cursos/listado-cursos.php',
            'editar-curso'    => 'cursos/editar-curso.php',
            'detalle-curso'   => 'cursos/detalle-curso.php',

            // Materias
            'listado-materias'=> 'materias/listado-materias.php',
            'crear-materia'   => 'materias/crear-materia.php',
            'editar-materia'  => 'materias/editar-materia.php',
            'detalle-seccion' => 'materias/detalle-seccion.php',
            'calificaciones-seccion' => 'materias/calificaciones-seccion.php',
            'calificaciones' => 'materias/calificaciones.php',
            'asistencias' => 'materias/asistencias.php',
            'asistencia-seccion' => 'materias/asistencia-seccion.php',
            'asistencia-curso' => 'cursos/asistencia-curso.php',

            // Mensajes
            'bandeja-entrada' => 'mensajes/bandeja-entrada.php',
            'nuevo-mensaje'   => 'mensajes/nuevo-mensaje.php',
            'mensajes-enviados'=> 'mensajes/mensajes-enviados.php',
            'papelera'        => 'mensajes/papelera.php',
            'detalle-mensaje' => 'mensajes/detalle-mensaje.php',
            'listado-actividades' => 'actividades/listado-actividades.php',
            'banco-actividades' => 'actividades/banco-actividades.php',
            'crear-actividad' => 'actividades/crear-actividad.php',
            'editar-actividad' => 'actividades/editar-actividad.php',
            'ver-actividad' => 'actividades/ver-actividad.php',
            'resultados-actividad' => 'actividades/resultados-actividad.php',

            // Administración global MenteMotion
            'superadmin'      => 'instituciones/panel-superadmin.php',

            // Web pública
            'login'           => 'web/login.php',
            'forgot'          => 'web/forgot-password.php',
            'actividad-publica' => 'web/actividad-publica.php',
            'logout'          => 'web/logout.php',
        ];
    }

    private static function redireccionSegura($destino, $fallback = 'index.php')
    {
        $destino = trim(urldecode((string) $destino));
        if ($destino === '') {
            return $fallback;
        }

        $partes = parse_url($destino);
        if ($partes === false || isset($partes['scheme'], $partes['host'])) {
            return $fallback;
        }

        $ruta = trim((string) ($partes['path'] ?? ''));
        if ($ruta !== '' && !preg_match('~(^|/)index\.php$~i', $ruta)) {
            return $fallback;
        }

        return $destino;
    }

    private static function rutaDestinoDesdeUrl($destino)
    {
        $destino = trim(urldecode((string) $destino));
        if ($destino === '') {
            return '';
        }

        $partes = parse_url($destino);
        if ($partes === false) {
            return '';
        }

        $consulta = [];
        if (!empty($partes['query'])) {
            parse_str($partes['query'], $consulta);
        }

        return trim((string) ($consulta['r'] ?? ''));
    }

    private static function rutaBasePaginas()
    {
        return __DIR__ . '/../vistas/paginas/';
    }

    private static function denegarRecursoInstitucional()
    {
        http_response_code(403);
        include self::rutaBasePaginas() . '404.php';
        exit;
    }

    private static function validarRecursoInstitucional($ruta)
    {
        if (!class_exists('ControladorInstitucion', false) || !ControladorInstitucion::activo()) {
            return true;
        }

        if (in_array($ruta, ['detalle-curso', 'editar-curso', 'asistencia-curso'], true)) {
            $idCurso = (int) ($_GET['idCurso'] ?? $_GET['id'] ?? 0);
            return $idCurso > 0 && ModeloCursos::mdlBuscarCursoPorId($idCurso) !== null;
        }

        if (in_array($ruta, ['detalle-seccion', 'editar-materia', 'calificaciones-seccion', 'asistencia-seccion'], true)) {
            $idSeccion = (int) ($_GET['idSeccion'] ?? $_GET['id'] ?? 0);
            return $idSeccion > 0 && ModeloMaterias::mdlBuscarMateriaPorId($idSeccion) !== false;
        }

        if (in_array($ruta, ['editar-actividad', 'ver-actividad', 'resultados-actividad'], true)) {
            $idActividad = (int) ($_GET['idActividad'] ?? 0);
            return $idActividad > 0 && ModeloActividades::mdlBuscarPorId($idActividad) !== null;
        }

        if ($ruta === 'detalle-mensaje') {
            $idMensaje = (int) ($_GET['idMensaje'] ?? 0);
            $idUsuario = (int) ($_SESSION['usuario']['id'] ?? 0);
            return $idMensaje > 0 && $idUsuario > 0
                && ModeloMensajes::mdlMensajeDetalle($idMensaje, $idUsuario) !== null;
        }

        if ($ruta === 'nuevo-mensaje' && (int) ($_GET['idMsj'] ?? 0) > 0) {
            $idMensaje = (int) $_GET['idMsj'];
            $idUsuario = (int) ($_SESSION['usuario']['id'] ?? 0);
            return $idUsuario > 0 && ModeloMensajes::mdlMensajeDetalle($idMensaje, $idUsuario) !== null;
        }

        if ($ruta === 'perfil-publico') {
            return ControladorUsuarios::crtPuedeVerPerfilEnSeccion(
                (int) ($_GET['idUsuario'] ?? 0),
                (int) ($_GET['idSeccion'] ?? 0)
            );
        }

        if ($ruta === 'editar-usuario') {
            $idUsuario = (int) ($_GET['id'] ?? 0);
            return $idUsuario > 0 && ModeloUsuarios::mdlObtenerUsuarioCompleto($idUsuario) !== false;
        }

        return true;
    }

    private static function validarAccionPostInstitucional($ruta)
    {
        if (!class_exists('ControladorInstitucion', false) || !ControladorInstitucion::activo()
            || strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return true;
        }

        try {
            if ($ruta === 'asistencia-curso') {
                $idCurso = (int)($_GET['idCurso'] ?? 0);
                if ($idCurso !== (int)($_POST['id_curso'] ?? 0) || !ControladorAsistencias::crtPuedeGestionarCurso($idCurso)) {
                    return false;
                }
                if ((int)($_POST['id_clase'] ?? 0) > 0 && !ModeloAsistenciasCurso::clase((int)$_POST['id_clase'], $idCurso)) {
                    return false;
                }
            }
            if (in_array($ruta, ['detalle-curso', 'editar-curso'], true)
                && isset($_POST['idCurso'])
                && (int) $_POST['idCurso'] !== (int) ($_GET['idCurso'] ?? $_GET['id'] ?? 0)) {
                return false;
            }

            if (in_array($ruta, ['detalle-seccion', 'editar-materia', 'calificaciones-seccion', 'asistencia-seccion'], true)) {
                $idSeccion = (int) ($_GET['idSeccion'] ?? $_GET['id'] ?? 0);
                $materia = $idSeccion > 0 ? ModeloMaterias::mdlBuscarMateriaPorId($idSeccion) : false;
                if (!$materia) {
                    return false;
                }

                if (isset($_POST['id_seccion']) && (int) $_POST['id_seccion'] !== $idSeccion) {
                    return false;
                }

                if (isset($_POST['idSeccion']) && (int) $_POST['idSeccion'] !== $idSeccion) {
                    return false;
                }

                if (isset($_POST['id_curso']) && (int) $_POST['id_curso'] !== (int) ($materia['id_curso'] ?? 0)) {
                    return false;
                }
            }

            if ($ruta === 'detalle-seccion') {
                $idSeccion = (int) ($_GET['idSeccion'] ?? 0);
                foreach (['id_leccion', 'idLeccion', 'id_modulo'] as $campoLeccion) {
                    if ((int) ($_POST[$campoLeccion] ?? 0) <= 0) {
                        continue;
                    }

                    $leccion = ModeloLecciones::mdlBuscarLeccionPorId((int) $_POST[$campoLeccion]);
                    if (!$leccion || (int) ($leccion['id_modulo'] ?? 0) !== $idSeccion) {
                        return false;
                    }
                }

                if ((int) ($_POST['idRecursoLeccion'] ?? 0) > 0) {
                    $recurso = ModeloLecciones::mdlBuscarRecursoPorId((int) $_POST['idRecursoLeccion']);
                    $leccion = $recurso
                        ? ModeloLecciones::mdlBuscarLeccionPorId((int) ($recurso['id_leccion'] ?? 0))
                        : null;
                    if (!$leccion || (int) ($leccion['id_modulo'] ?? 0) !== $idSeccion) {
                        return false;
                    }
                }
            }

            if ($ruta === 'calificaciones-seccion') {
                $idSeccion = (int) ($_GET['idSeccion'] ?? 0);
                if ((int) ($_POST['id_evaluacion'] ?? 0) > 0) {
                    $evaluacion = ModeloCalificaciones::mdlEvaluacionPorId((int) $_POST['id_evaluacion']);
                    if (!$evaluacion || (int) ($evaluacion['id_seccion'] ?? 0) !== $idSeccion) {
                        return false;
                    }
                }

                if ((int) ($_POST['id_periodo'] ?? 0) > 0
                    && ModeloCalificaciones::mdlPeriodoPorId((int) $_POST['id_periodo'], $idSeccion) === null) {
                    return false;
                }

                if ((int) ($_POST['id_instrumento'] ?? 0) > 0) {
                    $contexto = ModeloCalificaciones::mdlContextoAcademicoSeccion($idSeccion);
                    $instrumentos = array_map('intval', array_column($contexto['instrumentos'] ?? [], 'idInstrumento'));
                    if (!in_array((int) $_POST['id_instrumento'], $instrumentos, true)) {
                        return false;
                    }
                }
            }

            if ($ruta === 'asistencia-seccion' && (int) ($_POST['id_clase'] ?? 0) > 0) {
                $clase = ModeloAsistencias::mdlClase((int) $_POST['id_clase']);
                if (!$clase || (int) ($clase['id_seccion'] ?? 0) !== (int) ($_GET['idSeccion'] ?? 0)) {
                    return false;
                }
            }

            if (in_array($ruta, ['listado-actividades', 'banco-actividades', 'editar-actividad', 'ver-actividad'], true)
                && (int) ($_POST['idActividad'] ?? 0) > 0) {
                return ModeloActividades::mdlBuscarPorId((int) $_POST['idActividad']) !== null;
            }

            if (in_array($ruta, ['bandeja-entrada', 'mensajes-enviados', 'papelera', 'detalle-mensaje'], true)
                && (int) ($_POST['id_mensaje'] ?? 0) > 0) {
                return ModeloMensajes::mdlMensajeDetalle(
                    (int) $_POST['id_mensaje'],
                    (int) ($_SESSION['usuario']['id'] ?? 0)
                ) !== null;
            }

            if ($ruta === 'nuevo-mensaje') {
                $idUsuario = (int) ($_SESSION['usuario']['id'] ?? 0);
                if ((int) ($_POST['id_mensaje_respuesta'] ?? 0) > 0
                    && ModeloMensajes::mdlMensajeDetalle((int) $_POST['id_mensaje_respuesta'], $idUsuario) === null) {
                    return false;
                }

                if ((int) ($_POST['id_seccion_destino'] ?? 0) > 0
                    && ModeloMaterias::mdlBuscarMateriaPorId((int) $_POST['id_seccion_destino']) === false) {
                    return false;
                }
            }

            if ($ruta === 'editar-usuario' && isset($_POST['idUsuario'])
                && (int) $_POST['idUsuario'] !== (int) ($_GET['id'] ?? 0)) {
                return false;
            }

            if ($ruta === 'usuarios-inactivos' && (int) ($_POST['idReactivar'] ?? 0) > 0
                && ModeloUsuarios::mdlObtenerUsuarioCompleto((int) $_POST['idReactivar']) === false) {
                return false;
            }
        } catch (RuntimeException $e) {
            return false;
        }

        return true;
    }

    public static function procesarAntesDeRenderizar($ruta)
    {
        $ruta = trim((string) $ruta);
        if (($_SESSION['logueado'] ?? false) === true && $ruta === 'superadmin'
            && !ControladorPermisos::puedeAccederRuta($ruta)) {
            self::denegarRecursoInstitucional();
        }
        if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true || !ControladorPermisos::puedeAccederRuta($ruta)) {
            return;
        }

        if (!self::validarRecursoInstitucional($ruta)) {
            self::denegarRecursoInstitucional();
        }

        if (!self::validarAccionPostInstitucional($ruta)) {
            self::denegarRecursoInstitucional();
        }

        if ($ruta === 'superadmin') {
            $resultado = ControladorSuperAdmin::crtProcesar();
            if ($resultado === 'forbidden') {
                self::denegarRecursoInstitucional();
            }
            if ($resultado !== null) {
                header('Location: index.php?r=superadmin', true, 303);
                exit;
            }
            return;
        }

        if ($ruta === 'detalle-curso') {
            $idCurso = (int) ($_GET['idCurso'] ?? 0);
            $resultado = ControladorCursos::crtProcesarAdministracionCurso();
            if ($resultado !== null) {
                $eliminado = $resultado === 'ok' && ($_POST['accion_curso'] ?? '') === 'eliminar_curso';
                header('Location: ' . ($eliminado ? 'index.php?r=listado-cursos' : 'index.php?r=detalle-curso&idCurso=' . $idCurso));
                exit;
            }

            $curso = ControladorCursos::crtBuscarCursoPorId($idCurso);
            if ($curso && (int) ($curso['activo'] ?? 1) !== 1 && !ControladorPermisos::esAdministrador()) {
                $_SESSION['error_message'] = 'Este curso se encuentra dado de baja.';
                header('Location: index.php?r=listado-cursos');
                exit;
            }
        }

        if ($ruta === 'listado-cursos') {
            $idCursoNuevo = ControladorCursos::crtDuplicarCurso();
            if ($idCursoNuevo !== null) {
                header('Location: ' . ($idCursoNuevo > 0
                    ? 'index.php?r=detalle-curso&idCurso=' . (int) $idCursoNuevo
                    : 'index.php?r=listado-cursos'));
                exit;
            }
        }

        if ($ruta === 'asistencia-curso') {
            $resultado = ControladorAsistencias::crtProcesarCurso();
            if ($resultado !== null) {
                $destino = 'index.php?r=asistencia-curso&idCurso=' . (int)($_GET['idCurso'] ?? 0);
                if ((int)$resultado > 0) { $destino .= '&idClase=' . (int)$resultado; }
                header('Location: ' . $destino);
                exit;
            }
        }
        if ($ruta === 'asistencia-seccion' && isset($_POST['accion_asistencia'])) {
            $_SESSION['error_message'] = 'El historial por materia es de consulta. Registrá la asistencia desde el curso.';
            header('Location: index.php?r=asistencia-seccion&idSeccion=' . (int)($_GET['idSeccion'] ?? 0));
            exit;
        }

        if ($ruta === 'editar-curso') {
            $idCurso = (int) ($_GET['idCurso'] ?? $_GET['id'] ?? 0);
            if (!ControladorCursos::crtPuedeGestionarCurso($idCurso)) {
                $_SESSION['error_message'] = 'No podes editar un curso que no esta a tu cargo.';
                header('Location: index.php?r=listado-cursos');
                exit;
            }
        }

        if (in_array($ruta, ['detalle-seccion', 'editar-materia'], true)) {
            $idSeccion = (int) ($_GET['idSeccion'] ?? $_GET['id'] ?? 0);
            if ($ruta === 'detalle-seccion') {
                $resultado = ControladorMaterias::crtProcesarAdministracionMateria();
                if ($resultado !== null) {
                    $eliminada = $resultado === 'ok' && ($_POST['accion_materia'] ?? '') === 'eliminar_materia';
                    header('Location: ' . ($eliminada ? 'index.php?r=listado-materias' : 'index.php?r=detalle-seccion&idSeccion=' . $idSeccion . '#administracion'));
                    exit;
                }
            }

            $materia = ControladorMaterias::crtBuscarMateriaPorId($idSeccion);
            if ($materia && !ControladorPermisos::esAdministrador()) {
                $curso = ControladorCursos::crtBuscarCursoPorId((int) ($materia['id_curso'] ?? 0));
                if ((int) ($materia['activo'] ?? 1) !== 1 || ($curso && (int) ($curso['activo'] ?? 1) !== 1)) {
                    $_SESSION['error_message'] = 'Esta materia no se encuentra disponible.';
                    header('Location: index.php?r=listado-materias');
                    exit;
                }
            }
        }
    }

    public static function procesarVistaEstudiante()
    {
        $tieneSesion = isset($_SESSION['logueado']) && $_SESSION['logueado'] === true;
        if (!$tieneSesion) {
            return false;
        }

        $estado = trim((string) ($_GET['estado'] ?? '1'));
        $activar = in_array($estado, ['1', 'true', 'on', 'si'], true);
        $idEstudiante = max(0, (int) ($_GET['idEstudiante'] ?? 0));

        if ($activar && $idEstudiante > 0) {
            $rolReal = ControladorPermisos::rolReal();
            $idUsuarioReal = (int) ($_SESSION['usuario']['id'] ?? 0);
            $puedePrevisualizar = ControladorPermisos::usuarioTieneRolEnInstitucion($idEstudiante, ['ESTUDIANTE']);

            if ($puedePrevisualizar && $rolReal === 'DOCENTE') {
                $puedePrevisualizar = false;
                foreach (ControladorCursos::crtCursosPorDocente($idUsuarioReal) as $cursoDocente) {
                    if (ControladorCursos::crtEstudianteInscriptoCurso($idEstudiante, (int) ($cursoDocente['idCurso'] ?? 0))) {
                        $puedePrevisualizar = true;
                        break;
                    }
                }
            }

            if (!$puedePrevisualizar) {
                $idEstudiante = 0;
                $_SESSION['error_message'] = 'No podes previsualizar el campus con ese estudiante.';
            }
        }

        ControladorPermisos::activarVistaEstudiante($activar, $idEstudiante);

        $redirigir = self::redireccionSegura($_GET['redir'] ?? 'index.php');
        $rutaRedirigir = self::rutaDestinoDesdeUrl($redirigir);
        if ($rutaRedirigir !== '' && !ControladorPermisos::puedeAccederRuta($rutaRedirigir)) {
            $redirigir = 'index.php';
        }

        header('Location: ' . $redirigir);
        exit;
    }

    public static function cargarVista()
    {
        $ruta = isset($_GET['r']) ? trim($_GET['r']) : '';
        // En ensayo se resuelven antes del layout. Desactivado, no deben caer en Inicio.
        if (in_array($ruta, self::rutasInstitucionales(), true)) {
            http_response_code(404);
            include self::rutaBasePaginas() . '404.php';
            return;
        }
        $mapeo = self::mapaRutas();
        $tieneSesion = isset($_SESSION['logueado']) && $_SESSION['logueado'] === true;

        if ($ruta === 'logout') {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            session_unset();
            session_destroy();
            header('Location: index.php?r=login');
            exit;
        }

        if ($ruta === 'vista-estudiante') {
            self::procesarVistaEstudiante();
        }

        $esPublica = $ruta !== '' && in_array($ruta, self::rutasPublicas(), true);

        if (!$esPublica && !$tieneSesion) {
            header('Location: index.php?r=login');
            exit;
        }

        if ($tieneSesion && !ControladorPermisos::puedeAccederRuta($ruta)) {
            if ($ruta === 'superadmin') { http_response_code(403); }
            include self::rutaBasePaginas() . '404.php';
            return;
        }

        if ($ruta !== '' && array_key_exists($ruta, $mapeo)) {
            $archivo = self::rutaBasePaginas() . $mapeo[$ruta];
            include is_file($archivo) ? $archivo : self::rutaBasePaginas() . '404.php';
            return;
        }

        include self::rutaBasePaginas() . 'inicio.php';
    }
}
