<?php
$usuarios = ControladorUsuarios::crtUsuariosNoConectadosRecientes(60);
$usuariosNoConectados = count($usuarios);
$usuariosActivos = count(ControladorUsuarios::crtSeleccionarUsuario('activo', 1));
$usuariosInactivos = count(ControladorUsuarios::crtSeleccionarUsuario('activo', 0));
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
            <h1 class="entity-title mb-2">Usuarios sin conexión reciente</h1>
            <p class="entity-lead mb-3">
              Esta vista reúne a las cuentas activas que no se conectaron en los últimos 60 minutos o que todavía no registran ingreso.
            </p>
            <div class="d-flex flex-wrap" style="gap: .6rem;">
              <span class="entity-chip"><i class="fas fa-user-check"></i><?php echo (int) $usuariosActivos; ?> activos</span>
              <span class="entity-chip"><i class="fas fa-user-slash"></i><?php echo (int) $usuariosInactivos; ?> inactivos</span>
              <span class="entity-chip"><i class="fas fa-clock"></i><?php echo (int) $usuariosNoConectados; ?> sin conexión reciente</span>
            </div>
          </div>
          <div class="d-flex flex-wrap justify-content-end" style="gap: .75rem;">
            <a href="index.php?r=listado-usuarios" class="btn btn-outline-light btn-lg">
              <i class="fas fa-users mr-2"></i>Activos
            </a>
            <a href="index.php?r=usuarios-inactivos" class="btn btn-outline-light btn-lg">
              <i class="fas fa-user-slash mr-2"></i>Inactivos
            </a>
            <a href="index.php?r=usuarios-no-conectados" class="btn btn-light btn-lg text-primary">
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
            <div class="section-title">Sin conexión reciente</div>
            <div class="section-subtitle">Útil para detectar usuarios activos que no ingresaron en el último tramo</div>
          </div>
          <span class="badge badge-light border"><?php echo (int) $usuariosNoConectados; ?> registros</span>
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
                <th>Alta</th>
                <th>Última conexión</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($usuarios as $valor): ?>
                <?php
                  $ultimaConexion = !empty($valor['ultimaConexionFmt']) ? $valor['ultimaConexionFmt'] : 'Nunca';
                  $estado = empty($valor['ultimaConexion']) ? 'Sin registro' : 'No conectado';
                ?>
                <tr>
                  <td><?php echo $e($valor['apellidoUsuario'] ?? ''); ?></td>
                  <td><?php echo $e($valor['nombreUsuario'] ?? ''); ?></td>
                  <td><?php echo $e($valor['email'] ?? ''); ?></td>
                  <td><?php echo $e($valor['rol'] ?? ''); ?></td>
                  <td><?php echo $e($valor['fechaAltaFmt'] ?? ''); ?></td>
                  <td><?php echo $e($ultimaConexion); ?></td>
                  <td class="text-center">
                    <?php if ($estado === 'Sin registro'): ?>
                      <span class="badge badge-secondary">Nunca conectado</span>
                    <?php else: ?>
                      <span class="badge badge-warning">Fuera de rango</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <a href="index.php?r=editar-usuario&id=<?php echo (int) ($valor['idUsuario'] ?? 0); ?>" class="btn btn-success btn-sm" title="Ver detalle">
                      <i class="far fa-eye"></i>
                    </a>
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
