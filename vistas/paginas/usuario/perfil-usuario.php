<?php
$usuario = ControladorUsuarios::crtUsuarioActual();
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$perfil = $usuario ? $usuario : [];
$relacionesAcademicas = $idUsuarioActual > 0 ? ControladorUsuarios::crtRelacionesAcademicas($idUsuarioActual) : [];
$nombreCompleto = trim((string) (($perfil['nombreUsuario'] ?? '') . ' ' . ($perfil['apellidoUsuario'] ?? '')));
$imagenUsuario = !empty($perfil['imgUsuario']) ? $perfil['imgUsuario'] : 'user2-160x160.jpg';
$estaActivo = (int) ($perfil['activo'] ?? 0) === 1;
$e = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
?>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="profile-hero mb-4">
      <div class="profile-hero__content">
        <div class="profile-hero__avatar">
          <img src="./img/<?php echo $e($imagenUsuario); ?>" alt="Foto de perfil">
        </div>
        <div class="profile-hero__copy">
          <span class="profile-kicker">Mi perfil</span>
          <h1 class="profile-title mb-2"><?php echo $e($nombreCompleto !== '' ? $nombreCompleto : 'Usuario'); ?></h1>
          <p class="profile-lead mb-3">
            <?php echo $e($perfil['rol'] ?? 'Sin rol'); ?> · <?php echo $estaActivo ? 'Cuenta activa' : 'Cuenta dada de baja'; ?>
          </p>
          <div class="d-flex flex-wrap gap-2">
            <span class="badge badge-light badge-pill px-3 py-2"><?php echo $e($perfil['email'] ?? ''); ?></span>
            <span class="badge badge-info badge-pill px-3 py-2"><?php echo $e($perfil['fechaAltaFmt'] ?? 'Fecha de alta pendiente'); ?></span>
            <?php if (!$estaActivo): ?>
              <span class="badge badge-danger badge-pill px-3 py-2">Baja: <?php echo $e($perfil['fechaBajaFmt'] ?? ''); ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-4 mb-4">
        <div class="card glass-card h-100">
          <div class="card-header">
            <h3 class="card-title">Datos de usuario</h3>
          </div>
          <div class="card-body">
            <div class="profile-meta-item">
              <span class="profile-meta-label">Nombre completo</span>
              <strong><?php echo $e($nombreCompleto !== '' ? $nombreCompleto : 'Sin registrar'); ?></strong>
            </div>
            <div class="profile-meta-item">
              <span class="profile-meta-label">Correo electrónico</span>
              <strong><?php echo $e($perfil['email'] ?? 'Sin correo'); ?></strong>
            </div>
            <div class="profile-meta-item">
              <span class="profile-meta-label">Rol</span>
              <strong><?php echo $e($perfil['rol'] ?? 'Sin rol'); ?></strong>
            </div>
            <div class="profile-meta-item">
              <span class="profile-meta-label">Estado</span>
              <strong><?php echo $estaActivo ? 'Activo' : 'Dado de baja'; ?></strong>
            </div>
            <div class="profile-meta-item">
              <span class="profile-meta-label">Fecha de alta</span>
              <strong><?php echo $e($perfil['fechaAltaFmt'] ?? 'Sin fecha'); ?></strong>
            </div>
            <?php if (!$estaActivo): ?>
              <div class="profile-meta-item">
                <span class="profile-meta-label">Fecha de baja</span>
                <strong><?php echo $e($perfil['fechaBajaFmt'] ?? 'Sin fecha'); ?></strong>
              </div>
              <div class="profile-meta-item">
                <span class="profile-meta-label">Motivo de baja</span>
                <strong><?php echo $e($perfil['motivoBaja'] ?? ''); ?></strong>
              </div>
              <div class="profile-meta-item">
                <span class="profile-meta-label">Dado de baja por</span>
                <strong><?php echo $e($perfil['usuarioBajaNombre'] ?? ''); ?></strong>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-8 mb-4">
        <div class="card glass-card h-100">
          <div class="card-header">
            <h3 class="card-title">Datos personales</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <div class="profile-chip">DNI: <?php echo $e($perfil['dniPerfil'] ?? 'Sin completar'); ?></div>
              </div>
              <div class="col-md-6 mb-3">
                <div class="profile-chip">Teléfono: <?php echo $e($perfil['telefonoPerfil'] ?? 'Sin completar'); ?></div>
              </div>
              <div class="col-md-6 mb-3">
                <div class="profile-chip">Fecha de nacimiento: <?php echo $e($perfil['fnacFormateada'] ?? 'Sin completar'); ?></div>
              </div>
              <div class="col-md-6 mb-3">
                <div class="profile-chip">Domicilio: <?php echo $e($perfil['domicilioPerfil'] ?? 'Sin completar'); ?></div>
              </div>
              <div class="col-md-6 mb-3">
                <div class="profile-chip">Provincia: <?php echo $e($perfil['provinciaPerfil'] ?? 'Sin completar'); ?></div>
              </div>
            </div>

            <div class="mt-3">
              <h4 class="section-title mb-2">Sobre mí</h4>
              <div class="profile-about">
                <?php echo nl2br($e($perfil['contenidoPerfil'] ?? 'Todavía no completaste este espacio.')); ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="card glass-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Materias y cursos vinculados</h3>
            <span class="badge badge-light border"><?php echo count($relacionesAcademicas); ?> registros</span>
          </div>
          <div class="card-body">
            <?php if (empty($relacionesAcademicas)): ?>
              <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h4>No tenés materias vinculadas todavía</h4>
                <p class="mb-0">Cuando te asignen a un curso o una sección, aparecerá acá.</p>
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                  <thead>
                    <tr>
                      <th>Curso</th>
                      <th>Materia / Sección</th>
                      <th>Relación</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($relacionesAcademicas as $item): ?>
                      <tr>
                        <td><?php echo $e($item['nombreCurso'] ?? ''); ?></td>
                        <td><?php echo $e($item['tituloSeccion'] ?? ''); ?></td>
                        <td>
                          <?php if (($item['origen'] ?? '') === 'DOCENTE'): ?>
                            <span class="badge badge-info">Docente</span>
                          <?php else: ?>
                            <span class="badge badge-success">Estudiante</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
