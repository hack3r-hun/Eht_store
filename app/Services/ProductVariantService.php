<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductVariantService
{
    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    public function sync(Product $product, array $variants): void
    {
        $enabled = collect($variants)
            ->filter(fn (array $row) => ($row['enabled'] ?? $row['is_active'] ?? false) || filled($row['price'] ?? null))
            ->map(function (array $row) {
                $size = strtoupper((string) ($row['size'] ?? ''));

                return [
                    'size' => $size,
                    'price' => (float) $row['price'],
                    'sale_price' => filled($row['sale_price'] ?? null) ? (float) $row['sale_price'] : null,
                    'stock_quantity' => (int) ($row['stock_quantity'] ?? 0),
                    'sku' => filled($row['sku'] ?? null) ? (string) $row['sku'] : null,
                    'is_active' => true,
                    'sort_order' => ProductVariant::SIZE_ORDER[$size] ?? 99,
                ];
            })
            ->filter(fn (array $row) => in_array($row['size'], ProductVariant::SIZES, true))
            ->unique('size')
            ->values();

        if ($enabled->isEmpty()) {
            throw ValidationException::withMessages([
                'variants' => ['Select at least one size with price and stock.'],
            ]);
        }

        DB::transaction(function () use ($product, $enabled) {
            $submittedSizes = $enabled->pluck('size')->all();

            foreach ($enabled as $row) {
                $sku = $row['sku'] ?: $this->defaultSku($product->sku, $row['size']);

                $product->variants()->updateOrCreate(
                    ['size' => $row['size']],
                    [
                        'price' => $row['price'],
                        'sale_price' => $row['sale_price'],
                        'stock_quantity' => $row['stock_quantity'],
                        'sku' => $sku,
                        'is_active' => true,
                        'sort_order' => $row['sort_order'],
                    ]
                );
            }

            $product->variants()
                ->whereNotIn('size', $submittedSizes)
                ->update(['is_active' => false]);

            $this->refreshProductAggregates($product);
        });
    }

    public function refreshProductAggregates(Product $product): void
    {
        $active = $product->variants()->where('is_active', true)->get();

        if ($active->isEmpty()) {
            return;
        }

        $minPrice = $active->min(fn (ProductVariant $variant) => $variant->effective_price);
        $totalStock = $active->sum('stock_quantity');
        $cheapest = $active->sortBy(fn (ProductVariant $variant) => $variant->effective_price)->first();

        $product->forceFill([
            'price' => $cheapest?->price ?? $minPrice,
            'sale_price' => $cheapest?->sale_price,
            'stock_quantity' => $totalStock,
        ])->save();
    }

    public function defaultSku(string $productSku, string $size): string
    {
        return strtoupper(trim($productSku)).'-'.strtoupper($size);
    }
}
