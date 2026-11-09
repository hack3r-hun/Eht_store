<?php

namespace Tests\Feature;

use App\Models\CustomOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInsightsTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(string $password = 'old-password-1'): User
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
        $admin = User::factory()->create(['password' => Hash::make($password)]);
        $admin->assignRole('admin');

        return $admin;
    }

    protected function makeOrder(array $attributes = [], array $item = []): Order
    {
        $product = $item['product'] ?? $this->createTestProduct(['price' => 500]);
        $createdAt = $attributes['created_at'] ?? null;
        unset($attributes['created_at']);

        $order = Order::create(array_merge([
            'order_number' => 'EKY-'.strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'status' => 'delivered',
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'subtotal' => 1000,
            'tax' => 0,
            'shipping' => 150,
            'total' => 1150,
            'shipping_address' => ['full_name' => 'Test', 'phone' => '0300', 'city' => 'karachi'],
        ], $attributes));

        if ($createdAt) {
            $order->forceFill(['created_at' => $createdAt])->save();
        }

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'size' => 'M',
            'quantity' => 2,
            'unit_price' => 500,
            'line_total' => 1000,
        ]);

        return $order;
    }

    // ------------------------------------------------------------ password

    public function test_admin_can_change_their_password(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk()->assertSee('Change your password');

        $this->actingAs($admin)->put(route('admin.settings.password'), [
            'current_password' => 'old-password-1',
            'password' => 'brand-new-pass-2',
            'password_confirmation' => 'brand-new-pass-2',
        ])->assertRedirect(route('admin.settings.index'))->assertSessionHas('success');

        $this->assertTrue(Hash::check('brand-new-pass-2', $admin->fresh()->password));
    }

    public function test_password_change_requires_the_correct_current_password_and_a_new_value(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.password'), [
            'current_password' => 'wrong',
            'password' => 'brand-new-pass-2',
            'password_confirmation' => 'brand-new-pass-2',
        ])->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->actingAs($admin)->put(route('admin.settings.password'), [
            'current_password' => 'old-password-1',
            'password' => 'brand-new-pass-2',
            'password_confirmation' => 'does-not-match',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->actingAs($admin)->put(route('admin.settings.password'), [
            'current_password' => 'old-password-1',
            'password' => 'old-password-1',
            'password_confirmation' => 'old-password-1',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->assertTrue(Hash::check('old-password-1', $admin->fresh()->password));
    }

    public function test_customers_cannot_use_the_admin_password_route(): void
    {
        Role::findOrCreate('customer');
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)->put(route('admin.settings.password'), [
            'current_password' => 'password',
            'password' => 'brand-new-pass-2',
            'password_confirmation' => 'brand-new-pass-2',
        ])->assertForbidden();
    }

    // ------------------------------------------------------------ statistics

    public function test_delta_helper(): void
    {
        $this->assertSame(['pct' => 50.0, 'dir' => 'up'], StatisticsService::delta(150, 100));
        $this->assertSame(['pct' => -25.0, 'dir' => 'down'], StatisticsService::delta(75, 100));
        $this->assertSame(['pct' => null, 'dir' => 'new'], StatisticsService::delta(10, 0));
        $this->assertSame(['pct' => null, 'dir' => 'flat'], StatisticsService::delta(0, 0));
    }

    public function test_statistics_page_shows_real_numbers_and_comparison(): void
    {
        $buyer = User::factory()->create();
        $this->makeOrder(['user_id' => $buyer->id, 'created_at' => now()->subDays(3), 'total' => 1150]);
        $this->makeOrder(['created_at' => now()->subDays(1), 'status' => 'pending', 'payment_status' => 'pending', 'total' => 850, 'shipping_address' => ['city' => 'Lahore']]);
        $this->makeOrder(['created_at' => now()->subDays(1), 'status' => 'cancelled', 'payment_status' => 'pending', 'total' => 400]);
        // Previous 7-day window: one order, so "this week" is compared with it.
        $this->makeOrder(['created_at' => now()->subDays(10), 'total' => 1000]);

        $response = $this->actingAs($this->admin())->get(route('admin.statistics.index', ['period' => 'week']));

        $response->assertOk()
            ->assertSee('In plain words')
            ->assertSee('Paid revenue')
            ->assertSee('Rs. 1,150')   // paid revenue this week
            ->assertSee('Rs. 2,000')   // order value this week (1150 + 850, cancelled excluded)
            ->assertSee('Cancellation rate')
            ->assertSee('Best days of the week')
            ->assertSee('chart-trend', false)
            ->assertSee('What do these words mean?');

        $payload = json_decode(
            html_entity_decode(preg_replace('/.*id="stats-chart-data">(.*?)<\/script>.*/s', '$1', $response->getContent())),
            true
        );

        $this->assertSame(7, count($payload['trend']['labels']));
        $this->assertSame(3, array_sum($payload['trend']['orders']));
        $this->assertEquals(2000.0, array_sum($payload['trend']['order_value']));
        $this->assertEquals(1150.0, array_sum($payload['trend']['paid']));
        $this->assertContains('Karachi', $payload['cities']['labels']);
        $this->assertContains('Lahore', $payload['cities']['labels']);
    }

    public function test_statistics_page_is_friendly_when_there_is_no_data(): void
    {
        $this->actingAs($this->admin())->get(route('admin.statistics.index', ['period' => 'day']))
            ->assertOk()
            ->assertSee('No orders were placed in this period yet')
            ->assertSee('Nothing to show for this period yet.');
    }

    public function test_statistics_covers_every_period_and_survives_bad_input(): void
    {
        $this->actingAs($this->admin());

        foreach (['day', 'week', 'days30', 'month', 'last_month', 'year', 'custom', 'nonsense'] as $period) {
            $this->get(route('admin.statistics.index', ['period' => $period]))->assertOk();
        }

        $this->get(route('admin.statistics.index', ['period' => 'custom', 'date_from' => '2026-05-10', 'date_to' => '2026-04-01']))->assertOk();
        $this->get(route('admin.statistics.index', ['period' => 'custom', 'date_from' => 'garbage', 'date_to' => 'junk']))->assertOk();
        $this->get(route('admin.statistics.index', ['period' => 'month', 'month' => 'not-a-month']))->assertOk();
        $this->get(route('admin.statistics.index', ['period' => 'year', 'year' => 99999]))->assertOk();
    }

    public function test_statistics_includes_activity_beyond_orders(): void
    {
        $product = $this->createTestProduct(['name' => 'Wished Bear', 'slug' => 'wished']);
        WishlistItem::create(['session_id' => 'a', 'product_id' => $product->id]);
        WishlistItem::create(['session_id' => 'b', 'product_id' => $product->id]);
        CustomOrder::create(['name' => 'S', 'email' => 's@example.com', 'item_type' => 'Hat', 'description' => 'A warm woolly hat']);
        Review::create(['product_id' => $product->id, 'name' => 'A', 'rating' => 5, 'body' => 'Beautiful work!', 'is_approved' => true]);
        Review::create(['product_id' => $product->id, 'name' => 'B', 'rating' => 2, 'body' => 'Pending one here', 'is_approved' => false]);

        $this->actingAs($this->admin())->get(route('admin.statistics.index', ['period' => 'day']))
            ->assertOk()
            ->assertSee('Wished Bear')
            ->assertSee('Custom order requests')
            ->assertSee('1 review(s) waiting for approval');
    }

    public function test_orders_csv_export_is_formula_safe(): void
    {
        $this->makeOrder(['shipping_address' => ['city' => '=HYPERLINK("http://evil")'], 'created_at' => now()->subDay()]);

        $response = $this->actingAs($this->admin())->get(route('admin.statistics.export', ['period' => 'week']));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Order,Date,Status', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
    }

    public function test_customers_cannot_open_statistics_or_export(): void
    {
        Role::findOrCreate('customer');
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)->get(route('admin.statistics.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.statistics.export'))->assertForbidden();
    }

    // ------------------------------------------------------------ dashboard

    public function test_dashboard_lists_what_needs_attention_and_links_to_statistics(): void
    {
        $this->makeOrder(['status' => 'pending', 'payment_status' => 'pending', 'created_at' => now()]);
        CustomOrder::create(['name' => 'S', 'email' => 's@example.com', 'item_type' => 'Hat', 'description' => 'A warm woolly hat']);

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Needs your attention')
            ->assertSee('new orders to confirm')
            ->assertSee('custom order requests to quote')
            ->assertSee(route('admin.statistics.index', ['period' => 'week']), false)
            ->assertDontSee('Archived')
            ->assertDontSee('Total Revenue');
    }

    public function test_dashboard_shows_an_all_clear_state(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee("You're all caught up", false);
    }
}
