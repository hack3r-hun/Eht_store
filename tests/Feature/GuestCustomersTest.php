<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\CartService;
use App\Support\ContactNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuestCustomersTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    protected function guestOrder(string $email, string $name, string $phone, array $extra = []): Order
    {
        $order = Order::create(array_merge([
            'order_number' => 'EKY-'.strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'status' => 'delivered',
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'subtotal' => 1000, 'tax' => 0, 'shipping' => 150, 'total' => 1150,
            'shipping_address' => ['full_name' => $name, 'phone' => $phone, 'email' => $email, 'city' => 'Karachi'],
            'contact_name' => $name,
            'contact_email' => ContactNormalizer::email($email),
            'contact_phone' => ContactNormalizer::phone($phone),
            'marketing_opt_in' => true,
        ], array_diff_key($extra, ['created_at' => 1])));

        if (isset($extra['created_at'])) {
            $order->forceFill(['created_at' => $extra['created_at']])->save();
        }

        $product = $this->createTestProduct();
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name, 'product_sku' => $product->sku, 'size' => 'M', 'quantity' => 1, 'unit_price' => 1000, 'line_total' => 1000]);

        return $order;
    }

    public function test_contact_normalizer(): void
    {
        $this->assertSame('923001234567', ContactNormalizer::phone('0300 1234567'));
        $this->assertSame('923001234567', ContactNormalizer::phone('+92 300-1234567'));
        $this->assertSame('923001234567', ContactNormalizer::phone('00923001234567'));
        $this->assertNull(ContactNormalizer::phone('abc'));
        $this->assertNull(ContactNormalizer::phone('12'));
        $this->assertSame('sara@example.com', ContactNormalizer::email('  Sara@Example.COM '));
        $this->assertNull(ContactNormalizer::email('not-an-email'));
    }

    public function test_guest_checkout_stores_contact_details_and_offers_choice(): void
    {
        $product = $this->createTestProduct(['slug' => 'guest-item', 'price' => 800]);
        $guest = ['X-Guest-Cart-Token' => 'guest-checkout-token'];

        $this->withHeaders($guest)->post(route('cart.store'), ['product_id' => $product->id, 'product_variant_id' => $this->defaultVariant($product)->id]);
        $this->withHeaders($guest)->post(route('checkout.store'), [
            'full_name' => 'Guest Gul',
            'email' => 'Gul@Example.com',
            'phone' => '0301 5551234',
            'address_line' => '1 Street',
            'city' => 'Lahore',
            'payment_method' => 'cod',
            'marketing_opt_in' => '1',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertNull($order->user_id);
        $this->assertSame('gul@example.com', $order->contact_email);
        $this->assertSame('923015551234', $order->contact_phone);
        $this->assertSame('Guest Gul', $order->contact_name);
        $this->assertTrue($order->marketing_opt_in);
    }

    public function test_not_ticking_the_offers_box_means_no_consent(): void
    {
        $product = $this->createTestProduct(['slug' => 'guest-item-2', 'price' => 800]);
        $guest = ['X-Guest-Cart-Token' => 'guest-no-consent'];

        $this->withHeaders($guest)->post(route('cart.store'), ['product_id' => $product->id, 'product_variant_id' => $this->defaultVariant($product)->id]);
        $this->withHeaders($guest)->post(route('checkout.store'), [
            'full_name' => 'Private Person', 'email' => 'p@example.com', 'phone' => '03001112222',
            'address_line' => 'x', 'city' => 'Lahore', 'payment_method' => 'cod', 'marketing_opt_in' => '0',
        ])->assertRedirect();

        $this->assertFalse(Order::firstOrFail()->marketing_opt_in);
    }

    public function test_statistics_count_guests_as_new_and_returning_customers(): void
    {
        Role::findOrCreate('customer');

        // Ayesha (guest) ordered last month and again this week → returning.
        $this->guestOrder('ayesha@example.com', 'Ayesha', '03001234567', ['created_at' => now()->subDays(40)]);
        $this->guestOrder('AYESHA@example.com', 'Ayesha', '+92 300 1234567', ['created_at' => now()->subDays(2)]);
        // Bilal (guest) and Hina (guest, phone only) order for the first time this week → new.
        $this->guestOrder('bilal@example.com', 'Bilal', '03111111111', ['created_at' => now()->subDays(1)]);
        $this->guestOrder('', 'Hina', '0322 2222222', ['created_at' => now()->subDays(1)]);

        $service = app(\App\Services\StatisticsService::class);
        $data = $service->build(now()->subDays(6)->startOfDay(), now()->endOfDay());

        $this->assertSame(3, $data['current']['buyers']);
        $this->assertSame(1, $data['current']['returning_customers']);
        $this->assertSame(2, $data['current']['new_customers']);
        $this->assertSame(3, $data['current']['guest_buyers']);
        $this->assertSame(0, $data['current']['account_buyers']);
        $this->assertSame(3, $data['totals']['customers']);

        $page = $this->actingAs($this->admin())->get(route('admin.statistics.index', ['period' => 'week']));
        $page->assertOk()->assertSee('New vs returning buyers')->assertSee('Guest vs account')->assertSee('3 checked out as guests', false);
    }

    public function test_buyer_directory_lists_guests_with_latest_offers_choice(): void
    {
        $this->guestOrder('ayesha@example.com', 'Ayesha', '03001234567', ['created_at' => now()->subDays(10), 'marketing_opt_in' => true]);
        // Her newer order did not tick the box → she is no longer reachable for offers.
        $this->guestOrder('ayesha@example.com', 'Ayesha K', '03001234567', ['created_at' => now()->subDays(1), 'marketing_opt_in' => false]);
        $this->guestOrder('bilal@example.com', 'Bilal', '03111111111');

        $this->actingAs($this->admin());

        $this->get(route('admin.customers.buyers'))
            ->assertOk()
            ->assertSee('Everyone who ordered')
            ->assertSee('Ayesha K')
            ->assertSee('bilal@example.com')
            ->assertSee('+923111111111');

        $this->get(route('admin.customers.buyers', ['marketing' => 'opted']))->assertOk()->assertSee('bilal@example.com')->assertDontSee('ayesha@example.com');
        $this->get(route('admin.customers.buyers', ['type' => 'account']))->assertOk()->assertDontSee('bilal@example.com');
        $this->get(route('admin.customers.buyers', ['search' => '0311 111']))->assertOk()->assertSee('Bilal')->assertDontSee('ayesha@example.com');
    }

    public function test_buyer_csv_export_respects_filters_and_is_formula_safe(): void
    {
        $this->guestOrder('ok@example.com', 'Sana', '03001234567');
        $this->guestOrder('evil@example.com', '=cmd|calc', '03007654321', ['marketing_opt_in' => false]);

        $response = $this->actingAs($this->admin())->get(route('admin.customers.buyers.export', ['marketing' => 'opted']));
        $csv = $response->streamedContent();

        $response->assertOk();
        $this->assertStringContainsString('Name,Email,"Phone (international)"', $csv);
        $this->assertStringContainsString('ok@example.com', $csv);
        $this->assertStringContainsString('+923001234567', $csv);
        $this->assertStringNotContainsString('evil@example.com', $csv);

        $all = $this->get(route('admin.customers.buyers.export'))->streamedContent();
        $this->assertStringContainsString("'=cmd|calc", $all);
    }

    public function test_customers_cannot_open_the_buyer_list(): void
    {
        Role::findOrCreate('customer');
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)->get(route('admin.customers.buyers'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.customers.buyers.export'))->assertForbidden();
    }

    public function test_dashboard_has_refresh_controls_and_fresh_param_bypasses_the_cache(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $html = $this->get(route('admin.dashboard'))->assertOk()->assertSee('dashboardLive()', false)->assertSee('id="dashboard-live"', false)->assertSee('Refresh')->assertSee('Auto-refresh')->getContent();
        $this->assertStringContainsString('data-orders-total="0"', $html);
        $this->assertStringContainsString('id="admin-nav"', $html);

        // The sidebar badges are cached for a minute; a new order must not leave them stale after a refresh.
        $this->assertSame(0, Cache::get('admin.sidebar.badges')['pending_orders']);
        $this->guestOrder('new@example.com', 'New Buyer', '03001230000', ['status' => 'pending', 'created_at' => now()]);

        // Saving an order already clears the dashboard numbers, so they are current even without the button…
        $this->get(route('admin.dashboard'))->assertSee('data-orders-total="1"', false);
        $this->assertSame(0, Cache::get('admin.sidebar.badges')['pending_orders']);

        // …and the refresh button (fresh=1) also refreshes the sidebar badges.
        $this->get(route('admin.dashboard', ['fresh' => 1]))->assertOk()->assertSee('new orders to confirm')->assertDontSee('data-orders-total="0"', false);
        $this->assertSame(1, Cache::get('admin.sidebar.badges')['pending_orders']);
    }
}
