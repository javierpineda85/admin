<?php

$db= new Conexion;
$sql = "SELECT * FROM usuarios ORDER BY rol ";
$usuarios = $db->consultas($sql);

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
                  <th style="text-align: center;">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($usuarios as $campo => $valor) : ?>
                  <tr>
                    <td> <?php echo $valor['apellidoUsuario']; ?></td>
                    <td> <?php echo $valor['nombreUsuario']; ?></td>
                    <td> <?php echo $valor['email']; ?></td>
                    <td> <?php echo $valor['rol']; ?></td>
                    <td>
                      <div class="row d-flex justify-content-around">
                        <a href="index.php?r=editar-usuario&id=<?php echo $valor["idUsuario"]; ?>" class="btn btn-success btn-sm"><i class="fas fa-edit"></i></a>
                        <form method="post">
                          <input type="hidden" value="<?php echo $valor["idUsuario"]; ?>" name="idEliminar">
                          <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                          <?php

                          /* $eliminar = new ControladorFormularios();
                                            $eliminar->ctrEliminarVisita();*/

                          ?>

                        </form>
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


