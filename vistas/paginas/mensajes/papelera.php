<?php
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$accion = ControladorMensajes::crtProcesarAccion();
$mensajes = ControladorMensajes::crtMostrarPapelera();
$totalRecibidos = ControladorMensajes::crtContarMensajesRecibidos($idUsuarioActual);
$totalEnviados = ControladorMensajes::crtContarMensajesEnviados($idUsuarioActual);
$totalPapelera = ControladorMensajes::crtContarMensajesPapelera($idUsuarioActual);

$resumir = static function ($texto, $longitud = 90) {
  $texto = trim(strip_tags((string) $texto));
  if ($texto === '') {
    return '';
  }

  if (function_exists('mb_strimwidth')) {
    return mb_strimwidth($texto, 0, $longitud, '...');
  }

  return strlen($texto) > $longitud ? substr($texto, 0, $longitud - 3) . '...' : $texto;
};

?>

<section class="content page-fade">
  <div class="card glass-card">
    <div class="card-header bg-info">
      <h3 class="card-title">Mensajes</h3>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3">
          <a href="index.php?r=nuevo-mensaje&c=mensajes&t=" class="btn btn-primary btn-block mb-3">
            <i class="fas fa-pen mr-1"></i>Redactar
          </a>
          <div class="card">
            <div class="card-body p-0">
              <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                  <a href="index.php?r=bandeja-entrada&c=mensajes" class="nav-link">
                    <i class="fas fa-inbox"></i> Bandeja de entrada
                    <span class="badge bg-primary float-right"><?php echo (int) $totalRecibidos; ?></span>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="index.php?r=mensajes-enviados&c=mensajes" class="nav-link">
                    <i class="far fa-envelope"></i> Enviados
                    <span class="badge bg-secondary float-right"><?php echo (int) $totalEnviados; ?></span>
                  </a>
                </li>
                <li class="nav-item active">
                  <a href="index.php?r=papelera&c=mensajes" class="nav-link">
                    <i class="far fa-trash-alt"></i> Papelera
                    <span class="badge bg-danger float-right"><?php echo (int) $totalPapelera; ?></span>
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="col-md-9">
          <div class="card card-primary card-outline">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title mb-0">Papelera</h3>
              <div class="text-muted small"><?php echo (int) $totalPapelera; ?> mensajes</div>
            </div>
            <div class="card-body p-0">
              <div class="mailbox-controls p-3 border-bottom">
                <button type="button" class="btn btn-default btn-sm" onclick="location.reload();">
                  <i class="fas fa-sync-alt"></i>
                </button>
              </div>

              <div class="table-responsive mailbox-messages">
                <table class="table table-hover table-striped mb-0">
                  <tbody>
                    <?php if (empty($mensajes)): ?>
                      <tr>
                        <td colspan="6" class="text-center text-muted py-5">La papelera está vacía.</td>
                      </tr>
                    <?php endif; ?>

                    <?php foreach ($mensajes as $mensaje): ?>
                      <?php
                        $preview = $resumir($mensaje['contenidoMensaje'] ?? '', 100);
                        $origen = ((string) ($mensaje['rolParticipante'] ?? '') === 'REMITENTE') ? 'Enviado' : 'Recibido';
                      ?>
                      <tr>
                        <td class="mailbox-name">
                          <span class="badge badge-light border mr-2"><?php echo htmlspecialchars($origen, ENT_QUOTES, 'UTF-8'); ?></span>
                          <a href="index.php?r=detalle-mensaje&idMensaje=<?php echo (int) $mensaje['id_mensaje']; ?>" class="text-dark">
                            <?php echo htmlspecialchars(trim(($mensaje['nombreUsuario'] ?? '') . ' ' . ($mensaje['apellidoUsuario'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                          </a>
                        </td>
                        <td class="mailbox-subject">
                          <a href="index.php?r=detalle-mensaje&idMensaje=<?php echo (int) $mensaje['id_mensaje']; ?>" class="text-dark">
                            <?php echo htmlspecialchars($preview, ENT_QUOTES, 'UTF-8'); ?>
                          </a>
                        </td>
                        <td class="mailbox-date"><?php echo htmlspecialchars((string) ($mensaje['fechaPapelera'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td style="width: 180px;">
                          <div class="btn-group btn-group-sm">
                            <form method="post" class="d-inline">
                              <input type="hidden" name="id_mensaje" value="<?php echo (int) $mensaje['id_mensaje']; ?>">
                              <button type="submit" name="accion" value="restaurar_mensaje" class="btn btn-default" title="Restaurar">
                                <i class="fas fa-undo"></i>
                              </button>
                            </form>
                            <form method="post" class="d-inline" onsubmit="return confirm('Eliminar este mensaje de forma permanente?');">
                              <input type="hidden" name="id_mensaje" value="<?php echo (int) $mensaje['id_mensaje']; ?>">
                              <button type="submit" name="accion" value="eliminar_permanente" class="btn btn-danger" title="Eliminar permanentemente">
                                <i class="far fa-trash-alt"></i>
                              </button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
