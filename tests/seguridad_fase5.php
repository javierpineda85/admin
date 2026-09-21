<?php

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

function comprobarFase5($condicion, $mensaje)
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
    echo "OK: $mensaje\n";
}

$raiz = dirname(__DIR__);
$htaccess = file_get_contents($raiz . '/.htaccess');
$cabeceras = file_get_contents($raiz . '/controladores/seguridad-solicitudes.php');
$auditoria = file_get_contents($raiz . '/docs/auditoria-seguridad-2026-09-21.md');

comprobarFase5(str_contains($htaccess, 'Options -Indexes'), 'Apache impide listar directorios');
comprobarFase5(str_contains($htaccess, '^\\.git') && str_contains($htaccess, '[F,L,NC]'), 'Apache bloquea metadatos Git');
comprobarFase5(str_contains($htaccess, '(?:login|forgot-password)\\.html$') && str_contains($htaccess, '[G,L,NC]'), 'Apache retira las pantallas HTML heredadas aunque persistan en un despliegue incremental');
comprobarFase5(str_contains($htaccess, 'Header always unset X-Powered-By') && str_contains($cabeceras, "header_remove('X-Powered-By')"), 'PHP y Apache ocultan la firma de versión');
comprobarFase5(str_contains($cabeceras, 'upgrade-insecure-requests') && str_contains($cabeceras, "frame-ancestors"), 'La CSP conserva HTTPS y limita quién puede embeber páginas');
comprobarFase5(!is_file($raiz . '/login.html') && !is_file($raiz . '/forgot-password.html'), 'Las plantillas de acceso obsoletas dejaron el árbol versionado');
comprobarFase5(
    str_contains($auditoria, 'jQuery 3.6.0')
        && str_contains($auditoria, 'Bootstrap 4.6.1')
        && str_contains($auditoria, 'Summernote 0.8.20'),
    'La auditoría registra las dependencias vendorizadas que requieren actualización'
);
