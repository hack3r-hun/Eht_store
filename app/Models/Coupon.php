<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'type', 'value', 'min_subtotal', 'max_uses', 'used_count', 'expires_at', 'is_active'];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_subtotal' => 'decimal:2',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected function code(): Attribute
    {
        return Attribute::make(set: fn (string $value) => strtoupper(trim($value)));
    }

    /** Returns an error message when the coupon cannot be used, otherwise null. */
    public function problemFor(float $subtotal): ?string
    {
        if (! $this->is_active) {
            return 'This coupon is not active.';
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'This coupon has expired.';
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return 'This coupon has reached its usage limit.';
        }

        if ($subtotal < (float) $this->min_subtotal) {
            return 'Spend '.shop_money($this->min_subtotal).' or more to use this coupon.';
        }

        return null;
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === 'percent'
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        return round(min($discount, $subtotal), 2);
    }

    public function label(): string
    {
        return $this->type === 'percent'
            ? (float) $this->value.'% off'
            : shop_money($this->value).' off';
    }
}
