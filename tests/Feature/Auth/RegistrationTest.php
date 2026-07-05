<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'customer']);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_with_link_verification(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'verification_method' => 'link',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice'));
        $this->assertNull(auth()->user()->email_verified_at);
    }

    public function test_new_users_can_register_with_otp_verification(): void
    {
        $response = $this->post('/register', [
            'name' => 'OTP User',
            'email' => 'otp@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'verification_method' => 'otp',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.otp'));
    }

    public function test_user_can_verify_with_otp(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'verification_method' => 'otp',
            'email_otp' => Hash::make('123456'),
            'email_otp_expires_at' => now()->addMinutes(10),
        ]);
        $user->assignRole('customer');

        $response = $this->actingAs($user)->post(route('verification.otp.verify'), [
            'otp' => '123456',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
