<?php
$cursos = ControladorCursos::crtSeleccionarCurso(null, null);
$usuarios = ControladorUsuarios::crtSeleccionarUsuario('rol', 'DOCENTE');


?>

<!-- Default box -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Crear nueva materia</h3>

        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
            </button>

        </div>
    </div>
    <div class="card-body">

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
                            <!-- form start -->

                            <form action="" method="POST">
                                <div class="form-group">
                                    <label for="inputName">Título materia</label>
                                    <input type="text" id="inputName" class="form-control" name="tituloSeccion">
                                </div>
                                <div class="form-group">
                                    <label for="inputDescription">Descripción</label>
                                    <textarea id="inputDescription" class="form-control" rows="4" name="contenidoSeccion"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="inputName">Seleccione al curso que pertenece:</label>

                                    <select class="custom-select" name="id_curso">
                                        <?php foreach ($cursos as $campo => $valor) : ?>
                                            <option value="<?php echo $valor["idCurso"]; ?>"><?php echo $valor['nombreCurso']; ?></option>
                                        <?php endforeach ?>
                                    </select>

                                </div>
                                <div class="form-group">
                                    <label for="inputClientCompany">Docente a cargo:</label>
                                    <select class="custom-select" name="docente">
                                        <?php foreach ($usuarios as $campo => $valor) : ?>
                                            <option value="<?php echo $valor["idUsuario"]; ?>"><?php echo $valor['nombreUsuario'] . " " . $valor['apellidoUsuario']; ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="inputClientCompany">Tutor:</label>
                                    <select class="custom-select" name="tutor">
                                        <option value="NULL" disabel selected>Este curso no tiene tutor </option>
                                        <?php foreach ($usuarios as $campo => $valor) : ?>
                                            <option value="<?php echo $valor["idUsuario"]; ?>"><?php echo $valor['nombreUsuario'] . " " . $valor['apellidoUsuario']; ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <input type="reset" class="btn btn-primary" value="Cancelar">

                                    <?php $registro =  ControladorMaterias::crtGuardarMateria(); ?>

                                    <input type="submit" value="Crear nueva materia" class="btn btn-success float-right">

                                </div>
                                <div>
                                    
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
                            </form>
                            <!-- / end form -->
                        </div>

                    </div>

                </div>
            </div>

        </section>
        <!-- / Main content -->

    </div>
    <!-- /.card-body -->

</div>
<!-- / Default box -->
