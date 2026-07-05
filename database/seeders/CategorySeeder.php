<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Amigurumi',
                'description' => 'Cute handmade crochet plushies and character-inspired keepsakes.',
                'children' => ['Mini Plushies', 'Animal Friends', 'Custom Characters'],
            ],
            [
                'name' => 'Keychains',
                'description' => 'Small crochet charms for bags, keys, favors, and gifts.',
                'children' => ['Bag Charms', 'Initial Charms', 'Gift Favors'],
            ],
            [
                'name' => 'Home & Table',
                'description' => 'Crochet coasters and decorative pieces for everyday spaces.',
                'children' => ['Coasters', 'Table Sets', 'Decor Accents'],
            ],
            [
                'name' => 'Hair Accessories',
                'description' => 'Soft scrunchies, headbands, and hair pieces in custom colors.',
                'children' => ['Scrunchies', 'Headbands', 'Hair Clips'],
            ],
            [
                'name' => 'Wearables',
                'description' => 'Cozy handmade sweaters, shawls, and knit accessories.',
                'children' => ['Sweaters', 'Shawls', 'Scarves'],
            ],
            [
                'name' => 'Custom Gifts',
                'description' => 'Personalized crochet and knit pieces made for special moments.',
                'children' => ['Gift Boxes', 'Name Pieces', 'Event Orders'],
            ],
        ];

        foreach ($categories as $index => $data) {
            $parent = Category::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'description' => $data['description'],
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);

            foreach ($data['children'] as $childIndex => $childName) {
                Category::create([
                    'parent_id' => $parent->id,
                    'name' => $childName,
                    'slug' => Str::slug($childName.'-'.$parent->id),
                    'is_active' => true,
                    'sort_order' => $childIndex + 1,
                ]);
            }
        }
    }
}
