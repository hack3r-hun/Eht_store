<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'admin']);
    }

    public function test_guest_can_add_product_to_cart(): void
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Bulb',
            'slug' => 'test-bulb',
            'sku' => 'TST-001',
            'price' => 100,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);

        $response->assertRedirect();
        $this->assertEquals(2, app(CartService::class)->count());
    }

    public function test_cart_page_loads(): void
    {
        $response = $this->get(route('cart.index'));
        $response->assertOk();
    }

    public function test_archiving_product_removes_it_from_cart(): void
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cart Bulb',
            'slug' => 'cart-bulb',
            'sku' => 'CRT-001',
            'price' => 100,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        app(CartService::class)->add($product, 2);
        $this->assertEquals(2, app(CartService::class)->count());

        $product->delete();

        $this->assertEquals(0, app(CartService::class)->count());
        $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
    }
}
