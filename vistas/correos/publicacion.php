<!doctype html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Novedades de tu curso</title></head>
<body style="margin:0;padding:0;background-color:#f1f5f9;color:#1e293b;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;"><tr><td align="center" style="padding:32px 16px;">
  <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:16px;">
    <tr><td style="padding:28px 32px;background-color:#17365d;border-radius:16px 16px 0 0;color:#ffffff;">
      <p style="margin:0 0 10px;font-size:13px;letter-spacing:1px;">CAMPUS VIRTUAL</p>
      <h1 style="margin:0;font-size:25px;line-height:1.3;"><?php echo $e($datos['asuntoEmail'] ?? 'Nueva publicación'); ?></h1>
    </td></tr>
    <tr><td style="padding:32px;">
      <p style="margin:0 0 18px;font-size:17px;line-height:1.6;">Hola <?php echo $e($nombre); ?>,</p>
      <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#475569;">Se publicó nuevo contenido en tu curso. Ya podés ingresar al campus para verlo.</p>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td style="padding:20px;background-color:#f8fafc;border-left:4px solid #2563eb;">
        <h2 style="margin:0 0 10px;font-size:20px;line-height:1.4;"><?php echo $e($datos['tituloEmail'] ?? 'Nueva publicación'); ?></h2>
        <p style="margin:0;font-size:14px;line-height:1.6;color:#64748b;"><?php echo $e($datos['contextoEmail'] ?? ''); ?></p>
      </td></tr></table>
      <table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px 0;"><tr><td align="center" bgcolor="#2563eb" style="border-radius:8px;">
        <a href="<?php echo $e($url); ?>" style="display:inline-block;padding:15px 30px;border:1px solid #2563eb;border-radius:8px;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;">Ver aquí</a>
      </td></tr></table>
      <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">Ingresá con tu cuenta del campus para acceder al contenido.</p>
    </td></tr>
    <tr><td style="padding:20px 32px;border-top:1px solid #e2e8f0;font-size:12px;line-height:1.6;color:#64748b;">Notificación automática de <?php echo $e(MAIL_FROM_NAME); ?>.</td></tr>
  </table>
</td></tr></table>
</body>
</html>
