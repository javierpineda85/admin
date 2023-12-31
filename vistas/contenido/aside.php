<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index.php" class="brand-link">
      <img src="img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">Admin TBT</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block">Javier Pineda</a>
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
          <li class="nav-item"> <!-- perfil -->
            <a href="" class="nav-link">
              
              <i class="nav-icon fas fa-user text-info"></i>
              <p>
                Perfil<i class="fas fa-angle-left right"></i>
              </p>
              
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
          <li class="nav-item"> <!-- usuarios -->
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-users text-info"></i>
              <p>
               Usuarios
                <i class="right fas fa-angle-left"></i>
              </p>
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

            </ul>
          </li>
          <li class="nav-item"><!-- cursos -->
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-copy text-success"></i>
              <p>
                Cursos
                <i class="fas fa-angle-left right"></i>
                
              </p>
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

          <li class="nav-item"> <!-- materias -->
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-book-open text-success"></i>
              <p>
                Materias
                <i class="fas fa-angle-left right"></i>
              </p>
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

            </ul>
          </li>
          <li class="nav-item">  <!-- mensajes -->
            <a href="#" class="nav-link">
              <i class="nav-icon far fa-envelope text-primary"></i>
              <p>
                Mensajes
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="index.php?r=bandeja-entrada" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Bandeja de entrada</p>
                  <span class="badge badge-info right">6</span>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?r=nuevo-mensaje&t=" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Nuevo mensaje</p>
                </a>
              </li>

            </ul>
          </li>
          <li class="nav-item">  <!-- calendario -->
            <a href="../calendar.html" class="nav-link">
              <i class="nav-icon far fa-calendar-alt text-olive"></i>
              <p>
                Calendario
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="../UI/general.html" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Ver todo</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="../UI/icons.html" class="nav-link">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Crear Evento</p>
                </a>

            </ul>
          </li>



        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>