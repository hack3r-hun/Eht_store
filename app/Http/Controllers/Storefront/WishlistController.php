<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(protected WishlistService $wishlist) {}

    public function index()
    {
        return view('storefront.wishlist', ['products' => $this->wishlist->products()]);
    }

    public function toggle(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate(['product_id' => ['required', 'integer']]);

        $product = Product::where('is_active', true)->findOrFail($request->product_id);
        $added = $this->wishlist->toggle($product);
        // Reset the request-scoped memo so the count reflects the change.
        $request->attributes->remove('wishlist_ids');
        $count = wishlist_count();

        if ($request->wantsJson()) {
            return response()->json([
                'added' => $added,
                'count' => $count,
                'message' => $added ? "{$product->name} saved to your wishlist." : "{$product->name} removed from your wishlist.",
            ]);
        }

        return back()->with('success', $added ? 'Saved to your wishlist.' : 'Removed from your wishlist.');
    }
}
