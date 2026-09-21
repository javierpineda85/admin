<?php
$e = static function ($valor) { return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8'); };
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Restablecer contraseña</title></head>
<body style="margin:0;background:#f4f1f8;font-family:Arial,sans-serif;color:#272033;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f1f8;padding:24px 12px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden;">
        <tr><td style="background:#59249b;padding:24px;color:#ffffff;font-size:22px;font-weight:bold;">Campus MenteMotion</td></tr>
        <tr><td style="padding:28px;">
          <p style="margin:0 0 16px;">Hola <?php echo $e($nombre); ?>,</p>
          <p style="margin:0 0 22px;line-height:1.55;">Recibimos una solicitud para restablecer tu contraseña. El enlace es de un solo uso y vence en una hora.</p>
          <p style="margin:0 0 24px;text-align:center;">
            <a href="<?php echo $e($url); ?>" style="display:inline-block;background:#59249b;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 24px;border-radius:8px;">Ver aquí</a>
          </p>
          <p style="margin:0;color:#6b6473;font-size:14px;line-height:1.5;">Si no hiciste esta solicitud, podés ignorar este mensaje. Tu contraseña seguirá siendo la misma.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
