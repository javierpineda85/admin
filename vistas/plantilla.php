<?php include_once('contenido/head.php'); ?>

<body class="hold-transition sidebar-mini sidebar-collapse">
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
        // Se instancia el objeto de ruta para cargar la vista correspondiente
          RutasController::cargarVista();
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