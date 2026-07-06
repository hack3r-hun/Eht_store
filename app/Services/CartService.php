<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Support\CartIdentity;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

class CartService
{
    public function items(): Collection
    {
        $identity = CartIdentity::resolve();

        if ($identity->isGuest() && ! $identity->hasGuestToken()) {
            return collect();
        }

        $query = CartItem::with('product.images')
            ->whereHas('product', fn ($query) => $query->where('is_active', true));

        if ($identity->userId) {
            $query->where('user_id', $identity->userId);
        } else {
            $query->where('session_id', $identity->guestToken);
        }

        return $query->get();
    }

    public function count(): int
    {
        return $this->items()->sum('quantity');
    }

    public function subtotal(): float
    {
        return $this->items()->sum(fn (CartItem $item) => $item->line_total);
    }

    public function guestCartToken(): ?string
    {
        $identity = CartIdentity::resolve();

        return $identity->isGuest() ? $identity->guestToken : null;
    }

    public function isNewGuestToken(): bool
    {
        return CartIdentity::resolve(createIfMissing: true)->isNewGuestToken;
    }

    /**
     * @return array{clamped: bool, guest_cart_token: ?string}
     */
    public function add(Product $product, int $quantity = 1): array
    {
        if ($quantity < 1 || $product->stock_quantity < 1) {
            return ['clamped' => false, 'guest_cart_token' => $this->guestCartToken()];
        }

        $identity = CartIdentity::resolve(createIfMissing: true);

        $attributes = $identity->userId
            ? ['user_id' => $identity->userId, 'product_id' => $product->id]
            : ['session_id' => $identity->guestToken, 'product_id' => $product->id];

        $cartItem = CartItem::firstOrNew($attributes);
        $requestedTotal = ($cartItem->exists ? $cartItem->quantity : 0) + $quantity;
        $clamped = $requestedTotal > $product->stock_quantity;
        $cartItem->quantity = min($requestedTotal, $product->stock_quantity);

        try {
            $cartItem->save();
        } catch (UniqueConstraintViolationException) {
            $existing = CartItem::where($attributes)->first();

            if ($existing) {
                $requestedTotal = $existing->quantity + $quantity;
                $clamped = $requestedTotal > $product->stock_quantity;
                $existing->update([
                    'quantity' => min($requestedTotal, $product->stock_quantity),
                ]);
            }
        }

        return [
            'clamped' => $clamped,
            'guest_cart_token' => $identity->isGuest() ? $identity->guestToken : null,
        ];
    }

    /**
     * @return array{removed: bool, clamped: bool}
     */
    public function update(CartItem $cartItem, int $quantity): array
    {
        $this->authorizeItem($cartItem);

        if (! $cartItem->product || ! $cartItem->product->is_active) {
            $cartItem->delete();

            return ['removed' => true, 'clamped' => false];
        }

        if ($quantity < 1) {
            $cartItem->delete();

            return ['removed' => true, 'clamped' => false];
        }

        $clamped = $quantity > $cartItem->product->stock_quantity;

        $cartItem->update([
            'quantity' => min($quantity, $cartItem->product->stock_quantity),
        ]);

        return ['removed' => false, 'clamped' => $clamped];
    }

    public function remove(CartItem $cartItem): void
    {
        $this->authorizeItem($cartItem);
        $cartItem->delete();
    }

    public function clear(): void
    {
        $identity = CartIdentity::resolve(createIfMissing: false);

        if ($identity->userId) {
            CartItem::where('user_id', $identity->userId)->delete();

            return;
        }

        if ($identity->hasGuestToken()) {
            CartItem::where('session_id', $identity->guestToken)->delete();
        }
    }

    public function mergeGuestCart(int $userId, ?string $guestToken = null): void
    {
        $sessionId = $guestToken
            ?? request()->header('X-Guest-Cart-Token')
            ?? (request()->hasSession() ? session()->getId() : null);

        if (! $sessionId) {
            return;
        }

        CartItem::where('session_id', $sessionId)->each(function (CartItem $guestItem) use ($userId) {
            $existing = CartItem::where('user_id', $userId)
                ->where('product_id', $guestItem->product_id)
                ->first();

            if ($existing) {
                if (! $guestItem->product || ! $guestItem->product->is_active) {
                    $guestItem->delete();

                    return;
                }

                $existing->update([
                    'quantity' => min(
                        $existing->quantity + $guestItem->quantity,
                        $guestItem->product->stock_quantity
                    ),
                ]);
                $guestItem->delete();
            } else {
                if (! $guestItem->product || ! $guestItem->product->is_active) {
                    $guestItem->delete();

                    return;
                }

                $guestItem->update([
                    'user_id' => $userId,
                    'session_id' => null,
                ]);
            }
        });
    }

    protected function authorizeItem(CartItem $cartItem): void
    {
        $identity = CartIdentity::resolve();

        if ($identity->userId) {
            if ($cartItem->user_id !== $identity->userId) {
                abort(403);
            }

            return;
        }

        if (! $identity->hasGuestToken() || $cartItem->session_id !== $identity->guestToken) {
            abort(403);
        }
    }
}
