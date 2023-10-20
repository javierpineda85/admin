<?php
$_SESSION['id_usuario'] = "5";
$mensajes = ControladorMensajes::crtMostrarMensajes('id_destinatario', $_SESSION['id_usuario']);

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
            <a href="index.php?r=nuevo-mensaje&c=mensajes&t=" class="btn btn-primary btn-block mb-3">Enviar nuevo mensaje</a>

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
          <div class="col-md-9">
            <div class="card card-primary card-outline">
              <div class="card-header">
                <h3 class="card-title">Bandeja de entrada</h3>

                <div class="card-tools">
                  <div class="input-group input-group-sm">
                    <input type="text" class="form-control" placeholder="Buscar correo">
                    <div class="input-group-append">
                      <div class="btn btn-primary">
                        <i class="fas fa-search"></i>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- /.card-tools -->
              </div>
              <!-- /.card-header -->
              <div class="card-body p-0">
                <div class="mailbox-controls">
                  <!-- /.btn-group -->
                  <button type="button" class="btn btn-default btn-sm" onclick="actualizar()">
                    <i class="fas fa-sync-alt"></i>
                  </button>
                  <!-- /.btn-group -->
                </div>
                <!-- /.float-right -->
              </div>

              <!-- TABLA DE MENSAJES -->
              <div class="table-responsive mailbox-messages">
                <table class="table table-hover table-striped">
                  <tbody>
                    <tr>
                      <?php foreach ($mensajes as $campo => $valor) : ?>
                        <td>
                          <div class="icheck-primary">
                            <input type="checkbox" value="" id="check1">
                            <label for="check1"></label>
                          </div>
                        </td>
                        <td class="mailbox-name"><a href="index.php?r=nuevo-mensaje&c=mensajes&idMsj=<?php echo $valor['idMensaje'] ?>&t=reply"><?php echo $valor['nombreUsuario'] . " " . $valor['apellidoUsuario'];  ?></a></td>
                        <td class="mailbox-subject"> <?php echo $valor['contenidoMensaje'] ?></td>
                        <td class="mailbox-date"><?php echo $valor['fMensaje'] ?></td>
                        <td class="mailbox-date"><?php echo $valor['horaMensaje'] ?></td>
                        <td>
                          <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm" data-toggle="tooltip" data-placement="top" title="Eliminar">
                            <a href="#=<?php echo $valor['idMensaje'] ?>" class="text-dark"><i class="far fa-trash-alt"></i></a>
                            </button>
                            <button type="button" class="btn btn-default btn-sm"  data-toggle="tooltip" data-placement="top" title="Responder">
                              <a href="index.php?r=nuevo-mensaje&c=mensajes&idMsj=<?php echo $valor['idMensaje'] ?>&t=reply" class="text-dark"><i class="fas fa-reply"></i></a>
                            </button>
                            <button type="button" class="btn btn-default btn-sm"  data-toggle="tooltip" data-placement="top" title="Reenviar">
                              <a href="index.php?r=nuevo-mensaje&c=mensajes&idMsj=<?php echo $valor['idMensaje'] ?>&t=share" class="text-dark"><i class="fas fa-share"></i></a>
                            </button>
                        </td>
                    </tr>
                  <?php endforeach ?>


                  </tbody>
                </table>
                <!-- /.table -->
              </div>
              <!-- /.mail-box-messages -->
            </div>

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

<script>
  function actualizar() {
    location.reload();

  }
</script>