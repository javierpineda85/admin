<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idReactivar'])) {
  ControladorUsuarios::crtReactivarUsuario((int) $_POST['idReactivar']);
  echo "<script>setTimeout(function(){ window.location.href = 'index.php?r=usuarios-inactivos&c=usuario'; }, 650);</script>";
}

$usuarios = ControladorUsuarios::crtSeleccionarUsuario('activo', 0);

?>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Usuarios inactivos</h3>
            <span class="badge badge-secondary"><?php echo count($usuarios); ?> registros</span>
          </div>
          <div class="card-body">
            <table id="example1" class="table table-bordered table-striped table-sm">
              <thead>
                <tr>
                  <th style="text-align: center;">Apellido</th>
                  <th style="text-align: center;">Nombre</th>
                  <th style="text-align: center;">Email</th>
                  <th style="text-align: center;">Rol</th>
                  <th style="text-align: center;">Baja</th>
                  <th style="text-align: center;">Motivo</th>
                  <th style="text-align: center;">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($usuarios as $valor) : ?>
                  <tr>
                    <td><?php echo htmlspecialchars($valor['apellidoUsuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($valor['nombreUsuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($valor['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($valor['rol'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars((string) ($valor['fechaBajaFmt'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) ($valor['motivoBaja'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-center">
                      <div class="d-inline-flex align-items-center gap-2">
                        <a href="index.php?r=editar-usuario&id=<?php echo (int) $valor['idUsuario']; ?>" class="btn btn-success btn-sm" title="Ver detalle">
                          <i class="far fa-eye"></i>
                        </a>
                        <form method="post" class="d-inline">
                          <input type="hidden" value="<?php echo (int) $valor["idUsuario"]; ?>" name="idReactivar">
                          <button type="submit" class="btn btn-secondary btn-sm" title="Reactivar"><i class="fas fa-undo"></i></button>
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
  </div>
</section>
