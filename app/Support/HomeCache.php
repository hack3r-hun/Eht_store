<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class HomeCache
{
    public const TTL_SECONDS = 300;

    public const KEYS = [
        'home.featured_products',
        'home.categories',
        'home.latest_products',
    ];

    public static function flush(): void
    {
        foreach (self::KEYS as $key) {
            Cache::forget($key);
        }
    }
}
