<?php include_once('contenido/head.php');?>

<body class="hold-transition sidebar-mini">
<!-- Site wrapper -->
<div class="wrapper">

  <!-- Navbar -->

  <?php include_once('contenido/header.php'); ?>

  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <?php include_once('contenido/aside.php'); ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1></h1>
          </div>
          <div class="col-sm-6">

          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">

            <!--AQUI VAN LAS VISTAS DEPENDIENDO DE LA RUTA -->
            <?php
            if (isset($_GET['r'])) {
                if (
                    $_GET['r'] == "crear-usuario" ||
                    $_GET['r'] == "listado-usuarios" ||
                    $_GET['r'] == "crear-curso" ||
                    $_GET['r'] == "listado-cursos" ||
                    $_GET['r'] == "editar-curso" ||
                    $_GET['r'] == "listado-materias" ||
                    $_GET['r'] == "crear-materia" 

                ) {

                    include("paginas/" . $_GET['c'] . "/" . $_GET['r'] . ".php");
                } else {
                    include("paginas/404.php");
                }
            } else {
                include("paginas/inicio.php");
            }

            ?>

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <footer class="main-footer">
  <?php include_once('contenido/footer.php'); ?>
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- scripts -->
<?php include_once('contenido/scripts.php'); ?>


</body>
</html>
