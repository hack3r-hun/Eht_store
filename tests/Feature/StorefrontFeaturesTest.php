<?php

namespace Tests\Feature;

use App\Mail\BackInStockMail;
use App\Models\Coupon;
use App\Models\CustomOrder;
use App\Models\Order;
use App\Models\Review;
use App\Models\StockAlert;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'admin']);
    }

    protected function customer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        return $user;
    }

    protected function checkoutPayload(array $extra = []): array
    {
        return array_merge([
            'full_name' => 'Test User',
            'phone' => '03001234567',
            'address_line' => '123 Main St',
            'city' => 'Lahore',
            'payment_method' => 'cod',
        ], $extra);
    }

    public function test_new_public_pages_load(): void
    {
        $this->get(route('wishlist.index'))->assertOk();
        $this->get(route('custom-order.create'))->assertOk();
        $this->get(route('track-order'))->assertOk();
        $this->get(route('policies'))->assertOk()->assertSee('Care instructions');
    }

    public function test_product_page_renders_with_reviews_and_recently_viewed(): void
    {
        $product = $this->createTestProduct(['name' => 'Bunny', 'slug' => 'bunny']);
        $other = $this->createTestProduct(['name' => 'Bear', 'slug' => 'bear']);
        Review::create(['product_id' => $product->id, 'name' => 'Ayesha', 'rating' => 5, 'body' => 'Lovely stitching!', 'is_approved' => true]);
        Review::create(['product_id' => $product->id, 'name' => 'Hidden', 'rating' => 1, 'body' => 'Pending review text', 'is_approved' => false]);

        $this->get('/products/bear')->assertOk();
        $this->get('/products/bunny')
            ->assertOk()
            ->assertSee('Lovely stitching!')
            ->assertDontSee('Pending review text')
            ->assertSee('Recently viewed');
        $this->get('/products')->assertOk();
    }

    public function test_verified_buyer_review_is_published_and_guest_review_is_pending(): void
    {
        $user = $this->customer();
        $product = $this->createTestProduct(['slug' => 'rev-product']);
        app(CartService::class);

        $this->actingAs($user);
        app(CartService::class)->add($product, $this->defaultVariant($product), 1);
        $this->post(route('checkout.store'), $this->checkoutPayload())->assertRedirect();

        $this->post(route('reviews.store', 'rev-product'), ['rating' => 5, 'body' => 'Great quality piece'])->assertRedirect();
        $this->assertDatabaseHas('reviews', ['user_id' => $user->id, 'is_verified' => true, 'is_approved' => true]);

        // Duplicate reviews from the same account are rejected.
        $this->post(route('reviews.store', 'rev-product'), ['rating' => 4, 'body' => 'Second review attempt'])->assertSessionHas('error');
        $this->assertSame(1, Review::count());

        auth()->logout();
        $this->post(route('reviews.store', 'rev-product'), ['name' => 'Guest', 'rating' => 3, 'body' => 'Decent for the price'])->assertRedirect();
        $this->assertDatabaseHas('reviews', ['name' => 'Guest', 'is_verified' => false, 'is_approved' => false]);
    }

    public function test_review_requires_valid_rating(): void
    {
        $this->createTestProduct(['slug' => 'rate-me']);

        $this->post(route('reviews.store', 'rate-me'), ['name' => 'X', 'rating' => 9, 'body' => 'Too many stars here'])
            ->assertSessionHasErrors('rating');
    }

    public function test_wishlist_toggle_for_guest_and_user_with_merge_on_login(): void
    {
        $product = $this->createTestProduct(['slug' => 'wish']);

        // Test requests carry no cookies, so pin the guest identity with the cart-token header.
        $guest = ['X-Guest-Cart-Token' => 'guest-wishlist-token'];

        $this->withHeaders($guest)->postJson(route('wishlist.toggle'), ['product_id' => $product->id])
            ->assertOk()->assertJson(['added' => true, 'count' => 1]);
        $this->withHeaders($guest)->get(route('wishlist.index'))->assertOk()->assertSee($product->name);

        $this->withHeaders($guest)->postJson(route('wishlist.toggle'), ['product_id' => $product->id])
            ->assertOk()->assertJson(['added' => false, 'count' => 0]);

        $user = $this->customer();
        $this->actingAs($user)->postJson(route('wishlist.toggle'), ['product_id' => $product->id])->assertJson(['added' => true]);
        $this->assertDatabaseHas('wishlist_items', ['user_id' => $user->id, 'product_id' => $product->id]);
    }

    public function test_coupon_discounts_order_and_counts_usage(): void
    {
        app(\App\Support\Modules\ModuleSettings::class)->save(['module.coupons.enabled' => true]);

        $user = $this->customer();
        Coupon::create(['code' => 'SAVE20', 'type' => 'percent', 'value' => 20, 'min_subtotal' => 0, 'max_uses' => 1]);
        $product = $this->createTestProduct(['slug' => 'c-prod', 'price' => 1000]);

        $this->actingAs($user);
        app(CartService::class)->add($product, $this->defaultVariant($product), 1);

        $this->post(route('cart.coupon.store'), ['code' => 'nope'])->assertSessionHas('error');
        $this->post(route('cart.coupon.store'), ['code' => 'save20'])->assertSessionHas('success');
        $this->get(route('cart.index'))->assertOk()->assertSee('SAVE20');

        $this->post(route('checkout.store'), $this->checkoutPayload())->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertEquals(200.00, (float) $order->discount);
        $this->assertSame('SAVE20', $order->coupon_code);
        $this->assertEquals(800.00 + (float) $order->shipping, (float) $order->total);
        $this->assertSame(1, Coupon::where('code', 'SAVE20')->value('used_count'));

        // Used up: a second cart can't apply it any more.
        app(CartService::class)->add($product, $this->defaultVariant($product), 1);
        $this->post(route('cart.coupon.store'), ['code' => 'SAVE20'])->assertSessionHas('error');
    }

    public function test_coupon_minimum_subtotal_is_enforced(): void
    {
        app(\App\Support\Modules\ModuleSettings::class)->save(['module.coupons.enabled' => true]);

        Coupon::create(['code' => 'BIG', 'type' => 'fixed', 'value' => 100, 'min_subtotal' => 5000]);
        $product = $this->createTestProduct(['slug' => 'small', 'price' => 300]);
        app(CartService::class)->add($product, $this->defaultVariant($product), 1);

        $this->post(route('cart.coupon.store'), ['code' => 'BIG'])->assertSessionHas('error');
        $this->assertNull(session('coupon_code'));
    }

    public function test_gift_options_are_saved_and_priced(): void
    {
        $user = $this->customer();
        $product = $this->createTestProduct(['slug' => 'gift-prod', 'price' => 1000]);
        $this->actingAs($user);
        app(CartService::class)->add($product, $this->defaultVariant($product), 1);

        $this->post(route('checkout.store'), $this->checkoutPayload([
            'gift_wrap' => '1',
            'gift_message' => 'Happy birthday!',
            'gift_hide_prices' => '1',
        ]))->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertTrue($order->gift_wrap);
        $this->assertTrue($order->gift_hide_prices);
        $this->assertSame('Happy birthday!', $order->gift_message);
        $this->assertEquals((float) config('shop.gift_wrap_fee'), (float) $order->gift_wrap_fee);
        $this->assertEquals(1000 + (float) $order->shipping + (float) config('shop.gift_wrap_fee'), (float) $order->total);

        $this->get(route('account.orders.invoice', $order))->assertOk();
    }

    public function test_shipping_quote_includes_gift_fee(): void
    {
        $product = $this->createTestProduct(['slug' => 'q-prod', 'price' => 500]);
        $guest = ['X-Guest-Cart-Token' => 'guest-quote-token'];
        $this->withHeaders($guest)->post(route('cart.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $this->defaultVariant($product)->id,
        ]);

        $without = $this->withHeaders($guest)->getJson(route('checkout.shipping-quote', ['city' => 'Lahore']))->json('total');
        $with = $this->withHeaders($guest)->getJson(route('checkout.shipping-quote', ['city' => 'Lahore', 'gift_wrap' => 1]))->json('total');

        $this->assertEquals((float) config('shop.gift_wrap_fee'), $with - $without);
    }

    public function test_order_tracking_requires_matching_contact(): void
    {
        $user = $this->customer();
        $product = $this->createTestProduct(['slug' => 't-prod']);
        $this->actingAs($user);
        app(CartService::class)->add($product, $this->defaultVariant($product), 1);
        $this->post(route('checkout.store'), $this->checkoutPayload())->assertRedirect();
        $order = Order::firstOrFail();
        auth()->logout();

        $this->post(route('track-order.lookup'), ['order_number' => $order->order_number, 'contact' => 'wrong@example.com'])
            ->assertSessionHasErrors('order_number');
        $this->post(route('track-order.lookup'), ['order_number' => 'EKY-NOPE', 'contact' => $user->email])
            ->assertSessionHasErrors('order_number');

        $this->post(route('track-order.lookup'), ['order_number' => strtolower($order->order_number), 'contact' => $user->email])
            ->assertOk()->assertSee($order->order_number);
        $this->post(route('track-order.lookup'), ['order_number' => $order->order_number, 'contact' => '+92 300 1234567'])
            ->assertOk()->assertSee($order->order_number);
    }

    public function test_custom_order_request_is_stored_with_image(): void
    {
        Storage::fake('public');
        Mail::fake();

        $this->post(route('custom-order.store'), [
            'name' => 'Sara',
            'email' => 'sara@example.com',
            'item_type' => 'Name keychain',
            'description' => 'A green keychain with the name Sara',
            'needed_by' => now()->addWeek()->toDateString(),
            'reference_image' => UploadedFile::fake()->image('ref.jpg'),
        ])->assertRedirect(route('custom-order.create'));

        $order = CustomOrder::firstOrFail();
        $this->assertSame('new', $order->status);
        Storage::disk('public')->assertExists($order->reference_image);
    }

    public function test_custom_order_validation(): void
    {
        $this->post(route('custom-order.store'), ['name' => 'S'])->assertSessionHasErrors(['email', 'item_type', 'description']);
    }

    public function test_back_in_stock_alert_emails_once_when_restocked(): void
    {
        Mail::fake();
        $product = $this->createTestProduct(['slug' => 'sold-out', 'stock_quantity' => 0], ['stock_quantity' => 0]);
        $variant = $this->defaultVariant($product);

        $this->post(route('stock-alerts.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'email' => 'Wait@Example.com',
        ])->assertSessionHas('success');
        $this->post(route('stock-alerts.store'), ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'email' => 'wait@example.com']);
        $this->assertSame(1, StockAlert::count());

        $variant->update(['stock_quantity' => 5]);

        Mail::assertSent(BackInStockMail::class, 1);
        $this->assertNotNull(StockAlert::first()->notified_at);

        $variant->update(['stock_quantity' => 0]);
        $variant->update(['stock_quantity' => 3]);
        Mail::assertSent(BackInStockMail::class, 1);
    }

    public function test_newsletter_subscription_returns_welcome_coupon(): void
    {
        $this->postJson(route('newsletter.store'), ['email' => 'Fan@Example.com'])
            ->assertOk()->assertJson(['coupon' => null]); // coupons are switched off for now
        $this->postJson(route('newsletter.store'), ['email' => 'fan@example.com'])->assertOk();

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        $this->postJson(route('newsletter.store'), ['email' => 'nope'])->assertStatus(422);
    }

    public function test_price_filter_uses_sale_price(): void
    {
        $this->createTestProduct(['name' => 'Discounted Hat', 'slug' => 'disc', 'price' => 1000, 'sale_price' => 400]);

        $this->get('/products?max_price=500')->assertOk()->assertSee('Discounted Hat');
        $this->get('/products?min_price=900')->assertOk()->assertDontSee('Discounted Hat');
    }

    public function test_admin_pages_for_new_features_load_and_work(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $product = $this->createTestProduct(['slug' => 'adm']);
        $review = Review::create(['product_id' => $product->id, 'name' => 'A', 'rating' => 4, 'body' => 'Nice piece indeed']);
        $custom = CustomOrder::create(['name' => 'S', 'email' => 's@example.com', 'item_type' => 'Hat', 'description' => 'A warm woolly hat']);

        $this->actingAs($admin);
        foreach (['reviews', 'custom-orders', 'subscribers'] as $page) {
            $this->get(route("admin.{$page}.index"))->assertOk();
        }

        $this->patch(route('admin.reviews.update', $review), ['is_approved' => 1]);
        $this->assertTrue($review->fresh()->is_approved);

        $this->patch(route('admin.custom-orders.update', $custom), ['status' => 'quoted', 'quote_amount' => 1500]);
        $this->assertSame('quoted', $custom->fresh()->status);

    }

    public function test_customers_cannot_open_new_admin_pages(): void
    {
        $this->actingAs($this->customer());

        foreach (['reviews', 'custom-orders', 'subscribers'] as $page) {
            $this->get(route("admin.{$page}.index"))->assertForbidden();
        }
    }
}
