<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index.php" class="brand-link">
      <img src="img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">Classroom Hub</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <?php
            $imgUsuario = $_SESSION['usuario']['img'] ?? 'user2-160x160.jpg';
          ?>
          <img src="img/<?php echo htmlspecialchars($imgUsuario, ENT_QUOTES, 'UTF-8'); ?>" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block">
            <?php echo htmlspecialchars($_SESSION['usuario']['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8'); ?>
          </a>
          <small class="text-muted">
            <?php echo htmlspecialchars(ControladorPermisos::etiquetaRol(), ENT_QUOTES, 'UTF-8'); ?>
          </small>
        </div>
      </div>

      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Buscar" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
          <li class="nav-item"> <!-- dashboard -->
            <a href="index.php" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt text-warning"></i>
              <p>
                Panel de Control
              </p>
            </a>
            
          </li>
          <?php if (ControladorPermisos::puedeVerMenu('perfil')): ?>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon fas fa-user text-info"></i>
                <p>Perfil<i class="fas fa-angle-left right"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="index.php?r=perfil-usuario" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Mi perfil</p>
                  </a>
                </li>
              </ul>
            </li>
          <?php endif; ?>

          <?php if (ControladorPermisos::esAdministrador()): ?>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon fas fa-users text-info"></i>
                <p>Usuarios<i class="right fas fa-angle-left"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="index.php?r=listado-usuarios" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Ver todos</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="index.php?r=crear-usuario" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Crear</p>
                  </a>
                </li>
              </ul>
            </li>

            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon fas fa-copy text-success"></i>
                <p>Cursos<i class="fas fa-angle-left right"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="index.php?r=listado-cursos" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Ver todos</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="index.php?r=crear-curso" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Agregar</p>
                  </a>
                </li>
              </ul>
            </li>

            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon fas fa-book-open text-success"></i>
                <p>Secciones<i class="fas fa-angle-left right"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="index.php?r=listado-materias" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Ver todas</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="index.php?r=crear-materia" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Crear</p>
                  </a>
                </li>
              </ul>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a href="index.php?r=listado-cursos" class="nav-link">
                <i class="nav-icon fas fa-copy text-success"></i>
                <p>Cursos</p>
              </a>
            </li>
          <?php endif; ?>

          <?php if (ControladorPermisos::puedeVerMenu('mensajes')): ?>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon far fa-envelope text-primary"></i>
                <p>Mensajes<i class="fas fa-angle-left right"></i></p>
              </a>
              <ul class="nav nav-treeview">
                <li class="nav-item">
                  <a href="index.php?r=bandeja-entrada" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Bandeja de entrada</p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="index.php?r=nuevo-mensaje" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Nuevo mensaje</p>
                  </a>
                </li>
              </ul>
            </li>
          <?php endif; ?>

        </ul>
        <ul class="nav nav-pills nav-sidebar flex-column mt-3">
          <li class="nav-item">
            <a href="index.php?r=logout" class="nav-link text-danger">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>Cerrar sesión</p>
            </a>
          </li>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>
