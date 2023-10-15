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
    <form action="" method="POST">
      <div class="card card-info">
        <div class="card-header">
          <h3 class="card-title">Crear Usuario</h3>
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
                <input type="email" class="form-control" placeholder="Email" name="email">
              </div>
            </div>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label">Contraseña</label>
              <div class="col-sm-10">
                <input type="password" class="form-control" placeholder="Contraseña" name="pass">
              </div>

            </div>
            <div class="form-group row">
              <label class="col-sm-2 col-form-label">Rol de Usuario</label>
              <div class="col-sm-10">
                <select class="custom-select" name="rol">
                  <option value="" disabled selected> Rol de usuario</option>
                  <option value="ADMINISTRADOR">Administrador</option>
                  <option value="DOCENTE">Docente</option>
                  <option value="ESTUDIANTE">Estudiante</option>
                  <option value="GESTOR">Gestor</option>
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
            <?php

            $registro =  ControladorUsuarios::crtGuardarUsuario();
            ?>

            <input type="submit" class="btn btn-success" value="Registrar">

            <button type="reset" class="btn btn-default float-right">Borrar campos</button>
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