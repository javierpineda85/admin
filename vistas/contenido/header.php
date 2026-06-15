<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_notificacion'])) {
  $accionNotificacion = trim((string) $_POST['accion_notificacion']);

  if ($accionNotificacion === 'marcar_notificacion_leida') {
    ControladorPanel::crtMarcarNotificacionLeida($_POST['clave_notificacion'] ?? '');
  } elseif ($accionNotificacion === 'marcar_notificaciones_leidas') {
    ControladorPanel::crtMarcarNotificacionesLeidas($_POST['claves_notificaciones'] ?? []);
  }
}

$cabecera = ControladorPanel::crtIndicadoresCabecera();
$mensajesRecientes = $cabecera['mensajesRecientes'] ?? [];
$notificacionesRecientes = $cabecera['actividadReciente'] ?? [];
$puedeCambiarVista = ControladorPermisos::puedeActivarVistaEstudiante();
$vistaEstudianteActiva = ControladorPermisos::vistaEstudianteActiva();
$urlVistaEstudiante = 'index.php?r=vista-estudiante&estado=' . ($vistaEstudianteActiva ? '0' : '1') . '&redir=' . urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
$resumirTexto = static function ($texto, $longitud) {
  $texto = trim(strip_tags((string) $texto));
  if (function_exists('mb_strimwidth')) {
    return mb_strimwidth($texto, 0, $longitud, '...');
  }

  return strlen($texto) > $longitud ? substr($texto, 0, $longitud - 3) . '...' : $texto;
};
?>

