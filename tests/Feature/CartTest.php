<?php

namespace Tests\Feature;

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
        $product = $this->createTestProduct([
            'name' => 'Test Bulb',
            'slug' => 'test-bulb',
            'sku' => 'TST-001',
            'price' => 100,
            'stock_quantity' => 10,
        ]);
        $variant = $this->defaultVariant($product);

        $response = $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $this->assertEquals(2, app(CartService::class)->count());
    }

    public function test_add_to_cart_without_size_fails_validation(): void
    {
        $product = $this->createTestProduct([
            'slug' => 'needs-size',
            'sku' => 'NS-001',
        ]);

        $this->from(route('products.show', $product->slug))
            ->post(route('cart.store'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors(['product_variant_id']);
    }

    public function test_cart_page_loads(): void
    {
        $response = $this->get(route('cart.index'));
        $response->assertOk();
    }

    public function test_archiving_product_removes_it_from_cart(): void
    {
        $product = $this->createTestProduct([
            'name' => 'Cart Bulb',
            'slug' => 'cart-bulb',
            'sku' => 'CRT-001',
            'price' => 100,
            'stock_quantity' => 10,
        ]);
        $variant = $this->defaultVariant($product);

        app(CartService::class)->add($product, $variant, 2);
        $this->assertEquals(2, app(CartService::class)->count());

        $product->delete();

        $this->assertEquals(0, app(CartService::class)->count());
        $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
    }

    public function test_same_product_s_and_m_are_separate_cart_lines(): void
    {
        $product = $this->createTestProduct([
            'slug' => 'multi-size',
            'sku' => 'MS-001',
            'price' => 100,
            'stock_quantity' => 20,
        ]);
        $sizeM = $this->defaultVariant($product);
        $sizeS = $this->attachDefaultVariant($product, [
            'size' => 'S',
            'sku' => 'MS-001-S',
            'price' => 90,
            'stock_quantity' => 5,
        ]);

        app(CartService::class)->add($product, $sizeS, 1);
        app(CartService::class)->add($product, $sizeM, 2);

        $this->assertEquals(3, app(CartService::class)->count());
        $this->assertDatabaseCount('cart_items', 2);
    }
}
