<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'admin']);
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['email' => $user->email])->assertRedirect();
        }

        $this->post('/forgot-password', ['email' => $user->email])->assertStatus(429);
    }

    public function test_security_headers_are_present_on_web_and_api(): void
    {
        $web = $this->get('/');
        $web->assertOk();
        $web->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $web->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertNotEmpty($web->headers->get('Content-Security-Policy'));
        $this->assertNotEmpty($web->headers->get('Permissions-Policy'));

        $api = $this->getJson('/api/v1/products');
        $api->assertOk();
        $api->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertNotEmpty($api->headers->get('Content-Security-Policy'));
    }

    public function test_cart_count_uses_lightweight_sum_query(): void
    {
        $product = $this->createTestProduct([
            'name' => 'Badge Yarn',
            'slug' => 'badge-yarn',
            'sku' => 'BDG-001',
            'stock_quantity' => 10,
        ], ['stock_quantity' => 10]);
        $variant = $this->defaultVariant($product);

        $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ])->assertRedirect();

        $this->assertSame(3, app(CartService::class)->count());
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);
    }
}
