<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Hits every main page of the storefront and admin dashboard so a broken
 * view, route, or controller can never ship silently.
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'admin']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');

        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Smoke Bulb',
            'slug' => 'smoke-bulb',
            'sku' => 'SMK-001',
            'price' => 100,
            'stock_quantity' => 10,
            'is_active' => true,
            'is_featured' => true,
        ]);
        $this->product->images()->create(['path' => 'https://example.com/a.jpg', 'is_primary' => true, 'sort_order' => 0]);
    }

    public function test_public_pages_load(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('about'))->assertOk();
        $this->get(route('contact'))->assertOk();
        $this->get(route('products.index'))->assertOk();
        $this->get(route('products.index', ['search' => 'bulb', 'sort' => 'price_low', 'min_price' => 1, 'max_price' => 999]))->assertOk();
        $this->get(route('products.show', $this->product->slug))->assertOk();
        $this->get(route('cart.index'))->assertOk();
        $this->get(route('sitemap'))->assertOk();
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
        $this->get(route('password.request'))->assertOk();
    }

    public function test_checkout_flow_pages_load(): void
    {
        // Empty cart redirects away from checkout.
        $this->get(route('checkout.index'))->assertRedirect(route('cart.index'));

        $this->actingAs($this->customer);
        app(CartService::class)->add($this->product, 1);
        $this->get(route('checkout.index'))->assertOk();

        $this->post(route('checkout.store'), [
            'full_name' => 'Smoke Tester',
            'phone' => '03001234567',
            'address_line' => '123 Main St',
            'city' => 'Lahore',
            'payment_method' => 'cod',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->get(route('checkout.success', $order))->assertOk();
    }

    public function test_account_pages_load_for_verified_user(): void
    {
        $this->actingAs($this->customer);

        $this->get(route('profile.edit'))->assertOk();
        $this->get(route('account.addresses'))->assertOk();
        $this->get(route('account.orders.index'))->assertOk();
    }

    public function test_admin_pages_load(): void
    {
        // Data so every admin page has something to render.
        $this->actingAs($this->customer);
        app(CartService::class)->add($this->product, 1);
        $this->post(route('checkout.store'), [
            'full_name' => 'Smoke Tester',
            'phone' => '03001234567',
            'address_line' => '123 Main St',
            'city' => 'Lahore',
            'payment_method' => 'cod',
        ]);
        $order = Order::firstOrFail();
        $message = ContactMessage::create([
            'name' => 'Smoke',
            'email' => 'smoke@example.com',
            'subject' => 'Hello',
            'message' => 'Testing',
        ]);
        $page = Page::create(['slug' => 'about', 'title' => 'About Us', 'content' => 'Hello']);
        $category = Category::first();

        $this->actingAs($this->admin);

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.statistics.index'))->assertOk();
        $this->get(route('admin.statistics.index', ['period' => 'day']))->assertOk();
        $this->get(route('admin.statistics.index', ['period' => 'year', 'year' => now()->year]))->assertOk();
        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.products.index', ['search' => 'Smoke', 'status' => 'active', 'stock' => 'low', 'per_page' => 10]))->assertOk();
        $this->get(route('admin.products.create'))->assertOk();
        $this->get(route('admin.products.edit', $this->product))->assertOk();
        $this->get(route('admin.categories.index'))->assertOk();
        $this->get(route('admin.categories.create'))->assertOk();
        $this->get(route('admin.categories.edit', $category))->assertOk();
        $this->get(route('admin.orders.index'))->assertOk();
        $this->get(route('admin.orders.index', ['status' => 'awaiting_cod', 'payment_method' => 'cod', 'search' => 'Smoke', 'per_page' => 10]))->assertOk();
        $this->get(route('admin.orders.show', $order))->assertOk();
        $this->get(route('admin.customers.index'))->assertOk();
        $this->get(route('admin.customers.index', ['search' => $this->customer->email, 'orders' => 'with', 'sort' => 'orders', 'per_page' => 10]))->assertOk();
        $this->get(route('admin.messages.index'))->assertOk();
        $this->get(route('admin.messages.show', $message))->assertOk();
        $this->get(route('admin.pages.index'))->assertOk();
        $this->get(route('admin.pages.edit', $page))->assertOk();
        $this->get(route('admin.settings.index'))->assertOk();
    }
}
