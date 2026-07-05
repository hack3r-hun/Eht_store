<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CartService
{
    public function items(): Collection
    {
        $query = CartItem::with('product.images')
            ->whereHas('product', fn ($query) => $query->where('is_active', true));

        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        } else {
            $query->where('session_id', session()->getId());
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

    /**
     * @return bool true when the stored quantity was clamped below the request
     */
    public function add(Product $product, int $quantity = 1): bool
    {
        if ($quantity < 1 || $product->stock_quantity < 1) {
            return false;
        }

        $attributes = Auth::check()
            ? ['user_id' => Auth::id(), 'product_id' => $product->id]
            : ['session_id' => session()->getId(), 'product_id' => $product->id];

        $cartItem = CartItem::firstOrNew($attributes);
        $requestedTotal = ($cartItem->exists ? $cartItem->quantity : 0) + $quantity;
        $cartItem->quantity = min($requestedTotal, $product->stock_quantity);

        try {
            $cartItem->save();
        } catch (UniqueConstraintViolationException) {
            // A concurrent request created the row first — merge into it instead.
            $existing = CartItem::where($attributes)->first();

            if ($existing) {
                $requestedTotal = $existing->quantity + $quantity;
                $existing->update([
                    'quantity' => min($requestedTotal, $product->stock_quantity),
                ]);
            }
        }

        return $requestedTotal > $product->stock_quantity;
    }

    /**
     * @return bool true when the stored quantity was clamped below the request
     */
    public function update(CartItem $cartItem, int $quantity): bool
    {
        $this->authorizeItem($cartItem);

        if (! $cartItem->product || ! $cartItem->product->is_active) {
            $cartItem->delete();

            return false;
        }

        if ($quantity < 1) {
            $cartItem->delete();

            return false;
        }

        $cartItem->update([
            'quantity' => min($quantity, $cartItem->product->stock_quantity),
        ]);

        return $quantity > $cartItem->product->stock_quantity;
    }

    public function remove(CartItem $cartItem): void
    {
        $this->authorizeItem($cartItem);
        $cartItem->delete();
    }

    public function clear(): void
    {
        if (Auth::check()) {
            CartItem::where('user_id', Auth::id())->delete();
        } else {
            CartItem::where('session_id', session()->getId())->delete();
        }
    }

    public function mergeGuestCart(int $userId): void
    {
        $sessionId = session()->getId();

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
        if (Auth::check() && $cartItem->user_id !== Auth::id()) {
            abort(403);
        }

        if (! Auth::check() && $cartItem->session_id !== session()->getId()) {
            abort(403);
        }
    }
}
