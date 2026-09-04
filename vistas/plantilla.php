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

$ahora = time();
$limiteInactividad = defined('SESSION_INACTIVITY_TIMEOUT') ? (int) SESSION_INACTIVITY_TIMEOUT : 1800;
$ultimaActividad = (int) ($_SESSION['ultima_actividad'] ?? $ahora);

if (($ahora - $ultimaActividad) >= $limiteInactividad) {
  session_unset();
  session_destroy();

  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }

  session_regenerate_id(true);
  $_SESSION['login_error'] = 'Tu sesion se cerro por 30 minutos de inactividad.';
  header('Location: index.php?r=login');
  exit;
}

$_SESSION['ultima_actividad'] = $ahora;

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

RutasController::procesarAntesDeRenderizar($rutaVista);
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
