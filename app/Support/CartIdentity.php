<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartIdentity
{
    public function __construct(
        public readonly ?int $userId = null,
        public readonly ?string $guestToken = null,
        public readonly bool $isNewGuestToken = false,
    ) {}

    public static function resolve(?string $headerToken = null, bool $createIfMissing = false): self
    {
        if (Auth::check()) {
            return new self(userId: Auth::id());
        }

        $token = $headerToken
            ?? request()->header('X-Guest-Cart-Token')
            ?? request()->attributes->get('guest_cart_token');

        if ($token) {
            return new self(guestToken: $token);
        }

        if (request()->is('api/*')) {
            if ($createIfMissing) {
                $token = (string) Str::uuid();
                request()->attributes->set('guest_cart_token', $token);

                return new self(guestToken: $token, isNewGuestToken: true);
            }

            return new self;
        }

        if (request()->hasSession()) {
            return new self(guestToken: session()->getId());
        }

        if ($createIfMissing) {
            $token = (string) Str::uuid();
            request()->attributes->set('guest_cart_token', $token);

            return new self(guestToken: $token, isNewGuestToken: true);
        }

        return new self;
    }

    public function isGuest(): bool
    {
        return $this->userId === null;
    }

    public function hasGuestToken(): bool
    {
        return $this->guestToken !== null;
    }
}
