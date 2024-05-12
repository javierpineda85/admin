<?php
$idCurso = $_GET['idCurso'];
$db = new Conexion;
$sql = "SELECT * FROM cursos WHERE idCurso = $idCurso";
$curso = $db->consultas($sql);

$db = new Conexion;
$sql = "SELECT idSeccion, tituloSeccion, contenidoSeccion,id_curso, docente, tutor, cursos.nombreCurso, usuarios.nombreUsuario , usuarios.apellidoUsuario FROM secciones JOIN cursos ON secciones.id_curso = cursos.idCurso JOIN usuarios ON secciones.docente = usuarios.idUsuario WHERE secciones.id_curso = $idCurso ORDER BY tituloSeccion ASC";
$materias = $db->consultas($sql);

$db = new Conexion();
$sql = "SELECT * FROM usuarios WHERE rol = 'ESTUDIANTE'";
$estudiantes = $db->consultas($sql);

$db = new Conexion;
$sql = "SELECT * FROM asignacioncursos 
RIGHT JOIN usuarios ON  asignacioncursos.id_estudiante =usuarios.idUsuario
WHERE usuarios.rol = 'ESTUDIANTE'
AND asignacioncursos.id_seccion = $idCurso";
$cursantes = $db->consultas($sql);

?>


<!-- Detalle box -->

<div class="row">
    <div class="card-body col-12">
        <!-- Seccion general y materias pertenecientes al curso -->
        <section class="content">
            <div class="row">
                <!-- Columna general -->
                <div class="col-6">
                    <div class="card card-primary">
                        <div class="card-header bg-secondary">
                            <h3 class="card-title">General del curso: <b> <?php echo $curso[0]['nombreCurso']; ?></b></h3>

                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="" method="post">
                                <div class="row">
                                    <div class="form-group col-md-4 col-sm-12">
                                        <input type="text" name="idCurso" value="<?php echo $_GET['idCurso']; ?>" hidden>

                                        <label for="inputName">Nombre del Curso</label>
                                        <input type="text" class="form-control form-control-sm" name="nombreCurso" value="<?php echo $curso[0]['nombreCurso']; ?>">
                                    </div>
                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="inputStatus">Estado</label>
                                        <select id="inputStatus" class="form-control form-control-sm" name="estado">
                                            <option value="<?php echo $curso[0]['estado']; ?>" selected><?php echo $curso[0]['estado']; ?></option>
                                            <option value="En Curso">En curso</option>
                                            <option value="Programado">Programado</option>
                                            <option value="Cancelado">Cancelado</option>
                                            <option value="Finalizado">Finalizado</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="inputDescription">Descripción</label>
                                        <textarea id="inputDescription" class="form-control form-control-sm" rows="1" name="contenidoCurso" placeholder="Descripción no ingresada"><?php echo $curso[0]['contenidoCurso']; ?></textarea>
                                    </div>
                                </div>
                                <div class="row">

                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="inputEstimatedBudget">Inicio:</label>
                                        <input type="date" id="inputEstimatedBudget" class="form-control form-control-sm" name="fechaInicioCurso" value="<?php echo $curso[0]['fechaInicioCurso']; ?>">
                                    </div>
                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="inputSpentBudget">Finaliza:</label>
                                        <input type="date" id="inputSpentBudget" class="form-control form-control-sm" name="fechaFinCurso" value="<?php echo $curso[0]['fechaFinCurso']; ?>">
                                    </div>
                                    <div class="form-group col-md-3 col-sm-12">
                                        <label for="inputEstimatedDuration">Horario:</label>
                                        <input type="time" id="inputEstimatedDuration" class="form-control form-control-sm" name="horarioCurso" value="<?php echo $curso[0]['horarioCurso']; ?>">
                                    </div>

                                    <div class="form-group col-1 mt-4">
                                        <?php
                                        $editar = ControladorCursos::crtModificarCurso();

                                        ?>
                                        <button type="submit" class="btn btn-success btn-sm mt-2"><i class="fas fa-edit"></i></button>
                                    </div>
                                    <div class="col-12">

                                        <?php
                                        if (isset($_SESSION['success_message'])) {
                                            echo '<div class="alert alert-success alert-dismissible">
                                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                                    <h4 ><i class="icon fas fa-check"></i></h4>' . $_SESSION['success_message'] .
                                                '</div>';
                                            // Elimina el mensaje después de mostrarlo
                                            unset($_SESSION['success_message']);
                                        };
                                        ?>
                                    </div>

                                </div>
                            </form>

                        </div>
                        <!-- /.card-body -->


                    </div>
                    <!-- /.card -->
                </div>

                <!-- Materias pertenecientes al curso -->
                <div class="col-6">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Materias pertenecientes al curso</h3>

                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="example2" class="table table-bordered table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th style="text-align: center;">Materia</th>
                                        <th style="text-align: center;">Descripcion</th>
                                        <th style="text-align: center;">Docente</th>
                                        <th style="text-align: center;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materias as $campo => $valor) : ?>
                                        <tr>
                                            <td> <?php echo $valor['tituloSeccion']; ?></td>
                                            <td> <?php echo $valor['contenidoSeccion']; ?></td>
                                            <td> <?php echo $valor['nombreUsuario'] . " " . $valor['apellidoUsuario']; ?> </td>

                                            <td>
                                                <div class="row d-flex justify-content-around">
                                                    <a href="#" class="btn btn-success btn-sm"><i class="fas fa-edit"></i></a>
                                                    <form method="post">
                                                        <input type="hidden" value="" name="idEliminar">
                                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                                                        <?php

                                                        /* $eliminar = new ControladorFormularios();
                                            $eliminar->ctrEliminarVisita();*/

                                                        ?>

                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>


                                </tbody>

                            </table>

                        </div>
                        <!-- /.card-body -->


                    </div>
                    <!-- /.card -->
                </div>
            </div>

        </section>
        <!-- /.content -->
    </div>

