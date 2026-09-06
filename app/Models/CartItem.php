<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
        'product_variant_id',
        'quantity',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getUnitPriceAttribute(): float
    {
        if ($this->variant) {
            return $this->variant->effective_price;
        }

        return $this->product?->effective_price ?? 0.0;
    }

    public function getLineTotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }
}
