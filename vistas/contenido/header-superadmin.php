<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <ul class="navbar-nav">
    <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="index.php?r=superadmin" class="nav-link">Panel MenteMotion</a></li>
  </ul>
  <ul class="navbar-nav ml-auto">
    <li class="nav-item d-none d-sm-inline-block">
      <span class="nav-link text-muted badge badge-light border px-3 py-2"><i class="fas fa-shield-alt mr-1"></i>SUPERADMIN</span>
    </li>
    <?php if (count(ControladorInstitucion::membresias()) > 0): ?>
      <li class="nav-item"><a href="index.php?r=seleccionar-institucion" class="nav-link" title="Ingresar a una institución"><i class="fas fa-university mr-1"></i>Campus</a></li>
    <?php endif; ?>
    <li class="nav-item"><a href="index.php?r=logout" class="nav-link text-danger" title="Cerrar sesión"><i class="fas fa-sign-out-alt"></i></a></li>
  </ul>
</nav>
