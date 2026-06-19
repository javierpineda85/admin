<?php

class ControladorPermisos
{
    private static function normalizarRol($rol)
    {
        return strtoupper(trim((string) $rol));
    }

    public static function rolReal()
    {
        return self::normalizarRol($_SESSION['usuario']['rol'] ?? '');
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

    public static function puedeAccederRuta($ruta)
    {
        $ruta = trim((string) $ruta);

        if ($ruta === '' || in_array($ruta, ['login', 'forgot', 'logout', 'actividad-publica'], true)) {
            return true;
        }

        $rol = self::rolActual();

        if ($rol === 'ADMINISTRADOR') {
            return true;
        }

        $permisos = [
            'DOCENTE' => [
                'perfil-usuario',
                'editar-perfil',
                'listado-cursos',
                'detalle-curso',
                'detalle-seccion',
                'listado-materias',
                'editar-materia',
                'calificaciones-seccion',
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
                'editar-perfil',
                'listado-cursos',
                'detalle-curso',
                'detalle-seccion',
                'calificaciones-seccion',
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

        return in_array($ruta, $permisos[$rol] ?? [], true);
    }

    public static function puedeVerMenu($seccion)
    {
        $seccion = trim((string) $seccion);

        if (self::esAdministrador()) {
            return true;
        }

        $visibles = [
            'DOCENTE' => ['perfil', 'cursos', 'mensajes', 'actividades'],
            'ESTUDIANTE' => ['perfil', 'cursos', 'mensajes', 'actividades'],
        ];

        return in_array($seccion, $visibles[self::rolActual()] ?? [], true);
    }

    public static function etiquetaRol()
    {
        $rol = self::rolActual();
        if (self::vistaEstudianteActiva()) {
            return 'ESTUDIANTE';
        }

        return $rol !== '' ? $rol : 'SIN ROL';
    }

    public static function puedeActivarVistaEstudiante()
    {
        return in_array(self::rolReal(), ['ADMINISTRADOR', 'DOCENTE'], true);
    }

    public static function activarVistaEstudiante($activa)
    {
        if (!self::puedeActivarVistaEstudiante()) {
            return false;
        }

        $_SESSION['vista_estudiante'] = (bool) $activa;
        return true;
    }
}
