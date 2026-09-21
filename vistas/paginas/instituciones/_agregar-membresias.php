<div class="modal fade" id="modalAgregarMembresias" tabindex="-1" aria-labelledby="tituloAgregarMembresias" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable"><form method="post" action="index.php?r=superadmin" class="modal-content" id="formAgregarMembresias">
    <div class="modal-header">
      <h5 class="modal-title" id="tituloAgregarMembresias">Agregar membresías</h5>
      <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" name="accion_superadmin" value="agregar_membresias">
      <input type="hidden" name="superadmin_csrf" value="<?php echo $e($csrfSuperAdmin); ?>">
      <input type="hidden" name="idInstitucion" id="destinoMembresias" value="">
      <p>Agregar accesos a <strong id="nombreDestinoMembresias"></strong>. Las cuentas conservarán sus accesos en otras instituciones.</p>
      <fieldset class="mb-3">
        <legend class="h6">Roles para todos los usuarios seleccionados</legend>
        <?php foreach ($rolesDisponibles as $indice => $rol): ?>
          <div class="custom-control custom-checkbox custom-control-inline">
            <input type="checkbox" class="custom-control-input" name="roles[]" value="<?php echo $e($rol['codigo']); ?>" id="rolLote<?php echo $indice; ?>">
            <label class="custom-control-label" for="rolLote<?php echo $indice; ?>"><?php echo $e($rol['nombre']); ?></label>
          </div>
        <?php endforeach; ?>
        <small class="form-text text-muted">Por ejemplo, agregá primero los estudiantes con su rol y después el docente con el suyo.</small>
      </fieldset>
      <div class="row">
        <div class="col-md-7 form-group"><label for="buscarUsuarioMembresia">Buscar usuario</label><input type="search" class="form-control" id="buscarUsuarioMembresia" placeholder="Nombre, apellido, correo o ID"></div>
        <div class="col-md-5 form-group"><label for="origenUsuarioMembresia">Institución de origen</label><select class="form-control" id="origenUsuarioMembresia"><option value="">Todas</option>
          <?php foreach ($instituciones as $institucionFiltro): ?><option value="<?php echo (int)$institucionFiltro['idInstitucion']; ?>"><?php echo $e($institucionFiltro['nombre']); ?></option><?php endforeach; ?>
        </select></div>
      </div>
      <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
        <div><label class="mb-0 mr-3"><input type="checkbox" id="seleccionarUsuariosVisibles"> Seleccionar visibles</label><button type="button" class="btn btn-sm btn-outline-secondary" id="limpiarUsuariosSeleccionados">Limpiar selección</button></div>
        <span id="resumenUsuariosSeleccionados" role="status" aria-live="polite"></span>
      </div>
      <div class="table-responsive border" style="max-height:360px;overflow-y:auto">
        <table class="table table-hover table-sm mb-0" id="tablaUsuariosMembresia">
          <thead><tr><th scope="col">Elegir</th><th scope="col">ID</th><th scope="col">Usuario</th><th scope="col">Instituciones actuales</th><th scope="col">Destino</th></tr></thead>
          <tbody>
            <?php foreach ($usuariosDisponibles as $usuarioDisponible): ?>
              <?php
              $idDisponible = (int)$usuarioDisponible['idUsuario'];
              $nombreDisponible = trim($usuarioDisponible['apellidoUsuario'] . ', ' . $usuarioDisponible['nombreUsuario']);
              $vinculosDisponibles = $institucionesPorUsuario[$idDisponible] ?? [];
              ?>
              <tr data-instituciones="<?php echo $e(implode(',', array_keys($vinculosDisponibles))); ?>">
                <td><input type="checkbox" name="usuarios[]" value="<?php echo $idDisponible; ?>" aria-label="Seleccionar <?php echo $e($nombreDisponible); ?>"></td>
                <td><?php echo $idDisponible; ?></td>
                <td><strong><?php echo $e($nombreDisponible); ?></strong><br><small><?php echo $e($usuarioDisponible['email']); ?></small></td>
                <td><?php foreach ($vinculosDisponibles as $vinculoDisponible): ?><div><small><?php echo $e($vinculoDisponible['institucion']); ?><?php if (!(int)$vinculoDisponible['activo']): ?> (membresía suspendida)<?php endif; ?></small></div><?php endforeach; ?><?php if (!$vinculosDisponibles): ?><small class="text-muted">Sin membresías</small><?php endif; ?></td>
                <td class="estado-destino"></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <p class="text-muted text-center p-3 mb-0" id="sinUsuariosMembresia" hidden>No hay usuarios que coincidan con la búsqueda.</p>
      </div>
      <small class="form-text text-muted">Hasta 200 usuarios por operación. Las membresías existentes, incluidas las suspendidas, se administran desde el listado principal. Esta acción agrega accesos; no traslada cursos ni inscribe estudiantes en ellos.</small>
      <div class="alert alert-warning mt-2 mb-0" id="errorSeleccionMembresias" role="alert" hidden></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button class="btn btn-info" id="guardarMembresiasSeleccionadas" disabled>Agregar seleccionados</button></div>
  </form></div>
</div>
