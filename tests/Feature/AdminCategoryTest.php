<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'admin']);
    }

    protected function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_same_name_categories_get_unique_slugs(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Lights'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Lights'])->assertRedirect();

        $slugs = Category::pluck('slug');
        $this->assertCount(2, $slugs);
        $this->assertCount(2, $slugs->unique());
        $this->assertTrue($slugs->contains('lights'));
        $this->assertTrue($slugs->contains('lights-2'));
    }

    public function test_editing_category_without_slug_keeps_existing_slug(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Lights', 'slug' => 'lights', 'is_active' => true]);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), ['name' => 'Lighting & Bulbs'])
            ->assertRedirect();

        $category->refresh();
        $this->assertEquals('Lighting & Bulbs', $category->name);
        $this->assertEquals('lights', $category->slug);
    }

    public function test_non_admin_cannot_create_category(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $this->actingAs($user)
            ->post(route('admin.categories.store'), ['name' => 'Lights'])
            ->assertForbidden();

        $this->assertDatabaseCount('categories', 0);
    }
}
