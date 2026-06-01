<?php
$rolActual = ControladorPermisos::rolActual();
$nombreUsuario = trim((string) ($_SESSION['usuario']['nombre'] ?? 'Usuario'));
$apellidoUsuario = trim((string) ($_SESSION['usuario']['apellido'] ?? ''));
$nombreCompleto = trim($nombreUsuario . ' ' . $apellidoUsuario);
$nombreCompleto = $nombreCompleto !== '' ? $nombreCompleto : 'Usuario';
$resumen = ControladorPanel::crtResumenDashboard();

$db = new Conexion;
$sql = "SELECT idCurso, nombreCurso FROM cursos ORDER BY idCurso ASC LIMIT 1";
$primerCurso = $db->consultas($sql);

$ctaPrincipal = 'index.php?r=listado-cursos';
$ctaPrincipalTexto = 'Ver cursos';
$ctaPrincipalIcono = 'fas fa-layer-group';

if (ControladorPermisos::esDocente()) {
  $ctaPrincipal = 'index.php?r=listado-materias';
  $ctaPrincipalTexto = 'Abrir aulas';
  $ctaPrincipalIcono = 'fas fa-chalkboard-teacher';
} elseif (ControladorPermisos::esEstudiante()) {
  $ctaPrincipal = !empty($primerCurso) ? 'index.php?r=detalle-curso&idCurso=' . (int) $primerCurso[0]['idCurso'] : 'index.php?r=listado-cursos';
  $ctaPrincipalTexto = 'Continuar';
  $ctaPrincipalIcono = 'fas fa-play-circle';
}

