<?php
$e = static function ($valor) { return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8'); };
$datosPanel = ControladorSuperAdmin::crtDatosPanel();
$resumen = $datosPanel['resumen'];
$instituciones = $datosPanel['instituciones'];
$mensajeOk = $_SESSION['success_message'] ?? '';
$mensajeError = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
$csrfSuperAdmin = ControladorSuperAdmin::csrf();
?>

<div class="container-fluid py-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div><h1 class="h3 mb-1">Plataforma MenteMotion</h1><p class="text-muted mb-0">Administración global de instituciones y accesos.</p></div>
    <button class="btn btn-primary mt-2 mt-sm-0" data-toggle="modal" data-target="#modalCrearInstitucion"><i class="fas fa-plus mr-1"></i>Nueva institución</button>
  </div>

  <?php if ($mensajeOk): ?><div class="alert alert-success"><?php echo $e($mensajeOk); ?></div><?php endif; ?>
  <?php if ($mensajeError): ?><div class="alert alert-danger"><?php echo $e($mensajeError); ?></div><?php endif; ?>

  <div class="row">
    <?php foreach ([
      ['Instituciones', $resumen['instituciones'], 'fa-university', 'info'],
      ['Activas', $resumen['institucionesActivas'], 'fa-check-circle', 'success'],
      ['Suspendidas', $resumen['institucionesSuspendidas'], 'fa-pause-circle', 'warning'],
      ['Usuarios globales activos', $resumen['usuariosGlobalesActivos'], 'fa-users', 'primary'],
      ['Membresías activas', $resumen['membresiasActivas'], 'fa-id-badge', 'secondary'],
    ] as [$titulo, $cantidad, $icono, $color]): ?>
      <div class="col-12 col-sm-6 col-lg">
        <div class="small-box bg-<?php echo $e($color); ?>"><div class="inner"><h3><?php echo (int) $cantidad; ?></h3><p><?php echo $e($titulo); ?></p></div><div class="icon"><i class="fas <?php echo $e($icono); ?>"></i></div></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-header"><h3 class="card-title">Instituciones</h3></div>
    <div class="card-body table-responsive p-0">
      <table class="table table-hover mb-0">
        <thead><tr><th>Institución</th><th>Estado</th><th>Usuarios</th><th>Administradores</th><th>Alta</th><th class="text-right">Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($instituciones as $institucion): ?>
            <?php $idInstitucion = (int) $institucion['idInstitucion']; $activa = (int) $institucion['activo'] === 1; ?>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <?php if (!empty($institucion['logo'])): ?><img src="<?php echo $e($institucion['logo']); ?>" alt="" class="img-circle mr-2" style="width:38px;height:38px;object-fit:cover"><?php else: ?><span class="bg-light border rounded-circle text-center mr-2" style="width:38px;height:38px;line-height:36px"><i class="fas fa-university text-muted"></i></span><?php endif; ?>
                  <div><strong><?php echo $e($institucion['nombre']); ?></strong><br><small class="text-muted"><?php echo $e($institucion['slug']); ?></small></div>
                </div>
              </td>
              <td><span class="badge badge-<?php echo $activa ? 'success' : 'secondary'; ?>"><?php echo $activa ? 'Activa' : 'Suspendida'; ?></span><?php if (!$activa && !empty($institucion['motivoBaja'])): ?><br><small class="text-muted"><?php echo $e($institucion['motivoBaja']); ?></small><?php endif; ?></td>
              <td><?php echo (int) $institucion['totalUsuarios']; ?></td>
              <td><?php echo (int) $institucion['totalAdministradores']; ?></td>
              <td><?php echo $e($institucion['fechaAlta']); ?></td>
              <td class="text-right text-nowrap">
                <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editarInstitucion<?php echo $idInstitucion; ?>" title="Editar"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#administradorInstitucion<?php echo $idInstitucion; ?>" title="Asignar administrador" <?php echo $activa ? '' : 'disabled'; ?>><i class="fas fa-user-shield"></i></button>
                <button class="btn btn-sm btn-outline-<?php echo $activa ? 'warning' : 'success'; ?>" data-toggle="modal" data-target="#estadoInstitucion<?php echo $idInstitucion; ?>" title="<?php echo $activa ? 'Suspender' : 'Activar'; ?>"><i class="fas <?php echo $activa ? 'fa-pause' : 'fa-play'; ?>"></i></button>
              </td>
            </tr>

          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php foreach ($instituciones as $institucion): ?>
  <?php $idInstitucion = (int) $institucion['idInstitucion']; $activa = (int) $institucion['activo'] === 1; ?>
  <div class="modal fade" id="editarInstitucion<?php echo $idInstitucion; ?>" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content"><div class="modal-header"><h5 class="modal-title">Editar institución</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body">
    <input type="hidden" name="accion_superadmin" value="editar_institucion"><input type="hidden" name="superadmin_csrf" value="<?php echo $e($csrfSuperAdmin); ?>"><input type="hidden" name="idInstitucion" value="<?php echo $idInstitucion; ?>">
    <div class="form-group"><label>Nombre</label><input name="nombre" class="form-control" maxlength="150" required value="<?php echo $e($institucion['nombre']); ?>"></div>
    <div class="form-group"><label>Slug</label><input name="slug" class="form-control" maxlength="120" required value="<?php echo $e($institucion['slug']); ?>"></div>
    <div class="form-group"><label>Logo</label><input name="logo" class="form-control" maxlength="255" value="<?php echo $e($institucion['logo']); ?>" placeholder="img/instituciones/logo.png"><small class="form-text text-muted">Ruta dentro de img/instituciones/.</small></div>
  </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar</button></div></form></div></div>

  <div class="modal fade" id="administradorInstitucion<?php echo $idInstitucion; ?>" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content"><div class="modal-header"><h5 class="modal-title">Asignar administrador</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body">
    <input type="hidden" name="accion_superadmin" value="asignar_administrador"><input type="hidden" name="superadmin_csrf" value="<?php echo $e($csrfSuperAdmin); ?>"><input type="hidden" name="idInstitucion" value="<?php echo $idInstitucion; ?>">
    <p>Se agregará el rol ADMINISTRADOR en <strong><?php echo $e($institucion['nombre']); ?></strong> a una cuenta global existente.</p><div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required autocomplete="off"></div>
  </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-info">Asignar</button></div></form></div></div>

  <div class="modal fade" id="estadoInstitucion<?php echo $idInstitucion; ?>" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content"><div class="modal-header"><h5 class="modal-title"><?php echo $activa ? 'Suspender' : 'Activar'; ?> institución</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body">
    <input type="hidden" name="accion_superadmin" value="<?php echo $activa ? 'suspender_institucion' : 'activar_institucion'; ?>"><input type="hidden" name="superadmin_csrf" value="<?php echo $e($csrfSuperAdmin); ?>"><input type="hidden" name="idInstitucion" value="<?php echo $idInstitucion; ?>">
    <p><?php echo $activa ? 'Los usuarios perderán el acceso a esta institución mientras permanezca suspendida.' : 'Las membresías activas volverán a habilitar el acceso institucional.'; ?></p>
    <?php if ($activa): ?><div class="form-group"><label>Motivo de suspensión</label><textarea name="motivoBaja" class="form-control" maxlength="500" required></textarea></div><?php endif; ?>
  </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-<?php echo $activa ? 'warning' : 'success'; ?>"><?php echo $activa ? 'Suspender' : 'Activar'; ?></button></div></form></div></div>
<?php endforeach; ?>

<div class="modal fade" id="modalCrearInstitucion" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content"><div class="modal-header"><h5 class="modal-title">Nueva institución</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body">
  <input type="hidden" name="accion_superadmin" value="crear_institucion"><input type="hidden" name="superadmin_csrf" value="<?php echo $e($csrfSuperAdmin); ?>">
  <div class="form-group"><label>Nombre</label><input name="nombre" class="form-control" maxlength="150" required></div>
  <div class="form-group"><label>Slug</label><input name="slug" class="form-control" maxlength="120" placeholder="Se genera desde el nombre si queda vacío"></div>
  <div class="form-group"><label>Logo</label><input name="logo" class="form-control" maxlength="255" placeholder="img/instituciones/logo.png"><small class="form-text text-muted">Opcional. Ruta dentro de img/instituciones/.</small></div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-primary">Crear institución</button></div></form></div></div>
