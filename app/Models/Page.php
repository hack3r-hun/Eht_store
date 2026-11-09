<?php

namespace App\Models;

use App\Support\Cms\PageDefaults;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'content',
        'meta',
        'sections',
        'is_published',
    ];

    protected static function booted(): void
    {
        $flush = fn () => \Illuminate\Support\Facades\Cache::forget('cms.footer_pages');
        static::saved($flush);
        static::deleted($flush);
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'sections' => 'array',
            'is_published' => 'boolean',
        ];
    }

    /** Slugs a custom page may not use: built-in pages plus real top-level routes. */
    public const RESERVED_SLUGS = [
        'admin', 'api', 'cart', 'checkout', 'login', 'logout', 'register', 'profile', 'account', 'wishlist',
        'products', 'about', 'contact', 'home', 'custom-order', 'track-order', 'policies', 'newsletter',
        'stock-alerts', 'webhooks', 'sitemap', 'dashboard', 'storage', 'build', 'password', 'email', 'verify-email',
        'forgot-password', 'reset-password', 'confirm-password', 'up', 'page', 'p',
    ];

    public function isSystem(): bool
    {
        return array_key_exists($this->slug, PageDefaults::SYSTEM);
    }

    /**
     * The page for a slug; when no row exists yet (e.g. a fresh install that has not run
     * the seeders), an unsaved instance so the built-in layout still renders.
     */
    public static function forSlug(string $slug): self
    {
        return static::where('slug', $slug)->first()
            ?? new static([
                'slug' => $slug,
                'title' => PageDefaults::SYSTEM[$slug]['title'] ?? ucfirst($slug),
                'meta' => [],
                'is_published' => true,
            ]);
    }

    /** Saved layout, or the built-in default when none was saved. */
    public function resolvedSections(): array
    {
        return is_array($this->sections) ? $this->sections : PageDefaults::for($this->slug ?? '', $this);
    }

    /** Only enabled blocks, in order. */
    public function activeSections(): array
    {
        return array_values(array_filter($this->resolvedSections(), fn ($s) => ($s['enabled'] ?? true)));
    }

    public function metaValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->meta ?? [], $key, $default);
    }
}
