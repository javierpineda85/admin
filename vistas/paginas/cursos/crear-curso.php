       <!-- Default box -->
       <div class="card">
           <div class="card-header">
               <h3 class="card-title">Crear nuevo curso</h3>

               <div class="card-tools">
                   <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                       <i class="fas fa-minus"></i>
                   </button>

               </div>
           </div>
           <div class="card-body">
               <form action="" method="POST">

                   <!-- form start -->
                   <!-- Main content -->
                   <section class="content">
                       <div class="row">
                           <div class="col-md-6">
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
                                       <div class="form-group">
                                           <label for="inputName">Nombre del Curso</label>
                                           <input type="text" id="inputName" class="form-control" name="nombreCurso">
                                       </div>
                                       <div class="form-group">
                                           <label for="inputDescription">Descripción</label>
                                           <textarea id="inputDescription" class="form-control" rows="4" name="contenidoCurso"></textarea>
                                       </div>
                                       <div class="form-group">
                                           <label for="inputStatus">Estado</label>
                                           <select id="inputStatus" class="form-control custom-select" name="estado">
                                               <option selected disabled>Seleccionar una opción</option>
                                               <option value="En Curso">En curso</option>
                                               <option value="Programado">Programado</option>
                                               <option value="Cancelado">Cancelado</option>
                                               <option value="Finalizado">Finalizado</option>
                                           </select>
                                       </div>

                                   </div>
                                   <!-- /.card-body -->
                               </div>
                               <!-- /.card -->
                           </div>
                           <div class="col-md-6">
                               <div class="card card-secondary">
                                   <div class="card-header">
                                       <h3 class="card-title">Tiempo estimado</h3>

                                       <div class="card-tools">
                                           <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                               <i class="fas fa-minus"></i>
                                           </button>
                                       </div>
                                   </div>
                                   <div class="card-body">
                                       <div class="form-group">
                                           <label for="inputEstimatedBudget">Fecha de inicio</label>
                                           <input type="date" id="inputEstimatedBudget" class="form-control" name="fechaInicioCurso">
                                       </div>
                                       <div class="form-group">
                                           <label for="inputSpentBudget">Fecha de finalización</label>
                                           <input type="date" id="inputSpentBudget" class="form-control" name="fechaFinCurso">
                                       </div>
                                       <div class="form-group">
                                           <label for="inputEstimatedDuration">Horario cursado</label>
                                           <input type="time" id="inputEstimatedDuration" class="form-control" name="horarioCurso">
                                       </div>
                                   </div>
                                   <!-- /.card-body -->
                               </div>
                               <!-- /.card -->
                           </div>
                       </div>
                       <div class="row">
                           <div class="col-12">
                               <a href="#" class="btn btn-secondary">Cancelar</a>

                               <?php $registro =  ControladorCursos::crtGuardarCurso(); ?>

                               <input type="submit" value="Crear nuevo curso" class="btn btn-success float-right">
                               <div>
                                   <?php
                                    if (isset($_SESSION['success_message'])) {
                                        echo '<div class="alert alert-success alert-dismissible">
                                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                                    <h5><i class="icon fas fa-check"></i></h5>' . $_SESSION['success_message'] .
                                            '</div>';
                                        // Elimina el mensaje después de mostrarlo
                                        unset($_SESSION['success_message']);
                                    };
                                    ?>
                               </div>

                           </div>
                       </div>
                   </section>
                   <!-- /.content -->

                   <!-- /.content-wrapper -->
                   <!-- /.card-footer -->
               </form>
           </div>

           <!-- /.card-body -->

       </div>
       <!-- /.card -->