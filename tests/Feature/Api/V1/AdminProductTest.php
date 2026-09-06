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

    public function test_admin_can_create_product_with_size_variants(): void
    {
        $admin = $this->createAdmin();
        $category = \App\Models\Category::create(['name' => 'Admin Cat', 'slug' => 'admin-cat', 'is_active' => true]);

        $response = $this->actingAsApi($admin)->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name' => 'API Product',
            'sku' => 'API-001',
            'is_active' => true,
            'variants' => $this->sizeVariantsPayload(['S', 'M', 'L'], 999, 5, [
                'S' => ['price' => 899, 'stock_quantity' => 3],
                'M' => ['price' => 999, 'stock_quantity' => 5],
                'L' => ['price' => 1099, 'stock_quantity' => 2],
            ]),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'API Product');

        $this->assertDatabaseHas('products', ['sku' => 'API-001']);
        $this->assertDatabaseHas('product_variants', ['sku' => 'API-001-S', 'size' => 'S', 'price' => 899]);
        $this->assertDatabaseHas('product_variants', ['sku' => 'API-001-M', 'size' => 'M', 'price' => 999]);
        $this->assertDatabaseCount('product_variants', 3);
    }

    public function test_admin_api_requires_admin_token_ability(): void
    {
        $admin = $this->createAdmin();

        \Laravel\Sanctum\Sanctum::actingAs($admin, ['customer']);

        $this->getJson('/api/v1/admin/products')->assertForbidden();
    }
}
