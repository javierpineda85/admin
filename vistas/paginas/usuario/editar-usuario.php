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
$verUltimaConexion = ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente();
$imagenUsuario = ControladorUsuarios::rutaImagenUsuario($usuarioCompleto['imgUsuario'] ?? '', 'user2-160x160.jpg');
$e = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
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
    <div class="profile-hero mb-4">
      <div class="profile-hero__content">
        <div class="profile-hero__avatar">
          <img src="./img/<?php echo $e($imagenUsuario); ?>" alt="Foto de perfil">
        </div>
        <div class="profile-hero__copy">
          <span class="profile-kicker">Detalle de usuario</span>
          <h1 class="profile-title mb-2"><?php echo $e(trim((string) (($usuarioCompleto['nombreUsuario'] ?? '') . ' ' . ($usuarioCompleto['apellidoUsuario'] ?? ''))) ?: 'Usuario'); ?></h1>
          <p class="profile-lead mb-3">
            Revisa sus datos, su estado y la ultima conexion registrada antes de hacer cambios.
          </p>
          <div class="d-flex flex-wrap" style="gap: .6rem;">
            <span class="badge badge-light badge-pill px-3 py-2"><?php echo $e($usuarioCompleto['email'] ?? ''); ?></span>
            <span class="badge badge-info badge-pill px-3 py-2"><?php echo $e($usuarioCompleto['fechaAltaFmt'] ?? ''); ?></span>
            <span class="badge badge-<?php echo ((int) ($usuarioCompleto['activo'] ?? 0) === 1) ? 'success' : 'secondary'; ?> badge-pill px-3 py-2">
              <?php echo ((int) ($usuarioCompleto['activo'] ?? 0) === 1) ? 'Activo' : 'Dado de baja'; ?>
            </span>
            <?php if ($verUltimaConexion): ?>
              <span class="badge badge-dark badge-pill px-3 py-2"><?php echo $e($usuarioCompleto['ultimaConexionFmt'] ?? 'Sin registro'); ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <?php include __DIR__ . '/_formulario-usuario.php'; ?>
  </div>
</section>
