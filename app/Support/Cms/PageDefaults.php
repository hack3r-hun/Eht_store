<?php

namespace App\Support\Cms;

use App\Models\Page;

/**
 * Built-in layouts for every storefront page. Used until an admin saves a custom
 * layout, so an untouched site looks exactly as designed. Values from the legacy
 * CMS fields (hero_title, hero_subtitle, content) are honoured when present.
 */
class PageDefaults
{
    /** Pages that exist by design (cannot be deleted; slug is fixed). */
    public const SYSTEM = [
        'home' => ['title' => 'Home', 'url' => '/', 'layout' => 'full'],
        'about' => ['title' => 'About Us', 'url' => '/about', 'layout' => 'full'],
        'products' => ['title' => 'Products', 'url' => '/products', 'layout' => 'framed'],
        'contact' => ['title' => 'Contact Us', 'url' => '/contact', 'layout' => 'framed'],
        'custom-order' => ['title' => 'Custom Order', 'url' => '/custom-order', 'layout' => 'framed'],
        'track-order' => ['title' => 'Track Order', 'url' => '/track-order', 'layout' => 'framed'],
        'policies' => ['title' => 'Shipping, Returns & Care', 'url' => '/policies', 'layout' => 'full'],
    ];

    /** @return array<int, array<string, mixed>> */
    public static function for(string $slug, ?Page $page = null): array
    {
        $sections = match ($slug) {
            'home' => self::home($page),
            'about' => self::about($page),
            'products' => self::products($page),
            'contact' => self::contact($page),
            'custom-order' => self::customOrder($page),
            'track-order' => self::trackOrder($page),
            'policies' => self::policies($page),
            default => self::generic($page),
        };

        return array_map(fn ($s) => $s + ['enabled' => true], $sections);
    }

    protected static function s(string $id, string $type, array $data): array
    {
        return ['id' => $id, 'type' => $type, 'data' => SectionSchemaDefaults::fill($type, $data)];
    }

