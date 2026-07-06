<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPage($request);

        $products = Product::with(['category', 'images'])
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
                'low' => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->where('stock_quantity', '>', 0),
                default => $query,
            })
            ->latest()
            ->paginate($perPage);

        return $this->paginated($products, new ProductCollection($products));
    }

    public function archived(Request $request): JsonResponse
    {
        $products = Product::onlyTrashed()
            ->with(['category', 'images'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->latest('deleted_at')
            ->paginate(20);

        return $this->paginated($products, new ProductCollection($products));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']).'-'.Str::random(4);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured', false);
        unset($data['images']);

        $product = Product::create($data);
        $this->storeImages($product, $request->file('images', []));

        return $this->success(new ProductResource($product->load(['category', 'images'])), 201);
    }

    public function show(Product $product): JsonResponse
    {
        return $this->success(new ProductResource($product->load(['category', 'images'])));
    }

    public function update(StoreProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured', false);
        unset($data['images']);

        $product->update($data);
        $this->storeImages($product, $request->file('images', []));

        return $this->success(new ProductResource($product->fresh(['category', 'images'])));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return $this->success(['message' => 'Product archived.']);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $products = Product::whereIn('id', $data['product_ids'])->get();
        $count = $products->count();
        $products->each->delete();

        return $this->success(['message' => "{$count} products archived.", 'count' => $count]);
    }

    public function restore(int $product): JsonResponse
    {
        $product = Product::onlyTrashed()->findOrFail($product);
        $product->restore();

        return $this->success(new ProductResource($product->load(['category', 'images'])));
    }

    public function destroyImage(Product $product, ProductImage $image): JsonResponse
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

        return $this->success(new ProductResource($product->fresh(['category', 'images'])));
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
            $path = $file->store('products', 'public');
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
