<?php
$e = static function ($valor) { return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8'); };
$datosPanel = ControladorSuperAdmin::crtDatosPanel();
$resumen = $datosPanel['resumen'];
$instituciones = $datosPanel['instituciones'];
$membresias = $datosPanel['membresias'];
$rolesDisponibles = $datosPanel['roles'];
$usuariosDisponibles = $datosPanel['usuarios'];
$institucionesPorUsuario = [];
foreach ($membresias as $membresia) {
  $institucionesPorUsuario[(int)$membresia['id_usuario']][(int)$membresia['id_institucion']] = $membresia;
}
$mensajeOk = $_SESSION['success_message'] ?? '';
$mensajeError = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
$csrfSuperAdmin = ControladorSuperAdmin::csrf();
$logoSeguro = static function ($ruta) {
  return is_string($ruta) && preg_match('~^img/instituciones/[a-zA-Z0-9_-]+\.(png|jpe?g|webp|gif)$~i', $ruta) ? $ruta : '';
};
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

  <div class="alert alert-light border"><i class="fas fa-info-circle text-info mr-1"></i>Una <strong>membresía</strong> vincula una cuenta global con una institución y define sus roles allí. No es un plan comercial.</div>

  <div class="card">
    <div class="card-header"><h3 class="card-title">Instituciones</h3></div>
    <div class="card-body table-responsive p-0">
      <table class="table table-hover mb-0">
        <thead><tr><th>Institución</th><th>Estado</th><th>Usuarios</th><th>Administradores</th><th>Alta</th><th class="text-right">Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($instituciones as $institucion): ?>
            <?php $idInstitucion = (int) $institucion['idInstitucion']; $activa = (int) $institucion['activo'] === 1; $logoListado = $logoSeguro($institucion['logo'] ?? ''); ?>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <?php if ($logoListado !== ''): ?><img src="<?php echo $e($logoListado); ?>" alt="" class="img-circle mr-2" style="width:38px;height:38px;object-fit:cover"><?php else: ?><span class="bg-light border rounded-circle text-center mr-2" style="width:38px;height:38px;line-height:36px"><i class="fas fa-university text-muted"></i></span><?php endif; ?>
                  <div><strong><?php echo $e($institucion['nombre']); ?></strong><br><small class="text-muted"><?php echo $e($institucion['slug']); ?></small></div>
                </div>
              </td>
              <td><span class="badge badge-<?php echo $activa ? 'success' : 'secondary'; ?>"><?php echo $activa ? 'Activa' : 'Suspendida'; ?></span><?php if (!$activa && !empty($institucion['motivoBaja'])): ?><br><small class="text-muted"><?php echo $e($institucion['motivoBaja']); ?></small><?php endif; ?></td>
              <td><?php echo (int) $institucion['totalUsuarios']; ?></td>
              <td><?php echo (int) $institucion['totalAdministradores']; ?></td>
              <td><?php echo $e($institucion['fechaAlta']); ?></td>
              <td class="text-right text-nowrap">
                <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editarInstitucion<?php echo $idInstitucion; ?>" title="Editar"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#modalAgregarMembresias" data-institucion-id="<?php echo $idInstitucion; ?>" data-institucion-nombre="<?php echo $e($institucion['nombre']); ?>" title="Agregar membresías" aria-label="Agregar membresías a <?php echo $e($institucion['nombre']); ?>" <?php echo $activa ? '' : 'disabled'; ?>><i class="fas fa-user-plus"></i></button>
                <button class="btn btn-sm btn-outline-<?php echo $activa ? 'warning' : 'success'; ?>" data-toggle="modal" data-target="#estadoInstitucion<?php echo $idInstitucion; ?>" title="<?php echo $activa ? 'Suspender' : 'Activar'; ?>"><i class="fas <?php echo $activa ? 'fa-pause' : 'fa-play'; ?>"></i></button>
              </td>
            </tr>

          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card" id="membresias">
    <div class="card-header"><h3 class="card-title">Membresías institucionales</h3></div>
    <div class="card-body">
      <div class="row mb-3"><div class="col-md-7"><input id="buscarMembresia" class="form-control" placeholder="Buscar por nombre, email, institución o rol"></div><div class="col-md-5 mt-2 mt-md-0"><select id="filtrarInstitucion" class="form-control"><option value="">Todas las instituciones</option><?php foreach ($instituciones as $institucion): ?><option value="<?php echo $e(strtolower($institucion['nombre'])); ?>"><?php echo $e($institucion['nombre']); ?></option><?php endforeach; ?></select></div></div>
      <div class="table-responsive"><table class="table table-hover" id="tablaMembresias"><thead><tr><th>Usuario global</th><th>Institución</th><th>Roles</th><th>Estado</th><th>Alta</th><th class="text-right">Acciones</th></tr></thead><tbody>
      <?php foreach ($membresias as $membresia): ?><?php $idMembresia=(int)$membresia['idUsuarioInstitucion']; $membresiaActiva=(int)$membresia['activo']===1; $textoBusqueda=strtolower(trim(($membresia['nombreUsuario']??'').' '.($membresia['apellidoUsuario']??'').' '.$membresia['email'].' '.$membresia['institucion'].' '.implode(' ',$membresia['roles']))); ?>
        <tr data-busqueda="<?php echo $e($textoBusqueda); ?>" data-institucion="<?php echo $e(strtolower($membresia['institucion'])); ?>"><td><strong><?php echo $e(trim(($membresia['nombreUsuario']??'').' '.($membresia['apellidoUsuario']??''))); ?></strong><br><small><?php echo $e($membresia['email']); ?></small></td><td><?php echo $e($membresia['institucion']); ?></td><td><?php foreach ($membresia['roles'] as $rol): ?><span class="badge badge-info mr-1"><?php echo $e($rol); ?></span><?php endforeach; ?></td><td><span class="badge badge-<?php echo $membresiaActiva?'success':'secondary'; ?>"><?php echo $membresiaActiva?'Activa':'Suspendida'; ?></span><?php if (!$membresiaActiva && !empty($membresia['motivoBaja'])): ?><br><small class="text-muted"><?php echo $e($membresia['motivoBaja']); ?></small><?php endif; ?></td><td><?php echo $e($membresia['fechaAlta']); ?></td><td class="text-right text-nowrap"><button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#rolesMembresia<?php echo $idMembresia; ?>" title="Editar roles"><i class="fas fa-user-tag"></i></button> <button class="btn btn-sm btn-outline-<?php echo $membresiaActiva?'warning':'success'; ?>" data-toggle="modal" data-target="#estadoMembresia<?php echo $idMembresia; ?>" title="<?php echo $membresiaActiva?'Suspender':'Reactivar'; ?>"><i class="fas <?php echo $membresiaActiva?'fa-pause':'fa-play'; ?>"></i></button></td></tr>
      <?php endforeach; ?></tbody></table></div>
      <p id="sinMembresias" class="text-muted text-center mb-0" style="display:none">No hay membresías que coincidan con la búsqueda.</p>
    </div>
  </div>
</div>

<?php foreach ($instituciones as $institucion): ?>
  <?php $idInstitucion = (int) $institucion['idInstitucion']; $activa = (int) $institucion['activo'] === 1; ?>
  <div class="modal fade" id="editarInstitucion<?php echo $idInstitucion; ?>" tabindex="-1"><div class="modal-dialog"><form method="post" action="index.php?r=superadmin" enctype="multipart/form-data" class="modal-content"><div class="modal-header"><h5 class="modal-title">Editar institución</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body">
    <input type="hidden" name="accion_superadmin" value="editar_institucion"><input type="hidden" name="superadmin_csrf" value="<?php echo $e($csrfSuperAdmin); ?>"><input type="hidden" name="idInstitucion" value="<?php echo $idInstitucion; ?>">
    <div class="form-group"><label>Nombre</label><input name="nombre" class="form-control" maxlength="150" required value="<?php echo $e($institucion['nombre']); ?>"></div>
    <div class="form-group"><label>Slug</label><input name="slug" class="form-control" maxlength="120" required value="<?php echo $e($institucion['slug']); ?>"></div>
    <div class="form-group"><label>Logo</label><?php $logoEditar=$logoSeguro($institucion['logo']??''); ?><div class="mb-2"><img class="vista-previa-logo border rounded p-1" src="<?php echo $e($logoEditar); ?>" alt="Vista previa" style="<?php echo $logoEditar===''?'display:none;':''; ?>max-width:160px;max-height:90px"></div><div class="custom-file"><input type="file" name="logoArchivo" class="custom-file-input selector-logo" id="logo<?php echo $idInstitucion; ?>" accept="image/png,image/jpeg,image/webp,image/gif"><label class="custom-file-label" for="logo<?php echo $idInstitucion; ?>">Elegir imagen</label></div><small class="form-text text-muted">PNG, JPG, WEBP o GIF. Máximo 2 MB.</small><?php if($logoEditar!==''): ?><div class="custom-control custom-checkbox mt-2"><input type="checkbox" class="custom-control-input" name="quitarLogo" value="1" id="quitarLogo<?php echo $idInstitucion; ?>"><label class="custom-control-label" for="quitarLogo<?php echo $idInstitucion; ?>">Quitar logo actual</label></div><?php endif; ?></div>
  </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar</button></div></form></div></div>

  <div class="modal fade" id="estadoInstitucion<?php echo $idInstitucion; ?>" tabindex="-1"><div class="modal-dialog"><form method="post" action="index.php?r=superadmin" class="modal-content"><div class="modal-header"><h5 class="modal-title"><?php echo $activa ? 'Suspender' : 'Activar'; ?> institución</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body">
    <input type="hidden" name="accion_superadmin" value="<?php echo $activa ? 'suspender_institucion' : 'activar_institucion'; ?>"><input type="hidden" name="superadmin_csrf" value="<?php echo $e($csrfSuperAdmin); ?>"><input type="hidden" name="idInstitucion" value="<?php echo $idInstitucion; ?>">
    <p><?php echo $activa ? 'Los usuarios perderán el acceso a esta institución mientras permanezca suspendida.' : 'Las membresías activas volverán a habilitar el acceso institucional.'; ?></p>
    <?php if ($activa): ?><div class="form-group"><label>Motivo de suspensión</label><textarea name="motivoBaja" class="form-control" maxlength="500" required></textarea></div><?php endif; ?>
  </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-<?php echo $activa ? 'warning' : 'success'; ?>"><?php echo $activa ? 'Suspender' : 'Activar'; ?></button></div></form></div></div>
<?php endforeach; ?>

<?php foreach ($membresias as $membresia): ?>
  <?php $idMembresia=(int)$membresia['idUsuarioInstitucion']; $membresiaActiva=(int)$membresia['activo']===1; ?>
  <div class="modal fade" id="rolesMembresia<?php echo $idMembresia; ?>" tabindex="-1"><div class="modal-dialog"><form method="post" action="index.php?r=superadmin" class="modal-content"><div class="modal-header"><h5 class="modal-title">Editar roles institucionales</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body">
    <input type="hidden" name="accion_superadmin" value="actualizar_roles_membresia"><input type="hidden" name="superadmin_csrf" value="<?php echo $e($csrfSuperAdmin); ?>"><input type="hidden" name="idUsuarioInstitucion" value="<?php echo $idMembresia; ?>">
    <p><strong><?php echo $e($membresia['email']); ?></strong><br><span class="text-muted"><?php echo $e($membresia['institucion']); ?></span></p>
    <?php foreach($rolesDisponibles as $indice=>$rol): ?><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" name="roles[]" value="<?php echo $e($rol['codigo']); ?>" id="rolEditar<?php echo $idMembresia.'_'.$indice; ?>" <?php echo in_array($rol['codigo'],$membresia['roles'],true)?'checked':''; ?>><label class="custom-control-label" for="rolEditar<?php echo $idMembresia.'_'.$indice; ?>"><?php echo $e($rol['nombre']); ?></label></div><?php endforeach; ?>
  </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar roles</button></div></form></div></div>

  <div class="modal fade" id="estadoMembresia<?php echo $idMembresia; ?>" tabindex="-1"><div class="modal-dialog"><form method="post" action="index.php?r=superadmin" class="modal-content"><div class="modal-header"><h5 class="modal-title"><?php echo $membresiaActiva?'Suspender':'Reactivar'; ?> membresía</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body">
    <input type="hidden" name="accion_superadmin" value="<?php echo $membresiaActiva?'suspender_membresia':'activar_membresia'; ?>"><input type="hidden" name="superadmin_csrf" value="<?php echo $e($csrfSuperAdmin); ?>"><input type="hidden" name="idUsuarioInstitucion" value="<?php echo $idMembresia; ?>">
    <p><?php echo $membresiaActiva?'La cuenta perderá el acceso a esta institución, sin eliminar su historial ni sus roles.':'La cuenta recuperará el acceso con los roles que tiene asignados.'; ?></p><?php if($membresiaActiva): ?><div class="form-group"><label>Motivo de suspensión</label><textarea name="motivoBaja" class="form-control" maxlength="500" required></textarea></div><?php endif; ?>
  </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-<?php echo $membresiaActiva?'warning':'success'; ?>"><?php echo $membresiaActiva?'Suspender':'Reactivar'; ?></button></div></form></div></div>
<?php endforeach; ?>

<div class="modal fade" id="modalCrearInstitucion" tabindex="-1"><div class="modal-dialog"><form method="post" action="index.php?r=superadmin" enctype="multipart/form-data" class="modal-content"><div class="modal-header"><h5 class="modal-title">Nueva institución</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body">
  <input type="hidden" name="accion_superadmin" value="crear_institucion"><input type="hidden" name="superadmin_csrf" value="<?php echo $e($csrfSuperAdmin); ?>">
  <div class="form-group"><label>Nombre</label><input name="nombre" class="form-control" maxlength="150" required></div>
  <div class="form-group"><label>Slug</label><input name="slug" class="form-control" maxlength="120" placeholder="Se genera desde el nombre si queda vacío"></div>
  <div class="form-group"><label>Logo</label><div class="mb-2"><img class="vista-previa-logo border rounded p-1" alt="Vista previa" style="display:none;max-width:160px;max-height:90px"></div><div class="custom-file"><input type="file" name="logoArchivo" class="custom-file-input selector-logo" id="logoNueva" accept="image/png,image/jpeg,image/webp,image/gif"><label class="custom-file-label" for="logoNueva">Elegir imagen</label></div><small class="form-text text-muted">Opcional. PNG, JPG, WEBP o GIF. Máximo 2 MB.</small></div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-primary">Crear institución</button></div></form></div></div>

<?php require __DIR__ . '/_agregar-membresias.php'; ?>
<script src="js/superadmin-membresias.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.selector-logo').forEach(function (input) {
    input.addEventListener('change', function () {
      var archivo = this.files && this.files[0];
      var formulario = this.closest('form');
      var previa = formulario ? formulario.querySelector('.vista-previa-logo') : null;
      var etiqueta = this.nextElementSibling;
      if (!archivo || !previa) return;
      if (etiqueta) etiqueta.textContent = archivo.name;
      var lector = new FileReader();
      lector.onload = function (evento) { previa.src = evento.target.result; previa.style.display = 'block'; };
      lector.readAsDataURL(archivo);
    });
  });
  var buscar = document.getElementById('buscarMembresia');
  var filtrar = document.getElementById('filtrarInstitucion');
  var filas = Array.prototype.slice.call(document.querySelectorAll('#tablaMembresias tbody tr'));
  function aplicarFiltros() {
    var texto = (buscar.value || '').toLowerCase().trim();
    var institucion = (filtrar.value || '').toLowerCase();
    var visibles = 0;
    filas.forEach(function (fila) {
      var mostrar = fila.dataset.busqueda.indexOf(texto) !== -1 && (!institucion || fila.dataset.institucion === institucion);
      fila.style.display = mostrar ? '' : 'none';
      if (mostrar) visibles++;
    });
    document.getElementById('sinMembresias').style.display = visibles ? 'none' : 'block';
  }
  if (buscar && filtrar) { buscar.addEventListener('input', aplicarFiltros); filtrar.addEventListener('change', aplicarFiltros); }
});
</script>
