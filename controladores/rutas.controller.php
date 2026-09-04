<?php

class RutasController
{
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

    public static function procesarAntesDeRenderizar($ruta)
    {
        $ruta = trim((string) $ruta);
        if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true || !ControladorPermisos::puedeAccederRuta($ruta)) {
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
                    header('Location: ' . ($eliminada ? 'index.php?r=listado-materias' : 'index.php?r=detalle-seccion&idSeccion=' . $idSeccion));
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
            $estudiante = ModeloUsuarios::mdlObtenerUsuarioPorId($idEstudiante);
            $rolReal = ControladorPermisos::rolReal();
            $idUsuarioReal = (int) ($_SESSION['usuario']['id'] ?? 0);
            $puedePrevisualizar = $estudiante
                && strtoupper((string) ($estudiante['rol'] ?? '')) === 'ESTUDIANTE'
                && (int) ($estudiante['activo'] ?? 0) === 1;

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
