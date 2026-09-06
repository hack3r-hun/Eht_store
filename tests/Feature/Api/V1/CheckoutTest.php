<?php

namespace Tests\Feature\Api\V1;

class CheckoutTest extends ApiTestCase
{
    public function test_shipping_quote_requires_non_empty_cart(): void
    {
        $response = $this->getJson('/api/v1/checkout/shipping-quote?city=Lahore');

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Your cart is empty.');
    }

    public function test_authenticated_user_can_place_cod_order_via_api(): void
    {
        $user = $this->createCustomer();
        $product = $this->createProduct();
        $variant = $this->defaultVariant($product);

        $this->actingAsApi($user)
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ])
            ->assertCreated();

        $response = $this->actingAsApi($user)->postJson('/api/v1/checkout', [
            'full_name' => 'Test User',
            'phone' => '03001234567',
            'address_line' => '123 Main St',
            'city' => 'Lahore',
            'payment_method' => 'cod',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['order' => ['id', 'order_number', 'total']]]);

        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'size' => $variant->size,
        ]);
        $this->assertEquals(9, $variant->fresh()->stock_quantity);
    }

    public function test_bearer_token_checkout_ties_order_to_the_account_not_a_guest_token(): void
    {
        // Uses a real personal access token + Authorization header instead of Sanctum::actingAs(),
        // which forces the app's default guard and would mask a guard-resolution regression.
        $user = $this->createCustomer();
        $token = $user->createToken('test')->plainTextToken;
        $product = $this->createProduct();
        $variant = $this->defaultVariant($product);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ])->assertCreated();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/checkout', [
                'full_name' => 'Test User',
                'phone' => '03001234567',
                'address_line' => '123 Main St',
                'city' => 'Lahore',
                'payment_method' => 'cod',
            ]);

        $response->assertCreated();
        $this->assertNull($response->json('data.order.guest_access_token'));
        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_guest_checkout_returns_guest_access_token(): void
    {
        $product = $this->createProduct();
        $variant = $this->defaultVariant($product);

        $add = $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);
        $token = $add->json('data.guest_cart_token');

        $response = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/checkout', [
            'full_name' => 'Guest User',
            'email' => 'guest@example.com',
            'phone' => '03001234567',
            'address_line' => '123 Main St',
            'city' => 'Lahore',
            'payment_method' => 'cod',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['order' => ['guest_access_token']]]);
    }
}
