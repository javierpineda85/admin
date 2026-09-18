<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('MAIL_FROM_NAME', 'Institución de prueba');
define('MAIL_FROM_EMAIL', 'campus@example.org');
define('APP_BASE_URL', 'https://campus.example.org');
require __DIR__ . '/../modelos/correo.php';
$url = APP_BASE_URL . '/index.php?r=detalle-seccion&idSeccion=16&abrirLeccion=33#leccion-estudiante-33';
$html = CorreoCampus::publicacionHtml(['nombreUsuario'=>'Ariel <script>'], [
    'asuntoEmail'=>'Nueva lección publicada', 'tituloEmail'=>'PHP — estructuras de repetición y control',
    'contextoEmail'=>'Programador Web Miércoles · PHP · POO', 'urlEmail'=>$url,
]);
$doc = new DOMDocument();
@$doc->loadHTML($html);
$links = $doc->getElementsByTagName('a');
if ($links->length !== 1 || $links->item(0)->getAttribute('href') !== $url || trim($links->item(0)->textContent) !== 'Ver aquí') { throw new RuntimeException('Botón incorrecto'); }
if (str_contains($doc->textContent, $url) || str_contains($html, '<script>')) { throw new RuntimeException('Texto o HTML inseguro'); }
if (!str_contains(CorreoCampus::remitente(), '<campus@example.org>')) { throw new RuntimeException('Remitente incorrecto'); }
try { CorreoCampus::publicacionHtml([], ['urlEmail'=>'javascript:alert(1)']); throw new LogicException('Aceptó URL inválida'); }
catch (InvalidArgumentException $e) {}
if (!empty($argv[1])) {
    file_put_contents($argv[1], CorreoCampus::publicacionHtml(['nombreUsuario'=>'Ariel'], [
        'asuntoEmail'=>'Nueva lección publicada', 'tituloEmail'=>'PHP — estructuras de repetición y control',
        'contextoEmail'=>'Programador Web Miércoles · PHP · POO', 'urlEmail'=>$url,
    ]));
}
echo "OK: HTML escapado, botón Ver aquí, URL oculta en el texto y remitente configurable.\n";
