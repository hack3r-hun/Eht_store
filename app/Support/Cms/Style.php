<?php

namespace App\Support\Cms;

class Style
{
    /**
     * Section wrapper classes plus whether text must be light for the chosen background.
     *
     * @return array{class: string, dark: bool}
     */
    public static function section(?string $background, string $default = 'white'): array
    {
        return match ($background ?: $default) {
            'oat' => ['class' => 'bg-oat-100/70 bg-mesh', 'dark' => false],
            'gradient' => ['class' => 'bg-gradient-to-b from-oat-100/70 to-white', 'dark' => false],
            'dark' => ['class' => 'section-dark', 'dark' => true],
            default => ['class' => 'bg-white', 'dark' => false],
        };
    }

    /** Tailwind grid classes per column count (full literals so the JIT compiler keeps them). */
    public static function columns(?string $columns): string
    {
        return match ((string) $columns) {
            '2' => 'grid-cols-1 sm:grid-cols-2',
            '4' => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
            default => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
        };
    }
}
