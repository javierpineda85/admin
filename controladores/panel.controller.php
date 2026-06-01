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
}
