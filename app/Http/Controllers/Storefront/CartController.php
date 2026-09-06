<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected CheckoutService $checkoutService
    ) {}

    public function index()
    {
        $items = $this->cartService->items();
        $totals = $this->checkoutService->calculateTotals();

        return view('storefront.cart.index', compact('items', 'totals'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = Product::where('is_active', true)->findOrFail($request->product_id);
        $variant = ProductVariant::where('product_id', $product->id)
            ->where('is_active', true)
            ->findOrFail($request->product_variant_id);

        if (! $variant->is_in_stock) {
            return back()->with('error', "Size {$variant->size} is out of stock.");
        }

        $result = $this->cartService->add($product, $variant, (int) ($request->quantity ?? 1));

        if ($result['clamped']) {
            return back()->with('info', "{$product->name} ({$variant->size}) added — only {$variant->stock_quantity} in stock, so the quantity was adjusted.")->with('cart_updated', true);
        }

        return back()->with('success', "{$product->name} ({$variant->size}) added to cart.")->with('cart_updated', true);
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $request->validate(['quantity' => ['required', 'integer', 'min:0']]);
        $result = $this->cartService->update($cartItem, (int) $request->quantity);

        if ($result['removed']) {
            if ($request->wantsJson()) {
                return response()->json([
                    'removed' => true,
                    'item_id' => $cartItem->id,
                    'message' => 'Item removed from cart.',
                    'totals' => $this->formattedTotals(),
                    'cart_count' => $this->cartService->count(),
                ]);
            }

            return back()->with('success', 'Item removed from cart.');
        }

        $message = 'Cart updated.';

        if ($result['clamped']) {
            $stock = $cartItem->fresh()->variant?->stock_quantity ?? 0;
            $message = "Only {$stock} in stock — quantity adjusted.";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'removed' => false,
                'clamped' => $result['clamped'],
                'message' => $message,
                'item' => [
                    'id' => $cartItem->id,
                    'quantity' => $cartItem->quantity,
                    'line_total' => shop_money($cartItem->line_total),
                ],
                'totals' => $this->formattedTotals(),
                'cart_count' => $this->cartService->count(),
            ]);
        }

        return back()->with($result['clamped'] ? 'info' : 'success', $message);
    }

    /**
     * @return array<string, string>
     */
    protected function formattedTotals(): array
    {
        $totals = $this->checkoutService->calculateTotals();

        return [
            'subtotal' => shop_money($totals['subtotal']),
            'tax' => shop_money($totals['tax']),
            'shipping' => $totals['shipping'] > 0 ? shop_money($totals['shipping']) : 'Free',
            'total' => shop_money($totals['total']),
        ];
    }

    public function destroy(CartItem $cartItem): RedirectResponse
    {
        $this->cartService->remove($cartItem);

        return back()->with('success', 'Item removed from cart.');
    }
}
