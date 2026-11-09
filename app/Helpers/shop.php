<?php

if (! function_exists('shop_config')) {
    function shop_config(string $key, mixed $default = null): mixed
    {
        static $map = [
            'name' => 'shop_name',
            'tagline' => 'shop_tagline',
            'tax_rate' => 'tax_rate',
            'shipping_flat' => 'shipping_flat',
            'shipping_local' => 'shipping_local',
            'shipping_standard' => 'shipping_standard',
            'shipping_remote' => 'shipping_remote',
            'free_shipping_threshold' => 'free_shipping_threshold',
            'contact_email' => 'contact_email',
            'contact_phone' => 'contact_phone',
            'contact_address' => 'contact_address',
        ];

        if (isset($map[$key])) {
            try {
                // Memoized per-process, then cached: the storefront layout calls
                // shop_config()/shop_name() ~9 times per page render, and Schema::hasTable()
                // is an uncached schema introspection query each time it's called directly.
                static $hasSettingsTable = null;

                $hasSettingsTable ??= \Illuminate\Support\Facades\Cache::remember(
                    'schema.settings_table_exists',
                    3600,
                    fn () => \Illuminate\Support\Facades\Schema::hasTable('settings')
                );

                if ($hasSettingsTable) {
                    $value = \App\Models\Setting::get($map[$key]);

                    if ($value !== null && $value !== '') {
                        return in_array($key, ['tax_rate', 'shipping_flat', 'shipping_local', 'shipping_standard', 'shipping_remote', 'free_shipping_threshold'], true)
                            ? (float) $value
                            : $value;
                    }
                }
            } catch (\Throwable) {
                //
            }
        }

        return config("shop.{$key}", $default);
    }
}

if (! function_exists('shop_money')) {
    function shop_money(float|int|string $amount): string
    {
        $symbol = config('shop.currency_symbol', 'Rs.');

        return $symbol.' '.number_format((float) $amount, 0);
    }
}

if (! function_exists('shop_name')) {
    function shop_name(): string
    {
        return (string) shop_config('name', 'EK Yarn Co.');
    }
}

if (! function_exists('whatsapp_url')) {
    /** wa.me deep link built from the shop phone (or SHOP_WHATSAPP), optionally with a prefilled message. */
    function whatsapp_url(?string $message = null): string
    {
        $number = preg_replace('/\D+/', '', (string) module_value('whatsapp', 'number') ?: (string) config('shop.whatsapp') ?: (string) shop_config('contact_phone'));
        $url = 'https://wa.me/'.$number;

        return $message ? $url.'?text='.rawurlencode($message) : $url;
    }
}

if (! function_exists('wishlist_ids')) {
    /** Product ids on the current visitor's wishlist (memoized per request). */
    function wishlist_ids(): array
    {
        if (! module_enabled('wishlist')) {
            return [];
        }

        // Request-scoped (not `static`) so long-lived processes and test suites never leak ids between visitors.
        $attributes = request()->attributes;

        if (! $attributes->has('wishlist_ids')) {
            try {
                $attributes->set('wishlist_ids', app(\App\Services\WishlistService::class)->productIds());
            } catch (\Throwable) {
                $attributes->set('wishlist_ids', []);
            }
        }

        return $attributes->get('wishlist_ids');
    }
}

if (! function_exists('wishlist_count')) {
    function wishlist_count(): int
    {
        return count(wishlist_ids());
    }
}

if (! function_exists('cms_image')) {
    /**
     * URL for a CMS image value: http(s) URLs and site paths pass through, uploaded
     * "cms/…" files resolve through the public storage link on the current host.
     */
    function cms_image(?string $value, ?string $fallback = null): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $fallback;
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        return asset('storage/'.ltrim($value, '/'));
    }
}

if (! function_exists('cms_link')) {
    /** Normalises an editor-supplied link ("/products", "#faq", "tel:…", https URL). */
    function cms_link(?string $value, string $fallback = '#'): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : $fallback;
    }
}

if (! function_exists('module_enabled')) {
    /** Is this dashboard-controlled feature switched on? See App\Support\Modules\ModuleRegistry. */
    function module_enabled(string $module): bool
    {
        return app(\App\Support\Modules\ModuleSettings::class)->enabled($module);
    }
}

if (! function_exists('module_value')) {
    /** A setting belonging to a module (e.g. module_value('gift_options', 'fee')). */
    function module_value(string $module, string $field, mixed $fallback = null): mixed
    {
        return app(\App\Support\Modules\ModuleSettings::class)->value($module, $field, $fallback);
    }
}
