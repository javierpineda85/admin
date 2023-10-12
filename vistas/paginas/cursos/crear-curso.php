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
               <form action="">

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
                                               <input type="text" id="inputName" class="form-control">
                                           </div>
                                           <div class="form-group">
                                               <label for="inputDescription">Descripción</label>
                                               <textarea id="inputDescription" class="form-control" rows="4"></textarea>
                                           </div>
                                           <div class="form-group">
                                               <label for="inputStatus">Estado</label>
                                               <select id="inputStatus" class="form-control custom-select">
                                                   <option selected disabled>Seleccionar una opción</option>
                                                   <option>Programado</option>
                                                   <option>Cancelado</option>
                                                   <option>Finalizado</option>
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
                                               <input type="date" id="inputEstimatedBudget" class="form-control">
                                           </div>
                                           <div class="form-group">
                                               <label for="inputSpentBudget">Fecha de finalización</label>
                                               <input type="date" id="inputSpentBudget" class="form-control">
                                           </div>
                                           <div class="form-group">
                                               <label for="inputEstimatedDuration">Horario cursado</label>
                                               <input type="time" id="inputEstimatedDuration" class="form-control">
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
                                   <input type="submit" value="Crear nuevo curso" class="btn btn-success float-right">
                               </div>
                           </div>
                       </section>
                       <!-- /.content -->
                   </div>
                   <!-- /.content-wrapper -->
                   <!-- /.card-footer -->
               </form>
           </div>
           <!-- /.card -->
           </form>
       </div>
       <!-- /.card-body -->

       </div>
       <!-- /.card -->

       </section>
       <!-- /.content -->