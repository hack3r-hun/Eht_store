<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\CartService;
use App\Services\InvoiceBuilder;
use App\Support\InvoiceSettings;
use App\Support\Modules\ModuleSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModulesAndInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('customer');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    protected function setModule(string $key, bool $on): void
    {
        app(ModuleSettings::class)->save(["module.{$key}.enabled" => $on]);
    }

    public function test_defaults_and_modules_page(): void
    {
        $this->assertTrue(module_enabled('reviews'));
        $this->assertFalse(module_enabled('coupons'));

        $this->actingAs($this->admin())->get(route('admin.modules.index'))
            ->assertOk()->assertSee('Coupon codes')->assertSee('WhatsApp chat button')->assertSee('Customer invoices');
    }

    public function test_admin_can_switch_modules_and_routes_follow(): void
    {
        $this->get(route('wishlist.index'))->assertOk();
        $this->get(route('custom-order.create'))->assertOk();

        $this->actingAs($this->admin())->put(route('admin.modules.update'), [
            'modules' => [
                'wishlist' => ['enabled' => 0],
                'custom_orders' => ['enabled' => 0],
                'payment_cod' => ['enabled' => 1],
                'accepting_orders' => ['enabled' => 1],
            ],
        ])->assertSessionHas('success');

        auth()->logout();
        $this->get(route('wishlist.index'))->assertNotFound();
        $this->get(route('custom-order.create'))->assertNotFound();
        $this->get('/')->assertOk()->assertDontSee('>Custom Order</a>', false);
    }

    public function test_cannot_switch_off_every_payment_method_while_open(): void
    {
        $this->actingAs($this->admin())->put(route('admin.modules.update'), [
            'modules' => ['accepting_orders' => ['enabled' => 1], 'payment_cod' => ['enabled' => 0], 'payment_stripe' => ['enabled' => 0]],
        ])->assertSessionHas('error');

        $this->assertTrue(module_enabled('payment_cod'));
    }

    public function test_coupon_module_gates_routes_and_admin_page(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.coupons.index'))->assertNotFound();
        $this->post(route('cart.coupon.store'), ['code' => 'X'])->assertNotFound();

        $this->setModule('coupons', true);
        $this->get(route('admin.coupons.index'))->assertOk();
    }

    public function test_pausing_orders_blocks_checkout_with_the_message(): void
    {
        $product = $this->createTestProduct(['slug' => 'pause-item']);
        $guest = ['X-Guest-Cart-Token' => 'pause-token'];
        $this->withHeaders($guest)->post(route('cart.store'), ['product_id' => $product->id, 'product_variant_id' => $this->defaultVariant($product)->id]);

        app(ModuleSettings::class)->save(['module.accepting_orders.enabled' => false, 'module.accepting_orders.closed_message' => 'Back on Monday!']);

        $this->withHeaders($guest)->get(route('checkout.index'))->assertRedirect(route('cart.index'))->assertSessionHas('error', 'Back on Monday!');
        $this->get('/')->assertSee('Back on Monday!');
    }

    public function test_guest_checkout_and_payment_method_switches(): void
    {
        $product = $this->createTestProduct(['slug' => 'switch-item']);
        $guest = ['X-Guest-Cart-Token' => 'switch-token'];
        $this->withHeaders($guest)->post(route('cart.store'), ['product_id' => $product->id, 'product_variant_id' => $this->defaultVariant($product)->id]);

        $this->setModule('guest_checkout', false);
        $this->withHeaders($guest)->get(route('checkout.index'))->assertRedirect(route('login'));

        $this->setModule('guest_checkout', true);
        $this->setModule('payment_cod', false);
        $payload = ['full_name' => 'A', 'email' => 'a@example.com', 'phone' => '03001234567', 'address_line' => 'x', 'city' => 'Lahore', 'payment_method' => 'cod'];
        $this->withHeaders($guest)->post(route('checkout.store'), $payload)->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::count());
    }

    public function test_gift_fee_comes_from_the_module_and_off_means_no_gift(): void
    {
        $product = $this->createTestProduct(['slug' => 'gift-mod', 'price' => 1000]);
        $guest = ['X-Guest-Cart-Token' => 'gift-token'];
        $this->withHeaders($guest)->post(route('cart.store'), ['product_id' => $product->id, 'product_variant_id' => $this->defaultVariant($product)->id]);

        app(ModuleSettings::class)->save(['module.gift_options.fee' => '400']);
        $with = $this->withHeaders($guest)->getJson(route('checkout.shipping-quote', ['city' => 'Lahore', 'gift_wrap' => 1]))->json('total');
        $without = $this->withHeaders($guest)->getJson(route('checkout.shipping-quote', ['city' => 'Lahore']))->json('total');
        $this->assertEquals(400, $with - $without);

        $this->setModule('gift_options', false);
        $this->assertEquals($without, $this->withHeaders($guest)->getJson(route('checkout.shipping-quote', ['city' => 'Lahore', 'gift_wrap' => 1]))->json('total'));
    }

    public function test_whatsapp_module_number_and_switch(): void
    {
        app(ModuleSettings::class)->save(['module.whatsapp.number' => '+92 321 7654321']);
        $this->get('/')->assertSee('wa.me/923217654321', false);

        $this->setModule('whatsapp', false);
        $this->get('/')->assertDontSee('wa.me', false);
    }

    // ------------------------------------------------------------ invoice

    public function test_invoice_settings_defaults_save_and_copyright(): void
    {
        $s = InvoiceSettings::all();
        $this->assertSame('EK Yarn Co.', $s['legal_name']);
        $this->assertStringContainsString('EK Yarn Co. All rights reserved.', InvoiceSettings::copyrightLine());

        $this->actingAs($this->admin())->put(route('admin.invoice.update'), array_merge(
            ['company_name' => 'EK Yarn Studio', 'legal_name' => 'EK Yarn Co.', 'copyright_holder' => 'EK Yarn Co.', 'copyright_text' => 'All rights reserved.', 'accent' => '#112233', 'paper' => 'letter', 'tax_number' => '1234567-8', 'number_prefix' => 'EKI-'],
            ['show_sku' => 0, 'show_signature' => 1]
        ))->assertRedirect(route('admin.invoice.edit'));

        $saved = InvoiceSettings::all();
        $this->assertSame('EK Yarn Studio', $saved['company_name']);
        $this->assertSame('#112233', $saved['accent']);
        $this->assertFalse($saved['show_sku']);
        $this->assertTrue($saved['show_signature']);
        $this->assertSame('EKI-ABC', InvoiceSettings::numberFor('ABC'));
    }

    public function test_invoice_settings_validation(): void
    {
        $this->actingAs($this->admin())->put(route('admin.invoice.update'), ['company_name' => '', 'accent' => 'red', 'paper' => 'a0'])
            ->assertSessionHasErrors(['company_name', 'accent', 'paper', 'legal_name', 'copyright_holder']);
    }

    public function test_preview_and_real_invoices_render_as_pdf(): void
    {
        $this->actingAs($this->admin());
        $response = $this->get(route('admin.invoice.preview', ['source' => 'sample']));
        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $builder = app(InvoiceBuilder::class);
        $order = $builder->sampleOrder();
        $html = view('invoices.order', [
            'order' => $order, 's' => InvoiceSettings::all(), 'logo' => null,
            'invoiceNumber' => 'INV-EKY-SAMPLE01', 'copyright' => InvoiceSettings::copyrightLine(),
        ])->render();

        $this->assertStringContainsString('INVOICE', $html);
        $this->assertStringContainsString('© '.now()->year.' EK Yarn Co. All rights reserved.', $html);
        $this->assertStringContainsString('Ayesha Khan', $html);
        $this->assertStringContainsString('Mini Bunny Amigurumi', $html);
    }

    protected function savePromo(array $fields, bool $on = true, array $files = []): void
    {
        $this->actingAs($this->admin())->put(route('admin.modules.update'), [
            'modules' => [
                'accepting_orders' => ['enabled' => 1],
                'payment_cod' => ['enabled' => 1],
                'promo_popup' => ['enabled' => $on ? 1 : 0, 'fields' => $fields, 'upload' => $files],
            ],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        auth()->logout();
    }

    public function test_promo_popup_is_off_by_default_and_shows_admin_content_when_on(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Winter is here');

        $this->savePromo(['heading' => 'Winter is here', 'text' => 'Cozy knits', 'button_text' => 'Shop winter', 'button_url' => '/products', 'frequency' => 'once']);

        $this->get('/')->assertOk()->assertSee('Winter is here')->assertSee('Shop winter')->assertSee('href="/products"', false);
    }

    public function test_promo_popup_respects_page_scope_dates_and_off_switch(): void
    {
        $this->savePromo(['heading' => 'Winter is here', 'show_on' => 'home']);
        $this->get(route('products.index'))->assertOk()->assertDontSee('Winter is here');

        $this->savePromo(['heading' => 'Winter is here', 'show_on' => 'all']);
        $this->get(route('products.index'))->assertOk()->assertSee('Winter is here');

        $this->savePromo(['heading' => 'Winter is here', 'show_on' => 'all', 'start_date' => now()->addDays(3)->toDateString()]);
        $this->get('/')->assertDontSee('Winter is here');

        $this->savePromo(['heading' => 'Winter is here', 'show_on' => 'all', 'start_date' => now()->subDays(5)->toDateString(), 'end_date' => now()->subDay()->toDateString()]);
        $this->get('/')->assertDontSee('Winter is here');

        $this->savePromo(['heading' => 'Winter is here', 'show_on' => 'all', 'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->toDateString()]);
        $this->get('/')->assertSee('Winter is here');

        $this->savePromo(['heading' => 'Winter is here'], on: false);
        $this->get('/')->assertDontSee('Winter is here');
    }

    public function test_promo_popup_rejects_bad_values_and_unsafe_button_links(): void
    {
        $this->actingAs($this->admin())->put(route('admin.modules.update'), [
            'modules' => ['accepting_orders' => ['enabled' => 1], 'payment_cod' => ['enabled' => 1], 'promo_popup' => ['enabled' => 1, 'fields' => ['frequency' => 'whenever']]],
        ])->assertSessionHasErrors('modules.promo_popup.fields.frequency');

        $this->actingAs($this->admin())->put(route('admin.modules.update'), [
            'modules' => ['accepting_orders' => ['enabled' => 1], 'payment_cod' => ['enabled' => 1], 'promo_popup' => ['enabled' => 1, 'fields' => ['start_date' => '2026-12-10', 'end_date' => '2026-12-01']]],
        ])->assertSessionHas('error');

        auth()->logout();
        $this->savePromo(['heading' => 'Sale', 'button_text' => 'Click', 'button_url' => 'javascript:alert(1)']);
        $this->get('/')->assertSee('Sale')->assertDontSee('javascript:alert', false);
    }

    public function test_promo_popup_image_upload_and_removal(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $this->savePromo(['heading' => 'Sale'], files: ['image' => \Illuminate\Http\UploadedFile::fake()->image('winter.jpg', 800, 600)]);
        $path = module_value('promo_popup', 'image');
        $this->assertStringStartsWith('modules/', $path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($path);

        // Saving again without a file keeps the picture; "clear" removes it and deletes the file.
        $this->savePromo(['heading' => 'Sale']);
        $this->assertSame($path, module_value('promo_popup', 'image'));

        $this->actingAs($this->admin())->put(route('admin.modules.update'), [
            'modules' => ['accepting_orders' => ['enabled' => 1], 'payment_cod' => ['enabled' => 1], 'promo_popup' => ['enabled' => 1, 'clear' => ['image' => 1]]],
        ])->assertSessionHasNoErrors();
        $this->assertEmpty(module_value('promo_popup', 'image'));
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($path);
    }

    public function test_editable_module_fields_reach_the_storefront(): void
    {
        $this->actingAs($this->admin())->put(route('admin.modules.update'), [
            'modules' => [
                'accepting_orders' => ['enabled' => 1],
                'payment_cod' => ['enabled' => 1, 'fields' => ['label' => 'Pay at the door', 'description' => 'Cash only']],
                'low_stock_badges' => ['enabled' => 1, 'fields' => ['threshold' => 9]],
            ],
        ])->assertSessionHasNoErrors();
        auth()->logout();

        $this->assertSame('Pay at the door', module_value('payment_cod', 'label'));
        $this->assertEquals(9, module_value('low_stock_badges', 'threshold'));
    }

    public function test_each_module_has_its_own_edit_screen_and_save(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.modules.index'))
            ->assertOk()->assertSee(route('admin.modules.edit', 'promo_popup'), false);
        $this->actingAs($admin)->get(route('admin.modules.edit', 'promo_popup'))
            ->assertOk()->assertSee('Promo pop-up')->assertSee('Title')->assertSee('Save');
        $this->actingAs($admin)->get(route('admin.modules.edit', 'nope'))->assertNotFound();

        $this->actingAs($admin)->put(route('admin.modules.update-module', 'promo_popup'), [
            'modules' => ['promo_popup' => ['enabled' => 1, 'fields' => ['heading' => 'Winter sale', 'show_on' => 'all']]],
        ])->assertRedirect(route('admin.modules.edit', 'promo_popup'))->assertSessionHas('success');

        auth()->logout();
        $this->assertTrue(module_enabled('promo_popup'));
        $this->assertSame('Winter sale', module_value('promo_popup', 'heading'));
        $this->assertTrue(module_enabled('reviews'), 'saving one module must not touch the others');
        $this->get(route('products.index'))->assertSee('Winter sale');
    }

    public function test_overview_switches_do_not_wipe_module_settings(): void
    {
        $this->savePromo(['heading' => 'Keep me', 'text' => 'Hello']);

        $this->actingAs($this->admin())->put(route('admin.modules.update'), [
            'modules' => ['accepting_orders' => ['enabled' => 1], 'payment_cod' => ['enabled' => 1], 'promo_popup' => ['enabled' => 1]],
        ])->assertSessionHasNoErrors();

        $this->assertSame('Keep me', module_value('promo_popup', 'heading'));
        $this->assertSame('Hello', module_value('promo_popup', 'text'));
    }

    public function test_last_payment_method_cannot_be_switched_off_from_its_edit_screen(): void
    {
        $this->setModule('payment_stripe', false);

        $this->actingAs($this->admin())->put(route('admin.modules.update-module', 'payment_cod'), [
            'modules' => ['payment_cod' => ['enabled' => 0]],
        ])->assertSessionHas('error');

        $this->assertTrue(module_enabled('payment_cod'));
    }

    public function test_gift_orders_hide_prices_and_customer_invoice_module(): void
    {
        $order = app(InvoiceBuilder::class)->sampleOrder();
        $order->gift_hide_prices = true;
        $html = view('invoices.order', ['order' => $order, 's' => InvoiceSettings::all(), 'logo' => null, 'invoiceNumber' => 'X', 'copyright' => 'c'])->render();
        $this->assertStringContainsString('PACKING SLIP', $html);
        $this->assertStringNotContainsString('Rs. 3,750', $html);

        $customer = User::factory()->create();
        $this->setModule('invoice', false);
        $placed = Order::create(['user_id' => $customer->id, 'order_number' => 'EKY-ZZZZ', 'status' => 'pending', 'payment_method' => 'cod', 'payment_status' => 'pending', 'subtotal' => 1, 'total' => 1, 'shipping_address' => ['city' => 'x']]);
        $this->actingAs($customer)->get(route('account.orders.invoice', $placed))->assertNotFound();
    }
}
