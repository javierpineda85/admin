<?php
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$tipoMensaje = trim((string) ($_GET['t'] ?? ''));
$idMsj = (int) ($_GET['idMsj'] ?? 0);
$idDestinatarioPreseleccionado = (int) ($_GET['id_destinatario'] ?? 0);
$accion = ControladorMensajes::crtProcesarAccion();
$usuarios = ControladorMensajes::crtDestinatariosPermitidos();
$secciones = ControladorMensajes::crtSeccionesDisponibles();
$mensajeOriginal = null;
$destinatariosSeleccionados = [];
$textoInicial = '';

if ($tipoMensaje === 'reply' && $idMsj > 0) {
    $mensajeOriginal = ControladorMensajes::crtMostrarUnMensaje($idMsj);
    if (!empty($mensajeOriginal)) {
        $destinatariosSeleccionados[] = (int) ($mensajeOriginal['id_remitente'] ?? 0);
    }
} elseif ($tipoMensaje === 'share' && $idMsj > 0) {
    $mensajeOriginal = ControladorMensajes::crtMostrarUnMensaje($idMsj);
    if (!empty($mensajeOriginal)) {
        $textoInicial = (string) ($mensajeOriginal['contenidoMensaje'] ?? '');
    }
}

if ($idDestinatarioPreseleccionado > 0) {
    $destinatariosSeleccionados[] = $idDestinatarioPreseleccionado;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion !== null && isset($_SESSION['success_message'])) {
    $redirigir = 'index.php?r=bandeja-entrada&c=mensajes';
    echo "<script>setTimeout(function(){ window.location.href = " . json_encode($redirigir) . "; }, 5200);</script>";
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
                <div class="col-lg-3 mb-3">
                    <a href="index.php?r=bandeja-entrada&c=mensajes" class="btn btn-primary btn-block mb-3">
                        <i class="fas fa-arrow-left mr-1"></i>Volver a bandeja
                    </a>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Carpetas</h3>
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
                            <?php if (!empty($mensajeOriginal)): ?>
                                <div class="alert alert-light border">
                                    <strong>Mensaje original:</strong>
                                    <div class="mt-2 p-3 rounded bg-white border">
                                        <?php echo $mensajeOriginal['contenidoMensaje']; ?>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        Enviado el <?php echo htmlspecialchars((string) ($mensajeOriginal['fechaMensaje'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    </small>
                                </div>
                            <?php endif; ?>

                            <form action="" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="accion" value="enviar_mensaje">

                                <div class="row">
                                    <div class="form-group col-md-8">
                                        <label>Destinatarios</label>
                                        <select class="custom-select" name="id_destinatarios[]" multiple>
                                            <?php foreach ($usuarios as $valor): ?>
                                                <?php $seleccionado = in_array((int) $valor['idUsuario'], $destinatariosSeleccionados, true); ?>
                                                <option value="<?php echo (int) $valor['idUsuario']; ?>" <?php echo $seleccionado ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($valor['nombreUsuario'] . ' ' . $valor['apellidoUsuario'] . ' (' . $valor['rol'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted d-block mt-1">Usá Ctrl o Cmd para elegir más de un destinatario.</small>
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label>Materia / Sección</label>
                                        <select class="custom-select" name="id_seccion_destino">
                                            <option value="0">Sin envío masivo</option>
                                            <?php foreach ($secciones as $seccion): ?>
                                                <option value="<?php echo (int) $seccion['idSeccion']; ?>">
                                                    <?php echo htmlspecialchars($seccion['nombreCurso'] . ' - ' . $seccion['tituloSeccion'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted d-block mt-1">Solo admin y docentes pueden enviar a una sección completa.</small>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Mensaje</label>
                                    <textarea id="contenidoMensaje" class="form-control" name="contenidoMensaje" required><?php echo $textoInicial !== '' ? $textoInicial : ''; ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Adjuntos</label>
                                    <div class="classroom-file">
                                        <input type="file" class="classroom-file__input" id="adjuntosMensaje" name="adjuntos[]" multiple>
                                        <label class="classroom-file__button" for="adjuntosMensaje">
                                            <i class="fas fa-paperclip mr-2"></i>Seleccionar archivos
                                        </label>
                                        <span class="classroom-file__name">Ningún archivo seleccionado</span>
                                    </div>
                                    <small class="text-muted d-block mt-1">Podés adjuntar varios archivos.</small>
                                </div>

                                <div class="card-footer px-0 pb-0">
                                    <div class="float-right">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="far fa-envelope"></i> Enviar
                                        </button>
                                    </div>
                                    <button type="reset" class="btn btn-default">
                                        <i class="fas fa-times"></i> Descartar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
