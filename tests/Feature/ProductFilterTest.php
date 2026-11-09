<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_category_lists_products_from_subcollections(): void
    {
        $parent = Category::create(['name' => 'Toys', 'slug' => 'toys', 'is_active' => true]);
        $child = Category::create(['name' => 'Minis', 'slug' => 'minis', 'is_active' => true, 'parent_id' => $parent->id]);
        $this->createTestProduct(['name' => 'Tiny Bear', 'category' => $child]);

        $this->get('/products?category=toys')->assertOk()->assertSee('Tiny Bear');
        $this->get('/products?category=minis')->assertOk()->assertSee('Tiny Bear');
    }

    public function test_search_treats_wildcards_literally(): void
    {
        $this->createTestProduct(['name' => 'Tiny Bear']);

        $this->get('/products?search=%25')->assertOk()->assertDontSee('Tiny Bear');
    }
}
