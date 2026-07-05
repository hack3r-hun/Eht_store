<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['Mini Bunny Amigurumi', 'Amigurumi', 1450, 1299, 18, true],
            ['Crochet Bear Plushie', 'Amigurumi', 1650, null, 14, true],
            ['Tiny Dino Amigurumi', 'Amigurumi', 1350, 1199, 16, false],
            ['Crochet Heart Keychain', 'Keychains', 450, null, 60, true],
            ['Flower Bag Charm', 'Keychains', 550, 499, 45, true],
            ['Initial Letter Keychain', 'Keychains', 650, null, 35, false],
            ['Cotton Crochet Coaster Set', 'Home & Table', 1200, 1050, 25, true],
            ['Daisy Table Coasters', 'Home & Table', 1350, null, 20, false],
            ['Chunky Crochet Wallet', 'Custom Gifts', 1800, 1599, 12, true],
            ['Soft Yarn Scrunchie Pair', 'Hair Accessories', 650, null, 40, false],
            ['Crochet Bow Headband', 'Hair Accessories', 950, 849, 22, true],
            ['Textured Knit Headband', 'Hair Accessories', 1100, null, 18, false],
            ['Cozy Crochet Shawl', 'Wearables', 4500, 3999, 8, true],
            ['Handmade Knit Sweater', 'Wearables', 6800, null, 5, true],
            ['Custom Baby Gift Set', 'Custom Gifts', 3200, null, 10, true],
            ['Personalized Crochet Gift Box', 'Custom Gifts', 3800, 3499, 9, false],
        ];

        foreach ($products as $index => [$name, $categoryName, $price, $salePrice, $stock, $featured]) {
            $category = Category::where('name', $categoryName)->first();

            if (! $category) {
                continue;
            }

            Product::create([
                'category_id' => $category->id,
                'name' => $name,
                'slug' => Str::slug($name).'-'.($index + 1),
                'sku' => 'EKY-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'description' => "{$name} handmade by ".shop_name().' with quality yarn, careful finishing, and gift-ready attention to detail.',
                'price' => $price,
                'sale_price' => $salePrice,
                'stock_quantity' => $stock,
                'low_stock_threshold' => 10,
                'specifications' => [
                    'Brand' => 'EK Yarn Co.',
                    'Craft' => 'Handmade crochet or knit',
                    'Origin' => 'Karachi, Pakistan',
                ],
                'is_active' => true,
                'is_featured' => $featured,
            ]);
        }
    }
}
