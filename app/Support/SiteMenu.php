<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Admin-editable site chrome: header menu, footer links, footer text, top bar and
 * social links. Falls back to the original hard-coded design until something is saved.
 */
class SiteMenu
{
    public const MAX_NAV = 8;

    public const MAX_FOOTER = 12;

    public const MAX_SOCIAL = 6;

    /** @return array<int, array{label: string, url: string}> */
    public static function nav(): array
    {
        return self::links('site_nav', [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Products', 'url' => '/products'],
            ['label' => 'Custom Order', 'url' => '/custom-order'],
            ['label' => 'About', 'url' => '/about'],
            ['label' => 'Contact', 'url' => '/contact'],
        ]);
    }

    /** @return array<int, array{label: string, url: string}> */
    public static function footerLinks(): array
    {
        return self::links('site_footer_links', [
            ['label' => 'All Products', 'url' => '/products'],
            ['label' => 'About Us', 'url' => '/about'],
            ['label' => 'Custom Orders', 'url' => '/custom-order'],
            ['label' => 'Track Your Order', 'url' => '/track-order'],
            ['label' => 'Shipping, Returns & Care', 'url' => '/policies'],
            ['label' => 'Contact', 'url' => '/contact'],
        ]);
    }

    /** @return array<int, array{label: string, url: string}> */
    public static function socials(): array
    {
        return self::links('site_socials', []);
    }

    public static function topbarText(): string
    {
        return (string) Setting::get('site_topbar_text', 'Handmade with love in Karachi');
    }

    public static function footerText(): string
    {
        return (string) (Setting::get('site_footer_text') ?: shop_config('tagline'));
    }

    public static function footerHeading(): string
    {
        return (string) Setting::get('site_footer_heading', 'Quick Links');
    }

    /** Header link highlight: exact match for "/", prefix match (incl. sub-pages) for everything else. */
    public static function isActive(string $url): bool
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        if ($path === '') {
            return request()->path() === '/';
        }

        return request()->is($path) || request()->is($path.'/*');
    }

    /** Menu URLs are stored as typed ("/about", "https://…"); render with the current host for relative ones. */
    public static function href(string $url): string
    {
        return str_starts_with($url, '/') ? url($url) : $url;
    }

    /** @return array<int, array{label: string, url: string}> */
    protected static function links(string $key, array $default): array
    {
        $raw = Setting::get($key);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        if (! is_array($decoded)) {
            return self::withoutDisabledPages($default);
        }

        return self::withoutDisabledPages(array_values(array_filter(array_map(function ($row) {
            return is_array($row) && filled($row['label'] ?? null) && filled($row['url'] ?? null)
                ? ['label' => (string) $row['label'], 'url' => (string) $row['url']]
                : null;
        }, $decoded))));
    }

    /** Pages that belong to a Dashboard → Modules switch vanish from menus while their module is off. */
    protected static function withoutDisabledPages(array $links): array
    {
        $moduleFor = ['/custom-order' => 'custom_orders', '/track-order' => 'order_tracking', '/wishlist' => 'wishlist'];

        return array_values(array_filter($links, function ($link) use ($moduleFor) {
            $path = rtrim((string) parse_url($link['url'], PHP_URL_PATH), '/');

            return ! isset($moduleFor[$path]) || module_enabled($moduleFor[$path]);
        }));
    }
}
