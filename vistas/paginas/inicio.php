<?php
$db = new Conexion;
$sql = "SELECT idCurso, nombreCurso FROM cursos ORDER BY idCurso ASC LIMIT 1";
$primerCurso = $db->consultas($sql);

$db = new Conexion;
$sql = "SELECT COUNT(*) AS totalUsuarios FROM usuarios";
$usuarios = $db->consultas($sql);

$db = new Conexion;
$sql = "SELECT COUNT(*) AS totalCursos FROM cursos";
$cursos = $db->consultas($sql);

$db = new Conexion;
$sql = "SELECT COUNT(*) AS totalSecciones FROM secciones";
$secciones = $db->consultas($sql);

$db = new Conexion;
$sql = "SELECT COUNT(*) AS totalMensajes FROM mensajes";
$mensajes = $db->consultas($sql);

$rolActual = ControladorPermisos::rolActual();
$nombreUsuario = $_SESSION['usuario']['nombre'] ?? 'Usuario';
?>

<div class="page-fade">
  <div class="hero-shell mb-4">
    <div class="hero-content">
      <div class="d-flex flex-wrap align-items-start justify-content-between">
        <div class="mb-3 mb-md-0">
          <div class="hero-kicker mb-3">
            <i class="fas fa-graduation-cap"></i>
            Aula viva
          </div>
          <h1 class="hero-title mb-3">
            Bienvenido, <?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?>.
            Tu panel está listo para acompañar el aprendizaje.
          </h1>
          <p class="hero-lead mb-4">
            Gestioná cursos, clases, mensajes y seguimiento académico desde una interfaz más clara, más rápida y pensada para que estudiantes y docentes se orienten sin esfuerzo.
          </p>
          <div class="d-flex flex-wrap" style="gap: .75rem;">
            <a href="index.php?r=listado-cursos" class="btn btn-light btn-lg text-primary">
              <i class="fas fa-layer-group mr-2"></i>Ver cursos
            </a>
            <a href="index.php?r=bandeja-entrada" class="btn btn-outline-light btn-lg">
              <i class="fas fa-comments mr-2"></i>Mensajes
            </a>
            <a href="index.php?r=perfil-usuario" class="btn btn-outline-light btn-lg">
              <i class="fas fa-user mr-2"></i>Mi perfil
            </a>
          </div>
        </div>
        <div class="text-right">
          <div class="auth-pills justify-content-end">
            <span class="auth-pill">Rol: <?php echo htmlspecialchars($rolActual !== '' ? $rolActual : 'usuario', ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="auth-pill">Modo responsive</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6 col-xl-3 mb-3">
      <div class="metric-card">
        <div class="metric-icon" style="background: linear-gradient(135deg, #1d4ed8, #4f8cff);">
          <i class="fas fa-copy"></i>
        </div>
        <span class="metric-number"><?php echo (int) $cursos[0]['totalCursos']; ?></span>
        <span class="metric-label">Cursos activos</span>
      </div>
    </div>
    <div class="col-md-6 col-xl-3 mb-3">
      <div class="metric-card">
        <div class="metric-icon" style="background: linear-gradient(135deg, #16a34a, #22c55e);">
          <i class="fas fa-book-open"></i>
        </div>
        <span class="metric-number"><?php echo (int) $secciones[0]['totalSecciones']; ?></span>
        <span class="metric-label">Secciones y clases</span>
      </div>
    </div>
    <div class="col-md-6 col-xl-3 mb-3">
      <div class="metric-card">
        <div class="metric-icon" style="background: linear-gradient(135deg, #d97706, #f59e0b);">
          <i class="fas fa-users"></i>
        </div>
        <span class="metric-number"><?php echo (int) $usuarios[0]['totalUsuarios']; ?></span>
        <span class="metric-label">Usuarios registrados</span>
      </div>
    </div>
    <div class="col-md-6 col-xl-3 mb-3">
      <div class="metric-card">
        <div class="metric-icon" style="background: linear-gradient(135deg, #db2777, #f43f5e);">
          <i class="fas fa-comments"></i>
        </div>
        <span class="metric-number"><?php echo (int) $mensajes[0]['totalMensajes']; ?></span>
        <span class="metric-label">Mensajes enviados</span>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-8 mb-4">
      <div class="card glass-card h-100">
        <div class="card-header bg-white border-0">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="section-title">Accesos rápidos</div>
              <div class="section-subtitle">Atajos pensados para la navegación diaria</div>
            </div>
            <span class="badge badge-primary">Inicio</span>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
            <a href="index.php?r=<?php echo !empty($primerCurso) ? 'detalle-curso&idCurso=' . (int) $primerCurso[0]['idCurso'] : 'listado-cursos'; ?>" class="quick-action text-dark">
                <span class="qa-icon" style="background: linear-gradient(135deg, #1d4ed8, #4f8cff);"><i class="fas fa-laptop-code"></i></span>
                <div>
                    <strong>Entrar al curso</strong>
                  <div class="text-muted small">
                    <?php echo !empty($primerCurso) ? htmlspecialchars($primerCurso[0]['nombreCurso'], ENT_QUOTES, 'UTF-8') : 'Todavía no hay cursos cargados'; ?>
                  </div>
                </div>
              </a>
            </div>
            <div class="col-md-6 mb-3">
              <a href="index.php?r=bandeja-entrada" class="quick-action text-dark">
                <span class="qa-icon" style="background: linear-gradient(135deg, #db2777, #f43f5e);"><i class="fas fa-inbox"></i></span>
                <div>
                  <strong>Revisar mensajes</strong>
                  <div class="text-muted small">Respondé sin perder contexto</div>
                </div>
              </a>
            </div>
            <div class="col-md-6 mb-3">
              <a href="index.php?r=listado-cursos" class="quick-action text-dark">
                <span class="qa-icon" style="background: linear-gradient(135deg, #16a34a, #22c55e);"><i class="fas fa-layer-group"></i></span>
                <div>
                  <strong>Administrar cursos</strong>
                  <div class="text-muted small">Listado, edición y seguimiento</div>
                </div>
              </a>
            </div>
            <div class="col-md-6 mb-3">
              <a href="index.php?r=perfil-usuario" class="quick-action text-dark">
                <span class="qa-icon" style="background: linear-gradient(135deg, #d97706, #f59e0b);"><i class="fas fa-user-circle"></i></span>
                <div>
                  <strong>Mi perfil</strong>
                  <div class="text-muted small">Actualizá tus datos personales</div>
                </div>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4 mb-4">
      <div class="card glass-card h-100">
        <div class="card-header bg-white border-0">
          <div class="section-title">Tu experiencia</div>
          <div class="section-subtitle">Según el rol actual</div>
        </div>
        <div class="card-body">
          <?php if (ControladorPermisos::esAdministrador()): ?>
            <div class="alert alert-primary border-0">
              Administrás usuarios, cursos y materias. Este es el centro operativo del sistema.
            </div>
          <?php elseif (ControladorPermisos::esDocente()): ?>
            <div class="alert alert-success border-0">
              Tu espacio está orientado a clases, material académico, notas y comunicación con estudiantes.
            </div>
          <?php elseif (ControladorPermisos::esEstudiante()): ?>
            <div class="alert alert-warning border-0">
              Tu panel está enfocado en el acceso a clases, seguimiento de notas y mensajes permitidos.
            </div>
          <?php else: ?>
            <div class="alert alert-light border">
              Revisá tu configuración de acceso para mostrar contenido adaptado al rol.
            </div>
          <?php endif; ?>

          <div class="mt-4">
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Organización</span>
              <strong>Alta</strong>
            </div>
            <div class="progress mb-3" style="height: 10px;">
              <div class="progress-bar bg-primary" style="width: 84%"></div>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Comunicación</span>
              <strong>Media</strong>
            </div>
            <div class="progress mb-3" style="height: 10px;">
              <div class="progress-bar bg-success" style="width: 66%"></div>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Seguimiento académico</span>
              <strong>En progreso</strong>
            </div>
            <div class="progress" style="height: 10px;">
              <div class="progress-bar bg-warning" style="width: 52%"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
