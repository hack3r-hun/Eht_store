<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Stripe = 'stripe';
    case Cod = 'cod';

    public function label(): string
    {
        return match ($this) {
            self::Stripe => 'Card Payment',
            self::Cod => 'Cash on Delivery',
        };
    }
}
