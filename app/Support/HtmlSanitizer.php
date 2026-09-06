<?php

namespace App\Support;

class HtmlSanitizer
{
    /**
     * Strip the highest-risk XSS vectors from admin-authored rich text before it's persisted
     * and rendered unescaped ({!! !!}) on public storefront/API pages. This is a defense-in-depth
     * pass (not a full HTML sanitizer) so a compromised admin session can't leave persistent
     * script execution live for every visitor.
     */
    public static function sanitize(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        // Remove <script>/<style>/<iframe> blocks entirely, including their content.
        $html = preg_replace('#<(script|style|iframe)\b[^>]*>.*?</\1>#is', '', $html);

        // Strip on* event handler attributes (onclick=, onerror=, ...), quoted or unquoted.
        $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);

        // Neutralize javascript: URIs used as href/src (data: is left alone — legitimate for
        // inline base64 images from a rich text editor and not executable via img/href).
        $html = preg_replace('#(href|src)(\s*=\s*)("|\')\s*javascript:[^"\']*("|\')#i', '$1$2$3#$4', $html);

        return $html;
    }
}
