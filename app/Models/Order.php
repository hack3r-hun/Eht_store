<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Support\HomeCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    public ?string $plain_guest_access_token_for_api = null;

    protected static function booted(): void
    {
        static::saved(fn () => HomeCache::flush());
        static::deleted(fn () => HomeCache::flush());
    }

    protected $fillable = [
        'user_id',
        'guest_access_token',
        'order_number',
        'status',
        'payment_method',
        'payment_status',
        'subtotal',
        'tax',
        'shipping',
        'total',
        'shipping_address',
        'notes',
        'discount',
        'coupon_code',
        'gift_wrap',
        'gift_wrap_fee',
        'gift_message',
        'gift_hide_prices',
        'contact_name',
        'contact_email',
        'contact_phone',
        'marketing_opt_in',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'shipping' => 'decimal:2',
            'total' => 'decimal:2',
            'discount' => 'decimal:2',
            'gift_wrap' => 'boolean',
            'gift_wrap_fee' => 'decimal:2',
            'gift_hide_prices' => 'boolean',
            'marketing_opt_in' => 'boolean',
            'shipping_address' => 'array',
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
