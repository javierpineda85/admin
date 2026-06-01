<?php

class RutasController
{
    private static function rutasPublicas()
    {
        return ['login', 'forgot'];
    }

    private static function mapaRutas()
    {
        return [
            // Usuario / perfil
            'crear-usuario'   => 'usuario/crear-usuario.php',
            'listado-usuarios'=> 'usuario/listado-usuarios.php',
            'perfil-usuario'  => 'usuario/perfil-usuario.php',
            'editar-usuario'  => 'usuario/editar-usuario.php',

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

            // Mensajes
            'bandeja-entrada' => 'mensajes/bandeja-entrada.php',
            'nuevo-mensaje'   => 'mensajes/nuevo-mensaje.php',
            'mensajes-enviados'=> 'mensajes/mensajes-enviados.php',
            'papelera'        => 'mensajes/papelera.php',
            'detalle-mensaje' => 'mensajes/detalle-mensaje.php',

            // Web pública
            'login'           => 'web/login.php',
            'forgot'          => 'web/forgot-password.php',
            'logout'          => 'web/logout.php',
        ];
    }

    private static function rutaBasePaginas()
    {
        return __DIR__ . '/../vistas/paginas/';
    }

    public static function cargarVista()
    {
        $ruta = isset($_GET['r']) ? trim($_GET['r']) : '';
        $mapeo = self::mapaRutas();

        if ($ruta === 'logout') {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            session_unset();
            session_destroy();
            header('Location: index.php?r=login');
            exit;
        }

        $esPublica = $ruta !== '' && in_array($ruta, self::rutasPublicas(), true);
        $tieneSesion = isset($_SESSION['logueado']) && $_SESSION['logueado'] === true;

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
