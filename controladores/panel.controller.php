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

        return ModeloPanel::mdlResumenDashboard(
            $idUsuarioResumen,
            ControladorPermisos::rolActual()
        );
    }

    public static function crtIndicadoresCabecera()
    {
        return ModeloPanel::mdlIndicadoresCabecera(
            self::idUsuarioActual(),
            ControladorPermisos::rolActual()
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