    protected static function meta(?Page $page, string $key, string $fallback): string
    {
        $value = $page?->meta[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    protected static function home(?Page $page): array
    {
        return [
            self::s('hero', 'hero', [
                'style' => 'split',
                'eyebrow' => 'Handmade in Karachi',
                'title' => self::meta($page, 'hero_title', 'Handcrafted Crochet & Knit Pieces Made With Care'),
                'subtitle' => self::meta($page, 'hero_subtitle', 'Cute amigurumi, keychains, coasters, scrunchies, wallets, headbands, cozy wearables, and custom gifts made with quality yarns.'),
                'primary_text' => self::meta($page, 'hero_cta', 'Shop Handmade'),
                'primary_url' => '/products',
                'secondary_text' => 'Request Custom Order',
                'secondary_url' => '/custom-order',
                'image' => config('media.hero'),
                'badge_top' => 'Custom Orders Welcome',
                'badge_title' => '100% Handmade',
                'badge_text' => 'Made with care in Karachi',
            ]),
            self::s('trust', 'features', [
                'variant' => 'compact', 'columns' => '4', 'background' => 'white',
                'items' => [
                    ['icon' => 'check-circle', 'title' => 'Handmade Care', 'text' => 'Detailed finishing'],
                    ['icon' => 'sparkles', 'title' => 'Custom Pieces', 'text' => 'Personalized gifts'],
                    ['icon' => 'truck', 'title' => 'Nationwide Delivery', 'text' => 'From Karachi to you'],
                    ['icon' => 'chat', 'title' => 'Friendly Support', 'text' => 'Order help anytime'],
                ],
            ]),
            self::s('div1', 'divider', []),
            self::s('how', 'features', [
                'eyebrow' => 'How It Works', 'title' => 'From Idea to Handmade Piece',
                'subtitle' => 'Pick a ready-made item or tell us what you want customized.',
                'variant' => 'numbered', 'columns' => '3', 'background' => 'oat',
                'items' => [
                    ['icon' => 'search', 'title' => 'Browse or Share an Idea', 'text' => 'Choose from accessories, home pieces, wearables, and cute gifts, or message us for a custom concept.'],
                    ['icon' => 'chat', 'title' => 'Confirm Details', 'text' => 'We align on color, size, quantity, and delivery timing before the item is prepared.'],
                    ['icon' => 'truck', 'title' => 'Receive With Care', 'text' => 'Your handmade order is packed neatly and delivered locally or nationwide.'],
                ],
            ]),
            self::s('why', 'stats', [
                'eyebrow' => 'Why Handmade', 'title' => 'Every Piece Made With Intention',
                'subtitle' => 'No factory lines, no shortcuts — just careful stitching, quality yarn, and attention to the details that make a gift feel personal.',
                'background' => 'dark',
                'items' => [
                    ['count' => 500, 'suffix' => '+', 'label' => 'Happy Customers'],
                    ['count' => 1000, 'suffix' => '+', 'label' => 'Pieces Handmade'],
                    ['count' => 20, 'suffix' => '+', 'label' => 'Gift Styles'],
                    ['count' => 100, 'suffix' => '%', 'label' => 'Handmade Care'],
                ],
            ]),
            self::s('collections', 'categories', [
                'eyebrow' => 'Collections', 'title' => 'Shop by Collection',
                'subtitle' => 'Find crochet, knit, accessories, home decor, and gift-ready pieces.',
                'limit' => 6, 'background' => 'white',
            ]),
            self::s('div2', 'divider', []),
            self::s('featured', 'products', [
                'eyebrow' => 'Customer Favorites', 'title' => 'Featured Handmade Picks',
                'subtitle' => 'Thoughtfully made pieces selected for gifting and everyday use.',
                'source' => 'featured', 'limit' => 8, 'link_text' => 'View all', 'background' => 'gradient',
            ]),
            self::s('testimonials', 'testimonials', [
                'eyebrow' => 'Testimonials', 'title' => 'Loved by Handmade Gift Shoppers', 'background' => 'white',
                'items' => [
                    ['name' => 'Ayesha Noor', 'role' => 'Gift Customer', 'text' => 'The amigurumi was adorable and neatly finished. It felt personal, not mass-produced.'],
                    ['name' => 'Maham Tariq', 'role' => 'Accessory Buyer', 'text' => 'Ordered scrunchies and a headband. Colors were beautiful and delivery was smooth.'],
                    ['name' => 'Hina Ahmed', 'role' => 'Custom Order', 'text' => 'They helped me choose yarn colors for a custom gift and kept every detail exactly right.'],
                ],
            ]),
            self::s('latest', 'products', [
                'eyebrow' => 'Just Added', 'title' => 'New Handmade Arrivals',
                'subtitle' => 'Freshly added crochet and knit creations.',
                'source' => 'latest', 'limit' => 8, 'link_text' => 'View all', 'background' => 'oat',
            ]),
            self::s('cta', 'cta', [
                'title' => 'Want Something Made Just for You?',
                'text' => 'Share your colors, size, theme, or gift idea. We will help turn it into a handmade yarn piece.',
                'primary_text' => 'Start Custom Order', 'primary_url' => '/custom-order',
                'secondary_text' => (string) shop_config('contact_phone'),
                'secondary_url' => 'tel:'.preg_replace('/[^0-9+]/', '', (string) shop_config('contact_phone')),
            ]),
        ];
    }

    protected static function about(?Page $page): array
    {
        $story = $page?->content ?: '<p>EK Yarn Co. creates handcrafted crochet and knitted products made with creativity, quality yarns, and attention to detail. From cute amigurumi and daily accessories to cozy sweaters, shawls, and custom gifts, every piece is designed to feel personal and thoughtfully made.</p>';

        return [
            self::s('hero', 'hero', [
                'style' => 'center',
                'eyebrow' => 'About '.shop_name(),
                'title' => self::meta($page, 'hero_title', 'Handmade Yarn Creations With Personality'),
                'subtitle' => self::meta($page, 'hero_subtitle', 'Crochet, knit, accessories, cozy wearables, and custom gifts made with care in Karachi.'),
            ]),
            self::s('story', 'image_text', [
                'eyebrow' => 'Our Story', 'title' => 'Small-Batch Pieces Made With Care',
                'content' => $story,
                'image' => config('media.about_store'), 'image_alt' => 'Quality yarns and crochet hook', 'image_side' => 'right',
                'background' => 'white',
                'chips' => [
                    ['icon' => 'heart', 'label' => 'Crochet'],
                    ['icon' => 'sparkles', 'label' => 'Accessories'],
                    ['icon' => 'gift', 'label' => 'Custom Gifts'],
                    ['icon' => 'truck', 'label' => 'Nationwide Delivery'],
                ],
            ]),
            self::s('stats', 'stats', [
                'background' => 'oat',
                'items' => [
                    ['count' => 100, 'suffix' => '+', 'label' => 'Handmade Pieces'],
                    ['count' => 20, 'suffix' => '+', 'label' => 'Gift Styles'],
                    ['count' => 5, 'suffix' => '+', 'label' => 'Product Categories'],
                    ['count' => 1, 'suffix' => '', 'label' => 'Karachi Studio'],
                ],
            ]),
            self::s('values', 'features', [
                'eyebrow' => 'What We Stand For', 'title' => 'Craft, Comfort & Thoughtful Details',
                'variant' => 'cards', 'columns' => '3', 'background' => 'white',
                'items' => [
                    ['icon' => 'heart', 'title' => 'Our Mission', 'text' => 'To make handmade yarn products that feel personal, useful, giftable, and finished with care.'],
                    ['icon' => 'eye', 'title' => 'Our Vision', 'text' => 'To become a trusted Karachi-based handmade brand for crochet, knitwear, and custom yarn gifts.'],
                    ['icon' => 'shield', 'title' => 'Our Values', 'text' => 'Quality yarns, honest communication, neat finishing, and respect for every customer idea.'],
                ],
            ]),
            self::s('quote', 'quote', [
                'text' => 'Every stitch is a small decision — the yarn, the color, the finish. That care is what turns a handmade piece into something someone will actually keep.',
                'author' => 'The '.shop_name().' Studio', 'background' => 'dark',
            ]),
            self::s('why', 'features', [
                'eyebrow' => 'Why Choose Us', 'title' => 'Made for Gifting, Wearing, and Keeping',
                'subtitle' => 'Every order is handled like a small creative project.',
                'variant' => 'list', 'columns' => '3', 'background' => 'gradient',
                'items' => [
                    ['icon' => 'check-circle', 'title' => 'Hand-Finished Quality', 'text' => 'Neat details, careful shaping, and quality yarn selected for each product.'],
                    ['icon' => 'sparkles', 'title' => 'Personalized Options', 'text' => 'Colors, themes, and custom gift ideas can be discussed before ordering.'],
                    ['icon' => 'truck', 'title' => 'Local & Nationwide Delivery', 'text' => 'Based in Karachi with delivery available beyond the city.'],
                    ['icon' => 'gift', 'title' => 'Gift-Ready Pieces', 'text' => 'Cute, cozy, and practical items for birthdays, events, and everyday surprises.'],
                    ['icon' => 'cube', 'title' => 'Varied Collections', 'text' => 'Amigurumi, keychains, coasters, scrunchies, wallets, headbands, sweaters, and shawls.'],
                    ['icon' => 'chat', 'title' => 'Clear Communication', 'text' => 'We help confirm size, color, timing, and order details before dispatch.'],
                ],
            ]),
            self::s('cta', 'cta', [
                'title' => 'Ready to Find Your Next Handmade Piece?',
                'text' => 'Browse ready-made products or contact us for a custom crochet or knit gift.',
                'primary_text' => 'Browse Products', 'primary_url' => '/products',
                'secondary_text' => 'Start Custom Order', 'secondary_url' => '/custom-order',
            ]),
        ];
    }

    protected static function products(?Page $page): array
    {
        return [
            self::s('hero', 'hero', [
                'style' => 'left',
                'eyebrow' => 'Full Collection',
                'title' => self::meta($page, 'hero_title', 'Handmade Products'),
                'subtitle' => self::meta($page, 'hero_subtitle', 'Browse crochet, knitted accessories, amigurumi, coasters, scrunchies, wallets, headbands, cozy wearables, and custom gifts.'),
            ]),
        ];
    }

    protected static function contact(?Page $page): array
    {
        $free = (float) shop_config('free_shipping_threshold', 0);

        return [
            self::s('hero', 'hero', [
                'style' => 'center',
                'eyebrow' => 'Get In Touch',
                'title' => self::meta($page, 'hero_title', 'Contact Us'),
                'subtitle' => self::meta($page, 'hero_subtitle', "Have a question about a product, gift idea, custom color, or delivery? We're here to help."),
            ]),
            self::s('faq', 'faq', [
                'eyebrow' => 'FAQ', 'title' => 'Frequently Asked Questions', 'background' => 'oat',
                'items' => [
                    ['q' => 'What are your delivery options?', 'a' => 'We offer local delivery in Karachi and nationwide delivery.'.($free > 0 ? ' Free delivery on orders above '.shop_money($free).'.' : '').' Custom orders may need extra making time.'],
                    ['q' => 'Do you accept custom orders?', 'a' => 'Yes. Share your idea, color preference, size, quantity, and deadline so we can confirm feasibility and timing.'],
                    ['q' => 'What payment methods do you accept?', 'a' => 'We accept online card payments via Stripe and Cash on Delivery (COD) for eligible areas.'],
                    ['q' => 'Can I return a product?', 'a' => 'Ready-made unused items can be discussed for return within 7 days. Custom handmade pieces are made to your selected details, so returns depend on the issue reported.'],
                ],
            ]),
        ];
    }

    protected static function customOrder(?Page $page): array
    {
        return [
            self::s('hero', 'hero', [
                'style' => 'center',
                'eyebrow' => 'Made Just For You',
                'title' => self::meta($page, 'hero_title', 'Request a Custom Piece'),
                'subtitle' => self::meta($page, 'hero_subtitle', "Describe your idea — a favourite character, a name keychain, a matching gift set — and we'll reply with a quote and timeline."),
            ]),
        ];
    }

    protected static function trackOrder(?Page $page): array
    {
        return [
            self::s('hero', 'hero', [
                'style' => 'left',
                'eyebrow' => 'Where Is My Order?',
                'title' => self::meta($page, 'hero_title', 'Track Your Order'),
                'subtitle' => self::meta($page, 'hero_subtitle', ''),
            ]),
        ];
    }

    protected static function policies(?Page $page): array
    {
        $free = (float) shop_config('free_shipping_threshold', 0);
        $lead = (string) config('shop.default_lead_time');

        return [
            self::s('hero', 'hero', [
                'style' => 'center',
                'eyebrow' => 'Good To Know',
                'title' => self::meta($page, 'hero_title', 'Shipping, Returns & Care'),
                'subtitle' => self::meta($page, 'hero_subtitle', ''),
            ]),
            self::s('lead-time', 'info_card', [
                'icon' => 'clock', 'title' => 'Made-to-order lead times', 'anchor' => 'lead-time',
                'content' => "<p>Every piece is crocheted by hand. Most items are prepared within {$lead} of your order; larger or customised pieces can take longer and show their own lead time on the product page.</p><p>Need it by a date? Use the custom order form or message us on WhatsApp before ordering.</p>",
            ]),
            self::s('shipping', 'info_card', [
                'icon' => 'truck', 'title' => 'Shipping & delivery', 'anchor' => 'shipping',
                'content' => '<p>We ship nationwide from Karachi. Delivery charges depend on your city and are shown at checkout.'.($free > 0 ? ' Orders over '.shop_money($free).' ship free.' : '').'</p><p>You will receive an order number you can use on the Track Order page at any time.</p>',
            ]),
            self::s('returns', 'info_card', [
                'icon' => 'shield', 'title' => 'Returns & exchanges', 'anchor' => 'returns',
                'content' => '<p>If your piece arrives damaged or not as described, contact us within 7 days of delivery with a photo and we will repair, replace or refund it.</p><p>Custom and personalised orders are made for you specifically, so they can only be returned if faulty.</p>',
            ]),
            self::s('care', 'info_card', [
                'icon' => 'heart', 'title' => 'Care instructions', 'anchor' => 'care',
                'content' => '<p>Spot-clean amigurumi with a damp cloth and mild soap. Wearables and scrunchies can be hand washed in cool water and dried flat.</p><p>Avoid wringing, tumble-drying or direct heat — it can distort the stitches.</p>',
            ]),
            self::s('cta', 'cta', [
                'title' => 'Still have a question?', 'text' => 'We usually reply within a day.',
                'primary_text' => 'Contact us', 'primary_url' => '/contact',
            ]),
        ];
    }

    protected static function generic(?Page $page): array
    {
        return [
            self::s('hero', 'hero', [
                'style' => 'center',
                'title' => $page?->title ?: 'New page',
                'subtitle' => self::meta($page, 'hero_subtitle', ''),
            ]),
            self::s('body', 'rich_text', [
                'content' => $page?->content ?: '<p>Start writing here…</p>',
                'width' => 'narrow', 'background' => 'white',
            ]),
        ];
    }
}
