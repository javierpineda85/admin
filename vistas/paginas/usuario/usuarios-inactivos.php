<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idReactivar'])) {
  ControladorUsuarios::crtReactivarUsuario((int) $_POST['idReactivar']);
}

$usuarios = ControladorUsuarios::crtSeleccionarUsuario('activo', 0);
$usuariosInactivos = count($usuarios);
$usuariosActivos = count(ControladorUsuarios::crtSeleccionarUsuario('activo', 1));
$usuariosConectados = ControladorUsuarios::crtContarUsuariosConectadosRecientes(60);
$e = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
?>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4">
      <div class="entity-hero__content">
        <div class="d-flex flex-wrap align-items-start justify-content-between">
          <div class="mb-3 mb-lg-0">
            <span class="entity-kicker">Usuarios</span>
            <h1 class="entity-title mb-2">Usuarios inactivos</h1>
            <p class="entity-lead mb-3">
              Revisá las cuentas dadas de baja y reactivalas cuando corresponda, sin perder el historial de cada usuario.
            </p>
            <div class="d-flex flex-wrap" style="gap: .6rem;">
              <span class="entity-chip"><i class="fas fa-user-check"></i><?php echo (int) $usuariosActivos; ?> activos</span>
              <span class="entity-chip"><i class="fas fa-signal"></i><?php echo (int) $usuariosConectados; ?> conectados 60m</span>
              <span class="entity-chip"><i class="fas fa-user-slash"></i><?php echo (int) $usuariosInactivos; ?> inactivos</span>
            </div>
          </div>
          <div class="d-flex flex-wrap justify-content-end" style="gap: .75rem;">
            <a href="index.php?r=listado-usuarios" class="btn btn-outline-light btn-lg">
              <i class="fas fa-users mr-2"></i>Activos
            </a>
            <a href="index.php?r=usuarios-inactivos" class="btn btn-light btn-lg text-primary">
              <i class="fas fa-user-slash mr-2"></i>Inactivos
            </a>
            <a href="index.php?r=usuarios-no-conectados" class="btn btn-outline-light btn-lg">
              <i class="fas fa-clock mr-2"></i>No conectados
            </a>
            <a href="index.php?r=crear-usuario" class="btn btn-outline-light btn-lg">
              <i class="fas fa-user-plus mr-2"></i>Nuevo usuario
            </a>
          </div>
        </div>
      </div>
    </div>

    <div class="card glass-card">
      <div class="card-header bg-white border-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
          <div>
            <div class="section-title">Cuentas dadas de baja</div>
            <div class="section-subtitle">Podés ver el motivo, la fecha de baja y reactivarlas desde aquí</div>
          </div>
          <span class="badge badge-light border"><?php echo (int) $usuariosInactivos; ?> registros</span>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="example1" class="table table-hover table-striped table-borderless mb-0">
            <thead>
              <tr>
                <th>Apellido</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Baja</th>
                <th>Motivo</th>
                <th>Dado de baja por</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($usuarios as $valor): ?>
                <tr>
                  <td><?php echo $e($valor['apellidoUsuario'] ?? ''); ?></td>
                  <td><?php echo $e($valor['nombreUsuario'] ?? ''); ?></td>
                  <td><?php echo $e($valor['email'] ?? ''); ?></td>
                  <td><?php echo $e($valor['rol'] ?? ''); ?></td>
                  <td><?php echo $e($valor['fechaBajaFmt'] ?? ''); ?></td>
                  <td><?php echo $e($valor['motivoBaja'] ?? ''); ?></td>
                  <td><?php echo $e($valor['usuarioBajaNombre'] ?? ''); ?></td>
                  <td class="text-center">
                    <div class="d-inline-flex align-items-center gap-2">
                      <a href="index.php?r=editar-usuario&id=<?php echo (int) ($valor['idUsuario'] ?? 0); ?>" class="btn btn-success btn-sm" title="Ver detalle">
                        <i class="far fa-eye"></i>
                      </a>
                      <form method="post" class="d-inline">
                        <input type="hidden" value="<?php echo (int) ($valor['idUsuario'] ?? 0); ?>" name="idReactivar">
                        <button type="submit" class="btn btn-secondary btn-sm" title="Reactivar">
                          <i class="fas fa-undo"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
