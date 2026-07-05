<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class InventoryService
{
    public function lowStockProducts(int $limit = 10): Collection
    {
        return Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->limit($limit)
            ->get();
    }

    public function lowStockCount(): int
    {
        return Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();
    }

    public function restoreStock(Product $product, int $quantity): void
    {
        $product->increment('stock_quantity', $quantity);
    }
}
