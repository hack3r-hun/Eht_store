<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Everything on the PDF invoice that an admin can change from Dashboard → Invoice:
 * company ("from") details, what is shown, wording and branding. Stored as one JSON setting;
 * anything not saved yet falls back to the shop's own details.
 */
class InvoiceSettings
{
    public const KEY = 'invoice.settings';

    /** Toggles shown in the editor, with the label shown next to each. */
    public const TOGGLES = [
        'show_logo' => 'Show the logo',
        'show_tagline' => 'Show the tagline under the company name',
        'show_tax_number' => 'Show the tax / NTN number',
        'show_customer_contact' => 'Show customer email and phone',
        'show_shipping_address' => 'Show the delivery address',
        'show_sku' => 'Show product codes (SKU)',
        'show_size' => 'Show the size next to each item',
        'show_payment_info' => 'Show payment method and status',
        'show_status_stamp' => 'Show a PAID / UNPAID tag',
        'show_gift_message' => 'Show the gift message (gift orders)',
        'show_order_notes' => "Show the customer's order notes",
        'show_bank_details' => 'Show bank / payment instructions',
        'show_thank_you' => 'Show the thank-you message',
        'show_terms' => 'Show terms & conditions',
        'show_signature' => 'Show an authorised-signature line',
        'show_computer_generated' => 'Add “computer-generated invoice” line',
        'show_footer_contact' => 'Show contact details in the footer',
    ];

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            // From
            'company_name' => shop_name(),
            'legal_name' => 'EK Yarn Co.',
            'tagline' => 'Handcrafted crochet & knitted gifts',
            'address' => (string) shop_config('contact_address'),
            'phone' => (string) shop_config('contact_phone'),
            'email' => (string) shop_config('contact_email'),
            'website' => preg_replace('#^https?://#', '', rtrim(url('/'), '/')),
            'tax_label' => 'NTN',
            'tax_number' => '',
            // Wording
            'number_prefix' => 'INV-',
            'thank_you' => 'Thank you for supporting a small handmade business — every piece is made with care.',
            'terms' => "Handmade items may vary slightly in colour and size.\nDamaged items can be returned or exchanged within 7 days of delivery — please share a photo.\nCustom and personalised orders are made for you, so they can only be returned if faulty.",
            'bank_details' => '',
            'footer_note' => '',
            'copyright_holder' => 'EK Yarn Co.',
            'copyright_text' => 'All rights reserved.',
            // Branding
            'accent' => '#E2725B',
            'paper' => 'a4',
            'logo_path' => '',
            // Toggles
            'show_logo' => true,
            'show_tagline' => true,
            'show_tax_number' => true,
            'show_customer_contact' => true,
            'show_shipping_address' => true,
            'show_sku' => true,
            'show_size' => true,
            'show_payment_info' => true,
            'show_status_stamp' => true,
            'show_gift_message' => true,
            'show_order_notes' => false,
            'show_bank_details' => false,
            'show_thank_you' => true,
            'show_terms' => true,
            'show_signature' => false,
            'show_computer_generated' => true,
            'show_footer_contact' => true,
        ];
    }

    /** @return array<string, mixed> saved values over the defaults */
    public static function all(): array
    {
        $defaults = self::defaults();

        try {
            $saved = json_decode((string) Setting::get(self::KEY), true);
        } catch (\Throwable) {
            $saved = null;
        }

        if (! is_array($saved)) {
            return $defaults;
        }

        // Only known keys, with the right type, so a stale/odd saved value can never break the PDF.
        foreach ($defaults as $key => $default) {
            if (array_key_exists($key, $saved)) {
                $defaults[$key] = is_bool($default) ? (bool) $saved[$key] : (is_string($saved[$key]) ? $saved[$key] : $default);
            }
        }

        return $defaults;
    }

    /** @param  array<string, mixed>  $values */
    public static function save(array $values): void
    {
        Setting::set(self::KEY, json_encode(array_intersect_key($values, self::defaults())));
    }

    /** The invoice number printed on the PDF. */
    public static function numberFor(string $orderNumber, ?array $settings = null): string
    {
        return ($settings ?? self::all())['number_prefix'].$orderNumber;
    }

    /** "© 2026 EK Yarn Co. All rights reserved." */
    public static function copyrightLine(?array $settings = null): string
    {
        $s = $settings ?? self::all();

        $holder = trim($s['copyright_holder']);
        // "EK Yarn Co." already ends with a full stop — don't print two.
        $holder .= str_ends_with($holder, '.') ? ' ' : '. ';

        return trim('© '.now()->year.' '.$holder.$s['copyright_text']);
    }

    /** The logo as a data URI (DomPDF cannot fetch URLs reliably), or null when switched off / missing. */
    public static function logoDataUri(?array $settings = null): ?string
    {
        $s = $settings ?? self::all();

        if (! $s['show_logo']) {
            return null;
        }

        $path = null;

        if ($s['logo_path'] !== '' && Storage::disk('public')->exists($s['logo_path'])) {
            $path = Storage::disk('public')->path($s['logo_path']);
        } elseif (is_file(public_path('images/logo.png'))) {
            $path = public_path('images/logo.png');
        }

        if (! $path) {
            return null;
        }

        $mime = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'png' ? 'image/png' : (in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/webp');

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
