<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'admin']);
    }

    protected function createCustomer(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('customer');

        return $user;
    }

    protected function createAdmin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('admin');

        return $user;
    }

    protected function actingAsApi(User $user): static
    {
        Sanctum::actingAs($user, ['*']);

        return $this;
    }

    protected function createProduct(array $overrides = []): Product
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test-'.uniqid(), 'is_active' => true]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product-'.uniqid(),
            'sku' => 'SKU-'.uniqid(),
            'price' => 500,
            'stock_quantity' => 10,
            'is_active' => true,
        ], $overrides));
    }
}
