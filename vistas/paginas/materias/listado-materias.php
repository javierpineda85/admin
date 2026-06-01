<?php

$db = new Conexion;
$sql = "SELECT idSeccion, tituloSeccion, contenidoSeccion, id_curso, docente, tutor, cursos.nombreCurso, usuarios.nombreUsuario, usuarios.apellidoUsuario FROM secciones 
JOIN cursos ON secciones.id_curso = cursos.idCurso 
JOIN usuarios ON secciones.docente = usuarios.idUsuario  
ORDER BY tituloSeccion ASC";
$materias = $db->consultas($sql);

?>
<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Listado de secciones</h3>
          </div>
          <!-- /.card-header -->
          <div class="card-body">
            <table id="example1" class="table table-bordered table-striped table-sm">
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
                    <td> <?php echo htmlspecialchars($valor['tituloSeccion'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td> <?php echo htmlspecialchars($valor['contenidoSeccion'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td> <?php echo htmlspecialchars($valor['nombreCurso'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td> <?php echo htmlspecialchars($valor['nombreUsuario']. " " . $valor['apellidoUsuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                      <div class="row d-flex justify-content-around">
                        <a href="index.php?r=detalle-seccion&idSeccion=<?php echo (int) $valor["idSeccion"]; ?>" class="btn btn-info btn-sm" title="Abrir aula"><i class="fas fa-eye"></i></a>
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
                  <th style="text-align: center;">Sección</th>
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
