<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CartQuantityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'admin']);
    }

    protected function makeProduct(int $stock)
    {
        return $this->createTestProduct([
            'name' => 'Test Bulb',
            'slug' => 'test-bulb-'.uniqid(),
            'sku' => 'TST-'.uniqid(),
            'price' => 100,
            'stock_quantity' => $stock,
        ], ['stock_quantity' => $stock]);
    }

    public function test_quantity_is_clamped_to_stock_with_notice(): void
    {
        $product = $this->makeProduct(3);
        $variant = $this->defaultVariant($product);

        $response = $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('info');
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);
    }

    public function test_adding_same_product_twice_merges_into_one_row(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $product = $this->makeProduct(10);
        $variant = $this->defaultVariant($product);

        $this->actingAs($user);
        $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
        $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);
    }
}
