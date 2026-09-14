<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="index.php?r=superadmin" class="brand-link">
    <img src="img/logoCampus.png" alt="Logo Campus MenteMotion" class="brand-image img-circle elevation-3" style="opacity:.8">
    <span class="brand-text font-weight-light">MenteMotion</span>
  </a>
  <div class="sidebar">
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
      <div class="image">
        <?php $imgSuperAdmin = ControladorUsuarios::rutaImagenUsuario($_SESSION['usuario']['img'] ?? '', 'user2-160x160.jpg'); ?>
        <img src="img/<?php echo htmlspecialchars($imgSuperAdmin, ENT_QUOTES, 'UTF-8'); ?>" class="img-circle elevation-2" alt="Usuario">
      </div>
      <div class="info">
        <span class="d-block text-white"><?php echo htmlspecialchars($_SESSION['usuario']['nombre'] ?? 'SuperAdmin', ENT_QUOTES, 'UTF-8'); ?></span>
        <small class="text-muted">Administración global</small>
      </div>
    </div>
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column">
        <li class="nav-item"><a href="index.php?r=superadmin" class="nav-link active"><i class="nav-icon fas fa-university text-warning"></i><p>Instituciones</p></a></li>
        <?php if (count(ControladorInstitucion::membresias()) > 0): ?>
          <li class="nav-item"><a href="index.php?r=seleccionar-institucion" class="nav-link"><i class="nav-icon fas fa-sign-in-alt text-info"></i><p>Ingresar al Campus</p></a></li>
        <?php endif; ?>
        <li class="nav-item mt-3"><a href="index.php?r=logout" class="nav-link text-danger"><i class="nav-icon fas fa-sign-out-alt"></i><p>Cerrar sesión</p></a></li>
      </ul>
    </nav>
  </div>
</aside>
