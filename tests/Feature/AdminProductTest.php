<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'admin']);
    }

    public function test_admin_can_create_product(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $category = Category::create(['name' => 'Bulbs', 'slug' => 'bulbs', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'New LED Bulb',
            'sku' => 'LED-NEW',
            'price' => 350,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);

        $product = Product::where('sku', 'LED-NEW')->first();

        $response->assertRedirect(route('admin.products.edit', $product));
        $this->assertDatabaseHas('products', ['sku' => 'LED-NEW']);
    }

    public function test_non_admin_cannot_access_admin_products(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get(route('admin.products.index'));
        $response->assertForbidden();
    }

    public function test_deleting_product_image_does_not_delete_product(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $category = Category::create(['name' => 'Bulbs', 'slug' => 'bulbs', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'LED Bulb',
            'slug' => 'led-bulb',
            'sku' => 'LED-1',
            'price' => 100,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
        $primary = $product->images()->create(['path' => 'https://example.com/a.jpg', 'is_primary' => true, 'sort_order' => 0]);
        $other = $product->images()->create(['path' => 'https://example.com/b.jpg', 'is_primary' => false, 'sort_order' => 1]);

        $this->actingAs($admin)
            ->delete(route('admin.products.images.destroy', [$product, $primary]))
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_images', ['id' => $primary->id]);
        $this->assertTrue($other->fresh()->is_primary);

        // The edit page must not nest the image-delete form inside the product
        // form — the button references a standalone form instead.
        $page = $this->actingAs($admin)->get(route('admin.products.edit', $product));
        $page->assertOk();
        $page->assertSee('form="delete-image-'.$other->id.'"', false);
    }

    public function test_non_admin_cannot_write_products(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $category = Category::create(['name' => 'Bulbs', 'slug' => 'bulbs', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'LED Bulb',
            'slug' => 'led-bulb',
            'sku' => 'LED-1',
            'price' => 100,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $payload = [
            'category_id' => $category->id,
            'name' => 'Hacked',
            'sku' => 'HAX-1',
            'price' => 1,
            'stock_quantity' => 1,
        ];

        $this->actingAs($user)->post(route('admin.products.store'), $payload)->assertForbidden();
        $this->actingAs($user)->put(route('admin.products.update', $product), $payload)->assertForbidden();
        $this->actingAs($user)->delete(route('admin.products.destroy', $product))->assertForbidden();

        $this->assertDatabaseHas('products', ['sku' => 'LED-1', 'name' => 'LED Bulb']);
        $this->assertDatabaseMissing('products', ['sku' => 'HAX-1']);
    }

    public function test_admin_delete_archives_product_without_removing_database_record(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $category = Category::create(['name' => 'Bulbs', 'slug' => 'bulbs', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Archive Bulb',
            'slug' => 'archive-bulb',
            'sku' => 'ARC-1',
            'price' => 100,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'sku' => 'ARC-1']);

        $this->get(route('products.show', $product->slug))->assertNotFound();
        $this->get(route('products.index'))->assertDontSee('Archive Bulb');
        $this->actingAs($admin)->get(route('admin.products.index'))->assertDontSee('Archive Bulb');
        $this->actingAs($admin)->get(route('admin.products.archived'))->assertSee('Archive Bulb');
    }

    public function test_admin_can_bulk_archive_and_restore_products(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $category = Category::create(['name' => 'Bulbs', 'slug' => 'bulbs', 'is_active' => true]);
        $first = Product::create([
            'category_id' => $category->id,
            'name' => 'Bulk One',
            'slug' => 'bulk-one',
            'sku' => 'BLK-1',
            'price' => 100,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
        $second = Product::create([
            'category_id' => $category->id,
            'name' => 'Bulk Two',
            'slug' => 'bulk-two',
            'sku' => 'BLK-2',
            'price' => 100,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.products.bulk-destroy'), ['product_ids' => [$first->id, $second->id]])
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted('products', ['id' => $first->id]);
        $this->assertSoftDeleted('products', ['id' => $second->id]);

        $this->actingAs($admin)
            ->post(route('admin.products.restore', $first->id))
            ->assertRedirect(route('admin.products.archived'));

        $this->assertFalse($first->fresh()->trashed());
        $this->assertTrue($second->fresh()->trashed());
    }

    public function test_order_item_snapshot_survives_archived_product(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $category = Category::create(['name' => 'Bulbs', 'slug' => 'bulbs', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Snapshot Bulb',
            'slug' => 'snapshot-bulb',
            'sku' => 'SNP-1',
            'price' => 100,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
        $order = Order::create([
            'order_number' => 'TEST-ORDER-1',
            'status' => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'subtotal' => 100,
            'tax' => 0,
            'shipping' => 0,
            'total' => 100,
            'shipping_address' => ['full_name' => 'Test User', 'phone' => '03001234567', 'address_line' => 'Street', 'city' => 'Lahore'],
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Snapshot Bulb',
            'product_sku' => 'SNP-1',
        ]);
    }

    public function test_non_admin_cannot_archive_restore_or_view_archived_products(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $category = Category::create(['name' => 'Bulbs', 'slug' => 'bulbs', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Protected Bulb',
            'slug' => 'protected-bulb',
            'sku' => 'PRT-1',
            'price' => 100,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
        $product->delete();

        $this->actingAs($user)->get(route('admin.products.archived'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.products.restore', $product->id))->assertForbidden();
        $this->actingAs($user)->delete(route('admin.products.bulk-destroy'), ['product_ids' => [$product->id]])->assertForbidden();
    }
}
