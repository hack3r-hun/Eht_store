<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class MediaUrl
{
    /** @var array<string, bool> */
    protected static array $existsMemo = [];

    public static function resolve(?string $path, ?string $fallback = null): string
    {
        if (blank($path)) {
            return $fallback ?? self::placeholder('Image');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (self::fileExists($path)) {
            return Storage::disk('public')->url($path);
        }

        return $fallback ?? self::placeholder('Image');
    }

    /**
     * Like resolve(), but prefers the pre-generated thumbnail (see ImageProcessor::store())
     * for card/listing grids so they don't download the full detail-size image. Falls back
     * to the full-size resolve() for images uploaded before thumbnailing existed.
     */
    public static function resolveThumb(?string $path, ?string $fallback = null): string
    {
        if (blank($path) || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return self::resolve($path, $fallback);
        }

        $thumbPath = ImageProcessor::thumbPath($path);

        if (self::fileExists($thumbPath)) {
            return Storage::disk('public')->url($thumbPath);
        }

        return self::resolve($path, $fallback);
    }

    /**
     * Cached Storage::exists — listing pages resolve dozens of images per
     * request, and each uncached check is a filesystem stat.
     */
    public static function fileExists(string $path): bool
    {
        if (isset(self::$existsMemo[$path])) {
            return self::$existsMemo[$path];
        }

        return self::$existsMemo[$path] = Cache::remember(
            'media_exists:'.md5($path),
            now()->addMinutes(5),
            fn () => Storage::disk('public')->exists($path)
        );
    }

    public static function forgetExists(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        unset(self::$existsMemo[$path]);
        Cache::forget('media_exists:'.md5($path));
    }

    public static function categoryFallback(?string $name): string
    {
        if ($name && isset(config('media.categories')[$name])) {
            return config('media.categories')[$name];
        }

        return config('media.default_category');
    }

    public static function productFallback(?string $name): string
    {
        if ($name) {
            foreach (config('media.product_keywords') as $keyword => $url) {
                if (stripos($name, $keyword) !== false) {
                    return $url;
                }
            }
        }

        return config('media.default_product');
    }

    public static function teamPhoto(string $slug): string
    {
        return config("media.team.{$slug}")
            ?? 'https://ui-avatars.com/api/?name='.urlencode($slug).'&background=0ea5e9&color=fff&size=200';
    }

    public static function placeholder(string $text): string
    {
        return 'https://placehold.co/400x400/e2e8f0/64748b?text='.urlencode($text);
    }

    public static function isLocalPath(?string $path): bool
    {
        return filled($path)
            && ! str_starts_with($path, 'http://')
            && ! str_starts_with($path, 'https://');
    }

    public static function localFileExists(?string $path): bool
    {
        return self::isLocalPath($path) && self::fileExists($path);
    }

    public static function deleteLocalFile(?string $path): void
    {
        if (self::isLocalPath($path)) {
            $thumbPath = ImageProcessor::thumbPath($path);
            Storage::disk('public')->delete([$path, $thumbPath]);
            self::forgetExists($thumbPath);
        }

        self::forgetExists($path);
    }
}
