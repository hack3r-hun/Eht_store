<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;

trait CreatesTestProducts
{
    protected function createTestProduct(array $overrides = [], array $variantOverrides = []): Product
    {
        $category = $overrides['category'] ?? Category::firstOrCreate(
            ['slug' => $overrides['category_slug'] ?? 'test-'.uniqid()],
            ['name' => $overrides['category_name'] ?? 'Test', 'is_active' => true]
        );

        unset($overrides['category'], $overrides['category_slug'], $overrides['category_name']);

        $product = Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product-'.uniqid(),
            'sku' => 'SKU-'.uniqid(),
            'price' => 100,
            'stock_quantity' => 10,
            'is_active' => true,
        ], $overrides));

        $this->attachDefaultVariant($product, $variantOverrides);

        return $product->fresh(['variants', 'activeVariants']);
    }

    protected function attachDefaultVariant(Product $product, array $overrides = []): ProductVariant
    {
        $size = strtoupper((string) ($overrides['size'] ?? 'M'));

        return $product->variants()->create(array_merge([
            'size' => $size,
            'sku' => ($product->sku ?: 'SKU').'-'.$size,
            'price' => $product->price,
            'sale_price' => $product->sale_price,
            'stock_quantity' => $product->stock_quantity,
            'is_active' => true,
            'sort_order' => ProductVariant::SIZE_ORDER[$size] ?? 99,
        ], $overrides));
    }

    protected function defaultVariant(Product $product): ProductVariant
    {
        $variant = $product->variants()->where('is_active', true)->orderBy('sort_order')->first();

        if ($variant) {
            return $variant;
        }

        return $this->attachDefaultVariant($product);
    }

    /**
     * Form / API payload for size selection (S/M/L/XL checkboxes).
     *
     * @param  array<int, string>  $enabledSizes
     * @param  array<string, array<string, mixed>>  $perSize
     * @return array<string, array<string, mixed>>
     */
    protected function sizeVariantsPayload(array $enabledSizes = ['M'], float $price = 100, int $stock = 10, array $perSize = []): array
    {
        $variants = [];

        foreach (ProductVariant::SIZES as $size) {
            $enabled = in_array($size, $enabledSizes, true);
            $custom = $perSize[$size] ?? [];

            $variants[$size] = array_merge([
                'size' => $size,
                'enabled' => $enabled,
                'price' => $enabled ? ($custom['price'] ?? $price) : null,
                'sale_price' => $custom['sale_price'] ?? null,
                'stock_quantity' => $enabled ? ($custom['stock_quantity'] ?? $stock) : null,
                'sku' => $custom['sku'] ?? null,
            ], $custom, [
                'size' => $size,
                'enabled' => $enabled,
            ]);
        }

        return $variants;
    }
}
