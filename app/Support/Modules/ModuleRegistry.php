<?php

namespace App\Support\Modules;

/**
 * Every shop feature that an admin can switch on/off (and tune) from Dashboard → Modules,
 * instead of editing code or .env. Add an entry here and read it with module_enabled('key')
 * / module_value('key', 'field') where the feature is used.
 *
 * Field kinds: toggle, number, text, textarea, select (needs 'options'), date, image (uploaded file).
 */
class ModuleRegistry
{
    /** @return array<string, array<string, mixed>> */
    public static function definitions(): array
    {
        return [
            // ---------------------------------------------------------------- selling
            'accepting_orders' => [
                'group' => 'Selling & checkout',
                'title' => 'Accept new orders',
                'description' => 'Turn off to pause ordering (holiday, stock-take, busy season). Visitors can still browse; checkout shows your message.',
                'icon' => 'cart',
                'default' => true,
                'fields' => [
                    ['key' => 'closed_message', 'label' => 'Message shown while orders are paused', 'kind' => 'textarea', 'default' => "We're not taking new orders right now — please check back soon!", 'max' => 300],
                ],
            ],
            'guest_checkout' => [
                'group' => 'Selling & checkout',
                'title' => 'Guest checkout',
                'description' => 'Let people order without creating an account. Their email and phone are still saved with the order.',
                'icon' => 'user',
                'default' => true,
                'fields' => [],
            ],
            'payment_cod' => [
                'group' => 'Selling & checkout',
                'title' => 'Payment: Cash on delivery',
                'description' => 'Show “Cash on Delivery” at checkout.',
                'icon' => 'banknotes',
                'default' => true,
                'fields' => [
                    ['key' => 'label', 'label' => 'Name shown at checkout', 'kind' => 'text', 'default' => 'Cash on Delivery', 'max' => 60],
                    ['key' => 'description', 'label' => 'Short description', 'kind' => 'text', 'default' => 'Pay when your order arrives', 'max' => 120],
                ],
            ],
            'payment_stripe' => [
                'group' => 'Selling & checkout',
                'title' => 'Payment: Card (Stripe)',
                'description' => 'Show “Card Payment” at checkout. Needs your Stripe keys in the server settings.',
                'icon' => 'currency',
                'default' => true,
                'fields' => [
                    ['key' => 'label', 'label' => 'Name shown at checkout', 'kind' => 'text', 'default' => 'Card Payment', 'max' => 60],
                    ['key' => 'description', 'label' => 'Short description', 'kind' => 'text', 'default' => 'Pay securely with credit/debit card', 'max' => 120],
                ],
            ],
            'gift_options' => [
                'group' => 'Selling & checkout',
                'title' => 'Gift options',
                'description' => 'Gift wrap, gift message and “hide prices” choices at checkout.',
                'icon' => 'gift',
                'default' => true,
                'fields' => [
                    ['key' => 'fee', 'label' => 'Gift wrap price', 'kind' => 'number', 'default' => (float) config('shop.gift_wrap_fee', 150), 'min' => 0, 'max' => 100000],
                ],
            ],
            'coupons' => [
                'group' => 'Selling & checkout',
                'title' => 'Coupon codes',
                'description' => 'Discount codes in the cart, plus the Coupons page to create them. Off by default.',
                'icon' => 'sparkles',
                'default' => false,
                'fields' => [],
                'manage' => 'admin.coupons.index',
                'manage_label' => 'Manage coupons',
            ],
            'low_stock_badges' => [
                'group' => 'Selling & checkout',
                'title' => '“Only N left” badges',
                'description' => 'Show urgency badges on products that are running low.',
                'icon' => 'warning',
                'default' => true,
                'fields' => [
                    ['key' => 'threshold', 'label' => 'Show “Only N left” when this many or fewer are in stock', 'kind' => 'number', 'default' => (int) config('shop.low_stock_threshold', 5), 'min' => 1, 'max' => 1000],
                ],
            ],

            // ---------------------------------------------------------------- engagement
            'reviews' => [
                'group' => 'Customer engagement',
                'title' => 'Product reviews & ratings',
                'description' => 'Star ratings and reviews on product pages.',
                'icon' => 'star',
                'default' => true,
                'fields' => [
                    ['key' => 'auto_publish_verified', 'label' => 'Publish reviews from verified buyers automatically', 'kind' => 'toggle', 'default' => true],
                ],
                'manage' => 'admin.reviews.index',
                'manage_label' => 'Review inbox',
            ],
            'wishlist' => [
                'group' => 'Customer engagement',
                'title' => 'Wishlist',
                'description' => 'Heart buttons and the saved-items page.',
                'icon' => 'heart',
                'default' => true,
                'fields' => [],
            ],
            'newsletter_popup' => [
                'group' => 'Customer engagement',
                'title' => 'Newsletter pop-up',
                'description' => 'A friendly sign-up window for first-time visitors.',
                'icon' => 'mail',
                'default' => true,
                'fields' => [
                    ['key' => 'delay_seconds', 'label' => 'Show after (seconds)', 'kind' => 'number', 'default' => 12, 'min' => 2, 'max' => 300],
                    ['key' => 'heading', 'label' => 'Heading', 'kind' => 'text', 'default' => 'Join the EK Yarn Co. list', 'max' => 80],
                    ['key' => 'text', 'label' => 'Short text', 'kind' => 'text', 'default' => 'Be first to hear about new handmade drops and restocks.', 'max' => 160],
                ],
                'manage' => 'admin.subscribers.index',
                'manage_label' => 'Subscribers',
            ],
            'promo_popup' => [
                'group' => 'Customer engagement',
                'title' => 'Promo pop-up (sale / season / announcement)',
                'description' => 'Show an offer when visitors open the site — e.g. “Winter collection is here”. Add a picture and a button, choose how often it appears and schedule it with dates.',
                'icon' => 'bolt',
                'default' => false,
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'Small label above the title (optional)', 'kind' => 'text', 'default' => '', 'max' => 40],
                    ['key' => 'heading', 'label' => 'Title', 'kind' => 'text', 'default' => 'Our winter collection is here', 'max' => 100],
                    ['key' => 'text', 'label' => 'Message', 'kind' => 'textarea', 'default' => 'Cozy handmade pieces to keep you warm this season.', 'max' => 300],
                    ['key' => 'image', 'label' => 'Picture (optional)', 'kind' => 'image'],
                    ['key' => 'button_text', 'label' => 'Button text (leave empty for no button)', 'kind' => 'text', 'default' => '', 'max' => 40],
                    ['key' => 'button_url', 'label' => 'Button link (e.g. /products or /products?category=winter)', 'kind' => 'text', 'default' => '', 'max' => 255],
                    ['key' => 'delay_seconds', 'label' => 'Show after (seconds)', 'kind' => 'number', 'default' => 2, 'min' => 0, 'max' => 300],
                    ['key' => 'frequency', 'label' => 'How often should a visitor see it?', 'kind' => 'select', 'default' => 'once_per_day', 'options' => [
                        'every_visit' => 'Every time a page loads',
                        'once_per_session' => 'Once per visit (until they close the browser)',
                        'once_per_day' => 'Once a day',
                        'once' => 'Only once (until you change the message)',
                    ]],
                    ['key' => 'show_on', 'label' => 'Where to show it', 'kind' => 'select', 'default' => 'home', 'options' => [
                        'home' => 'Home page only',
                        'all' => 'Every page',
                    ]],
                    ['key' => 'start_date', 'label' => 'Start showing on (optional)', 'kind' => 'date', 'default' => ''],
                    ['key' => 'end_date', 'label' => 'Stop showing after (optional)', 'kind' => 'date', 'default' => ''],
                ],
            ],
            'back_in_stock' => [
                'group' => 'Customer engagement',
                'title' => 'Back-in-stock alerts',
                'description' => '“Email me when it’s back” on sold-out sizes, and the email sent when stock returns.',
                'icon' => 'inbox',
                'default' => true,
                'fields' => [],
            ],
            'recently_viewed' => [
                'group' => 'Customer engagement',
                'title' => 'Recently viewed',
                'description' => 'A “Recently viewed” row on product pages.',
                'icon' => 'eye',
                'default' => true,
                'fields' => [
                    ['key' => 'heading', 'label' => 'Section title', 'kind' => 'text', 'default' => 'Recently viewed', 'max' => 60],
                ],
            ],
            'custom_orders' => [
                'group' => 'Customer engagement',
                'title' => 'Custom order requests',
                'description' => 'The “Request a custom piece” form and page.',
                'icon' => 'clipboard',
                'default' => true,
                'fields' => [],
                'manage' => 'admin.custom-orders.index',
                'manage_label' => 'Requests',
            ],
            'order_tracking' => [
                'group' => 'Customer engagement',
                'title' => 'Order tracking page',
                'description' => 'Let customers look up an order with their order number and email or phone.',
                'icon' => 'truck',
                'default' => true,
                'fields' => [],
            ],
            'whatsapp' => [
                'group' => 'Customer engagement',
                'title' => 'WhatsApp chat button',
                'description' => 'Floating chat button and “Order on WhatsApp” links.',
                'icon' => 'chat',
                'default' => true,
                'fields' => [
                    ['key' => 'number', 'label' => 'WhatsApp number (with country code)', 'kind' => 'text', 'default' => '', 'max' => 25],
                    ['key' => 'message', 'label' => 'Default message', 'kind' => 'text', 'default' => 'Hi! I have a question about your handmade pieces.', 'max' => 160],
                ],
            ],

            // ---------------------------------------------------------------- documents
            'invoice' => [
                'group' => 'Documents',
                'title' => 'Customer invoices',
                'description' => 'Let customers download a PDF invoice from their order. Admins can always download invoices.',
                'icon' => 'document',
                'default' => true,
                'fields' => [],
                'manage' => 'admin.invoice.edit',
                'manage_label' => 'Design the invoice',
            ],
        ];
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::definitions());
    }
}
