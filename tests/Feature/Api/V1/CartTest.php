<?php

namespace Tests\Feature\Api\V1;

class CartTest extends ApiTestCase
{
    public function test_guest_can_add_item_and_receives_cart_token(): void
    {
        $product = $this->createProduct();

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.count', 2)
            ->assertJsonStructure(['data' => ['guest_cart_token', 'items', 'subtotal']]);

        $token = $response->json('data.guest_cart_token');
        $this->assertNotEmpty($token);

        $showResponse = $this->withHeader('X-Guest-Cart-Token', $token)
            ->getJson('/api/v1/cart');

        $showResponse->assertOk()->assertJsonPath('data.count', 2);
    }

    public function test_guest_cart_token_is_merged_on_api_login(): void
    {
        $product = $this->createProduct();
        $user = $this->createCustomer(['email' => 'cartmerge@example.com']);

        $addResponse = $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $token = $addResponse->json('data.guest_cart_token');

        $this->withHeader('X-Guest-Cart-Token', $token)
            ->postJson('/api/v1/auth/login', [
                'email' => 'cartmerge@example.com',
                'password' => 'password',
            ])
            ->assertOk();

        $this->actingAsApi($user)
            ->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.count', 1);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $product = $this->createProduct(['stock_quantity' => 2]);

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.count', 2);
    }
}