$mensajeRol = 'Tu panel está listo para trabajar.';
if (ControladorPermisos::esAdministrador()) {
  $mensajeRol = 'Administrás usuarios, cursos, materias y la estructura completa del sistema.';
} elseif (ControladorPermisos::esDocente()) {
  $mensajeRol = 'Tu espacio centraliza aulas, materiales, tareas, foros y calificaciones.';
} elseif (ControladorPermisos::esEstudiante()) {
  $mensajeRol = 'Acá tenés el acceso directo a tus clases, notas, entregas y mensajes permitidos.';
}
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
            Bienvenido, <?php echo htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8'); ?>.
            <?php echo $rolActual !== '' ? htmlspecialchars(strtolower($rolActual), ENT_QUOTES, 'UTF-8') : 'Tu panel'; ?> está listo.
          </h1>
          <p class="hero-lead mb-4">
            <?php echo htmlspecialchars($mensajeRol, ENT_QUOTES, 'UTF-8'); ?>
          </p>
          <div class="d-flex flex-wrap" style="gap: .75rem;">
            <a href="<?php echo htmlspecialchars($ctaPrincipal, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light btn-lg text-primary">
              <i class="<?php echo htmlspecialchars($ctaPrincipalIcono, ENT_QUOTES, 'UTF-8'); ?> mr-2"></i><?php echo htmlspecialchars($ctaPrincipalTexto, ENT_QUOTES, 'UTF-8'); ?>
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
            <span class="auth-pill">UX optimizada</span>
          </div>
          <div class="mt-3">
            <span class="badge badge-light border px-3 py-2">Seguimiento real</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <?php foreach (($resumen['tarjetas'] ?? []) as $tarjeta): ?>
      <?php
        $estilosIcono = [
          'bg-info' => 'background: linear-gradient(135deg, #0ea5e9, #38bdf8);',
          'bg-primary' => 'background: linear-gradient(135deg, #1d4ed8, #4f8cff);',
          'bg-success' => 'background: linear-gradient(135deg, #16a34a, #22c55e);',
          'bg-warning' => 'background: linear-gradient(135deg, #d97706, #f59e0b);',
          'bg-danger' => 'background: linear-gradient(135deg, #db2777, #f43f5e);',
        ];
        $claseTarjeta = (string) ($tarjeta['class'] ?? 'bg-primary');
        $estiloIcono = $estilosIcono[$claseTarjeta] ?? $estilosIcono['bg-primary'];
      ?>
      <div class="col-md-6 col-xl-3 mb-3">
        <div class="metric-card">
          <div class="metric-icon" style="<?php echo htmlspecialchars($estiloIcono, ENT_QUOTES, 'UTF-8'); ?>">
            <i class="<?php echo htmlspecialchars((string) ($tarjeta['icon'] ?? 'fas fa-chart-bar'), ENT_QUOTES, 'UTF-8'); ?>"></i>
          </div>
          <span class="metric-number"><?php echo htmlspecialchars((string) ($tarjeta['value'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></span>
          <span class="metric-label"><?php echo htmlspecialchars((string) ($tarjeta['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
          <small class="text-muted d-block mt-1"><?php echo htmlspecialchars((string) ($tarjeta['note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="row">
    <div class="col-lg-7 mb-4">
      <div class="card glass-card h-100">
        <div class="card-header bg-white border-0">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="section-title">Actividad reciente</div>
              <div class="section-subtitle">Movimientos que importan en tu aula</div>
            </div>
            <span class="badge badge-primary">En vivo</span>
          </div>
        </div>
        <div class="card-body">
          <?php if (empty($resumen['actividad'])): ?>
            <div class="alert alert-light border mb-0">
              Todavía no hay actividad reciente para mostrar.
            </div>
          <?php else: ?>
            <div class="timeline">
              <?php foreach ($resumen['actividad'] as $evento): ?>
                <div class="time-label">
                  <span class="bg-light"><?php echo htmlspecialchars((string) ($evento['fecha'] ?? 'Reciente'), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div>
                  <i class="timeline-icon <?php echo htmlspecialchars((string) ($evento['class'] ?? 'bg-primary'), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string) ($evento['icon'] ?? 'fas fa-bell'), ENT_QUOTES, 'UTF-8'); ?>"></i>
                  <div class="timeline-item">
                    <h3 class="timeline-header"><?php echo htmlspecialchars((string) ($evento['titulo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <div class="timeline-body">
                      <?php echo htmlspecialchars((string) ($evento['detalle'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-5 mb-4">
      <div class="card glass-card h-100">
        <div class="card-header bg-white border-0">
          <div class="section-title">Tu experiencia</div>
          <div class="section-subtitle">Atajos y contexto según tu rol</div>
        </div>
        <div class="card-body">
          <?php if (ControladorPermisos::esAdministrador()): ?>
            <div class="alert alert-primary border-0">
              Administrás la estructura completa del sistema. Usá el panel para revisar actividad, mensajes y pendientes de corrección.
            </div>
          <?php elseif (ControladorPermisos::esDocente()): ?>
            <div class="alert alert-success border-0">
              Tu foco está en aulas, material, entregas y seguimiento académico. Podés abrir una sección y gestionar todo desde ahí.
            </div>
          <?php elseif (ControladorPermisos::esEstudiante()): ?>
            <div class="alert alert-warning border-0">
              Encontrás tus clases, tareas y notas en un solo lugar, con accesos claros para volver a lo que te falta.
            </div>
          <?php else: ?>
            <div class="alert alert-light border">
              Revisá tu configuración de acceso para mostrar contenido adaptado al rol.
            </div>
          <?php endif; ?>

          <div class="mt-4">
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Mensajes</span>
              <strong><?php echo (int) (ControladorMensajes::crtContarMensajesNoLeidos((int) ($_SESSION['usuario']['id'] ?? 0))); ?></strong>
            </div>
            <div class="progress mb-3" style="height: 10px;">
              <div class="progress-bar bg-primary" style="width: 84%"></div>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Actividad reciente</span>
              <strong><?php echo (int) (($resumen['actividad'] ?? []) ? count($resumen['actividad']) : 0); ?></strong>
            </div>
            <div class="progress mb-3" style="height: 10px;">
              <div class="progress-bar bg-success" style="width: 66%"></div>
            </div>

            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Seguimiento académico</span>
              <strong><?php echo ControladorPermisos::esEstudiante() ? 'En progreso' : 'Activo'; ?></strong>
            </div>
            <div class="progress" style="height: 10px;">
              <div class="progress-bar bg-warning" style="width: 52%"></div>
            </div>
          </div>

          <div class="mt-4">
            <a href="index.php?r=bandeja-entrada" class="quick-action text-dark mb-3">
              <span class="qa-icon" style="background: linear-gradient(135deg, #db2777, #f43f5e);"><i class="fas fa-inbox"></i></span>
              <div>
                <strong>Ir a mensajes</strong>
                <div class="text-muted small">Revisá tus conversaciones activas</div>
              </div>
            </a>
            <a href="index.php?r=perfil-usuario" class="quick-action text-dark">
              <span class="qa-icon" style="background: linear-gradient(135deg, #1d4ed8, #4f8cff);"><i class="fas fa-user-circle"></i></span>
              <div>
                <strong>Editar perfil</strong>
                <div class="text-muted small">Mantené tus datos actualizados</div>
              </div>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
