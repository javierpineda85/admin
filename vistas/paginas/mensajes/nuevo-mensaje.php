<?php

if (isset($_GET['t'])) {
    if ($_GET['t'] == 'reply') {
        /*traer el msj mensaje completo e imprimir solo el contenido, fecha y hora del mensaje
    traer tb todos los usuarios
    */
        $mensaje = ControladorMensajes::crtMostrarUnMensaje($_GET['idMsj']);
        $usuarios = ControladorUsuarios::crtSeleccionarUsuario('idUsuario', $mensaje[0]['id_remitente']); /*Busca el remitente */
    } else if ($_GET['t'] == 'share') {
        /*Cargar solo el id del destinatario
            y despues todos los usuarios
        */
        $mensaje = ControladorMensajes::crtMostrarUnMensaje($_GET['idMsj']);
        $usuarios = ControladorUsuarios::crtSeleccionarUsuario(null, null);
    } else {
        $usuarios = ControladorUsuarios::crtSeleccionarUsuario(null, null);
    }
}

?>

<!-- Main content -->
<section class="content">

    <!-- Default box -->
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
                                    <li class="nav-item active">
                                        <a href="#" class="nav-link">
                                            <i class="fas fa-inbox"></i> Bandeja de entrada
                                            <span class="badge bg-primary float-right">12</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            <i class="far fa-envelope"></i> Enviados
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            <i class="far fa-trash-alt"></i> Papelera
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <!-- /.card-body -->
                        </div>

                    </div>
                    <!-- /.col -->
                    <div class="col-md-7">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <?php if ($_GET['t'] == 'reply') : ?>
                                    <h3 class="card-title">Responder mensaje</h3>
                                <?php elseif ($_GET['t'] == 'share') : ?>
                                    <h3 class="card-title">Compartir mensaje</h3>
                                <?php else : ?>
                                    <h3 class="card-title">Redactar nuevo mensaje</h3>
                                <?php endif ?>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <form action="" method="post">
                                    <div class="form-group">
                                        <input type="text" name="id_remitente" value="<?php echo $_SESSION['id_usuario']; ?>" hidden>
                                        <select class="custom-select" name="id_destinatario">
                                            <option value="NULL" disabel selected>Para: </option>
                                            <?php if ($_GET['t'] == 'reply') : ?>
                                                <option value="<?php echo $usuarios[0]["idUsuario"]; ?>" disabled selected> Para: <?php echo $usuarios[0]['nombreUsuario'] . " " . $usuarios[0]['apellidoUsuario']; ?></option>
                                                <?php else : foreach ($usuarios as $campo => $valor) : ?>
                                                    <option value="<?php echo $valor["idUsuario"]; ?>"><?php echo $valor['nombreUsuario'] . " " . $valor['apellidoUsuario']; ?></option>
                                            <?php endforeach;
                                            endif ?>
                                        </select>

                                    </div>
                                    <div class="form-group">
                                        <?php if ($_GET['t'] == 'reply') : ?>
                                            <textarea id="compose-textarea" class="form-control" style="height: 100px" name="contenidoMensaje" disabled> Mensaje anterior: <?php echo $mensaje[0]['contenidoMensaje'] ?>. - Enviado el: <?php echo $mensaje[0]['fMensaje'] . " - " . $mensaje[0]['horaMensaje']  ?> </textarea>
                                            <textarea id="compose-textarea" class="form-control" style="height: 100px" name="contenidoMensaje"> <?php ?> </textarea>
                                        <?php elseif ($_GET['t'] == 'share') : ?>
                                            <textarea id="compose-textarea" class="form-control" style="height: 100px" name="contenidoMensaje"> <?php echo $mensaje[0]['contenidoMensaje'] ?>. - Enviado el: <?php echo $mensaje[0]['fMensaje'] . " - " . $mensaje[0]['horaMensaje']  ?> </textarea>
                                        <?php else : ?>
                                            <textarea id="compose-textarea" class="form-control" style="height: 100px" name="contenidoMensaje"> </textarea>
                                        <?php endif ?>
                                    </div>

                                    <!-- /.card-body -->
                                    <div class="card-footer">
                                        <div class="float-right">
                                            <?php $registro = ControladorMensajes::crtGuardarMensaje(); ?>
                                            <button type="submit" class="btn btn-primary"><i class="far fa-envelope"></i> Enviar</button>
                                        </div>
                                        <button type="reset" class="btn btn-default"><i class="fas fa-times"></i> Descartar</button>
                                    </div>
                                    <!-- /.card-footer -->
                                </form>
                            </div>
                            <!-- /.card -->
                        </div>
                        <!-- /.col -->
                    </div>
                    <!-- /.row -->
            </section>
        </div>
        <!-- /.card-body -->

    </div>
    <!-- /.card -->

</section>
<!-- /.content -->