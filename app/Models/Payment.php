<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Support\HomeCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected static function booted(): void
    {
        static::saved(fn () => HomeCache::flush());
    }

    protected $fillable = [
        'order_id',
        'stripe_payment_intent_id',
        'status',
        'amount',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'meta' => 'array',
            'status' => PaymentStatus::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
