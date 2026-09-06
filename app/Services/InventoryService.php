<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class InventoryService
{
    public function lowStockProducts(int $limit = 10): Collection
    {
        return Product::with('activeVariants')
            ->where(function ($query) {
                $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                    ->orWhereHas('activeVariants', function ($vq) {
                        $vq->whereColumn('product_variants.stock_quantity', '<=', 'products.low_stock_threshold');
                    });
            })
            ->orderBy('stock_quantity')
            ->limit($limit)
            ->get();
    }

    public function lowStockCount(): int
    {
        return Product::where(function ($query) {
            $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->orWhereHas('activeVariants', function ($vq) {
                    $vq->whereColumn('product_variants.stock_quantity', '<=', 'products.low_stock_threshold')
                        ->where('product_variants.stock_quantity', '>', 0);
                });
        })->count();
    }

    public function restoreStock(Product $product, int $quantity): void
    {
        $product->increment('stock_quantity', $quantity);
    }

    public function restoreVariantStock(?ProductVariant $variant, Product $product, int $quantity): void
    {
        if ($variant) {
            $variant->increment('stock_quantity', $quantity);
            app(ProductVariantService::class)->refreshProductAggregates($variant->product ?? $product);

            return;
        }

        $this->restoreStock($product, $quantity);
    }
}
