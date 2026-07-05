<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'customer']);
    }

    public function test_admin_can_login_without_email_verification(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'email_verified_at' => null,
            'verification_method' => 'otp',
        ]);
        $admin->assignRole('admin');

        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_is_not_sent_to_otp_verification_page(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => null,
            'verification_method' => 'otp',
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('verification.otp'));

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_customer_still_requires_email_verification(): void
    {
        $customer = User::factory()->create([
            'email_verified_at' => null,
            'verification_method' => 'otp',
        ]);
        $customer->assignRole('customer');

        $response = $this->post('/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($customer);
        $response->assertRedirect(route('verification.otp'));
    }
}
