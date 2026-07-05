<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('media.categories') as $name => $url) {
            $category = Category::where('name', $name)->whereNull('parent_id')->first();

            if ($category) {
                $category->update(['image' => $url]);
            }
        }

        foreach (Product::all() as $product) {
            $url = $this->resolveProductUrl($product->name);

            ProductImage::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'is_primary' => true,
                ],
                [
                    'path' => $url,
                    'sort_order' => 0,
                ]
            );
        }
    }

    private function resolveProductUrl(string $name): string
    {
        foreach (config('media.product_keywords') as $keyword => $url) {
            if (stripos($name, $keyword) !== false) {
                return $url;
            }
        }

        return config('media.default_product');
    }
}
