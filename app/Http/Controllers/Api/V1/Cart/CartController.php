<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\StoreCartItemRequest;
use App\Http\Requests\Api\V1\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;

class CartController extends ApiController
{
    public function __construct(protected CartService $cartService) {}

    public function show(): JsonResponse
    {
        return $this->success(new CartResource($this->cartPayload()));
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        $product = Product::where('is_active', true)->findOrFail($request->product_id);

        if (! $product->is_in_stock) {
            return $this->error('This product is out of stock.', 422, [
                'product_id' => ['This product is out of stock.'],
            ]);
        }

        $result = $this->cartService->add($product, (int) ($request->quantity ?? 1));

        $payload = $this->cartPayload($result['guest_cart_token']);

        if ($result['clamped']) {
            $payload['message'] = "Only {$product->stock_quantity} in stock — quantity adjusted.";
        }

        return $this->success(new CartResource($payload), 201);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        $result = $this->cartService->update($cartItem, (int) $request->quantity);

        $payload = $this->cartPayload();

        if ($result['removed']) {
            $payload['message'] = 'Item removed from cart.';
        } elseif ($result['clamped']) {
            $payload['message'] = 'Quantity adjusted to available stock.';
        }

        return $this->success(new CartResource($payload));
    }

    public function destroy(CartItem $cartItem): JsonResponse
    {
        $this->cartService->remove($cartItem);

        return $this->success(new CartResource($this->cartPayload()));
    }

    protected function cartPayload(?string $guestToken = null): array
    {
        return [
            'items' => $this->cartService->items(),
            'count' => $this->cartService->count(),
            'subtotal' => $this->cartService->subtotal(),
            'guest_cart_token' => $guestToken ?? $this->cartService->guestCartToken(),
        ];
    }
}
