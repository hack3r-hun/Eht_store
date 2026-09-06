<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ProductVariantService;
use App\Support\ImageProcessor;
use App\Support\MediaUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(protected ProductVariantService $variantService) {}

    public function index(Request $request): View
    {
        $perPage = $this->perPage($request);

        $products = Product::with(['category', 'images', 'activeVariants'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->when($request->filled('status'), fn ($query) => match ($request->status) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                default => $query,
            })
            ->when($request->filled('stock'), fn ($query) => match ($request->stock) {
                'out' => $query->where('stock_quantity', 0),
                'low' => $query->where(function ($q) {
                    $q->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                        ->where('stock_quantity', '>', 0)
                        ->orWhereHas('activeVariants', function ($vq) {
                            $vq->whereColumn('product_variants.stock_quantity', '<=', 'products.low_stock_threshold')
                                ->where('product_variants.stock_quantity', '>', 0);
                        });
                }),
                default => $query,
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();
        $archivedCount = Product::onlyTrashed()->count();

        return view('admin.products.index', compact('products', 'categories', 'archivedCount', 'perPage'));
    }

    public function archived(Request $request): View
    {
        $products = Product::onlyTrashed()
            ->with(['category', 'images', 'variants'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->latest('deleted_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.archived', compact('products'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        $product = new Product;
        $sizes = ProductVariant::SIZES;

        return view('admin.products.create', compact('categories', 'product', 'sizes'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']).'-'.Str::random(4);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        unset($data['images'], $data['variants']);

        $enabledVariants = $request->enabledVariants();
        $first = $enabledVariants[0];
        $data['price'] = $first['price'];
        $data['sale_price'] = $first['sale_price'] ?? null;
        $data['stock_quantity'] = collect($enabledVariants)->sum(fn ($v) => (int) $v['stock_quantity']);

        $product = DB::transaction(function () use ($data, $request, $enabledVariants) {
            $product = Product::create($data);
            $this->variantService->sync($product, $enabledVariants);
            $this->storeImages($product, $request->file('images', []));

            return $product;
        });

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Product created with sizes. Images are live on the store.');
    }

    public function edit(Product $product): View
    {
        $categories = Category::orderBy('name')->get();
        $product->load(['images', 'variants']);
        $sizes = ProductVariant::SIZES;

        return view('admin.products.edit', compact('product', 'categories', 'sizes'));
    }

    public function update(StoreProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        unset($data['images'], $data['variants']);

        DB::transaction(function () use ($data, $request, $product) {
            $product->update($data);
            $this->variantService->sync($product, $request->enabledVariants());
            $this->storeImages($product, $request->file('images', []));
        });

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Product and sizes saved.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product archived and hidden from the store.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $products = Product::whereIn('id', $data['product_ids'])->get();
        $count = $products->count();
        $products->each->delete();

        return redirect()->route('admin.products.index')
            ->with('success', "{$count} products archived and hidden from the store.");
    }

    public function restore(int $product): RedirectResponse
    {
        $product = Product::onlyTrashed()->findOrFail($product);
        $product->restore();

        return redirect()->route('admin.products.archived')->with('success', "{$product->name} restored.");
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        if ($image->product_id !== $product->id) {
            abort(404);
        }

        $wasPrimary = $image->is_primary;
        MediaUrl::deleteLocalFile($image->path);
        $image->delete();

        if ($wasPrimary) {
            $next = $product->images()->get()->first(
                fn (ProductImage $img) => MediaUrl::localFileExists($img->path)
            ) ?? $product->images()->first();

            $next?->update(['is_primary' => true]);
        }

        return back()->with('success', 'Image removed.');
    }

    protected function storeImages(Product $product, array $images): void
    {
        $files = array_values(array_filter($images));

        if ($files === []) {
            return;
        }

        $product->images()
            ->get()
            ->filter(fn (ProductImage $image) => ! MediaUrl::isLocalPath($image->path))
            ->each(fn (ProductImage $image) => $image->delete());

        $product->images()->update(['is_primary' => false]);

        $existingCount = $product->images()->count();

        foreach ($files as $index => $file) {
            $path = ImageProcessor::store($file, 'products');
            MediaUrl::forgetExists($path);

            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'is_primary' => $index === 0,
                'sort_order' => $existingCount + $index,
            ]);
        }
    }

    protected function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 20);

        return in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;
    }
}
