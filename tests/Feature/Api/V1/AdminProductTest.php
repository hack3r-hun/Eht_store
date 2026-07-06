<?php

namespace Tests\Feature\Api\V1;

class AdminProductTest extends ApiTestCase
{
    public function test_admin_products_requires_admin_role(): void
    {
        $customer = $this->createCustomer();

        $this->actingAsApi($customer)
            ->getJson('/api/v1/admin/products')
            ->assertForbidden();
    }

    public function test_admin_can_list_products(): void
    {
        $admin = $this->createAdmin();
        $this->createProduct();

        $this->actingAsApi($admin)
            ->getJson('/api/v1/admin/products')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_can_create_product(): void
    {
        $admin = $this->createAdmin();
        $category = \App\Models\Category::create(['name' => 'Admin Cat', 'slug' => 'admin-cat', 'is_active' => true]);

        $response = $this->actingAsApi($admin)->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name' => 'API Product',
            'sku' => 'API-001',
            'price' => 999,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'API Product');

        $this->assertDatabaseHas('products', ['sku' => 'API-001']);
    }
}
