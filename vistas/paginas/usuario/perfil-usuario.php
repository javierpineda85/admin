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
        <section class="content d-flex justify-content-around">
            <div class="row">
                <div class="col-md-8" id="about">
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
                        <div class="card card-primary">
                            <div class="card-body box-profile">
                                <div class="text-center">
                                    <img class="profile-user-img img-fluid img-circle" src="./img/user2-160x160.jpg" alt="User profile picture">
                                </div>

                                <h3 class="profile-username text-center">Javier Pineda</h3>
                            </div>

                            <div class="card card-primary">

                                <!-- /.card-header -->
                                <div class="card-body">
                                    <strong><i class="fas fa-birthday-cake"></i> Fecha de nacimiento</strong>

                                    <p class="text-muted">
                                        17/10/1986
                                    </p>

                                    <hr>

                                    <strong><i class="fas fa-map-marker-alt mr-1"></i> Domicilio</strong>

                                    <p class="text-muted">Av Siempre Viva 742, Springfield</p>

                                    <hr>

                                    <strong><i class="far fa-file-alt mr-1"></i> Algo mas sobre mi</strong>

                                    <p class="text-muted">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam fermentum enim neque.</p>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
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

        </section>
    </div>
    <!-- /Main content  -->

</div>
<!-- /default boxes-->
<!-- Button trigger modal -->


<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-secondary">
                <h3 class="card-title">Modificar perfil</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Perfil extras -->

                <div class="card-body">
                    <!-- Start FORM-->
                    <form class="form-horizontal">
                    <div class="form-group row">
                            <label for="inputImgPerfil" class="col-sm-2 col-form-label">Foto de perfil</label>
                            <div class="col-sm-10">
                                <input type="file" class="form-control" id="inputImgPerfil" placeholder="imgUsuario">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="inputDomicilio" class="col-sm-2 col-form-label">Domicilio</label>
                            <div class="col-sm-10">
                                <input type="email" class="form-control" id="inputDomicilio" placeholder="domicioPerfil">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="inputFnac" class="col-sm-2 col-form-label">Fecha de nacimiento</label>
                            <div class="col-sm-10">
                                <input type="date" class="form-control" name="fnacPerfil">
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

                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                <button type="submit" class="btn btn-success">Guardar Cambios</button>
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