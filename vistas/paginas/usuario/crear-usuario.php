<?php
$registro = ControladorUsuarios::crtGuardarUsuario();
$redirigir = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['success_message']));
$modoFormulario = 'crear';
$usuarioFormulario = [];
$perfilFormulario = [];
?>

<?php if ($redirigir): ?>
  <script>
    setTimeout(function () {
      window.location.href = 'index.php?r=listado-usuarios&c=usuario';
    }, 5200);
  </script>
<?php endif; ?>

<section class="content page-fade">
  <div class="container-fluid">
    <?php include __DIR__ . '/_formulario-usuario.php'; ?>
  </div>
</section>
