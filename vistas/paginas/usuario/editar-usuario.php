<?php
$idUsuario = (int) ($_GET['id'] ?? 0);
$registro = ControladorUsuarios::crtModificarUsuario();
$baja = ControladorUsuarios::crtDarBajaUsuario();
$redirigir = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['success_message']));
$usuarioCompleto = $idUsuario > 0 ? ControladorUsuarios::crtUsuarioCompleto($idUsuario) : null;

if (empty($usuarioCompleto)) {
    $usuarioCompleto = [
        'idUsuario' => 0,
        'nombreUsuario' => '',
        'apellidoUsuario' => '',
        'email' => '',
        'rol' => 'ESTUDIANTE',
        'fechaAltaFmt' => '',
    ];
}

$perfilFormulario = [
    'dniPerfil' => $usuarioCompleto['dniPerfil'] ?? '',
    'telefonoPerfil' => $usuarioCompleto['telefonoPerfil'] ?? '',
    'fnacPerfil' => $usuarioCompleto['fnacPerfil'] ?? '',
    'domicilioPerfil' => $usuarioCompleto['domicilioPerfil'] ?? '',
    'provinciaPerfil' => $usuarioCompleto['provinciaPerfil'] ?? '',
    'contenidoPerfil' => $usuarioCompleto['contenidoPerfil'] ?? '',
];

$usuarioFormulario = $usuarioCompleto;
$modoFormulario = 'editar';
$mostrarBaja = ((int) ($usuarioCompleto['activo'] ?? 1) === 1);
?>

<?php if ($redirigir): ?>
  <script>
    setTimeout(function () {
      window.location.href = 'index.php?r=listado-usuarios&c=usuario';
    }, 650);
  </script>
<?php endif; ?>

<section class="content page-fade">
  <div class="container-fluid">
    <?php include __DIR__ . '/_formulario-usuario.php'; ?>
  </div>
</section>
