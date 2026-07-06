<?php

namespace Tests\Feature\Api\V1;

class AuthTest extends ApiTestCase
{
    public function test_user_can_register_via_api(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'API User',
            'email' => 'api@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['token', 'token_type', 'user' => ['id', 'email', 'name']],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'api@example.com']);
    }

    public function test_user_can_login_via_api(): void
    {
        $user = $this->createCustomer(['email' => 'login@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'login@example.com')
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $this->createCustomer(['email' => 'login@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_fetch_profile_via_auth_user_endpoint(): void
    {
        $user = $this->createCustomer();

        $response = $this->actingAsApi($user)->getJson('/api/v1/auth/user');

        $response->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_user_can_logout_via_api(): void
    {
        $user = $this->createCustomer();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }
}
