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
