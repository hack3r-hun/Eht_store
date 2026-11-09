<?php

namespace App\Support\Cms;

/** Merges partial block data over the schema's blank/default values. */
class SectionSchemaDefaults
{
    public static function fill(string $type, array $data): array
    {
        $filled = array_merge(SectionSchema::blankData($type), $data);

        // Repeater rows get every sub-field so editors never meet undefined keys.
        foreach (SectionSchema::type($type)['fields'] ?? [] as $field) {
            if ($field['kind'] !== 'repeater') {
                continue;
            }

            $blank = [];
            foreach ($field['fields'] as $sub) {
                $blank[$sub['key']] = $sub['default'] ?? '';
            }

            $filled[$field['key']] = array_map(fn ($row) => array_merge($blank, $row), $filled[$field['key']] ?? []);
        }

        return $filled;
    }
}
