<?php

$db= new Conexion;
$sql = "SELECT *,DATE_FORMAT(fechaInicioCurso, '%d/%m/%Y') AS fInicio, DATE_FORMAT(fechaFinCurso, '%d/%m/%Y') AS fFin FROM cursos ORDER BY nombreCurso ASC ";
$cursos = $db->consultas($sql);

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
                  <th style="text-align: center;">Curso</th>
                  <th style="text-align: center;">Estado</th>
                  <th style="text-align: center;">Fecha Inicio</th>
                  <th style="text-align: center;">Horario</th>
                  <th style="text-align: center;">Fecha Finalización</th>
                  <th style="text-align: center;">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($cursos as $campo => $valor) : ?>
                  <tr>
                    <td> <?php echo $valor['nombreCurso']; ?></td>
                    <td> <?php echo $valor['estado']; ?></td>
                    <td> <?php echo $valor['fInicio']; ?></td>
                    <td> <?php echo $valor['horarioCurso']; ?></td>
                    <td> <?php echo $valor['fFin']; ?></td>
                    <td>
                      <div class="row d-flex justify-content-around">
                      <a href="index.php?r=detalle-curso&c=cursos&idCurso=<?php echo $valor["idCurso"]; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                        
                        <form method="post">
                          <input type="hidden" value="<?php echo $valor["idCurso"]; ?>" name="idEliminar">
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
                  <th style="text-align: center;">Curso</th>
                  <th style="text-align: center;">Estado</th>
                  <th style="text-align: center;">Fecha Inicio</th>
                  <th style="text-align: center;">Horario</th>
                  <th style="text-align: center;">Fecha Finalización</th>
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