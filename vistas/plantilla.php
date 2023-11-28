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


      <!-- Main content -->
      <section class="content mt-2">

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

    
  <!-- scripts -->
  <?php include_once('contenido/scripts.php'); ?>


</body>

</html>