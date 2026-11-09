<?php

namespace App\Services;

use App\Models\Product;
use App\Models\WishlistItem;
use App\Support\CartIdentity;
use Illuminate\Support\Collection;

class WishlistService
{
    protected function scope()
    {
        $identity = CartIdentity::resolve();

        if ($identity->userId) {
            return WishlistItem::where('user_id', $identity->userId);
        }

        if ($identity->hasGuestToken()) {
            return WishlistItem::whereNull('user_id')->where('session_id', $identity->guestToken);
        }

        return null;
    }

    /** @return array<int, int> */
    public function productIds(): array
    {
        return $this->scope()?->pluck('product_id')->map(fn ($id) => (int) $id)->all() ?? [];
    }

    public function products(): Collection
    {
        $ids = $this->scope()?->latest()->pluck('product_id') ?? collect();

        if ($ids->isEmpty()) {
            return collect();
        }

        $products = Product::with(['images', 'category', 'activeVariants'])
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return $ids->map(fn ($id) => $products->get($id))->filter()->values();
    }

    /** Toggles a product and returns whether it is now on the wishlist. */
    public function toggle(Product $product): bool
    {
        $identity = CartIdentity::resolve(createIfMissing: true);

        $attributes = $identity->userId
            ? ['user_id' => $identity->userId, 'product_id' => $product->id]
            : ['user_id' => null, 'session_id' => $identity->guestToken, 'product_id' => $product->id];

        $existing = WishlistItem::where($attributes)->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        WishlistItem::create($attributes);

        return true;
    }

    /** Moves a guest wishlist into the account on login. */
    public function mergeGuest(int $userId, string $sessionId): void
    {
        $owned = WishlistItem::where('user_id', $userId)->pluck('product_id')->all();

        WishlistItem::whereNull('user_id')
            ->where('session_id', $sessionId)
            ->get()
            ->each(function (WishlistItem $item) use ($userId, $owned) {
                if (in_array($item->product_id, $owned, true)) {
                    $item->delete();
                } else {
                    $item->update(['user_id' => $userId, 'session_id' => null]);
                }
            });
    }
}
