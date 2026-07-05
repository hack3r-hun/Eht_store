<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'admin']);
    }

    protected function makeProduct(int $stock = 20): Product
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Wire',
            'slug' => 'wire',
            'sku' => 'W-001',
            'price' => 500,
            'stock_quantity' => $stock,
            'is_active' => true,
        ]);
    }

    protected function checkoutPayload(string $method = 'cod'): array
    {
        return [
            'full_name' => 'Test User',
            'phone' => '03001234567',
            'address_line' => '123 Main St',
            'city' => 'Lahore',
            'payment_method' => $method,
        ];
    }

    public function test_checkout_is_rejected_when_stock_is_insufficient(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $product = $this->makeProduct(5);

        $this->actingAs($user);
        app(CartService::class)->add($product, 5);

        // Simulate a concurrent purchase consuming the stock after the cart was filled.
        Product::whereKey($product->id)->update(['stock_quantity' => 2]);

        $response = $this->post(route('checkout.store'), $this->checkoutPayload());

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);
        $this->assertEquals(2, $product->fresh()->stock_quantity);
    }

    public function test_cancelling_an_order_restores_stock_once(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $product = $this->makeProduct(20);

        $this->actingAs($user);
        app(CartService::class)->add($product, 3);
        $this->post(route('checkout.store'), $this->checkoutPayload())->assertRedirect();

        $this->assertEquals(17, $product->fresh()->stock_quantity);
        $order = Order::firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'cancelled'])
            ->assertRedirect();

        $this->assertEquals(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertEquals(20, $product->fresh()->stock_quantity);

        // Cancelling again must not restore stock a second time.
        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'cancelled'])
            ->assertRedirect();

        $this->assertEquals(20, $product->fresh()->stock_quantity);
    }

    public function test_deleting_an_order_removes_it_and_restores_stock(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $product = $this->makeProduct(20);

        $this->actingAs($user);
        app(CartService::class)->add($product, 3);
        $this->post(route('checkout.store'), $this->checkoutPayload())->assertRedirect();
        $this->assertEquals(17, $product->fresh()->stock_quantity);

        $order = Order::firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.orders.destroy', $order))
            ->assertRedirect(route('admin.orders.index'));

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
        $this->assertEquals(20, $product->fresh()->stock_quantity);
    }

    public function test_deleting_a_cancelled_order_does_not_double_restore_stock(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $product = $this->makeProduct(20);

        $this->actingAs($user);
        app(CartService::class)->add($product, 3);
        $this->post(route('checkout.store'), $this->checkoutPayload())->assertRedirect();

        $order = Order::firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'cancelled'])
            ->assertRedirect();
        $this->assertEquals(20, $product->fresh()->stock_quantity);

        $this->actingAs($admin)
            ->delete(route('admin.orders.destroy', $order))
            ->assertRedirect(route('admin.orders.index'));

        $this->assertEquals(20, $product->fresh()->stock_quantity);
    }

    public function test_non_admin_cannot_delete_orders(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $product = $this->makeProduct(20);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);
        $this->post(route('checkout.store'), $this->checkoutPayload())->assertRedirect();

        $order = Order::firstOrFail();

        $this->actingAs($user)
            ->delete(route('admin.orders.destroy', $order))
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_stale_unpaid_stripe_orders_are_expired_and_stock_restored(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $product = $this->makeProduct(10);

        $this->actingAs($user);
        app(CartService::class)->add($product, 4);

        $order = app(CheckoutService::class)->placeOrder([
            'full_name' => 'Test User',
            'phone' => '03001234567',
            'address_line' => '123 Main St',
            'city' => 'Lahore',
        ], PaymentMethod::Stripe);

        $this->assertEquals(6, $product->fresh()->stock_quantity);

        Order::whereKey($order->id)->update(['created_at' => now()->subHours(2)]);

        $this->artisan('orders:expire-stale')->assertSuccessful();

        $this->assertEquals(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertEquals(10, $product->fresh()->stock_quantity);
    }

    public function test_fresh_pending_stripe_orders_are_not_expired(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $product = $this->makeProduct(10);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $order = app(CheckoutService::class)->placeOrder([
            'full_name' => 'Test User',
            'phone' => '03001234567',
            'address_line' => '123 Main St',
            'city' => 'Lahore',
        ], PaymentMethod::Stripe);

        $this->artisan('orders:expire-stale')->assertSuccessful();

        $this->assertEquals(OrderStatus::Pending, $order->fresh()->status);
        $this->assertEquals(9, $product->fresh()->stock_quantity);
    }
}
