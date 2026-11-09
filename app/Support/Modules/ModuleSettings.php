<?php

namespace App\Support\Modules;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Reads/writes module switches. All values are loaded with ONE cached query per request
 * (bound as a singleton), so asking "is X on?" many times per page is free.
 */
class ModuleSettings
{
    protected const CACHE_KEY = 'modules.settings';

    /** @var array<string, mixed>|null */
    protected ?array $stored = null;

    public function enabled(string $module): bool
    {
        $definition = ModuleRegistry::definitions()[$module] ?? null;

        if (! $definition) {
            return false;
        }

        $stored = $this->stored()["module.{$module}.enabled"] ?? null;

        return $stored === null ? (bool) $definition['default'] : $stored === '1';
    }

    public function value(string $module, string $field, mixed $fallback = null): mixed
    {
        $definition = ModuleRegistry::definitions()[$module] ?? null;
        $fieldDef = collect($definition['fields'] ?? [])->firstWhere('key', $field);

        if (! $fieldDef) {
            return $fallback;
        }

        $stored = $this->stored()["module.{$module}.{$field}"] ?? null;

        if ($stored === null || $stored === '') {
            return $fieldDef['default'] ?? $fallback;
        }

        return match ($fieldDef['kind']) {
            'toggle' => $stored === '1',
            'number' => is_numeric($stored) ? $stored + 0 : ($fieldDef['default'] ?? $fallback),
            default => (string) $stored,
        };
    }

    /** @param  array<string, mixed>  $values  flat map of "module.key.field" => value */
    public function save(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::set($key, is_bool($value) ? ($value ? '1' : '0') : (string) $value);
        }

        $this->forget();
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->stored = null;
    }

    /** @return array<string, string|null> */
    protected function stored(): array
    {
        if ($this->stored !== null) {
            return $this->stored;
        }

        try {
            return $this->stored = Cache::remember(
                self::CACHE_KEY,
                3600,
                fn () => Setting::where('key', 'like', 'module.%')->pluck('value', 'key')->all()
            );
        } catch (\Throwable) {
            // Table not migrated yet (fresh install / CI) — fall back to the built-in defaults.
            return $this->stored = [];
        }
    }
}
