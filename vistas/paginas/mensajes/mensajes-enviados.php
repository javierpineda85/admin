<?php
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$accion = ControladorMensajes::crtProcesarAccion();
$mensajes = ControladorMensajes::crtMostrarMensajesEnviados('id_remitente', $idUsuarioActual);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion !== null) {
  echo "<script>setTimeout(function(){ window.location.href = 'index.php?r=mensajes-enviados&c=mensajes'; }, 5200);</script>";
}
?>

<section class="content page-fade">
  <div class="card glass-card">
    <div class="card-header bg-info">
      <h3 class="card-title">Mensajes</h3>
      <div class="card-tools">
        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
          <i class="fas fa-minus"></i>
        </button>
      </div>
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
                <li class="nav-item active">
                  <a href="index.php?r=mensajes-enviados&c=mensajes" class="nav-link">
                    <i class="far fa-envelope"></i> Enviados
                    <span class="badge bg-secondary float-right"><?php echo (int) $totalEnviados; ?></span>
                  </a>
                </li>
                <li class="nav-item">
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
              <h3 class="card-title mb-0">Mensajes enviados</h3>
              <div class="text-muted small"><?php echo (int) $totalEnviados; ?> enviados</div>
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
                        <td colspan="6" class="text-center text-muted py-5">Todavía no enviaste mensajes.</td>
                      </tr>
                    <?php endif; ?>

                    <?php foreach ($mensajes as $mensaje): ?>
                      <?php
                        $preview = $resumir($mensaje['contenidoMensaje'] ?? '', 100);
                        $destinatarios = trim((string) ($mensaje['destinatariosResumen'] ?? ''));
                      ?>
                      <tr>
                        <td style="width: 34px;">
                          <div class="icheck-primary">
                            <input type="checkbox" value="" id="check-out-<?php echo (int) $mensaje['idMensajeParticipante']; ?>">
                            <label for="check-out-<?php echo (int) $mensaje['idMensajeParticipante']; ?>"></label>
                          </div>
                        </td>
                        <td class="mailbox-name">
                          <a href="index.php?r=detalle-mensaje&idMensaje=<?php echo (int) $mensaje['id_mensaje']; ?>" class="text-dark">
                            Para: <?php echo htmlspecialchars($destinatarios !== '' ? $destinatarios : 'Destinatarios múltiples', ENT_QUOTES, 'UTF-8'); ?>
                          </a>
                        </td>
                        <td class="mailbox-subject">
                          <a href="index.php?r=detalle-mensaje&idMensaje=<?php echo (int) $mensaje['id_mensaje']; ?>" class="text-dark">
                            <?php echo htmlspecialchars($preview, ENT_QUOTES, 'UTF-8'); ?>
                          </a>
                          <?php if ((int) ($mensaje['totalAdjuntos'] ?? 0) > 0): ?>
                            <span class="badge badge-light border ml-2"><i class="fas fa-paperclip mr-1"></i><?php echo (int) $mensaje['totalAdjuntos']; ?></span>
                          <?php endif; ?>
                        </td>
                        <td class="mailbox-date"><?php echo htmlspecialchars((string) ($mensaje['fechaMensaje'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td style="width: 180px;">
                          <div class="btn-group btn-group-sm">
                            <a href="index.php?r=detalle-mensaje&idMensaje=<?php echo (int) $mensaje['id_mensaje']; ?>" class="btn btn-default" title="Abrir">
                              <i class="far fa-eye"></i>
                            </a>
                            <form method="post" class="d-inline" onsubmit="return confirm('Mover este mensaje a la papelera?');">
                              <input type="hidden" name="id_mensaje" value="<?php echo (int) $mensaje['id_mensaje']; ?>">
                              <button type="submit" name="accion" value="mover_papelera" class="btn btn-default" title="Papelera">
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
