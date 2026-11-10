<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allowlist HTML sanitizer for admin-authored rich text that is later rendered unescaped
 * ({!! !!}) on public pages and the API. The markup is parsed into a DOM and rebuilt: only
 * known-safe tags/attributes survive, URLs are restricted to safe schemes, and everything
 * else (script, style, iframe, svg, form, on* handlers, ...) is dropped. A regex blacklist
 * is bypassable (e.g. `<img/onerror=...>`, entity-encoded `javascript:`), so this works on
 * parsed nodes instead.
 */
class HtmlSanitizer
{
    /** Tags kept as-is (attributes still filtered). */
    protected const ALLOWED_TAGS = [
        'p', 'br', 'hr', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'sub', 'sup', 'small', 'mark',
        'blockquote', 'pre', 'code', 'span', 'div',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li',
        'a', 'img',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
    ];

    /** Tags removed together with everything inside them. */
    protected const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'svg', 'math',
        'form', 'template', 'noscript', 'head', 'title', 'link', 'meta', 'base', 'audio', 'video',
        'canvas', 'select', 'textarea', 'button', 'input', 'xml',
    ];

    /** Attributes allowed per tag ('*' applies to every allowed tag). */
    protected const ALLOWED_ATTRS = [
        '*' => ['class', 'title', 'dir', 'lang'],
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height', 'loading'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
    ];

    public static function sanitize(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        $previous = libxml_use_internal_errors(true);

        $doc = new DOMDocument('1.0', 'UTF-8');
        // The XML prolog forces UTF-8; LIBXML_NONET blocks network access, NOERROR/NOWARNING keep it quiet.
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root');

        if (! $root) {
            return e($html);
        }

        self::cleanChildren($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    protected static function cleanChildren(DOMNode $parent): void
    {
        // Snapshot: we mutate the live list while walking it.
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMElement) {
                self::cleanElement($node);

                continue;
            }

            // Text nodes are safe (re-encoded on output); comments/CDATA/PIs are not needed.
            if ($node->nodeType !== XML_TEXT_NODE) {
                $parent->removeChild($node);
            }
        }
    }

    protected static function cleanElement(DOMElement $el): void
    {
        $tag = strtolower($el->tagName);

        if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
            $el->parentNode->removeChild($el);

            return;
        }

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            // Unknown wrapper: keep its (cleaned) children, drop the tag itself.
            self::cleanChildren($el);

            while ($el->firstChild) {
                $el->parentNode->insertBefore($el->firstChild, $el);
            }
            $el->parentNode->removeChild($el);

            return;
        }

        $allowed = array_merge(self::ALLOWED_ATTRS['*'], self::ALLOWED_ATTRS[$tag] ?? []);

        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);

            if (! in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);

                continue;
            }

            if (in_array($name, ['href', 'src'], true) && ! self::isSafeUrl($attr->value, $name === 'src')) {
                $el->removeAttribute($attr->name);
            }
        }

        if ($tag === 'a') {
            if ($el->getAttribute('target') !== '' && $el->getAttribute('target') !== '_blank') {
                $el->removeAttribute('target');
            }
            if ($el->getAttribute('target') === '_blank') {
                $el->setAttribute('rel', 'noopener noreferrer');
            } else {
                $el->removeAttribute('rel');
            }
        }

        self::cleanChildren($el);
    }

    protected static function isSafeUrl(string $url, bool $isImage): bool
    {
        // Browsers ignore control chars/whitespace inside the scheme ("java\tscript:") — strip them first.
        $normalised = preg_replace('/[\x00-\x20\x7f]+/u', '', html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '';

        if ($normalised === '') {
            return false;
        }

        // Relative URLs, anchors and protocol-relative links (no scheme).
        if (! preg_match('#^[a-z][a-z0-9+.\-]*:#i', $normalised)) {
            return true;
        }

        if (preg_match('#^https?:#i', $normalised)) {
            return true;
        }

        if ($isImage) {
            // Raster data URIs from rich-text editors only (never svg+xml / html).
            return (bool) preg_match('#^data:image/(png|jpe?g|gif|webp);base64,[a-z0-9+/=]+$#i', $normalised);
        }

        return (bool) preg_match('#^(mailto|tel):#i', $normalised);
    }
}
