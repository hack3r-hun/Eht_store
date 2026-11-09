<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['images', 'category', 'activeVariants'])
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->where('is_active', true);

        if ($request->filled('category')) {
            // A parent collection also lists products filed under its sub-collections.
            $query->whereHas('category', fn ($q) => $q
                ->where('slug', $request->category)
                ->orWhereHas('parent', fn ($p) => $p->where('slug', $request->category)));
        }

        if ($request->filled('search')) {
            $search = addcslashes((string) $request->search, '%_\\');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter on the price shoppers actually pay (sale price when set).
        if ($request->filled('min_price') && is_numeric($request->min_price)) {
            $query->whereRaw('COALESCE(sale_price, price) >= ?', [(float) $request->min_price]);
        }

        if ($request->filled('max_price') && is_numeric($request->max_price)) {
            $query->whereRaw('COALESCE(sale_price, price) <= ?', [(float) $request->max_price]);
        }

        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'price_low' => $query->orderByRaw('COALESCE(sale_price, price) asc'),
            'price_high' => $query->orderByRaw('COALESCE(sale_price, price) desc'),
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::where('is_active', true)->whereNull('parent_id')->with('children')->orderBy('sort_order')->get();

        $page = Page::forSlug('products');

        return view('storefront.products.index', compact('products', 'categories', 'page'));
    }

    public function show(string $slug)
    {
        $product = Product::with(['images', 'category', 'activeVariants'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $relatedProducts = Product::with(['category', 'images', 'activeVariants'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->take(4)
            ->get();

        // Recently viewed: remember this product (most recent first), show the others.
        $recentlyViewedOn = module_enabled('recently_viewed');
        $viewed = $recentlyViewedOn
            ? collect(session('recently_viewed', []))->reject(fn ($id) => $id === $product->id)->prepend($product->id)->take(9)->values()
            : collect();
        $recentlyViewedOn && session(['recently_viewed' => $viewed->all()]);

        $recentIds = $viewed->slice(1)->values();
        $recentlyViewed = $recentIds->isEmpty()
            ? collect()
            : Product::with(['category', 'images', 'activeVariants'])
                ->where('is_active', true)
                ->whereIn('id', $recentIds)
                ->get()
                ->sortBy(fn ($p) => $recentIds->search($p->id))
                ->values();

        $reviewsOn = module_enabled('reviews');
        $reviews = $reviewsOn ? $product->approvedReviews()->latest()->take(20)->get() : collect();
        $reviewCount = $reviewsOn ? $product->approvedReviews()->count() : 0;
        $averageRating = $reviewCount ? round((float) $product->approvedReviews()->avg('rating'), 1) : null;
        $ratingBreakdown = $reviewCount
            ? $product->approvedReviews()->selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating')
            : collect();

        return view('storefront.products.show', compact(
            'product', 'relatedProducts', 'recentlyViewed', 'reviews', 'reviewCount', 'averageRating', 'ratingBreakdown'
        ));
    }
}
