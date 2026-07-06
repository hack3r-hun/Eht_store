<?php

namespace Tests\Feature\Api\V1;

class ProductTest extends ApiTestCase
{
    public function test_products_index_returns_paginated_json(): void
    {
        $this->createProduct(['name' => 'Yarn Bunny', 'slug' => 'yarn-bunny']);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'slug', 'effective_price']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_product_show_returns_product_by_slug(): void
    {
        $product = $this->createProduct(['name' => 'Cozy Shawl', 'slug' => 'cozy-shawl']);

        $response = $this->getJson('/api/v1/products/cozy-shawl');

        $response->assertOk()
            ->assertJsonPath('data.product.slug', 'cozy-shawl')
            ->assertJsonPath('data.product.id', $product->id);
    }

    public function test_product_show_returns_404_for_unknown_slug(): void
    {
        $response = $this->getJson('/api/v1/products/does-not-exist');

        $response->assertNotFound();
    }
}
