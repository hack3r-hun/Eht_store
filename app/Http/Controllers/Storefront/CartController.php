<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CheckoutService;
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
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = Product::where('is_active', true)->findOrFail($request->product_id);

        if (! $product->is_in_stock) {
            return back()->with('error', 'This product is out of stock.');
        }

        $clamped = $this->cartService->add($product, (int) ($request->quantity ?? 1));

        if ($clamped) {
            return back()->with('info', "{$product->name} added — only {$product->stock_quantity} in stock, so the quantity was adjusted.");
        }

        return back()->with('success', "{$product->name} added to cart.");
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        $request->validate(['quantity' => ['required', 'integer', 'min:0']]);
        $clamped = $this->cartService->update($cartItem, (int) $request->quantity);

        if ($clamped) {
            return back()->with('info', "Only {$cartItem->product->stock_quantity} in stock — quantity adjusted.");
        }

        return back()->with('success', 'Cart updated.');
    }

    public function destroy(CartItem $cartItem): RedirectResponse
    {
        $this->cartService->remove($cartItem);

        return back()->with('success', 'Item removed from cart.');
    }
}