<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
    </li>
    <li class="nav-item d-none d-sm-inline-block">
      <a href="index.php" class="nav-link">Inicio</a>
    </li>
    <li class="nav-item d-none d-sm-inline-block">
      <a href="https://mentemotion.com" class="nav-link">Sitio Web</a>
    </li>

  </ul>

  <ul class="navbar-nav ml-auto">
    <li class="nav-item d-none d-sm-inline-block">
      <?php if ($puedeCambiarVista): ?>
        <a
          class="nav-link text-muted badge <?php echo $vistaEstudianteActiva ? 'badge-warning border border-warning' : 'badge-light border'; ?> px-3 py-2 classroom-role-switch"
          href="<?php echo htmlspecialchars($urlVistaEstudiante, ENT_QUOTES, 'UTF-8'); ?>"
          title="<?php echo $vistaEstudianteActiva ? 'Volver a mi rol real' : 'Cambiar a vista estudiante'; ?>"
        >
          <i class="fas <?php echo $vistaEstudianteActiva ? 'fa-user-graduate' : 'fa-user-tag'; ?> mr-1"></i>
          <?php echo htmlspecialchars(ControladorPermisos::etiquetaRol(), ENT_QUOTES, 'UTF-8'); ?>
        </a>
      <?php else: ?>
        <span class="nav-link text-muted badge badge-light border px-3 py-2">
          <i class="fas fa-user mr-1"></i>
          <?php echo htmlspecialchars(ControladorPermisos::etiquetaRol(), ENT_QUOTES, 'UTF-8'); ?>
        </span>
      <?php endif; ?>
    </li>

    <li class="nav-item dropdown">
      <a class="nav-link" data-toggle="dropdown" href="#">
        <i class="far fa-comments"></i>
        <span class="badge badge-danger navbar-badge"><?php echo (int) ($cabecera['mensajes'] ?? 0); ?></span>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right dropdown-notifications-menu">
        <span class="dropdown-item dropdown-header">
          <?php echo (int) ($cabecera['mensajes'] ?? 0); ?> mensajes sin leer
        </span>
        <div class="dropdown-divider"></div>
        <?php if (!empty($mensajesRecientes)): ?>
          <?php foreach ($mensajesRecientes as $mensaje): ?>
            <?php $avatarRemitente = ControladorUsuarios::rutaImagenUsuario($mensaje['imgUsuario'] ?? '', 'user1-128x128.jpg'); ?>
            <a href="index.php?r=bandeja-entrada&c=mensajes" class="dropdown-item">
              <div class="media">
                <img src="img/<?php echo htmlspecialchars($avatarRemitente, ENT_QUOTES, 'UTF-8'); ?>" alt="User Avatar" class="img-size-50 mr-3 img-circle">
                <div class="media-body">
                  <h3 class="dropdown-item-title">
                    <?php echo htmlspecialchars(trim(($mensaje['nombreUsuario'] ?? '') . ' ' . ($mensaje['apellidoUsuario'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                    <span class="float-right text-sm text-danger"><i class="fas fa-envelope"></i></span>
                  </h3>
                  <p class="text-sm"><?php echo htmlspecialchars($resumirTexto($mensaje['contenidoMensaje'] ?? '', 48), ENT_QUOTES, 'UTF-8'); ?></p>
                  <p class="text-sm text-muted">
                    <i class="far fa-clock mr-1"></i>
                    <?php echo htmlspecialchars((string) ($mensaje['fechaMensaje'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                  </p>
                </div>
              </div>
            </a>
            <div class="dropdown-divider"></div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="dropdown-item text-muted">No hay mensajes sin leer.</div>
          <div class="dropdown-divider"></div>
        <?php endif; ?>
        <a href="index.php?r=bandeja-entrada&c=mensajes" class="dropdown-item dropdown-footer">Ver todos los mensajes</a>
      </div>
    </li>

    <li class="nav-item dropdown">
      <a class="nav-link" data-toggle="dropdown" href="#">
        <i class="far fa-bell"></i>
        <span class="badge badge-warning navbar-badge"><?php echo (int) ($cabecera['notificaciones'] ?? 0); ?></span>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <span class="dropdown-item dropdown-header">
          <?php echo (int) ($cabecera['notificaciones'] ?? 0); ?> notificaciones
        </span>
        <div class="dropdown-divider"></div>
        <?php if (!empty($notificacionesRecientes)): ?>
          <?php foreach ($notificacionesRecientes as $notificacion): ?>
            <?php $notificacionLeida = !empty($notificacion['leida']); ?>
            <div class="dropdown-item <?php echo $notificacionLeida ? 'text-muted' : ''; ?>">
              <div class="d-flex align-items-start">
                <i class="<?php echo htmlspecialchars((string) ($notificacion['icon'] ?? 'fas fa-bell'), ENT_QUOTES, 'UTF-8'); ?> mr-2 mt-1"></i>
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between">
                    <strong class="text-sm"><?php echo htmlspecialchars($resumirTexto($notificacion['titulo'] ?? 'Actividad reciente', 36), ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span class="text-muted text-sm"><?php echo htmlspecialchars($resumirTexto($notificacion['fecha'] ?? '', 16), ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                  <div class="text-sm"><?php echo htmlspecialchars($resumirTexto($notificacion['detalle'] ?? '', 58), ENT_QUOTES, 'UTF-8'); ?></div>
                  <?php if (!$notificacionLeida): ?>
                    <form method="post" class="mt-1">
                      <input type="hidden" name="accion_notificacion" value="marcar_notificacion_leida">
                      <input type="hidden" name="clave_notificacion" value="<?php echo htmlspecialchars((string) ($notificacion['clave'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                      <button type="submit" class="btn btn-link btn-xs p-0">Marcar como leida</button>
                    </form>
                  <?php else: ?>
                    <span class="badge badge-light border mt-1">Leida</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div class="dropdown-divider"></div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="dropdown-item text-muted">No hay notificaciones nuevas.</div>
          <div class="dropdown-divider"></div>
        <?php endif; ?>
        <?php if (!empty($notificacionesRecientes)): ?>
          <form method="post" class="dropdown-item dropdown-footer mb-0">
            <input type="hidden" name="accion_notificacion" value="marcar_notificaciones_leidas">
            <?php foreach ($notificacionesRecientes as $notificacion): ?>
              <input type="hidden" name="claves_notificaciones[]" value="<?php echo htmlspecialchars((string) ($notificacion['clave'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <?php endforeach; ?>
            <button type="submit" class="btn btn-link btn-sm p-0">Marcar todas como leidas</button>
          </form>
        <?php endif; ?>
        <a href="index.php" class="dropdown-item dropdown-footer">Ir al panel</a>
      </div>
    </li>

    <li class="nav-item">
      <a class="nav-link" data-widget="fullscreen" href="#" role="button">
        <i class="fas fa-expand-arrows-alt"></i>
      </a>
    </li>
    <li class="nav-item d-none d-sm-inline-block">
      <a href="index.php?r=logout" class="nav-link text-danger">
        <i class="nav-icon fas fa-sign-out-alt"></i>

      </a>
    </li>
  </ul>
</nav>
