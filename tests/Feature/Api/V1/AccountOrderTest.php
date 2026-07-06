<?php

namespace Tests\Feature\Api\V1;

use App\Models\Order;

class AccountOrderTest extends ApiTestCase
{
    public function test_orders_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/orders')->assertUnauthorized();
    }

    public function test_user_can_list_own_orders(): void
    {
        $user = $this->createCustomer();
        Order::create([
            'user_id' => $user->id,
            'order_number' => 'EKY-TEST0001',
            'status' => 'awaiting_cod',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'subtotal' => 100,
            'tax' => 0,
            'shipping' => 0,
            'total' => 100,
            'shipping_address' => ['full_name' => 'Test', 'phone' => '1', 'address_line' => 'A', 'city' => 'Lahore'],
        ]);

        $this->actingAsApi($user)
            ->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $owner = $this->createCustomer(['email' => 'owner@example.com']);
        $other = $this->createCustomer(['email' => 'other@example.com']);

        $order = Order::create([
            'user_id' => $owner->id,
            'order_number' => 'EKY-TEST0002',
            'status' => 'awaiting_cod',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'subtotal' => 100,
            'tax' => 0,
            'shipping' => 0,
            'total' => 100,
            'shipping_address' => ['full_name' => 'Test', 'phone' => '1', 'address_line' => 'A', 'city' => 'Lahore'],
        ]);

        $this->actingAsApi($other)
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertForbidden();
    }
}
