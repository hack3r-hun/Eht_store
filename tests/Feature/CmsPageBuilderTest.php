<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\Support\Cms\SectionSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CmsPageBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    protected function save(Page $page, array $sections, array $extra = [])
    {
        return $this->put(route('admin.pages.update', $page), array_merge([
            'title' => $page->title,
            'sections' => json_encode($sections),
        ], $extra));
    }

    public function test_every_builtin_page_renders_its_default_layout(): void
    {
        $this->get('/')->assertOk()->assertSee('Handcrafted Crochet &amp; Knit Pieces Made With Care', false)->assertSee('From Idea to Handmade Piece');
        $this->get('/about')->assertOk()->assertSee('Our Mission');
        $this->get('/contact')->assertOk()->assertSee('Frequently Asked Questions');
        $this->get('/products')->assertOk()->assertSee('Handmade Products');
        $this->get('/custom-order')->assertOk()->assertSee('Request a Custom Piece');
        $this->get('/track-order')->assertOk()->assertSee('Track Your Order');
        $this->get('/policies')->assertOk()->assertSee('Care instructions')->assertSee('id="shipping"', false);
    }

    public function test_pages_render_without_any_database_rows(): void
    {
        Page::query()->delete();

        $this->get('/')->assertOk();
        $this->get('/about')->assertOk();
        $this->get('/contact')->assertOk();
    }

    public function test_admin_edits_hero_text_and_the_storefront_shows_it(): void
    {
        $this->actingAs($this->admin());
        $page = Page::forSlug('home');

        $edit = $this->get(route('admin.pages.edit', $page));
        $edit->assertOk()->assertSee('original design');

        $sections = $page->resolvedSections();
        $sections[0]['data']['title'] = 'Brand New Headline';
        $this->save($page, $sections)->assertRedirect();

        $this->get('/')->assertSee('Brand New Headline');
        $this->assertIsArray(Page::where('slug', 'home')->first()->sections);
    }

    public function test_reordering_and_hiding_sections_changes_the_page(): void
    {
        $this->actingAs($this->admin());
        $page = Page::forSlug('about');
        $sections = $page->resolvedSections();

        foreach ($sections as &$section) {
            if ($section['id'] === 'quote') {
                $section['enabled'] = false;
            }
        }
        unset($section);

        $this->get('/about')->assertSee('Every stitch is a small decision');
        $this->save($page, $sections);
        $this->get('/about')->assertDontSee('Every stitch is a small decision');

        // Move the CTA to the top: it must appear before the hero title in the markup.
        $reordered = array_values(array_filter(Page::where('slug', 'about')->first()->sections, fn ($s) => $s['id'] !== 'cta'));
        array_unshift($reordered, collect($sections)->firstWhere('id', 'cta'));
        $this->save(Page::where('slug', 'about')->first(), $reordered);

        $html = $this->get('/about')->getContent();
        $this->assertLessThan(strpos($html, 'Handmade Yarn Creations With Personality'), strpos($html, 'Ready to Find Your Next Handmade Piece?'));
    }

    public function test_hero_on_functional_pages_stays_above_the_form(): void
    {
        $this->actingAs($this->admin());
        $page = Page::forSlug('contact');
        $sections = $page->resolvedSections();
        $sections[0]['data']['title'] = 'Talk To Us Today';
        $this->save($page, $sections);

        $html = $this->get('/contact')->getContent();
        $this->assertStringContainsString('Talk To Us Today', $html);
        $this->assertLessThan(strpos($html, 'Send us a message'), strpos($html, 'Talk To Us Today'));
        // The FAQ block comes after the form.
        $this->assertGreaterThan(strpos($html, 'Send us a message'), strpos($html, 'Frequently Asked Questions'));
    }

    public function test_contact_hours_and_form_text_are_editable(): void
    {
        $this->actingAs($this->admin());
        $page = Page::forSlug('contact');

        $this->save($page, $page->resolvedSections(), [
            'meta' => [
                'form_title' => 'Drop us a line',
                'hours' => [['label' => 'Every day', 'value' => 'Noon to midnight']],
            ],
        ])->assertRedirect();

        $this->get('/contact')->assertSee('Drop us a line')->assertSee('Noon to midnight')->assertDontSee('10:00 AM - 5:00 PM');
    }

    public function test_adding_a_new_block_with_an_image_and_products(): void
    {
        $this->actingAs($this->admin());
        $this->createTestProduct(['name' => 'Showcase Bunny', 'slug' => 'showcase', 'is_featured' => true]);
        $page = Page::forSlug('about');
        $sections = $page->resolvedSections();
        $sections[] = ['id' => 'newg', 'type' => 'gallery', 'enabled' => true, 'data' => ['title' => 'Studio Life', 'columns' => '3', 'items' => [['image' => 'https://example.com/a.jpg', 'caption' => 'Our desk']]]];
        $sections[] = ['id' => 'newp', 'type' => 'products', 'enabled' => true, 'data' => ['title' => 'Editors Picks', 'source' => 'featured', 'limit' => 4, 'link_text' => 'Shop all']];
        $this->save($page, $sections);

        $this->get('/about')->assertSee('Studio Life')->assertSee('https://example.com/a.jpg')->assertSee('Our desk')->assertSee('Editors Picks')->assertSee('Showcase Bunny');
    }

    public function test_image_upload_endpoint_stores_a_file(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $response = $this->postJson(route('admin.pages.upload-image'), ['image' => UploadedFile::fake()->image('hero.jpg', 800, 600)]);

        $response->assertOk()->assertJsonStructure(['path', 'url']);
        Storage::disk('public')->assertExists($response->json('path'));
        $this->postJson(route('admin.pages.upload-image'), ['image' => UploadedFile::fake()->create('evil.php', 10)])->assertStatus(422);
    }

    public function test_custom_page_lifecycle_draft_publish_footer_and_delete(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.pages.store'), ['title' => 'Gift Guide'])->assertRedirect();
        $page = Page::where('slug', 'gift-guide')->firstOrFail();
        $this->assertFalse($page->is_published);

        auth()->logout();
        $this->get('/p/gift-guide')->assertNotFound();

        $this->actingAs($this->admin());
        $sections = $page->sections;
        $sections[1]['data']['content'] = '<p>Our favourite picks</p>';
        $this->save($page, $sections, ['slug' => 'gift-guide', 'is_published' => 1, 'meta' => ['show_in_footer' => 1]])->assertRedirect();

        auth()->logout();
        $this->get('/p/gift-guide')->assertOk()->assertSee('Our favourite picks');
        $this->get('/')->assertSee('Gift Guide'); // footer link

        $this->actingAs($this->admin())->delete(route('admin.pages.destroy', $page))->assertRedirect();
        $this->get('/p/gift-guide')->assertNotFound();
    }

    public function test_reserved_and_duplicate_addresses_are_rejected_and_builtins_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.pages.store'), ['title' => 'Cart', 'slug' => 'cart'])->assertSessionHasErrors('slug');
        $this->post(route('admin.pages.store'), ['title' => 'About', 'slug' => 'about'])->assertSessionHasErrors('slug');

        $about = Page::forSlug('about');
        $this->delete(route('admin.pages.destroy', $about))->assertForbidden();
        $this->get('/p/about')->assertNotFound();
    }

    public function test_reset_restores_the_original_design(): void
    {
        $this->actingAs($this->admin());
        $page = Page::forSlug('home');
        $sections = $page->resolvedSections();
        $sections[0]['data']['title'] = 'Temporary Title';
        $this->save($page, $sections);
        $this->get('/')->assertSee('Temporary Title');

        $this->post(route('admin.pages.reset', Page::where('slug', 'home')->first()))->assertRedirect();

        $this->get('/')->assertDontSee('Temporary Title')->assertSee('Handcrafted Crochet &amp; Knit Pieces Made With Care', false);
    }

    public function test_sanitizer_strips_scripts_bad_links_and_unknown_blocks(): void
    {
        $clean = SectionSanitizer::clean([
            ['id' => 'a b<c>', 'type' => 'rich_text', 'enabled' => true, 'data' => [
                'title' => '<script>alert(1)</script>Hello',
                'content' => '<p onclick="x()">Hi</p><script>alert(2)</script><a href="javascript:alert(3)">bad</a>',
                'background' => 'neon',
            ]],
            ['type' => 'hero', 'data' => ['primary_url' => 'javascript:alert(1)', 'secondary_url' => '/products', 'image' => 'javascript:alert(1)']],
            ['type' => 'does_not_exist', 'data' => []],
            'garbage',
        ]);

        $this->assertCount(2, $clean);
        $this->assertSame('abc', $clean[0]['id']);
        $this->assertStringNotContainsString('<script', $clean[0]['data']['title']);
        $this->assertStringNotContainsString('<script', $clean[0]['data']['content']);
        $this->assertStringNotContainsString('onclick', $clean[0]['data']['content']);
        $this->assertStringNotContainsString('javascript:alert(3)', $clean[0]['data']['content']);
        $this->assertSame('white', $clean[0]['data']['background']);
        $this->assertSame('', $clean[1]['data']['primary_url']);
        $this->assertSame('/products', $clean[1]['data']['secondary_url']);
        $this->assertSame('', $clean[1]['data']['image']);
    }

    public function test_invalid_layout_payload_is_rejected_and_customers_are_blocked(): void
    {
        $this->actingAs($this->admin());
        $page = Page::forSlug('home');

        $this->put(route('admin.pages.update', $page), ['title' => 'Home', 'sections' => 'not json'])->assertSessionHas('error');

        auth()->logout();
        $customer = User::factory()->create();
        $this->actingAs($customer)->get(route('admin.pages.edit', $page))->assertForbidden();
        $this->put(route('admin.pages.update', $page), ['title' => 'x', 'sections' => '[]'])->assertForbidden();
    }
}
