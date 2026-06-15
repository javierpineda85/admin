<?php
$registro = ControladorUsuarios::crtGuardarUsuario();
$modoFormulario = 'crear';
$usuarioFormulario = [];
$perfilFormulario = [];
?>

<section class="content page-fade">
  <div class="container-fluid">
    <?php include __DIR__ . '/_formulario-usuario.php'; ?>
  </div>
</section>
