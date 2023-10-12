<?php $titulo = "Crear Usuario"; ?>

        <!-- Default box -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Completa el formulario para dar de alta a un usuario</h3>

            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>

            </div>
          </div>
          <div class="card-body">
            <form action="">
              <div class="card card-info">
                <div class="card-header">
                  <h3 class="card-title">Editar Usuario</h3>
                </div>
                <!-- /.card-header -->
                <!-- form start -->
                <form class="form-horizontal">
                  <div class="card-body">
                    <div class="form-group row">
                      <label class="col-sm-2 col-form-label">Nombre</label>
                      <div class="col-sm-10">
                        <input type="text" class="form-control" placeholder="Juan Carlos" name="nombreUsuario">
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="col-sm-2 col-form-label">Apellido</label>
                      <div class="col-sm-10">
                        <input type="text" class="form-control" placeholder="Perez" name="apellidoUsuario">
                      </div>
                    </div>

                    <div class="form-group row">
                      <label class="col-sm-2 col-form-label">Email</label>
                      <div class="col-sm-10">
                        <input type="email" class="form-control" placeholder="Email" name="emailUsuario">
                      </div>
                    </div>
                    <div class="form-group row">
                      <label class="col-sm-2 col-form-label">Nueva contraseña</label>
                      <div class="col-sm-10">
                        <input type="password" class="form-control" placeholder="Contraseña" name="passUsuario">
                      </div>
                      
                    </div>
                    <div class="form-group row">
                      <label class="col-sm-2 col-form-label">Nivel de Usuario</label>
                      <div class="col-sm-10">
                        <select class="custom-select" name="nivelUsuario">
                          <option>option 1</option>
                          <option>option 2</option>
                          <option>option 3</option>
                          <option>option 4</option>
                          <option>option 5</option>
                        </select>
                     
                      </div>
                      
                    </div>
                    <div class="form-group row">
                      <div class="offset-sm-2 col-sm-10">
                        <div class="form-check">
                          <input type="checkbox" class="form-check-input" id="exampleCheck2" name="resetPass">
                          <label class="form-check-label" for="exampleCheck2">Crear una nueva contraseña al iniciar sesion la próxima vez</label>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- /.card-body -->
                  <div class="card-footer">
                    <button type="submit" class="btn btn-success">Registrar</button>
                    <button type="reset" class="btn btn-default float-right">Borrar campos</button>
                  </div>
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
   
