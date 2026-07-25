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
