<?php

$_SESSION['id_usuario'] = "5";
$usuario = ControladorUsuarios::crtSeleccionarUsuario('idUsuario', $_SESSION['id_usuario']);
$perfil = ControladorPerfiles::crtSeleccionarPerfil('id_usuario', $_SESSION['id_usuario']);

?>

<!-- Default box -->
<div class="card">
    <div class="card-header bg-primary">
        <h3 class="card-title">Mi perfil</h3>

        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
            </button>

        </div>
    </div>
    <div class="card-body">


        <!-- Main content -->
      
            <div class="row d-flex justify-content-around">
                <div class="col-6" id="about">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Sobre mi</h3>

                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <!-- Profile Image -->
                        <div class="card card-primary col-12">
                            <div class="card-body box-profile">
                                <div class="text-center">
                                    <img class="profile-user-img img-fluid img-circle" src="./img/user2-160x160.jpg" alt="User profile picture">
                                </div>

                                <h3 class="profile-username text-center"><?php echo $usuario[0]['nombreUsuario'] . " " . $usuario[0]['apellidoUsuario'] ?></h3>
                            </div>

                            <div class="card card-primary">

                                <!-- /.card-header -->
                                <div class="card-body">
                                    <strong><i class="fas fa-birthday-cake"></i> Fecha de nacimiento</strong>

                                    <p class="text-muted">
                                        <?php
                                        if ($perfil != null) {
                                            echo $perfil[0]['fnac'];
                                        } else {
                                            echo "Aun no completaste tu fecha de nacimiento.";
                                        }
                                        ?>
                                    </p>

                                    <hr>

                                    <strong><i class="fas fa-map-marker-alt mr-1"></i> Domicilio</strong>

                                    <p class="text-muted">
                                        <?php
                                        if ($perfil != null) {
                                            echo $perfil[0]['domicilioPerfil'];
                                        } else {
                                            echo "Aun no completaste tu domicilio.";
                                        }
                                        ?>

                                        <hr>

                                        <strong><i class="far fa-file-alt mr-1"></i> Algo mas sobre mi</strong>

                                    <p class="text-muted">
                                        <?php
                                        if ($perfil != null) {
                                            echo $perfil[0]['contenidoPerfil'];
                                        } else {
                                            echo "Todavia no has escrito nada interesante sobre vos";
                                        }
                                        ?>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <button type="button" class="btn btn-primary">
                                Editar mi perfil
                            </button>

                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        
    </div>

</div>

