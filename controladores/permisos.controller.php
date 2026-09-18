<?php

class ControladorPermisos
{
    private const PRIORIDAD_ROLES = ['ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE'];

    private static function normalizarRol($rol)
    {
        return strtoupper(trim((string) $rol));
    }

    public static function rolReal()
    {
        foreach (self::PRIORIDAD_ROLES as $rol) {
            if (in_array($rol, self::rolesReales(), true)) {
                return $rol;
            }
        }
        return '';
    }

    public static function rolesReales()
    {
        if (class_exists('ControladorInstitucion', false) && ControladorInstitucion::activo()) {
            $roles = ControladorInstitucion::roles();
        } else {
            $roles = [$_SESSION['usuario']['rol'] ?? ''];
        }

        $normalizados = [];
        foreach ((array) $roles as $rol) {
            $rol = self::normalizarRol($rol);
            if (in_array($rol, self::PRIORIDAD_ROLES, true)) {
                $normalizados[$rol] = true;
            }
        }
        return array_values(array_filter(
            self::PRIORIDAD_ROLES,
            static function ($rol) use ($normalizados) { return isset($normalizados[$rol]); }
        ));
    }

    public static function tieneRol($rol)
    {
        return in_array(self::normalizarRol($rol), self::rolesReales(), true);
    }

    public static function tieneAlgunoDeLosRoles(array $roles)
    {
        foreach ($roles as $rol) {
            if (self::tieneRol($rol)) { return true; }
        }
        return false;
    }

    public static function vistaEstudianteActiva()
    {
        return !empty($_SESSION['vista_estudiante'])
            && in_array(self::rolReal(), ['ADMINISTRADOR', 'DOCENTE'], true);
    }

    public static function rolActual()
    {
        if (self::vistaEstudianteActiva()) {
            return 'ESTUDIANTE';
        }

        return self::rolReal();
    }

    public static function esAdministrador()
    {
        return self::rolActual() === 'ADMINISTRADOR';
    }

    public static function esDocente()
    {
        return self::rolActual() === 'DOCENTE';
    }

    public static function esEstudiante()
    {
        return self::rolActual() === 'ESTUDIANTE';
    }

    public static function idEstudianteContexto()
    {
        if (!self::vistaEstudianteActiva()) {
            return (int) ($_SESSION['usuario']['id'] ?? 0);
        }

        return max(0, (int) ($_SESSION['vista_estudiante_id'] ?? 0));
    }

    public static function puedeAccederRuta($ruta)
    {
        $ruta = trim((string) $ruta);

        if ($ruta === 'superadmin') {
            return class_exists('ControladorInstitucion', false)
                && ControladorInstitucion::esRutaGlobalSuperAdmin($ruta);
        }

        if ($ruta === '' || in_array($ruta, ['login', 'forgot', 'logout', 'actividad-publica'], true)) {
            return true;
        }

        if (!self::vistaEstudianteActiva() && self::tieneRol('ADMINISTRADOR')) {
            return true;
        }

        $permisos = [
            'DOCENTE' => [
                'perfil-usuario',
                'perfil-publico',
                'editar-perfil',
                'crear-curso',
                'editar-curso',
                'listado-cursos',
                'detalle-curso',
                'detalle-seccion',
                'listado-materias',
                'crear-materia',
                'editar-materia',
                'calificaciones-seccion',
                'calificaciones',
                'asistencias','asistencia-seccion','asistencia-curso',
                'bandeja-entrada',
                'nuevo-mensaje',
                'mensajes-enviados',
                'papelera',
                'detalle-mensaje',
                'listado-actividades',
                'banco-actividades',
                'crear-actividad',
                'editar-actividad',
                'ver-actividad',
                'resultados-actividad',
                'vista-estudiante',
            ],
            'ESTUDIANTE' => [
                'perfil-usuario',
                'perfil-publico',
                'editar-perfil',
                'listado-cursos',
                'detalle-curso',
                'detalle-seccion',
                'calificaciones-seccion',
                'calificaciones',
                'asistencias',
                'bandeja-entrada',
                'nuevo-mensaje',
                'mensajes-enviados',
                'papelera',
                'detalle-mensaje',
                'listado-actividades',
                'ver-actividad',
                'vista-estudiante',
            ],
        ];

        $roles = self::vistaEstudianteActiva() ? ['ESTUDIANTE'] : self::rolesReales();
        foreach ($roles as $rol) {
            if (in_array($ruta, $permisos[$rol] ?? [], true)) { return true; }
        }
        return false;
    }

    public static function puedeVerMenu($seccion)
    {
        $seccion = trim((string) $seccion);

        if (!self::vistaEstudianteActiva() && self::tieneRol('ADMINISTRADOR')) {
            return true;
        }

        $visibles = [
            'DOCENTE' => ['perfil', 'cursos', 'mensajes', 'actividades'],
            'ESTUDIANTE' => ['perfil', 'cursos', 'mensajes', 'actividades'],
        ];

        $roles = self::vistaEstudianteActiva() ? ['ESTUDIANTE'] : self::rolesReales();
        foreach ($roles as $rol) {
            if (in_array($seccion, $visibles[$rol] ?? [], true)) { return true; }
        }
        return false;
    }

    public static function etiquetaRol()
    {
        if (class_exists('ControladorInstitucion', false) && ControladorInstitucion::esRutaGlobalSuperAdmin()) {
            return 'SUPERADMIN';
        }
        $rol = self::rolActual();
        if (self::vistaEstudianteActiva()) {
            return 'ESTUDIANTE';
        }

        return $rol !== '' ? $rol : 'SIN ROL';
    }

    public static function puedeActivarVistaEstudiante()
    {
        return self::tieneAlgunoDeLosRoles(['ADMINISTRADOR', 'DOCENTE']);
    }

    public static function usuarioTieneRolEnInstitucion($idUsuario, array $roles, $idInstitucion = null)
    {
        $idUsuario = (int) $idUsuario;
        $roles = array_values(array_unique(array_map([self::class, 'normalizarRol'], $roles)));
        if ($idUsuario <= 0 || !$roles) { return false; }

        if (class_exists('ControladorInstitucion', false) && ControladorInstitucion::activo()) {
            $idInstitucion = $idInstitucion === null ? ControladorInstitucion::id() : (int) $idInstitucion;
            if ($idInstitucion <= 0) { return false; }
            $rolesUsuario = ModeloInstituciones::mdlRolesUsuarioInstitucion($idUsuario, $idInstitucion);
            return (bool) array_intersect($roles, $rolesUsuario);
        }

        $usuario = ModeloUsuarios::mdlObtenerUsuarioPorId($idUsuario);
        return $usuario && (int) ($usuario['activo'] ?? 0) === 1
            && in_array(self::normalizarRol($usuario['rol'] ?? ''), $roles, true);
    }

    public static function activarVistaEstudiante($activa, $idEstudiante = 0)
    {
        if (!self::puedeActivarVistaEstudiante()) {
            return false;
        }

        $_SESSION['vista_estudiante'] = (bool) $activa;
        $idEstudiante = max(0, (int) $idEstudiante);

        if (!$activa || $idEstudiante <= 0) {
            unset($_SESSION['vista_estudiante_id']);
        } else {
            $_SESSION['vista_estudiante_id'] = $idEstudiante;
        }

        return true;
    }
}
