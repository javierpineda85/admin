<?php

class SeguridadSolicitudes
{
    public static function aplicarCabeceras($ruta, array $servidor = null)
    {
        if (headers_sent()) { return; }
        header_remove('X-Powered-By');
        $servidor = $servidor ?? $_SERVER;
        $ruta = trim((string) $ruta);
        $ancestros = $ruta === 'actividad-publica'
            ? "'self' https://mentemotion.com https://*.mentemotion.com"
            : "'self'";
        header("Content-Security-Policy: upgrade-insecure-requests; base-uri 'self'; object-src 'none'; form-action 'self'; frame-ancestors " . $ancestros);
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cache-Control: no-store, private');

        if ($ruta !== 'actividad-publica') { header('X-Frame-Options: SAMEORIGIN'); }
        $https = !empty($servidor['HTTPS']) && strtolower((string) $servidor['HTTPS']) !== 'off';
        $host = strtolower(preg_replace('/:\d+$/', '', (string) ($servidor['HTTP_HOST'] ?? '')));
        if ($https && ($host === 'mentemotion.com' || str_ends_with($host, '.mentemotion.com'))) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }

    public static function origenPostValido(array $servidor = null)
    {
        $servidor = $servidor ?? $_SERVER;
        if (strtoupper((string) ($servidor['REQUEST_METHOD'] ?? 'GET')) !== 'POST') { return true; }

        $fetchSite = strtolower(trim((string) ($servidor['HTTP_SEC_FETCH_SITE'] ?? '')));
        if ($fetchSite !== '' && !in_array($fetchSite, ['same-origin', 'none'], true)) { return false; }

        $origen = trim((string) ($servidor['HTTP_ORIGIN'] ?? ''));
        if ($origen !== '') { return self::origenCoincideConSolicitud($origen, $servidor); }

        $referer = trim((string) ($servidor['HTTP_REFERER'] ?? ''));
        if ($referer !== '') { return self::origenCoincideConSolicitud($referer, $servidor); }

        // Clientes antiguos y pruebas de consola pueden no enviar metadatos.
        // Las sesiones SameSite y los tokens de los flujos críticos mantienen
        // la defensa, sin bloquear esos clientes legítimos.
        return true;
    }

    private static function origenAplicacion()
    {
        return self::normalizarOrigen((string) APP_BASE_URL);
    }

    private static function origenCoincideConSolicitud($url, array $servidor)
    {
        $origenRecibido = self::normalizarOrigen($url);
        if ($origenRecibido === '') { return false; }

        $origenConfigurado = self::origenAplicacion();
        if ($origenConfigurado !== '' && hash_equals($origenConfigurado, $origenRecibido)) { return true; }

        $host = trim((string) ($servidor['HTTP_HOST'] ?? ''));
        if ($host === '' || preg_match('/[^a-z0-9.\-:\[\]]/i', $host)) { return false; }

        $protoReenviado = strtolower(trim(explode(',', (string) ($servidor['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
        $https = !empty($servidor['HTTPS']) && strtolower((string) $servidor['HTTPS']) !== 'off';
        $esquema = in_array($protoReenviado, ['http', 'https'], true)
            ? $protoReenviado
            : ($https ? 'https' : 'http');
        $origenSolicitud = self::normalizarOrigen($esquema . '://' . $host);

        return $origenSolicitud !== '' && hash_equals($origenSolicitud, $origenRecibido);
    }

    private static function normalizarOrigen($url)
    {
        $partes = parse_url(trim((string) $url));
        if (!is_array($partes) || empty($partes['scheme']) || empty($partes['host'])) { return ''; }
        $esquema = strtolower((string) $partes['scheme']);
        $host = strtolower((string) $partes['host']);
        $puerto = isset($partes['port']) ? (int) $partes['port'] : null;
        $puertoPredeterminado = ($esquema === 'https' && $puerto === 443) || ($esquema === 'http' && $puerto === 80);
        return $esquema . '://' . $host . ($puerto && !$puertoPredeterminado ? ':' . $puerto : '');
    }
}
