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
        return ModeloPanel::mdlResumenDashboard(
            self::idUsuarioActual(),
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