</div>
<div class="row">
    <div class="card-body col-lg-12 col-md-12">
        <!-- Estudiantes -->
        <section class="content">
            <div class="row">
                <!-- Inscribir estudiantes -->
                <div class="col-6">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Inscribir estudiantes</h3>

                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <table id="example1" class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th style="text-align: center;">Apellido y Nombre</th>
                                            <th style="text-align: center;">Email</th>
                                            <th style="text-align: center;"><i class="fas fa-user-plus"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($estudiantes as $campo => $valor) : ?>
                                            <tr>
                                                <td> <?php echo $valor['apellidoUsuario'] . " " . $valor['nombreUsuario']; ?> </td>
                                                <td> <?php echo $valor['email']; ?> </td>
                                                <td> <input type="checkbox" name="idUsuarios[]" value="<?php echo $valor['idUsuario']; ?>"> </td>
                                            </tr>
                                        <?php endforeach ?>


                                    </tbody>
                                </table>
                                <?php $registro =  ControladorCursos::crtAsignarCurso(); ?>
                                <input type="submit" value="AGREGAR" class="btn btn-primary">
                                <div>
                                    <?php
                                    
                                    if (isset($_SESSION['success_message'])) {
                                        echo "<script>window.location.href = 'index?r=detalle-curso&idCurso=". $_GET['idCurso']."';</script>";
                                        //borré el mensaje de suceso pero actualiza la página
                                        unset($_SESSION['success_message']);
                                        
                                    };
                                    ?>
                                </div>
                            </form>
                        </div>
                        <!-- /.card-body -->


                    </div>
                    <!-- /.card -->
                </div>
                <!-- Estudiantes inscriptos al curso -->
                <div class="col-6">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Estudiantes Inscriptos al curso</h3>

                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="example1" class="table table-bordered table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th style="text-align: center;">Apellido y Nombre</th>
                                        <th style="text-align: center;">DNI</th>
                                        <th style="text-align: center;">Email</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cursantes as $campo => $valor) : ?>
                                        <tr>
                                            <td> <?php echo $valor['apellidoUsuario'] . " " . $valor['nombreUsuario']; ?> </td>
                                            <td> <?php echo $valor['idUsuario']; ?> </td>
                                            <td> <?php echo $valor['email']; ?> </td>

                                        </tr>
                                    <?php endforeach ?>


                                </tbody>
                            </table>

                        </div>
                        <!-- /.card-body -->


                    </div>
                    <!-- /.card -->
                </div>
            </div>

        </section>
        <!-- /.content -->
    </div>
</div>