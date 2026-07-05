<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\CartService;
use App\Services\ShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShippingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'admin']);
    }

    protected function makeProduct(float $price = 500): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'test'],
            ['name' => 'Test', 'is_active' => true]
        );

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Coaster '.uniqid(),
            'slug' => 'coaster-'.uniqid(),
            'sku' => 'C-'.uniqid(),
            'price' => $price,
            'stock_quantity' => 20,
            'is_active' => true,
        ]);
    }

    public function test_zone_rates_resolve_by_city(): void
    {
        $service = app(ShippingService::class);

        $this->assertSame(150.0, $service->rateForCity('Karachi'));
        $this->assertSame(150.0, $service->rateForCity('karachi cantt'));
        $this->assertSame(150.0, $service->rateForCity('North Karachi'));
        $this->assertSame(250.0, $service->rateForCity('Lahore'));
        $this->assertSame(250.0, $service->rateForCity('ISLAMABAD'));
        $this->assertSame(350.0, $service->rateForCity('Sukkur'));
        $this->assertSame(350.0, $service->rateForCity('Some Village'));
        // No city yet: standard rate is used as the checkout estimate.
        $this->assertSame(250.0, $service->rateForCity(null));
        $this->assertSame(250.0, $service->rateForCity('   '));
    }

    public function test_admin_settings_override_zone_rates(): void
    {
        Setting::set('shipping_local', '100');
        Setting::set('shipping_remote', '400');

        $service = app(ShippingService::class);

        $this->assertSame(100.0, $service->rateForCity('Karachi'));
        $this->assertSame(400.0, $service->rateForCity('Gwadar'));
    }

    public function test_cod_order_gets_local_rate_for_karachi(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $product = $this->makeProduct(500);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->post(route('checkout.store'), [
            'full_name' => 'Test User',
            'phone' => '03001234567',
            'address_line' => '123 Main St',
            'city' => 'Karachi',
            'payment_method' => 'cod',
        ])->assertRedirect();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(150.0, (float) $order->shipping);
        $this->assertSame(650.0, (float) $order->total);
        $this->assertStringStartsWith('EKY-', $order->order_number);
    }

    public function test_cod_order_gets_remote_rate_for_other_city(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $product = $this->makeProduct(500);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->post(route('checkout.store'), [
            'full_name' => 'Test User',
            'phone' => '03001234567',
            'address_line' => '123 Main St',
            'city' => 'Skardu',
            'payment_method' => 'cod',
        ])->assertRedirect();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(350.0, (float) $order->shipping);
        $this->assertSame(850.0, (float) $order->total);
    }

    public function test_free_shipping_above_threshold(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $product = $this->makeProduct(6000);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->post(route('checkout.store'), [
            'full_name' => 'Test User',
            'phone' => '03001234567',
            'address_line' => '123 Main St',
            'city' => 'Skardu',
            'payment_method' => 'cod',
        ])->assertRedirect();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(0.0, (float) $order->shipping);
        $this->assertSame(6000.0, (float) $order->total);
    }

    public function test_shipping_quote_endpoint_returns_city_rate(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $product = $this->makeProduct(500);

        $this->actingAs($user);
        app(CartService::class)->add($product, 1);

        $this->getJson(route('checkout.shipping-quote', ['city' => 'Karachi']))
            ->assertOk()
            ->assertJson([
                'shipping' => 150,
                'total' => 650,
                'zone_label' => 'Karachi (local delivery)',
            ]);

        $this->getJson(route('checkout.shipping-quote', ['city' => 'Hunza']))
            ->assertOk()
            ->assertJson(['shipping' => 350, 'total' => 850]);
    }

    public function test_shipping_quote_rejects_empty_cart(): void
    {
        $this->getJson(route('checkout.shipping-quote', ['city' => 'Karachi']))
            ->assertStatus(422);
    }

    protected function makeOrder(User $user, PaymentStatus $paymentStatus = PaymentStatus::Pending): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'order_number' => 'EKY-TEST'.uniqid(),
            'status' => OrderStatus::AwaitingCod,
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => $paymentStatus,
            'subtotal' => 1000,
            'tax' => 0,
            'shipping' => 250,
            'total' => 1250,
            'shipping_address' => ['full_name' => 'T', 'phone' => '0300', 'address_line' => 'St', 'city' => 'Lahore'],
        ]);
    }

    public function test_admin_can_override_order_shipping(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $order = $this->makeOrder($admin);

        $this->actingAs($admin)
            ->patch(route('admin.orders.shipping', $order), ['shipping' => 500])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(500.0, (float) $order->shipping);
        $this->assertSame(1500.0, (float) $order->total);
    }

    public function test_admin_cannot_override_shipping_on_paid_order(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $order = $this->makeOrder($admin, PaymentStatus::Paid);

        $this->actingAs($admin)
            ->patch(route('admin.orders.shipping', $order), ['shipping' => 500])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(250.0, (float) $order->shipping);
        $this->assertSame(1250.0, (float) $order->total);
    }

    public function test_customer_cannot_override_order_shipping(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $order = $this->makeOrder($customer);

        $this->actingAs($customer)
            ->patch(route('admin.orders.shipping', $order), ['shipping' => 1])
            ->assertForbidden();

        $this->assertSame(250.0, (float) $order->fresh()->shipping);
    }
}
