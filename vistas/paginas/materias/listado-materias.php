<?php
$materias = ControladorMaterias::crtSeleccionarMateria('join', null);

?>
<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Listado de cursos</h3>
          </div>
          <!-- /.card-header -->
          <div class="card-body">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th style="text-align: center;">Materia</th>
                  <th style="text-align: center;">Descripcion</th>
                  <th style="text-align: center;">Curso asignado</th>
                  <th style="text-align: center;">Docente</th>
                  <th style="text-align: center;">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($materias as $campo => $valor) : ?>
                  <tr>
                    <td> <?php echo $valor['tituloSeccion']; ?></td>
                    <td> <?php echo $valor['contenidoSeccion']; ?></td>
                    <td> <?php echo $valor['nombreCurso']; ?></td>
                    <td> <?php echo $valor['nombreUsuario']. " " . $valor['apellidoUsuario']; ?></td>
                    <td>
                      <div class="row d-flex justify-content-around">
                        <a href="index.php?ruta=editar-materia&idSeccion=<?php echo $valor["idSeccion"]; ?>" class="btn btn-success btn-sm"><i class="fas fa-edit"></i></a>
                        <form method="post">
                          <input type="hidden" value="<?php echo $valor["idSeccion"]; ?>" name="idEliminar">
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
                <th style="text-align: center;">Materia</th>
                  <th style="text-align: center;">Descripcion</th>
                  <th style="text-align: center;">Curso asignado</th>
                  <th style="text-align: center;">Docente</th>
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