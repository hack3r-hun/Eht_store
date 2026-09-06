<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'admin']);
    }

    public function test_user_can_place_cod_order(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $product = $this->createTestProduct([
            'name' => 'Wire',
            'slug' => 'wire',
            'sku' => 'W-001',
            'price' => 500,
            'stock_quantity' => 20,
        ]);
        $variant = $this->defaultVariant($product);

        $this->actingAs($user);
        app(CartService::class)->add($product, $variant, 1);

        $response = $this->post(route('checkout.store'), [
            'full_name' => 'Test User',
            'phone' => '03001234567',
            'address_line' => '123 Main St',
            'city' => 'Lahore',
            'payment_method' => 'cod',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'size' => 'M',
            'product_variant_id' => $variant->id,
        ]);
        $this->assertEquals(19, $variant->fresh()->stock_quantity);
        $this->assertEquals(19, $product->fresh()->stock_quantity);
    }

    public function test_inactive_product_cannot_be_checked_out_from_stale_cart(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $product = $this->createTestProduct([
            'name' => 'Hidden Wire',
            'slug' => 'hidden-wire',
            'sku' => 'H-001',
            'price' => 500,
            'stock_quantity' => 20,
        ]);
        $variant = $this->defaultVariant($product);

        $this->actingAs($user);
        app(CartService::class)->add($product, $variant, 1);
        $product->update(['is_active' => false]);

        $response = $this->from(route('checkout.index'))->post(route('checkout.store'), [
            'full_name' => 'Test User',
            'phone' => '03001234567',
            'address_line' => '123 Main St',
            'city' => 'Lahore',
            'payment_method' => 'cod',
        ]);

        $response->assertRedirect(route('checkout.index'));
        $this->assertDatabaseMissing('orders', ['user_id' => $user->id]);
        $this->assertEquals(20, $variant->fresh()->stock_quantity);
        $this->assertEquals(20, $product->fresh()->stock_quantity);
    }
}
