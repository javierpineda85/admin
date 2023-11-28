<?php

$_SESSION['id_usuario'] = "5";

$db= new Conexion;
$sql = "SELECT * FROM usuarios WHERE idUsuarios =" .$_SESSION['id_usuario'];
$usuario = $db->consultas($sql);

$perfil = ControladorPerfiles::crtSeleccionarPerfil('id_usuario', $_SESSION['id_usuario']);
$fnac = $perfil[0]['fnacPerfil'];
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
            <div class="col-sm-12 col-md-5" id="about">
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
                        <button type="button" class="btn btn-primary" onclick="ocultar()">Editar mi perfil </button>

                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
                <!-- /.card-body -->
            </div>
            <!-- /.card -->

            <!-- editar perfil -->

            <div class="col-7 d-none" id="ocultar">
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Editar Perfil</h3>

                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- Perfil extras -->

                    <div class="card card-primary col-12">
                        <!-- Start FORM-->
                        <form class="form-horizontal" method="POST" enctype="multipart/form-data" id="editar-perfil">
                            <div class="form-group row">
                                <input type="text" value="<?php echo $_SESSION['id_usuario']; ?>" name="id_usuario" hidden>
                                <label for="inputImgPerfil" class="col-sm-2 col-form-label">Foto de perfil</label>
                                <div class="col-sm-10">
                                    <input type="file" class="form-control" id="inputImgPerfil" name="imgUsuario">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="domicilioPerfil" class="col-sm-2 col-form-label">Domicilio</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="domicilioPerfil" name="domicilioPerfil" value="                                   <?php
                                    if ($perfil != null) {
                                        echo $perfil[0]['domicilioPerfil'];
                                    } else {
                                        echo "Todavia no has escrito nada interesante sobre vos";
                                    }
                                    ?>">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="fnac" class="col-sm-2 col-form-label">Fecha de nacimiento</label>
                                <div class="col-sm-10">
                                    <input type="date" class="form-control" name="fnacPerfil" id="fnac" value="
                                    <?php
                                    if ($perfil != null) {
                                        echo $fnac;
                                    }
                                    ?>">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="contenidoPerfil" class="col-sm-2 col-form-label">Sobre mi</label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" id="contenidoPerfil" name="contenidoPerfil"  value="">
                                    <?php
                                    if ($perfil != null) {
                                        echo $perfil[0]['contenidoPerfil'];
                                    } else {
                                        echo "Todavia no has escrito nada interesante sobre vos";
                                    }
                                    ?>
                                    </textarea>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="offset-sm-2 col-sm-10">

                                    <button type="button" class="btn btn-secondary" onclick="ocultar()">Cerrar</button>
                                    <button type="submit" class="btn btn-success">Guardar Cambios</button>
                                    <?php
                                    $registro = ControladorPerfiles::crtEditarPerfil();
                                    ?>

                                </div>
                            </div>
                        </form>
                        <!-- /.form -->
                    </div>
                    <!-- /.card-body -->

                    <!-- /Perfil extras-->
                </div>
            </div>
        </div>

    </div>

</div>
