<?php

namespace App\Models;

use App\Support\HomeCache;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::saved(fn () => HomeCache::flush());
        static::deleted(fn () => HomeCache::flush());
        static::restored(fn () => HomeCache::flush());
        static::softDeleted(function (Product $product) {
            \App\Models\CartItem::where('product_id', $product->id)->delete();
        });
    }

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'sale_price',
        'stock_quantity',
        'low_stock_threshold',
        'specifications',
        'is_active',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'specifications' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): ?ProductImage
    {
        $images = $this->relationLoaded('images')
            ? $this->images
            : $this->images()->get();

        $localImage = $images->first(
            fn (ProductImage $image) => MediaUrl::localFileExists($image->path)
        );

        if ($localImage) {
            return $localImage;
        }

        return $images->where('is_primary', true)->first() ?? $images->first();
    }

    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->price);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->price;
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity > 0
            && $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function getImageUrlAttribute(): string
    {
        $image = $this->primaryImage();

        if ($image) {
            return MediaUrl::resolve($image->path, MediaUrl::productFallback($this->name));
        }

        return MediaUrl::productFallback($this->name);
    }
}
