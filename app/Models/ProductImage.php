<?php

namespace App\Models;

use App\Support\HomeCache;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected static function booted(): void
    {
        static::saved(fn () => HomeCache::flush());
        static::deleted(fn () => HomeCache::flush());
    }

    protected $fillable = [
        'product_id',
        'path',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): string
    {
        $fallback = $this->relationLoaded('product')
            ? MediaUrl::productFallback($this->product?->name)
            : MediaUrl::productFallback(null);

        return MediaUrl::resolve($this->path, $fallback);
    }
}
