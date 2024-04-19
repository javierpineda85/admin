<?php

$db = new Conexion;
$sql = "SELECT * FROM usuarios WHERE idUsuario = " . $_GET['id'];
$usuario = $db->consultas($sql);

?>

<!-- Default box -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Completa el formulario para modificar al usuario </h3>

    <div class="card-tools">
      <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
        <i class="fas fa-minus"></i>
      </button>

    </div>
  </div>
  <div class="card-body">

    <div class="card card-info">
      <div class="card-header">
        <h3 class="card-title">Editar Usuario</h3>
      </div>
      <!-- /.card-header -->
      <!-- form start -->
      <form action="" method="post">
        <div class="card-body">
          <div class="form-group row">
            <input type="text" name="idUsuario" value="<?php echo $usuario[0]['idUsuario'] ?>" hidden>
            <label class="col-sm-2 col-form-label">Nombre</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" placeholder="Juan Carlos" name="nombreUsuario" value="<?php echo $usuario[0]['nombreUsuario']; ?>">
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label">Apellido</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" placeholder="Perez" name="apellidoUsuario" value="<?php echo $usuario[0]['apellidoUsuario']; ?>">
            </div>
          </div>

          <div class="form-group row">
            <label class="col-sm-2 col-form-label">Email</label>
            <div class="col-sm-10">
              <input type="email" class="form-control" placeholder="Email" name="emailUsuario" value="<?php echo $usuario[0]['email']; ?>">
            </div>
          </div>
          <div class="form-group row d-none" id="ocultar">
            <label class="col-sm-2 col-form-label">Nueva contraseña</label>
            <div class="col-sm-10">
              <input type="password" class="form-control" placeholder="Contraseña" name="passUsuario">
            </div>
            <div class="offset-sm-2 col-sm-10">
              <div class="form-check border rounded border-warning py-1">
                <label class="form-check-label text-secondary">Al iniciar sesion la próxima vez, el usuario deberá crear una nueva contraseña</label>
              </div>
            </div>
          </div>
          <div class="form-group row">
            <label class="col-sm-2 col-form-label">Nivel de Usuario</label>
            <div class="col-sm-10">
              <select class="custom-select" name="rol">
                <option value="<?php echo $usuario[0]['rol']; ?>" selected><?php echo $usuario[0]['rol']; ?></option>
                <option value="ADMINISTRADOR">ADMINISTRADOR</option>
                <option value="DOCENTE">DOCENTE</option>
                <option value="ESTUDIANTE">ESTUDIANTE</option>
                <option value="GESTOR">GESTOR</option>
              </select>

            </div>

          </div>
          <div class="form-group row">

          </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
          <button type="button" class="btn btn-success" onclick="ocultar()">Cambiar contraseña</button>

          <input type="submit" class="btn btn-success" value="Modificar datos">
          <?php
          $registro = ControladorUsuarios::crtModificarUsuario();
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

  </div>
  <!-- /.card-body -->

</div>
<!-- /.card -->