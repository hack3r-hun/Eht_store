<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, string $slug): RedirectResponse
    {
        $product = Product::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $user = $request->user();

        $data = $request->validate([
            'name' => [$user ? 'nullable' : 'required', 'string', 'max:80'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        if ($user && Review::where('product_id', $product->id)->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'You have already reviewed this product.');
        }

        // Verified buyers (a non-cancelled order containing this product) are published
        // immediately; everyone else waits for admin approval.
        $verified = $user && OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn ($q) => $q->where('user_id', $user->id)->where('status', '!=', 'cancelled'))
            ->exists();

        Review::create([
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'name' => $data['name'] ?? $user?->name ?? 'Customer',
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => strip_tags($data['body']),
            'is_verified' => (bool) $verified,
            'is_approved' => (bool) $verified && (bool) module_value('reviews', 'auto_publish_verified', true),
        ]);

        return back()->with('success', $verified && module_value('reviews', 'auto_publish_verified', true)
            ? 'Thanks! Your review is now live.'
            : 'Thanks! Your review will appear once it has been approved.');
    }
}
