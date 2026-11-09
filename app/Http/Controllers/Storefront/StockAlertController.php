<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StockAlertController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $product = Product::where('is_active', true)->findOrFail($data['product_id']);

        StockAlert::firstOrCreate(
            [
                'product_id' => $product->id,
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'email' => strtolower($data['email']),
                'notified_at' => null,
            ]
        );

        return back()->with('success', "We'll email you as soon as {$product->name} is back in stock.");
    }
}
