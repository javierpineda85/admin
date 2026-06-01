<?php
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$tipoMensaje = $_GET['t'] ?? '';
$idMsj = (int) ($_GET['idMsj'] ?? 0);

$mensaje = [];
$usuarios = ControladorMensajes::crtDestinatariosPermitidos();
$destinatarioFijo = null;

if ($tipoMensaje === 'reply' && $idMsj > 0) {
    $mensaje = ControladorMensajes::crtMostrarUnMensaje($idMsj);
    if (!empty($mensaje)) {
        $destinatarioFijo = ControladorUsuarios::crtSeleccionarUsuario('idUsuario', $mensaje[0]['id_remitente']);
    }
} elseif ($tipoMensaje === 'share' && $idMsj > 0) {
    $mensaje = ControladorMensajes::crtMostrarUnMensaje($idMsj);
}
?>

<section class="content">
    <div class="card">
        <div class="card-header bg-info">
            <h3 class="card-title">Mensajes</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <section class="content">
                <div class="row">
                    <div class="col-md-3">
                        <a href="index.php?r=bandeja-entrada&c=mensajes" class="btn btn-primary btn-block mb-3">Volver a bandeja de entrada</a>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Carpetas</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <ul class="nav nav-pills flex-column">
                                    <li class="nav-item">
                                        <a href="index.php?r=bandeja-entrada&c=mensajes" class="nav-link">
                                            <i class="fas fa-inbox"></i> Bandeja de entrada
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="index.php?r=mensajes-enviados&c=mensajes" class="nav-link">
                                            <i class="far fa-envelope"></i> Enviados
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <?php if ($tipoMensaje === 'reply'): ?>
                                    <h3 class="card-title">Responder mensaje</h3>
                                <?php elseif ($tipoMensaje === 'share'): ?>
                                    <h3 class="card-title">Reenviar mensaje</h3>
                                <?php else: ?>
                                    <h3 class="card-title">Redactar nuevo mensaje</h3>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if ($tipoMensaje === 'reply' && !empty($mensaje)): ?>
                                    <div class="alert alert-light border">
                                        <strong>Mensaje original:</strong><br>
                                        <?php echo htmlspecialchars($mensaje[0]['contenidoMensaje'], ENT_QUOTES, 'UTF-8'); ?><br>
                                        <small class="text-muted">
                                            Enviado el <?php echo htmlspecialchars($mensaje[0]['fMensaje'] . ' - ' . $mensaje[0]['horaMensaje'], ENT_QUOTES, 'UTF-8'); ?>
                                        </small>
                                    </div>
                                <?php elseif ($tipoMensaje === 'share' && !empty($mensaje)): ?>
                                    <div class="alert alert-light border">
                                        <strong>Contenido a reenviar:</strong><br>
                                        <?php echo htmlspecialchars($mensaje[0]['contenidoMensaje'], ENT_QUOTES, 'UTF-8'); ?><br>
                                        <small class="text-muted">
                                            Enviado el <?php echo htmlspecialchars($mensaje[0]['fMensaje'] . ' - ' . $mensaje[0]['horaMensaje'], ENT_QUOTES, 'UTF-8'); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <form action="" method="post">
                                    <input type="hidden" name="id_remitente" value="<?php echo $idUsuarioActual; ?>">

                                    <div class="form-group">
                                        <label>Para</label>
                                        <?php if ($tipoMensaje === 'reply' && !empty($destinatarioFijo)): ?>
                                            <input type="hidden" name="id_destinatario" value="<?php echo (int) $destinatarioFijo[0]['idUsuario']; ?>">
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($destinatarioFijo[0]['nombreUsuario'] . ' ' . $destinatarioFijo[0]['apellidoUsuario'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                        <?php else: ?>
                                            <select class="custom-select" name="id_destinatario" required>
                                                <option value="" selected disabled>Elegí un destinatario</option>
                                                <?php foreach ($usuarios as $valor): ?>
                                                    <option value="<?php echo (int) $valor['idUsuario']; ?>">
                                                        <?php echo htmlspecialchars($valor['nombreUsuario'] . ' ' . $valor['apellidoUsuario'] . ' (' . $valor['rol'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (empty($usuarios)): ?>
                                                <small class="text-muted">No hay destinatarios disponibles para tu rol.</small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-group">
                                        <label>Mensaje</label>
                                        <textarea class="form-control" style="height: 140px" name="contenidoMensaje" required><?php echo $tipoMensaje === 'share' && !empty($mensaje) ? htmlspecialchars($mensaje[0]['contenidoMensaje'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                                    </div>

                                    <div class="card-footer px-0">
                                        <div class="float-right">
                                            <?php $registro = ControladorMensajes::crtGuardarMensaje(); ?>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="far fa-envelope"></i> Enviar
                                            </button>
                                        </div>
                                        <button type="reset" class="btn btn-default"><i class="fas fa-times"></i> Descartar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
