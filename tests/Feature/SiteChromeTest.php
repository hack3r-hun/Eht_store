<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SiteChromeTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_default_menu_and_footer_render_before_anything_is_saved(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Custom Order')
            ->assertSee('Track Your Order')
            ->assertSee('Handmade with love in Karachi');
    }

    public function test_admin_page_loads_for_admins_only(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.site.edit'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.site.edit'))->assertOk()->assertSee('Header menu');
    }

    public function test_saved_menu_footer_and_socials_appear_on_the_storefront(): void
    {
        $this->actingAs($this->admin())->put(route('admin.site.update'), [
            'topbar' => 'Free gift wrap this week',
            'footer_heading' => 'Explore',
            'footer_text' => 'Small-batch crochet from Karachi.',
            'nav' => [
                ['label' => 'Shop', 'url' => '/products'],
                ['label' => 'Our Story', 'url' => '/about'],
                ['label' => '', 'url' => '/blank-label-is-dropped'],
            ],
            'footer' => [['label' => 'Delivery info', 'url' => '/policies#shipping']],
            'socials' => [['label' => 'Instagram', 'url' => 'https://instagram.com/ekyarnco']],
        ])->assertSessionHasNoErrors();

        auth()->logout();

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('Free gift wrap this week', $html);
        $this->assertStringContainsString('>Shop</a>', $html);
        $this->assertStringContainsString('>Our Story</a>', $html);
        $this->assertStringNotContainsString('blank-label-is-dropped', $html);
        $this->assertStringContainsString('Explore', $html);
        $this->assertStringContainsString('Delivery info', $html);
        $this->assertStringContainsString('Small-batch crochet from Karachi.', $html);
        $this->assertStringContainsString('https://instagram.com/ekyarnco', $html);
        // The old fixed "Custom Order" nav link is gone because the admin replaced the menu.
        $this->assertStringNotContainsString('nav-link ">Custom Order', $html);
    }

    public function test_unsafe_menu_links_are_dropped(): void
    {
        $this->actingAs($this->admin())->put(route('admin.site.update'), [
            'nav' => [
                ['label' => 'Evil', 'url' => 'javascript:alert(1)'],
                ['label' => 'Data', 'url' => 'data:text/html,<script>alert(1)</script>'],
                ['label' => 'Fine', 'url' => 'https://example.com/ok'],
                ['label' => 'Call', 'url' => 'tel:+923001234567'],
            ],
        ]);

        auth()->logout();
        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('javascript:alert', $html);
        $this->assertStringNotContainsString('data:text/html', $html);
        $this->assertStringContainsString('https://example.com/ok', $html);
        $this->assertStringContainsString('tel:+923001234567', $html);
    }

    public function test_active_menu_item_is_highlighted(): void
    {
        $html = $this->get('/about')->getContent();

        $this->assertMatchesRegularExpression('#nav-link nav-link-active">About</a>#', $html);
        $this->assertDoesNotMatchRegularExpression('#nav-link nav-link-active">Home</a>#', $html);
    }

    public function test_sitemap_lists_new_pages_and_published_custom_pages_only(): void
    {
        Page::create(['slug' => 'gift-guide', 'title' => 'Gift Guide', 'meta' => [], 'is_published' => true]);
        Page::create(['slug' => 'secret-draft', 'title' => 'Draft', 'meta' => [], 'is_published' => false]);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('/p/gift-guide', $xml);
        $this->assertStringNotContainsString('secret-draft', $xml);
        $this->assertStringContainsString('/custom-order', $xml);
        $this->assertStringContainsString('/policies', $xml);
    }
}
