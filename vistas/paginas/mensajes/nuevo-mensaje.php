<!-- Main content -->
<section class="content">

    <!-- Default box -->
    <div class="card">
        <div class="card-header bg-info">
            <h3 class="card-title">Mensajes</h3>

            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                    <i class="fas fa-minus"></i>
                </button>

            </div>
        </div>
        <div class="card-body">
            <section class="content">
                <div class="row">
                    <div class="col-md-3">
                        <a href="index.php?r=bandeja-entrada&c=mensajes" class="btn btn-primary btn-block mb-3">Volver a bandeja de entrada</a>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Carpetas</h3>

                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <ul class="nav nav-pills flex-column">
                                    <li class="nav-item active">
                                        <a href="#" class="nav-link">
                                            <i class="fas fa-inbox"></i> Bandeja de entrada
                                            <span class="badge bg-primary float-right">12</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            <i class="far fa-envelope"></i> Enviados
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" class="nav-link">
                                            <i class="far fa-trash-alt"></i> Papelera
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <!-- /.card-body -->
                        </div>

                    </div>
                    <!-- /.col -->
                    <div class="col-md-9">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">Redactar nuevo mensaje</h3>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <div class="form-group">
                                    <input class="form-control" placeholder="Para:">
                                </div>
                                <div class="form-group">
                                    <textarea id="compose-textarea" class="form-control" style="height: 300px">

                      But I must explain to you how all this mistaken idea of denouncing pleasure and praising pain
                        was born and I will give you a complete account of the system, and expound the actual teachings
                        of the great explorer of the truth, the master-builder of human happiness. No one rejects,
                        dislikes, or avoids pleasure itself, because it is pleasure, but because those who do not know
                        how to pursue pleasure rationally encounter consequences that are extremely painful. 

                    </textarea>
                                </div>

                                <!-- /.card-body -->
                                <div class="card-footer">
                                    <div class="float-right">
                                        <button type="submit" class="btn btn-primary"><i class="far fa-envelope"></i> Enviar</button>
                                    </div>
                                    <button type="reset" class="btn btn-default"><i class="fas fa-times"></i> Descartar</button>
                                </div>
                                <!-- /.card-footer -->
                            </div>
                            <!-- /.card -->
                        </div>
                        <!-- /.col -->
                    </div>
                    <!-- /.row -->
            </section>
        </div>
        <!-- /.card-body -->

    </div>
    <!-- /.card -->

</section>
<!-- /.content -->