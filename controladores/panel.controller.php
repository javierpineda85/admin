<?php
require_once('modelos/panel.modelo.php');

class ControladorPanel
{
    private static function idUsuarioActual()
    {
        return (int) ($_SESSION['usuario']['id'] ?? 0);
    }

    public static function crtResumenDashboard()
    {
        $idUsuarioResumen = ControladorPermisos::esEstudiante()
            ? ControladorPermisos::idEstudianteContexto()
            : self::idUsuarioActual();

        $rol = ControladorPermisos::rolActual();
        if ($rol === 'COORDINADOR') { $rol = 'ADMINISTRADOR'; }
        return ModeloPanel::mdlResumenDashboard(
            $idUsuarioResumen,
            $rol
        );
    }

    public static function crtIndicadoresCabecera()
    {
        $rol = ControladorPermisos::rolActual();
        if ($rol === 'COORDINADOR') { $rol = 'ADMINISTRADOR'; }
        return ModeloPanel::mdlIndicadoresCabecera(
            self::idUsuarioActual(),
            $rol
        );
    }

    public static function crtMarcarNotificacionLeida($clave)
    {
        return ModeloPanel::mdlMarcarNotificacionLeida(self::idUsuarioActual(), $clave);
    }

    public static function crtMarcarNotificacionesLeidas(array $claves)
    {
        return ModeloPanel::mdlMarcarNotificacionesLeidas(self::idUsuarioActual(), $claves);
    }
}
