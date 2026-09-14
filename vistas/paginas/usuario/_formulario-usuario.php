<?php
$modoFormulario = $modoFormulario ?? 'crear';
$usuarioFormulario = $usuarioFormulario ?? [];
$perfilFormulario = $perfilFormulario ?? [];
$mostrarBaja = $mostrarBaja ?? false;
$e = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
$fechaAltaVista = $usuarioFormulario['fechaAltaFmt'] ?? '';
$gestionInstitucional = class_exists('ControladorInstitucion', false) && ControladorInstitucion::activo();
$soloMembresia = $gestionInstitucional && $modoFormulario === 'editar';
$rolesActuales = preg_split('/\s*·\s*/', strtoupper((string) ($usuarioFormulario['rol'] ?? 'ESTUDIANTE'))) ?: ['ESTUDIANTE'];
?>

<div class="card glass-card">
  <div class="card-header">
    <div>
      <h3 class="card-title mb-1"><?php echo $modoFormulario === 'editar' ? 'Editar usuario' : 'Crear usuario'; ?></h3>
      <small class="text-muted">
        <?php if ($modoFormulario === 'editar'): ?>
          <?php echo $soloMembresia ? 'Ajustá los roles y el estado de la membresía institucional.' : 'Ajusta los datos de cuenta, perfil y estado del usuario.'; ?>
        <?php else: ?>
          Cargá una identidad nueva o incorporá por email una identidad global existente.
        <?php endif; ?>
      </small>
    </div>
  </div>

  <div class="card-body">
    <div class="profile-form-banner mb-4">
      <div>
        <p class="mb-1 text-uppercase small font-weight-bold">Cuenta</p>
        <h4 class="mb-0"><?php echo $modoFormulario === 'editar' ? 'Gestión de usuario' : 'Alta de usuario'; ?></h4>
      </div>
      <?php if ($modoFormulario === 'editar'): ?>
        <span class="badge badge-light border px-3 py-2">Fecha de alta: <?php echo $e($fechaAltaVista !== '' ? $fechaAltaVista : 'Auto'); ?></span>
      <?php else: ?>
        <span class="badge badge-light border px-3 py-2">La fecha de alta se guarda automáticamente</span>
      <?php endif; ?>
    </div>

    <?php if ($gestionInstitucional): ?>
      <div class="alert alert-info border-0">
        La identidad, contraseña, foto y perfil son globales. Al editar una cuenta existente, esta institución sólo administra su membresía y sus roles.
      </div>
    <?php endif; ?>

    <form action="" method="post" autocomplete="off" enctype="multipart/form-data">
      <input type="hidden" name="idUsuario" value="<?php echo (int) ($usuarioFormulario['idUsuario'] ?? 0); ?>">

      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label>Nombre</label>
            <input type="text" class="form-control" placeholder="Juan" name="nombreUsuario" value="<?php echo $e($usuarioFormulario['nombreUsuario'] ?? ''); ?>" <?php echo $soloMembresia ? 'readonly' : ''; ?> required>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Apellido</label>
            <input type="text" class="form-control" placeholder="Pérez" name="apellidoUsuario" value="<?php echo $e($usuarioFormulario['apellidoUsuario'] ?? ''); ?>" <?php echo $soloMembresia ? 'readonly' : ''; ?> required>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control" placeholder="usuario@correo.com" name="emailUsuario" value="<?php echo $e($usuarioFormulario['email'] ?? $usuarioFormulario['emailUsuario'] ?? ''); ?>" <?php echo $soloMembresia ? 'readonly' : ''; ?> required>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Rol</label>
            <select class="custom-select" name="<?php echo $gestionInstitucional ? 'roles[]' : 'rol'; ?>" <?php echo $gestionInstitucional ? 'multiple size="3"' : ''; ?> required>
              <?php foreach (['ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE'] as $rol): ?>
                <option value="<?php echo $rol; ?>" <?php echo in_array($rol, $rolesActuales, true) ? 'selected' : ''; ?>><?php echo $rol; ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($gestionInstitucional): ?><small class="form-text text-muted">Podés seleccionar más de un rol.</small><?php endif; ?>
          </div>
        </div>
        <?php if (!$soloMembresia): ?>
        <div class="col-md-4">
          <div class="form-group">
            <label>Contraseña</label>
            <input type="password" class="form-control" placeholder="<?php echo $modoFormulario === 'editar' ? 'Dejar vacío para no cambiar' : 'Asignar contraseña'; ?>" name="passUsuario">
            <small class="form-text text-muted"><?php echo $modoFormulario === 'editar' ? 'Opcional. Solo completalo si querés forzar un nuevo acceso.' : ($gestionInstitucional ? 'Obligatoria sólo si el email todavía no posee una identidad global.' : 'Se guardará la contraseña inicial del usuario.'); ?></small>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label>Foto de perfil</label>
            <div class="classroom-file profile-upload">
              <input type="file" class="classroom-file__input profile-upload__input profile-file-input" id="imgUsuarioCampo" name="imgUsuario" accept="image/*">
              <label class="classroom-file__button profile-upload__button" for="imgUsuarioCampo">
                <i class="fas fa-image mr-2"></i>Seleccionar foto
              </label>
              <span class="classroom-file__name profile-file-name">
                <?php echo !empty($usuarioFormulario['imgUsuario']) ? $e($usuarioFormulario['imgUsuario']) : 'Ningún archivo seleccionado'; ?>
              </span>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <?php if (!$soloMembresia): ?>
      <div class="mt-4">
        <h4 class="section-title mb-3">Perfil personal</h4>
        <div class="row">
          <div class="col-md-3">
            <div class="form-group">
              <label>DNI</label>
              <input type="text" class="form-control" placeholder="12345678" name="dniPerfil" value="<?php echo $e($perfilFormulario['dniPerfil'] ?? ''); ?>" maxlength="8">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Teléfono</label>
              <input type="text" class="form-control" placeholder="2612223333" name="telefonoPerfil" value="<?php echo $e($perfilFormulario['telefonoPerfil'] ?? ''); ?>">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Fecha nac.</label>
              <input type="date" class="form-control" name="fnacPerfil" value="<?php echo $e($perfilFormulario['fnacPerfil'] ?? ''); ?>">
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label>Provincia</label>
              <input type="text" class="form-control" placeholder="Mendoza" name="provinciaPerfil" value="<?php echo $e($perfilFormulario['provinciaPerfil'] ?? ''); ?>">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Domicilio</label>
              <input type="text" class="form-control" placeholder="Av. San Martín 123" name="domicilioPerfil" value="<?php echo $e($perfilFormulario['domicilioPerfil'] ?? ''); ?>">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <label>Sobre mí</label>
              <textarea class="form-control" rows="4" name="contenidoPerfil" placeholder="Contá algo sobre el usuario..."><?php echo $e($perfilFormulario['contenidoPerfil'] ?? ''); ?></textarea>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="form-actions mt-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div class="text-muted small">
            <?php if ($modoFormulario === 'editar'): ?>
              <?php echo $soloMembresia ? 'Los cambios se aplican sólo a la membresía de esta institución.' : 'Los cambios se aplican sobre la cuenta seleccionada.'; ?>
            <?php else: ?>
              El sistema asignará la fecha de alta automáticamente.
            <?php endif; ?>
          </div>
          <div class="d-flex gap-2">
            <button type="reset" class="btn btn-light border">Limpiar</button>
            <button type="submit" class="btn btn-primary"><?php echo $modoFormulario === 'editar' ? 'Guardar cambios' : 'Crear usuario'; ?></button>
          </div>
        </div>
      </div>
    </form>

    <?php if ($modoFormulario === 'editar' && $mostrarBaja): ?>
      <hr class="my-4">
      <div class="danger-panel">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <h4 class="section-title mb-1 text-danger">Dar de baja</h4>
            <small class="text-muted"><?php echo $gestionInstitucional ? 'Se desactiva sólo el acceso a esta institución; la identidad global se conserva.' : 'El usuario no se elimina. Solo queda inactivo con historial.'; ?></small>
          </div>
        </div>

        <form action="" method="post">
          <input type="hidden" name="accion_usuario" value="baja_usuario">
          <input type="hidden" name="idUsuario" value="<?php echo (int) ($usuarioFormulario['idUsuario'] ?? 0); ?>">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Fecha de baja</label>
                <input type="date" class="form-control" name="fechaBaja" value="<?php echo date('Y-m-d'); ?>">
              </div>
            </div>
            <div class="col-md-8">
              <div class="form-group">
                <label>Motivo</label>
                <input type="text" class="form-control" name="motivoBaja" placeholder="Ej: Baja administrativa">
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-danger">Dar de baja</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>
