<?php
$cabecera = ControladorPanel::crtIndicadoresCabecera();
$mensajesRecientes = $cabecera['mensajesRecientes'] ?? [];
$notificacionesRecientes = $cabecera['actividadReciente'] ?? [];
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
      <span class="nav-link text-muted badge badge-light border px-3 py-2" style="font-weight: 700;">
        <i class="fas fa-user-tag mr-1"></i>
        <?php echo htmlspecialchars(ControladorPermisos::etiquetaRol(), ENT_QUOTES, 'UTF-8'); ?>
      </span>
    </li>

    <li class="nav-item dropdown">
      <a class="nav-link" data-toggle="dropdown" href="#">
        <i class="far fa-comments"></i>
        <span class="badge badge-danger navbar-badge"><?php echo (int) ($cabecera['mensajes'] ?? 0); ?></span>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <span class="dropdown-item dropdown-header">
          <?php echo (int) ($cabecera['mensajes'] ?? 0); ?> mensajes sin leer
        </span>
        <div class="dropdown-divider"></div>
        <?php if (!empty($mensajesRecientes)): ?>
          <?php foreach ($mensajesRecientes as $mensaje): ?>
            <a href="index.php?r=bandeja-entrada&c=mensajes" class="dropdown-item">
              <div class="media">
                <img src="img/user1-128x128.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">
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
            <a href="#" class="dropdown-item">
              <i class="<?php echo htmlspecialchars((string) ($notificacion['icon'] ?? 'fas fa-bell'), ENT_QUOTES, 'UTF-8'); ?> mr-2"></i>
              <?php echo htmlspecialchars($resumirTexto($notificacion['titulo'] ?? 'Actividad reciente', 32), ENT_QUOTES, 'UTF-8'); ?>
              <span class="float-right text-muted text-sm">
                <?php echo htmlspecialchars($resumirTexto($notificacion['fecha'] ?? '', 16), ENT_QUOTES, 'UTF-8'); ?>
              </span>
            </a>
            <div class="dropdown-divider"></div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="dropdown-item text-muted">No hay notificaciones nuevas.</div>
          <div class="dropdown-divider"></div>
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