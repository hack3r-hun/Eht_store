<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
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

        $query = CartItem::with(['product.images', 'variant'])
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->whereHas('variant', fn ($query) => $query->where('is_active', true));

        if ($identity->userId) {
            $query->where('user_id', $identity->userId);
        } else {
            $query->where('session_id', $identity->guestToken);
        }

        return $query->get();
    }

    public function count(): int
    {
        $identity = CartIdentity::resolve();

        if ($identity->isGuest() && ! $identity->hasGuestToken()) {
            return 0;
        }

        $query = CartItem::query()
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->whereHas('variant', fn ($query) => $query->where('is_active', true));

        if ($identity->userId) {
            $query->where('user_id', $identity->userId);
        } else {
            $query->where('session_id', $identity->guestToken);
        }

        return (int) $query->sum('quantity');
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
    public function add(Product $product, ProductVariant $variant, int $quantity = 1): array
    {
        if ($variant->product_id !== $product->id) {
            abort(422, 'Selected size does not belong to this product.');
        }

        if ($quantity < 1 || ! $variant->is_active || $variant->stock_quantity < 1) {
            return ['clamped' => false, 'guest_cart_token' => $this->guestCartToken()];
        }

        $identity = CartIdentity::resolve(createIfMissing: true);

        $attributes = $identity->userId
            ? [
                'user_id' => $identity->userId,
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
            ]
            : [
                'session_id' => $identity->guestToken,
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
            ];

        $cartItem = CartItem::firstOrNew($attributes);
        $requestedTotal = ($cartItem->exists ? $cartItem->quantity : 0) + $quantity;
        $clamped = $requestedTotal > $variant->stock_quantity;
        $cartItem->quantity = min($requestedTotal, $variant->stock_quantity);

        try {
            $cartItem->save();
        } catch (UniqueConstraintViolationException) {
            $existing = CartItem::where('product_variant_id', $variant->id)
                ->when(
                    $identity->userId,
                    fn ($q) => $q->where('user_id', $identity->userId),
                    fn ($q) => $q->where('session_id', $identity->guestToken)
                )
                ->first();

            if ($existing) {
                $requestedTotal = $existing->quantity + $quantity;
                $clamped = $requestedTotal > $variant->stock_quantity;
                $existing->update([
                    'quantity' => min($requestedTotal, $variant->stock_quantity),
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
        $cartItem->loadMissing(['product', 'variant']);

        if (! $cartItem->product || ! $cartItem->product->is_active || ! $cartItem->variant || ! $cartItem->variant->is_active) {
            $cartItem->delete();

            return ['removed' => true, 'clamped' => false];
        }

        $stock = $cartItem->variant->stock_quantity;

        if ($quantity < 1 || $stock < 1) {
            $cartItem->delete();

            return ['removed' => true, 'clamped' => false];
        }

        $clamped = $quantity > $stock;

        $cartItem->update([
            'quantity' => min($quantity, $stock),
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
            $guestItem->loadMissing(['product', 'variant']);

            if (! $guestItem->product || ! $guestItem->product->is_active || ! $guestItem->variant || ! $guestItem->variant->is_active) {
                $guestItem->delete();

                return;
            }

            $existing = CartItem::where('user_id', $userId)
                ->where('product_variant_id', $guestItem->product_variant_id)
                ->first();

            if ($existing) {
                $existing->update([
                    'quantity' => min(
                        $existing->quantity + $guestItem->quantity,
                        $guestItem->variant->stock_quantity
                    ),
                ]);
                $guestItem->delete();
            } else {
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
