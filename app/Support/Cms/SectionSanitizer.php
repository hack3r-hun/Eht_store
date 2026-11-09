<?php

namespace App\Support\Cms;

use App\Support\HtmlSanitizer;

/**
 * Turns untrusted editor JSON into a clean, schema-conformant list of sections.
 * Unknown block types and fields are dropped; every value is coerced by field kind.
 */
class SectionSanitizer
{
    /**
     * @param  mixed  $raw  decoded JSON from the editor
     * @return array<int, array{id: string, type: string, enabled: bool, data: array<string, mixed>}>
     */
    public static function clean(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $clean = [];

        foreach (array_slice(array_values($raw), 0, SectionSchema::MAX_SECTIONS) as $section) {
            if (! is_array($section) || ! ($def = SectionSchema::type((string) ($section['type'] ?? '')))) {
                continue;
            }

            $clean[] = [
                'id' => self::cleanId($section['id'] ?? null),
                'type' => $section['type'],
                'enabled' => (bool) ($section['enabled'] ?? true),
                'data' => self::cleanFields($def['fields'], is_array($section['data'] ?? null) ? $section['data'] : []),
            ];
        }

        return $clean;
    }

    protected static function cleanId(mixed $id): string
    {
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $id);

        return $id !== '' ? substr($id, 0, 40) : 's'.bin2hex(random_bytes(4));
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected static function cleanFields(array $fields, array $input): array
    {
        $out = [];

        foreach ($fields as $field) {
            $key = $field['key'];
            $value = $input[$key] ?? null;

            $out[$key] = match ($field['kind']) {
                'text' => self::text($value, $field['max'] ?? 255),
                'textarea' => self::text($value, $field['max'] ?? 2000, multiline: true),
                'richtext' => (string) HtmlSanitizer::sanitize(self::limit(is_string($value) ? $value : '', 20000)),
                'image' => self::image($value),
                'url' => self::url($value),
                'icon' => in_array($value, SectionSchema::ICONS, true) ? $value : ($field['default'] ?? 'sparkles'),
                'select' => array_key_exists((string) $value, $field['options']) ? (string) $value : (string) ($field['default'] ?? array_key_first($field['options'])),
                'number' => is_numeric($value) ? max(0, min(1000000, (int) $value)) : (int) ($field['default'] ?? 0),
                'toggle' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'category' => preg_match('/^[a-z0-9-]{0,120}$/', (string) $value) ? (string) $value : '',
                'repeater' => self::repeater($field, $value),
                default => null,
            };
        }

        return $out;
    }

    protected static function repeater(array $field, mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach (array_slice(array_values($value), 0, SectionSchema::MAX_ITEMS) as $item) {
            if (is_array($item)) {
                $items[] = self::cleanFields($field['fields'], $item);
            }
        }

        return $items;
    }

    protected static function limit(string $value, int $max): string
    {
        return mb_substr($value, 0, $max);
    }

    protected static function text(mixed $value, int $max, bool $multiline = false): string
    {
        $value = is_scalar($value) ? (string) $value : '';
        $value = strip_tags($value);
        $value = $multiline ? trim($value) : trim(preg_replace('/\s+/', ' ', $value));

        return self::limit($value, $max);
    }

    /** Uploaded files are stored as "cms/xyz.jpg"; pasted images may be http(s) URLs or site-relative paths. */
    protected static function image(mixed $value): string
    {
        $value = trim(is_string($value) ? $value : '');

        if ($value === '' || strlen($value) > 500) {
            return '';
        }

        if (preg_match('#^https?://[^\s<>"\']+$#i', $value) || preg_match('#^/[A-Za-z0-9/_.\-]+$#', $value) || preg_match('#^cms/[A-Za-z0-9_.\-]+$#', $value)) {
            return $value;
        }

        return '';
    }

    public static function url(mixed $value): string
    {
        $value = trim(is_string($value) ? $value : '');

        if ($value === '' || strlen($value) > 500) {
            return '';
        }

        // Relative site links, anchors, http(s), mailto and tel only — never javascript:/data:.
        if (preg_match('#^(/|\#)[^\s<>"\']*$#', $value) || preg_match('#^(https?://|mailto:|tel:)[^\s<>"\']+$#i', $value)) {
            return $value;
        }

        return '';
    }
}
