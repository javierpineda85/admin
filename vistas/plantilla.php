<?php
$rutaActual = isset($_GET['r']) ? trim($_GET['r']) : '';
$rutasPublicas = ['login', 'forgot', 'actividad-publica'];

if ($rutaActual === 'logout') {
  RutasController::cargarVista();
  return;
}

if (in_array($rutaActual, $rutasPublicas, true)) {
  RutasController::cargarVista();
  return;
}

if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
  header('Location: index.php?r=login');
  exit;
}

$rutaVista = isset($_GET['r']) ? trim($_GET['r']) : '';
if ($rutaVista === 'vista-estudiante') {
  RutasController::procesarVistaEstudiante();
}

$usuarioSesionActual = ModeloUsuarios::mdlObtenerUsuarioPorId((int) ($_SESSION['usuario']['id'] ?? 0));
if (!$usuarioSesionActual || (int) ($usuarioSesionActual['activo'] ?? 0) !== 1) {
  session_destroy();
  header('Location: index.php?r=login');
  exit;
}
?>
<?php include_once('contenido/head.php'); ?>

<body class="hold-transition sidebar-mini sidebar-collapse classroom-app">
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
