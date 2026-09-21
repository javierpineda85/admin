<?php

class SeguridadHtml
{
    private const ETIQUETAS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'span', 'div',
        'ul', 'ol', 'li', 'blockquote', 'pre', 'code',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a', 'img',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    private const ELIMINAR_COMPLETO = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'meta', 'link',
        'base', 'svg', 'math', 'template',
    ];

    public static function urlHttpSegura($url, $permitirRelativa = true)
    {
        $url = trim(html_entity_decode((string) $url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($url === '' || preg_match('/[\x00-\x20\x7F]/', $url)) { return ''; }
        $esRelativa = !preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) && !str_starts_with($url, '//');
        if ($permitirRelativa && $esRelativa) { return $url; }
        if (!filter_var($url, FILTER_VALIDATE_URL)) { return ''; }
        $esquema = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($esquema, ['http', 'https'], true) ? $url : '';
    }

    public static function sanitizarFragmento($valor)
    {
        $html = trim((string) $valor);
        if ($html === '') { return ''; }
        if (!class_exists('DOMDocument')) {
            return nl2br(htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $estadoErrores = libxml_use_internal_errors(true);
        $cargado = $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="campus-contenido">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($estadoErrores);
        if (!$cargado) {
            return nl2br(htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        foreach (iterator_to_array($dom->childNodes) as $nodo) {
            if ($nodo->nodeType === XML_PI_NODE) { $dom->removeChild($nodo); }
        }
        $contenedor = $dom->getElementById('campus-contenido');
        if (!$contenedor) { return ''; }
        self::limpiarHijos($contenedor);

        $salida = '';
        foreach ($contenedor->childNodes as $hijo) { $salida .= $dom->saveHTML($hijo); }
        return $salida;
    }

    private static function limpiarHijos(DOMNode $padre)
    {
        for ($nodo = $padre->firstChild; $nodo !== null;) {
            $siguiente = $nodo->nextSibling;
            if ($nodo instanceof DOMElement) {
                $tag = strtolower($nodo->tagName);
                if (in_array($tag, self::ELIMINAR_COMPLETO, true)) {
                    $padre->removeChild($nodo);
                    $nodo = $siguiente;
                    continue;
                }
                if (!in_array($tag, self::ETIQUETAS, true)) {
                    self::limpiarHijos($nodo);
                    while ($nodo->firstChild) { $padre->insertBefore($nodo->firstChild, $nodo); }
                    $padre->removeChild($nodo);
                    $nodo = $siguiente;
                    continue;
                }
                self::limpiarAtributos($nodo, $tag);
                self::limpiarHijos($nodo);
            } elseif (!in_array($nodo->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE], true)) {
                $padre->removeChild($nodo);
            }
            $nodo = $siguiente;
        }
    }

    private static function limpiarAtributos(DOMElement $elemento, $tag)
    {
        $atributos = [];
        foreach (iterator_to_array($elemento->attributes) as $atributo) {
            $atributos[strtolower($atributo->name)] = $atributo->value;
            $elemento->removeAttribute($atributo->name);
        }

        if ($tag === 'a') {
            $href = self::urlHttpSegura($atributos['href'] ?? '', true);
            if ($href !== '') { $elemento->setAttribute('href', $href); }
            if (($atributos['target'] ?? '') === '_blank') {
                $elemento->setAttribute('target', '_blank');
                $elemento->setAttribute('rel', 'noopener noreferrer');
            }
            if (isset($atributos['title'])) {
                $elemento->setAttribute('title', mb_substr($atributos['title'], 0, 200));
            }
        }

        if ($tag === 'img') {
            $src = self::urlHttpSegura($atributos['src'] ?? '', true);
            if ($src !== '') { $elemento->setAttribute('src', $src); }
            $elemento->setAttribute('alt', mb_substr((string) ($atributos['alt'] ?? ''), 0, 200));
            foreach (['width', 'height'] as $dimension) {
                $valor = (int) ($atributos[$dimension] ?? 0);
                if ($valor > 0 && $valor <= 2000) { $elemento->setAttribute($dimension, (string) $valor); }
            }
        }
    }
}
