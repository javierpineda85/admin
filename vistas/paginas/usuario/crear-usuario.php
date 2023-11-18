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
        <form class="form-horizontal" method="POST">
          <div class="card-body">
            <div class="row">
              <div class="form-group col-sm-12 col-md-5">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" placeholder="Juan Carlos" name="nombreUsuario">
              </div>
              <div class="form-group col-sm-12 col-md-5">
                <label class="form-label">Apellido</label>
                <input type="text" class="form-control" placeholder="Perez" name="apellidoUsuario">
              </div>
              <div class="form-group col-sm-12 col-md-2">
                <label class="form-label">DNI</label>
                <input type="text" class="form-control" placeholder="12345678" name="dniPerfil">
              </div>
              <div class="form-group col-sm-12 col-md-2">
                <label class="form-label">Fecha Nac</label>
                <input type="date" class="form-control" name="fnacPerfil">
              </div>

              <div class="form-group col-sm-12 col-md-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" placeholder="usuario@correo.com" name="email">
              </div>
              <div class="form-group col-sm-12 col-md-3">
                <label class="form-label">Contraseña</label>
                <input type="password" class="form-control" placeholder="Ingresá el DNI como contraseña" name="pass">
              </div>


              <div class="form-group col-sm-12 col-md-3">
                <label class="form-label">Teléfono</label>
                <input type="text" class="form-control" placeholder="2612223333" name="telefonoPerfil">
              </div>
              <div class="form-group col-sm-12 col-md-5">
                <label class="form-label">Domicilio</label>
                <input type="text" class="form-control" placeholder="Av San Martin 123 Ciudad" name="domicilioPerfil">
              </div>
              <div class="form-group col-sm-12 col-md-4">
                <label class="form-label">Provincia</label>
                <select id="provincia" name="provinciaPerfil" class="form-control">
                  <option value="" disabled selected>Elige una provincia</option>
                </select>
              </div>
              <div class="form-group col-sm-12 col-md-3">
                <label class="form-label">Rol de Usuario</label>
                <select class="custom-select" name="rol">
                  <option value="" disabled selected> Rol de usuario</option>
                  <option value="Administrador">Administrador</option>
                  <option value="Docente">Docente</option>
                  <option value="Estudiante">Estudiante</option>
                </select>
              </div>
            </div>

            <!-- /.card-body -->
            <div class="card-footer">
            <button type="reset" class="btn btn-default float-right">Borrar campos</button>
              <?php

              $registro =  ControladorUsuarios::crtGuardarUsuario();
              ?>

              <input type="submit" class="btn btn-success" value="Registrar">

              
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

<script>
  // Obtener el elemento select
  var selectProvincia = document.getElementById("provincia");

  // JSON con las provincias argentinas
  var provinciasJSON = {
  "provincias": [
    {"nombre": "Buenos Aires"},{"nombre": "Catamarca"},{"nombre": "Chaco"},{"nombre": "Chubut"},
    {"nombre": "Ciudad Autónoma de Buenos Aires"},{"nombre": "Córdoba"},{"nombre": "Corrientes"},
    {"nombre": "Entre Ríos"},{"nombre": "Formosa"},{"nombre": "Jujuy"},{"nombre": "La Pampa"},
    {"nombre": "La Rioja"},{"nombre": "Mendoza"},{"nombre": "Misiones"},{"nombre": "Neuquén"},
    {"nombre": "Río Negro"},{"nombre": "Salta"},{"nombre": "San Juan"},{"nombre": "San Luis"},
    {"nombre": "Santa Cruz"},{"nombre": "Santa Fe"},{"nombre": "Santiago del Estero"},
    {"nombre": "Tierra del Fuego, Antártida e Islas del Atlántico Sur"},{"nombre": "Tucumán"}
  ]
};


  // Agregar opciones al select
  provinciasJSON.provincias.forEach(function(provincia) {
    var option = document.createElement("option");
    option.value = provincia.nombre;
    option.text = provincia.nombre;
    selectProvincia.add(option);
  });
</script>