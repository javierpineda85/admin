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
        <section class="d-flex justify-content-around">
            <div class="row">
                <div class="col-8">
                    <div class="card card-primary">
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
                            <form class="form-horizontal" method="POST" enctype="multipart/form-data">
                                <div class="form-group row">
                                    <input type="text" value="<?php echo $_SESSION['id_usuario']; ?>" name="id_usuario" hidden>
                                    <label for="inputImgPerfil" class="col-sm-2 col-form-label">Foto de perfil</label>
                                    <div class="col-sm-10">
                                        <input type="file" class="form-control" id="inputImgPerfil" name="imgUsuario">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="inputDomicilio" class="col-sm-2 col-form-label">Domicilio</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" id="inputDomicilio" name="domicilioPerfil">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="inputFnac" class="col-sm-2 col-form-label">Fecha de nacimiento</label>
                                    <div class="col-sm-10">
                                        <input type="date" class="form-control" name="fnacPerfil" value="">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="inputExperience" class="col-sm-2 col-form-label">Sobre mi</label>
                                    <div class="col-sm-10">
                                        <textarea class="form-control" id="inputExperience" name="contenidoPerfil"></textarea>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="offset-sm-2 col-sm-10">

                                        <button type="button" class="btn btn-secondary">Cerrar</button>
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

