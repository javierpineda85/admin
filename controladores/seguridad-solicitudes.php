<?php

class SeguridadSolicitudes
{
    public static function origenPostValido(array $servidor = null)
    {
        $servidor = $servidor ?? $_SERVER;
        if (strtoupper((string) ($servidor['REQUEST_METHOD'] ?? 'GET')) !== 'POST') { return true; }

        $fetchSite = strtolower(trim((string) ($servidor['HTTP_SEC_FETCH_SITE'] ?? '')));
        if ($fetchSite !== '' && !in_array($fetchSite, ['same-origin', 'none'], true)) { return false; }

        $origen = trim((string) ($servidor['HTTP_ORIGIN'] ?? ''));
        if ($origen !== '') { return hash_equals(self::origenAplicacion(), self::normalizarOrigen($origen)); }

        $referer = trim((string) ($servidor['HTTP_REFERER'] ?? ''));
        if ($referer !== '') { return hash_equals(self::origenAplicacion(), self::normalizarOrigen($referer)); }

        // Clientes antiguos y pruebas de consola pueden no enviar metadatos.
        // Las sesiones SameSite y los tokens de los flujos críticos mantienen
        // la defensa, sin bloquear esos clientes legítimos.
        return true;
    }

    private static function origenAplicacion()
    {
        return self::normalizarOrigen((string) APP_BASE_URL);
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
