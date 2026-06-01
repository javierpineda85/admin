<?php

class ControladorPermisos
{
    private static function normalizarRol($rol)
    {
        return strtoupper(trim((string) $rol));
    }

    public static function rolActual()
    {
        return self::normalizarRol($_SESSION['usuario']['rol'] ?? '');
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

        if ($ruta === '' || in_array($ruta, ['login', 'forgot', 'logout'], true)) {
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
                'bandeja-entrada',
                'nuevo-mensaje',
                'mensajes-enviados',
            ],
            'ESTUDIANTE' => [
                'perfil-usuario',
                'editar-perfil',
                'listado-cursos',
                'detalle-curso',
                'detalle-seccion',
                'bandeja-entrada',
                'nuevo-mensaje',
                'mensajes-enviados',
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
            'DOCENTE' => ['perfil', 'cursos', 'mensajes'],
            'ESTUDIANTE' => ['perfil', 'cursos', 'mensajes'],
        ];

        return in_array($seccion, $visibles[self::rolActual()] ?? [], true);
    }

    public static function etiquetaRol()
    {
        $rol = self::rolActual();
        return $rol !== '' ? $rol : 'SIN ROL';
    }
}
