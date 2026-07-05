<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_subtotal_calculates_correctly(): void
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Capacitor',
            'slug' => 'cap',
            'sku' => 'C-001',
            'price' => 100,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $cart = app(CartService::class);
        $cart->add($product, 3);

        $this->assertEquals(300.0, $cart->subtotal());
        $this->assertEquals(3, $cart->count());
    }
}
