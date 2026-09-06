<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    public const SIZES = ['S', 'M', 'L', 'XL'];

    public const SIZE_ORDER = [
        'S' => 1,
        'M' => 2,
        'L' => 3,
        'XL' => 4,
    ];

    protected $fillable = [
        'product_id',
        'size',
        'sku',
        'price',
        'sale_price',
        'stock_quantity',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
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
        $threshold = $this->product?->low_stock_threshold ?? 5;

        return $this->stock_quantity > 0 && $this->stock_quantity <= $threshold;
    }
}
