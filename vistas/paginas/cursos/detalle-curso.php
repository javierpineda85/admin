<?php
$curso = ControladorCursos::crtSeleccionarCurso('idCurso', $_GET['idCurso']);
$materias = ControladorMaterias::crtBuscarMateriaXcurso('join-1-curso', $_GET['idCurso']);
//$url = "index.php?r=".$_GET['r']."&c=". $_GET['c']."&idCurso=".$_GET['idCurso'];
//buscar estudiantes  en asignacion curso

?>

<!-- Detalle box -->
<div class="card col-12">
    <!-- header box -->
    <div class="card-header">
        <h3 class="card-title">Detalle de curso: <b> <?php echo $curso['nombreCurso']; ?></b> </h3>

        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
            </button>

        </div>
    </div>
    <div class="row">
        <div class="card-body col-lg-6 col-md-12">
            <!-- Seccion general -->
            <section class="content">
                <div class="row">
                    <div class="col-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">General</h3>

                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="" method="post">
                                    <div class="row">
                                        <div class="form-group col-md-6 col-sm-12">
                                            <input type="text" name="idCurso" value="<?php echo $_GET['idCurso']; ?>" hidden>
                                            <label for="inputName">Nombre del Curso</label>
                                            <input type="text" class="form-control" name="nombreCurso" value="<?php echo $curso['nombreCurso']; ?>">
                                        </div>
                                        <div class="form-group col-md-6 col-sm-12">
                                            <label for="inputStatus">Estado</label>
                                            <select id="inputStatus" class="form-control custom-select" name="estado">
                                                <option value="<?php echo $curso['estado']; ?>" selected><?php echo $curso['estado']; ?></option>
                                                <option value="En Curso">En curso</option>
                                                <option value="Programado">Programado</option>
                                                <option value="Cancelado">Cancelado</option>
                                                <option value="Finalizado">Finalizado</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-12 ">
                                            <label for="inputDescription">Descripción</label>
                                            <textarea id="inputDescription" class="form-control" rows="4" name="contenidoCurso" placeholder="Descripción no ingresada"><?php echo $curso['contenidoCurso']; ?></textarea>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-4 col-sm-12">
                                            <label for="inputEstimatedBudget">Inicio:</label>
                                            <input type="date" id="inputEstimatedBudget" class="form-control" name="fechaInicioCurso" value="<?php echo $curso['fechaInicioCurso']; ?>">
                                        </div>
                                        <div class="form-group col-md-4 col-sm-12">
                                            <label for="inputSpentBudget">Finaliza:</label>
                                            <input type="date" id="inputSpentBudget" class="form-control" name="fechaFinCurso" value="<?php echo $curso['fechaFinCurso']; ?>">
                                        </div>
                                        <div class="form-group col-md-4 col-sm-12">
                                            <label for="inputEstimatedDuration">Horario cursado</label>
                                            <input type="time" id="inputEstimatedDuration" class="form-control" name="horarioCurso" value="<?php echo $curso['horarioCurso']; ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-3">
                                            <?php 
                                            $editar = ControladorCursos::crtModificarCurso();  
                                                           
                                            ?>
                                            <button class="btn btn-success" >Modificar</button>
                                        </div>
                                        <div class="col-9">
                                            
                                            <?php
                                            if (isset($_SESSION['success_message'])) {
                                                echo '<div class="alert alert-success alert-dismissible">
                                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                                    <h4 ><i class="icon fas fa-check"></i></h4>' . $_SESSION['success_message'] .
                                                    '</div>';
                                                // Elimina el mensaje después de mostrarlo
                                                unset($_SESSION['success_message']);
                                            };
                                            ?>
                                        </div>

                                    </div>
                                </form>

                            </div>
                            <!-- /.card-body -->


                        </div>
                        <!-- /.card -->
                    </div>
                </div>

            </section>
            <!-- /.content -->
        </div>

        <div class="card-body col-lg-6 col-md-12">
            <!-- Listado estudiantes -->
            <section class="content">
                <div class="row">
                    <div class="col-12">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Materias pertenecientes al curso</h3>

                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="example1" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="text-align: center;">Materia</th>
                                            <th style="text-align: center;">Descripcion</th>
                                            <th style="text-align: center;">Docente</th>
                                            <th style="text-align: center;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                           <?php foreach ($materias as $campo => $valor) : ?>
                                               <tr>
                                                   <td> <?php echo $valor['tituloSeccion']; ?></td>
                                                   <td> <?php echo $valor['contenidoSeccion']; ?></td>
                                                   <td> <?php echo $valor['nombreUsuario']. " " . $valor['apellidoUsuario']; ?> </td>

                                                   <td>
                                                       <div class="row d-flex justify-content-around">
                                                           <a href="#" class="btn btn-success btn-sm"><i class="fas fa-edit"></i></a>
                                                           <form method="post">
                                                               <input type="hidden" value="" name="idEliminar">
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
                </div>

            </section>
            <!-- /.content -->
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="staticBackdrop" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabel">Agregar estudiantes al curso</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="card card-default">

                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Multiple</label>
                                    <select class="duallistbox" multiple="multiple">
                                        <option selected>Alabama</option>
                                        <option>Alaska</option>
                                        <option>California</option>
                                        <option>Delaware</option>
                                        <option>Tennessee</option>
                                        <option>Texas</option>
                                        <option>Washington</option>
                                    </select>
                                </div>
                                <!-- /.form-group -->
                            </div>
                            <!-- /.col -->
                        </div>
                        <!-- /.row -->
                    </div>
                    <!-- /.card-body -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
function actualizar(){
   location.reload();
  
}
   
    $(function() {
        //Bootstrap Duallistbox
        $('.duallistbox').bootstrapDualListbox(2)
    });

</script>