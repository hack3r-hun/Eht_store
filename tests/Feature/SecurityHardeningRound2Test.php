<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\EmailVerificationService;
use App\Services\OrderService;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityHardeningRound2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'admin']);
    }

    /** @dataProvider xssPayloads */
    public function test_html_sanitizer_blocks_xss_vectors(string $payload): void
    {
        $clean = strtolower((string) HtmlSanitizer::sanitize($payload));

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('onload', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('<svg', $clean);
        $this->assertStringNotContainsString('<iframe', $clean);
    }

    public static function xssPayloads(): array
    {
        return [
            'slash separated handler' => ['<img/src=x/onerror=alert(1)>'],
            'svg onload' => ['<svg onload=alert(1)>'],
            'entity encoded js url' => ['<a href="&#106;avascript:alert(1)">x</a>'],
            'tab in scheme' => ['<a href="java&#9;script:alert(1)">x</a>'],
            'script tag' => ['<p>hi</p><script>alert(1)</script>'],
            'iframe' => ['<iframe src="https://evil.test"></iframe>'],
            'uppercase handler' => ['<div ONCLICK="alert(1)">x</div>'],
            'nested unknown tag' => ['<foo><img src=x onerror=alert(1)></foo>'],
            'data html url' => ['<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>'],
        ];
    }

    public function test_html_sanitizer_keeps_normal_formatting(): void
    {
        $clean = HtmlSanitizer::sanitize('<h2>Title</h2><p>Hello <strong>world</strong> <a href="https://example.com" target="_blank">link</a></p><ul><li>One</li></ul>');

        $this->assertStringContainsString('<h2>Title</h2>', $clean);
        $this->assertStringContainsString('<strong>world</strong>', $clean);
        $this->assertStringContainsString('href="https://example.com"', $clean);
        $this->assertStringContainsString('noopener', $clean);
        $this->assertStringContainsString('<li>One</li>', $clean);
    }

    public function test_cancelled_order_cannot_be_reopened(): void
    {
        $order = Order::create([
            'order_number' => 'EKY-TEST0001', 'status' => OrderStatus::Cancelled,
            'payment_method' => 'cod', 'payment_status' => 'pending',
            'subtotal' => 10, 'tax' => 0, 'shipping' => 0, 'total' => 10,
            'shipping_address' => ['full_name' => 'A'],
        ]);

        $this->expectException(\RuntimeException::class);

        app(OrderService::class)->updateStatus($order, OrderStatus::Shipped);
    }

    public function test_otp_is_burned_after_too_many_wrong_guesses(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $user->assignRole('customer');
        $user->forceFill([
            'email_otp' => Hash::make('123456'),
            'email_otp_expires_at' => now()->addMinutes(10),
        ])->save();

        $service = app(EmailVerificationService::class);

        for ($i = 0; $i < EmailVerificationService::MAX_OTP_ATTEMPTS; $i++) {
            $this->assertFalse($service->verifyOtp($user->fresh(), '000000'));
        }

        // Even the correct code no longer works once the attempt budget is spent.
        $this->assertFalse($service->verifyOtp($user->fresh(), '123456'));
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_password_change_revokes_api_tokens(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPassw0rd!x')]);
        $user->assignRole('customer');
        $user->createToken('t');

        $this->actingAs($user)->put('/password', [
            'current_password' => 'OldPassw0rd!x',
            'password' => 'NewPassw0rd!xyz',
            'password_confirmation' => 'NewPassw0rd!xyz',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, $user->tokens()->count());
    }
}
