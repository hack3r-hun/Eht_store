<?php

namespace Tests\Feature\Api\V1;

class CartTest extends ApiTestCase
{
    public function test_guest_can_add_item_and_receives_cart_token(): void
    {
        $product = $this->createProduct();
        $variant = $this->defaultVariant($product);

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
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

    public function test_add_to_cart_requires_product_variant_id(): void
    {
        $product = $this->createProduct();

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['product_variant_id']);
    }

    public function test_guest_cart_token_is_merged_on_api_login(): void
    {
        $product = $this->createProduct();
        $variant = $this->defaultVariant($product);
        $user = $this->createCustomer(['email' => 'cartmerge@example.com']);

        $addResponse = $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
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
        $product = $this->createProduct(['stock_quantity' => 2], ['stock_quantity' => 2]);
        $variant = $this->defaultVariant($product);

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.count', 2);
    }

    public function test_same_product_different_sizes_are_separate_lines(): void
    {
        $product = $this->createProduct(['price' => 100, 'stock_quantity' => 10]);
        $sizeM = $this->defaultVariant($product);
        $sizeS = $this->attachDefaultVariant($product, [
            'size' => 'S',
            'sku' => $product->sku.'-S',
            'price' => 90,
            'stock_quantity' => 5,
        ]);

        $first = $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'product_variant_id' => $sizeS->id,
            'quantity' => 1,
        ])->assertCreated();

        $token = $first->json('data.guest_cart_token');

        $response = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'product_variant_id' => $sizeM->id,
            'quantity' => 2,
        ]);

        $response->assertCreated()->assertJsonPath('data.count', 3);
        $this->assertCount(2, $response->json('data.items'));
    }

    public function test_bearer_token_request_ties_cart_item_to_the_account_not_a_guest_session(): void
    {
        // Uses a real personal access token + Authorization header instead of Sanctum::actingAs(),
        // which forces the app's default guard and would mask a guard-resolution regression.
        $user = $this->createCustomer();
        $token = $user->createToken('test')->plainTextToken;
        $product = $this->createProduct();
        $variant = $this->defaultVariant($product);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        $response->assertCreated();
        $this->assertNull($response->json('data.guest_cart_token'));
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
        ]);
    }
}
