<?php
$esc = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};

$docenteNombre = trim((string) ($seccion['nombreUsuario'] ?? '') . ' ' . (string) ($seccion['apellidoUsuario'] ?? ''));
$tutorNombre = trim((string) ($seccion['nombreTutor'] ?? '') . ' ' . (string) ($seccion['apellidoTutor'] ?? ''));
$idDocente = (int) ($seccion['docente'] ?? 0);
$idTutor = (int) ($seccion['tutor'] ?? 0);
$puedeEnviarMensaje = function (int $idUsuario) use ($idUsuarioActual): bool {
    return $idUsuario > 0 && $idUsuario !== (int) $idUsuarioActual;
};
$urlPerfil = function (int $idUsuario) use ($idSeccion): string {
    return 'index.php?r=perfil-publico&idUsuario=' . $idUsuario . '&idSeccion=' . (int) $idSeccion;
};
?>

<div class="people-panel">
  <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
    <h3 class="mb-0">Docentes y tutores</h3>
    <span class="badge badge-info">Materia</span>
  </div>

  <div class="people-row">
    <div class="classroom-avatar"><?php echo strtoupper(substr($docenteNombre !== '' ? $docenteNombre : 'D', 0, 1)); ?></div>
    <div class="flex-grow-1">
      <strong><?php echo $esc($docenteNombre !== '' ? $docenteNombre : 'Docente'); ?></strong>
      <small>Docente responsable</small>
    </div>
    <div class="people-row__actions d-flex flex-wrap align-items-center">
      <a class="btn btn-outline-secondary btn-sm mr-2" href="<?php echo $esc($urlPerfil($idDocente)); ?>">
        <i class="fas fa-user mr-1"></i>Perfil
      </a>
      <?php if ($puedeEnviarMensaje($idDocente)): ?>
        <a class="btn btn-outline-primary btn-sm" href="index.php?r=nuevo-mensaje&id_destinatario=<?php echo $idDocente; ?>">
          <i class="fas fa-paper-plane mr-1"></i>Mensaje
        </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($idTutor > 0): ?>
    <div class="people-row mt-2">
      <div class="classroom-avatar"><?php echo strtoupper(substr($tutorNombre !== '' ? $tutorNombre : 'T', 0, 1)); ?></div>
      <div class="flex-grow-1">
        <strong><?php echo $esc($tutorNombre !== '' ? $tutorNombre : 'Tutor'); ?></strong>
        <small>Tutor de la materia</small>
      </div>
      <div class="people-row__actions d-flex flex-wrap align-items-center">
        <a class="btn btn-outline-secondary btn-sm mr-2" href="<?php echo $esc($urlPerfil($idTutor)); ?>">
          <i class="fas fa-user mr-1"></i>Perfil
        </a>
        <?php if ($puedeEnviarMensaje($idTutor)): ?>
          <a class="btn btn-outline-primary btn-sm" href="index.php?r=nuevo-mensaje&id_destinatario=<?php echo $idTutor; ?>">
            <i class="fas fa-paper-plane mr-1"></i>Mensaje
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<div class="people-panel mt-3">
  <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
    <h3 class="mb-0">Compañeros</h3>
    <span class="badge badge-light border"><?php echo count($estudiantesCurso); ?> estudiantes</span>
  </div>

  <?php if (empty($estudiantesCurso)): ?>
    <div class="text-muted">Todavía no hay estudiantes asignados.</div>
  <?php else: ?>
    <?php foreach ($estudiantesCurso as $estudiante): ?>
      <?php
      $idEstudiantePersona = (int) ($estudiante['idUsuario'] ?? 0);
      $nombreEstudiantePersona = trim((string) ($estudiante['nombreUsuario'] ?? '') . ' ' . (string) ($estudiante['apellidoUsuario'] ?? ''));
      ?>
      <div class="people-row">
        <div class="classroom-avatar"><?php echo strtoupper(substr($nombreEstudiantePersona !== '' ? $nombreEstudiantePersona : 'E', 0, 1)); ?></div>
        <div class="flex-grow-1">
          <strong><?php echo $esc($nombreEstudiantePersona !== '' ? $nombreEstudiantePersona : 'Estudiante'); ?></strong>
          <small><?php echo $esc($estudiante['email'] ?? ''); ?></small>
        </div>
        <div class="people-row__actions d-flex flex-wrap align-items-center">
          <a class="btn btn-outline-secondary btn-sm mr-2" href="<?php echo $esc($urlPerfil($idEstudiantePersona)); ?>">
            <i class="fas fa-user mr-1"></i>Perfil
          </a>
          <?php if ($puedeEnviarMensaje($idEstudiantePersona)): ?>
            <a class="btn btn-outline-primary btn-sm" href="index.php?r=nuevo-mensaje&id_destinatario=<?php echo $idEstudiantePersona; ?>">
              <i class="fas fa-paper-plane mr-1"></i>Mensaje
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
