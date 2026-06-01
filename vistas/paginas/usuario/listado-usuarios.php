<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idReactivar'])) {
  ControladorUsuarios::crtReactivarUsuario((int) $_POST['idReactivar']);
  echo "<script>setTimeout(function(){ window.location.href = 'index.php?r=listado-usuarios&c=usuario'; }, 650);</script>";
}

$usuarios = ControladorUsuarios::crtSeleccionarUsuario(null, null);

?>

<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Listado de usuarios</h3>
          </div>
          <!-- /.card-header -->
          <div class="card-body">
            <table id="example1" class="table table-bordered table-striped table-sm">
              <thead>
                <tr>
                  <th style="text-align: center;">Apellido</th>
                  <th style="text-align: center;">Nombre</th>
                  <th style="text-align: center;">Email</th>
                  <th style="text-align: center;">Rol</th>
                  <th style="text-align: center;">Estado</th>
                  <th style="text-align: center;">Alta</th>
                  <th style="text-align: center;">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($usuarios as $campo => $valor) : ?>
                  <tr>
                    <td> <?php echo htmlspecialchars($valor['apellidoUsuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td> <?php echo htmlspecialchars($valor['nombreUsuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td> <?php echo htmlspecialchars($valor['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td> <?php echo htmlspecialchars($valor['rol'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-center">
                      <?php if ((int) ($valor['activo'] ?? 0) === 1): ?>
                        <span class="badge badge-success">Activo</span>
                      <?php else: ?>
                        <span class="badge badge-secondary">Baja</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-center">
                      <?php echo htmlspecialchars((string) ($valor['fechaAltaFmt'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                    <td>
                      <div class="row d-flex justify-content-around">
                        <a href="index.php?r=editar-usuario&id=<?php echo $valor["idUsuario"]; ?>" class="btn btn-success btn-sm"><i class="fas fa-edit"></i></a>
                        <?php if ((int) ($valor['activo'] ?? 0) === 0): ?>
                          <form method="post">
                            <input type="hidden" value="<?php echo (int) $valor["idUsuario"]; ?>" name="idReactivar">
                            <button type="submit" class="btn btn-secondary btn-sm" title="Reactivar"><i class="fas fa-undo"></i></button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach ?>


              </tbody>
              <tfoot>
                <tr>
                  <th style="text-align: center;">Apellido</th>
                  <th style="text-align: center;">Nombre</th>
                  <th style="text-align: center;">Email</th>
                  <th style="text-align: center;">Rol</th>
                  <th style="text-align: center;">Estado</th>
                  <th style="text-align: center;">Alta</th>
                  <th style="text-align: center;">Acciones</th>
                </tr>
              </tfoot>
            </table>
          </div>
          <!-- /.card-body -->
        </div>
        <!-- /.card -->
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row -->
  </div>
  <!-- /.container-fluid -->
</section>
<!-- /.content -->


