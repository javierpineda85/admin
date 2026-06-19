<?php
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$idMensaje = (int) ($_GET['idMensaje'] ?? 0);
$accion = ControladorMensajes::crtProcesarAccion();
$mensaje = ControladorMensajes::crtVerMensaje($idMensaje);

if (empty($mensaje)) {
  $mensaje = [
    'contenidoMensaje' => '',
    'nombreUsuario' => 'Mensaje no encontrado',
    'apellidoUsuario' => '',
    'fechaMensaje' => '',
    'rolParticipante' => '',
    'leido' => 0,
    'enPapelera' => 0,
    'adjuntos' => [],
    'destinatarios' => [],
  ];
}

$esRemitente = (string) ($mensaje['rolParticipante'] ?? '') === 'REMITENTE';
$esDestinatario = (string) ($mensaje['rolParticipante'] ?? '') === 'DESTINATARIO';
$enPapelera = (int) ($mensaje['enPapelera'] ?? 0) === 1;
?>

<section class="content page-fade">
  <div class="card glass-card">
    <div class="card-header bg-info">
      <h3 class="card-title">Detalle del mensaje</h3>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-lg-3 mb-3">
          <a href="index.php?r=bandeja-entrada&c=mensajes" class="btn btn-primary btn-block mb-3">
            <i class="fas fa-arrow-left mr-1"></i>Volver
          </a>
          <div class="card">
            <div class="card-body p-0">
              <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                  <a href="index.php?r=bandeja-entrada&c=mensajes" class="nav-link">
                    <i class="fas fa-inbox"></i> Bandeja
                  </a>
                </li>
                <li class="nav-item">
                  <a href="index.php?r=mensajes-enviados&c=mensajes" class="nav-link">
                    <i class="far fa-envelope"></i> Enviados
                  </a>
                </li>
                <li class="nav-item">
                  <a href="index.php?r=papelera&c=mensajes" class="nav-link">
                    <i class="far fa-trash-alt"></i> Papelera
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="col-lg-9">
          <div class="card card-primary card-outline">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div>
                <h3 class="card-title mb-1">
                  <?php echo htmlspecialchars(trim(($mensaje['nombreUsuario'] ?? '') . ' ' . ($mensaje['apellidoUsuario'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                </h3>
                <small class="text-muted">
                  <?php echo htmlspecialchars((string) ($mensaje['fechaMensaje'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                </small>
              </div>
              <div class="btn-group btn-group-sm">
                <?php if (!$enPapelera): ?>
                  <?php if ($esDestinatario): ?>
                    <a href="index.php?r=nuevo-mensaje&t=reply&idMsj=<?php echo (int) $idMensaje; ?>" class="btn btn-primary" title="Responder">
                      <i class="fas fa-reply mr-1"></i>Responder
                    </a>
                  <?php endif; ?>
                  <?php if ($esDestinatario): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="id_mensaje" value="<?php echo (int) $idMensaje; ?>">
                      <button type="submit" name="accion" value="<?php echo (int) ($mensaje['leido'] ?? 0) === 1 ? 'marcar_no_leido' : 'marcar_leido'; ?>" class="btn btn-default">
                        <i class="fas fa-<?php echo (int) ($mensaje['leido'] ?? 0) === 1 ? 'envelope-open' : 'envelope'; ?>"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="id_mensaje" value="<?php echo (int) $idMensaje; ?>">
                    <button type="submit" name="accion" value="mover_papelera" class="btn btn-default">
                      <i class="far fa-trash-alt"></i>
                    </button>
                  </form>
                <?php else: ?>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="id_mensaje" value="<?php echo (int) $idMensaje; ?>">
                    <button type="submit" name="accion" value="restaurar_mensaje" class="btn btn-default">
                      <i class="fas fa-undo"></i>
                    </button>
                  </form>
                  <form method="post" class="d-inline" onsubmit="return confirm('Eliminar definitivamente este mensaje?');">
                    <input type="hidden" name="id_mensaje" value="<?php echo (int) $idMensaje; ?>">
                    <button type="submit" name="accion" value="eliminar_permanente" class="btn btn-danger">
                      <i class="far fa-trash-alt"></i>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>

            <div class="card-body">
              <div class="mb-3">
                <span class="badge badge-light border mr-2"><?php echo $esRemitente ? 'Enviado' : 'Recibido'; ?></span>
                <?php if ($esDestinatario): ?>
                  <span class="badge badge-<?php echo (int) ($mensaje['leido'] ?? 0) === 1 ? 'success' : 'warning'; ?>">
                    <?php echo (int) ($mensaje['leido'] ?? 0) === 1 ? 'Leído' : 'No leído'; ?>
                  </span>
                <?php endif; ?>
                <?php if ($enPapelera): ?>
                  <span class="badge badge-danger">En papelera</span>
                <?php endif; ?>
              </div>

              <div class="p-4 rounded border bg-white mb-4" style="min-height: 160px;">
                <?php echo $mensaje['contenidoMensaje']; ?>
              </div>

              <div class="mb-4">
                <h5 class="mb-3">Destinatarios</h5>
                <?php if (!empty($mensaje['destinatarios'])): ?>
                  <div class="d-flex flex-wrap" style="gap: .5rem;">
                    <?php foreach ($mensaje['destinatarios'] as $destinatario): ?>
                      <span class="badge badge-light border px-3 py-2">
                        <?php echo htmlspecialchars(trim(($destinatario['nombreUsuario'] ?? '') . ' ' . ($destinatario['apellidoUsuario'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                        <?php echo htmlspecialchars('(' . ($destinatario['rol'] ?? '') . ')', ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="text-muted">Sin destinatarios asociados.</div>
                <?php endif; ?>
              </div>

              <div class="mb-0">
                <h5 class="mb-3">Adjuntos</h5>
                <?php if (!empty($mensaje['adjuntos'])): ?>
                  <div class="list-group">
                    <?php foreach ($mensaje['adjuntos'] as $adjunto): ?>
                      <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?php echo htmlspecialchars((string) $adjunto['rutaArchivo'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                        <span>
                          <i class="fas fa-paperclip mr-2"></i>
                          <?php echo htmlspecialchars((string) $adjunto['nombreOriginal'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <small class="text-muted"><?php echo htmlspecialchars((string) ($adjunto['mimeType'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                      </a>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="text-muted">No hay archivos adjuntos.</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
