<?php

namespace App\Support\Cms;

/**
 * Describes every page-builder block: its editable fields and defaults.
 * The same schema drives the admin editor form, server-side validation and rendering.
 *
 * Field kinds: text, textarea, richtext, image, url, select, number, toggle, icon, repeater.
 */
class SectionSchema
{
    public const ICONS = [
        'heart', 'sparkles', 'gift', 'truck', 'chat', 'check-circle', 'shield', 'star', 'cube', 'eye',
        'search', 'clock', 'map-pin', 'phone', 'mail', 'user', 'users', 'home', 'bolt', 'light-bulb',
        'banknotes', 'globe', 'calendar', 'clipboard', 'folder', 'document', 'info',
    ];

    public const BACKGROUNDS = [
        'white' => 'White',
        'oat' => 'Warm oat (soft pattern)',
        'gradient' => 'Soft gradient',
        'dark' => 'Dark charcoal',
    ];

    public const MAX_SECTIONS = 40;

    public const MAX_ITEMS = 24;

    protected static function background(string $default = 'white'): array
    {
        return ['key' => 'background', 'label' => 'Background', 'kind' => 'select', 'options' => self::BACKGROUNDS, 'default' => $default];
    }

    protected static function heading(): array
    {
        return [
            ['key' => 'eyebrow', 'label' => 'Small label above title', 'kind' => 'text', 'max' => 80],
            ['key' => 'title', 'label' => 'Title', 'kind' => 'text', 'max' => 200],
            ['key' => 'subtitle', 'label' => 'Subtitle', 'kind' => 'textarea', 'max' => 500],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public static function types(): array
    {
        return [
            'hero' => [
                'label' => 'Hero banner',
                'description' => 'Big headline area with optional image and buttons.',
                'fields' => [
                    ['key' => 'style', 'label' => 'Layout', 'kind' => 'select', 'default' => 'center', 'options' => [
                        'split' => 'Text left + image right',
                        'center' => 'Centered text',
                        'left' => 'Left-aligned text',
                    ]],
                    ['key' => 'eyebrow', 'label' => 'Small label above title', 'kind' => 'text', 'max' => 80],
                    ['key' => 'title', 'label' => 'Title', 'kind' => 'text', 'max' => 200],
                    ['key' => 'subtitle', 'label' => 'Subtitle', 'kind' => 'textarea', 'max' => 500],
                    ['key' => 'primary_text', 'label' => 'Main button text', 'kind' => 'text', 'max' => 60],
                    ['key' => 'primary_url', 'label' => 'Main button link', 'kind' => 'url'],
                    ['key' => 'secondary_text', 'label' => 'Second button text', 'kind' => 'text', 'max' => 60],
                    ['key' => 'secondary_url', 'label' => 'Second button link', 'kind' => 'url'],
                    ['key' => 'image', 'label' => 'Hero image (used by the split layout)', 'kind' => 'image'],
                    ['key' => 'badge_top', 'label' => 'Floating badge (top)', 'kind' => 'text', 'max' => 60],
                    ['key' => 'badge_title', 'label' => 'Floating card title (bottom)', 'kind' => 'text', 'max' => 60],
                    ['key' => 'badge_text', 'label' => 'Floating card text (bottom)', 'kind' => 'text', 'max' => 100],
                ],
            ],
            'features' => [
                'label' => 'Icon cards',
                'description' => 'Row or grid of cards with an icon, title and short text.',
                'fields' => array_merge(self::heading(), [
                    ['key' => 'variant', 'label' => 'Card style', 'kind' => 'select', 'default' => 'cards', 'options' => [
                        'cards' => 'Centered cards',
                        'numbered' => 'Numbered steps',
                        'list' => 'Icon + text rows',
                        'compact' => 'Compact strip (overlaps the section above)',
                    ]],
                    ['key' => 'columns', 'label' => 'Columns on desktop', 'kind' => 'select', 'default' => '3', 'options' => ['2' => '2', '3' => '3', '4' => '4']],
                    self::background(),
                    ['key' => 'items', 'label' => 'Cards', 'kind' => 'repeater', 'item_label' => 'title', 'fields' => [
                        ['key' => 'icon', 'label' => 'Icon', 'kind' => 'icon', 'default' => 'sparkles'],
                        ['key' => 'title', 'label' => 'Title', 'kind' => 'text', 'max' => 120],
                        ['key' => 'text', 'label' => 'Text', 'kind' => 'textarea', 'max' => 400],
                    ]],
                ]),
            ],
            'stats' => [
                'label' => 'Numbers / stats',
                'description' => 'Animated counters such as "500+ happy customers".',
                'fields' => array_merge(self::heading(), [
                    self::background('oat'),
                    ['key' => 'items', 'label' => 'Stats', 'kind' => 'repeater', 'item_label' => 'label', 'fields' => [
                        ['key' => 'count', 'label' => 'Number', 'kind' => 'number'],
                        ['key' => 'suffix', 'label' => 'After number (e.g. + or %)', 'kind' => 'text', 'max' => 4],
                        ['key' => 'label', 'label' => 'Label', 'kind' => 'text', 'max' => 80],
                    ]],
                ]),
            ],
            'rich_text' => [
                'label' => 'Text block',
                'description' => 'A heading with a formatted paragraph area.',
                'fields' => array_merge(self::heading(), [
                    ['key' => 'content', 'label' => 'Content', 'kind' => 'richtext'],
                    ['key' => 'align', 'label' => 'Alignment', 'kind' => 'select', 'default' => 'left', 'options' => ['left' => 'Left', 'center' => 'Centered']],
                    ['key' => 'width', 'label' => 'Width', 'kind' => 'select', 'default' => 'narrow', 'options' => ['narrow' => 'Narrow (easy to read)', 'wide' => 'Wide']],
                    self::background(),
                ]),
            ],
            'image_text' => [
                'label' => 'Image + text',
                'description' => 'A picture next to a story, with optional icon chips and button.',
                'fields' => array_merge(self::heading(), [
                    ['key' => 'content', 'label' => 'Content', 'kind' => 'richtext'],
                    ['key' => 'image', 'label' => 'Image', 'kind' => 'image'],
                    ['key' => 'image_alt', 'label' => 'Image description (for accessibility)', 'kind' => 'text', 'max' => 160],
                    ['key' => 'image_side', 'label' => 'Image position', 'kind' => 'select', 'default' => 'right', 'options' => ['right' => 'Right', 'left' => 'Left']],
                    ['key' => 'button_text', 'label' => 'Button text', 'kind' => 'text', 'max' => 60],
                    ['key' => 'button_url', 'label' => 'Button link', 'kind' => 'url'],
                    self::background(),
                    ['key' => 'chips', 'label' => 'Small icon labels under the image', 'kind' => 'repeater', 'item_label' => 'label', 'fields' => [
                        ['key' => 'icon', 'label' => 'Icon', 'kind' => 'icon', 'default' => 'heart'],
                        ['key' => 'label', 'label' => 'Label', 'kind' => 'text', 'max' => 60],
                    ]],
                ]),
            ],
            'products' => [
                'label' => 'Product grid',
                'description' => 'Shows featured, newest or a specific category of products automatically.',
                'fields' => array_merge(self::heading(), [
                    ['key' => 'source', 'label' => 'Which products', 'kind' => 'select', 'default' => 'featured', 'options' => [
                        'featured' => 'Featured products',
                        'latest' => 'Newest products',
                        'category' => 'A specific category',
                    ]],
                    ['key' => 'category', 'label' => 'Category (when "specific category")', 'kind' => 'category'],
                    ['key' => 'limit', 'label' => 'How many to show', 'kind' => 'number', 'default' => 8],
                    ['key' => 'link_text', 'label' => '"View all" link text', 'kind' => 'text', 'max' => 60, 'default' => 'View all'],
                    self::background('gradient'),
                ]),
            ],
            'categories' => [
                'label' => 'Collections grid',
                'description' => 'Your top-level categories with pictures, pulled in automatically.',
                'fields' => array_merge(self::heading(), [
                    ['key' => 'limit', 'label' => 'How many to show', 'kind' => 'number', 'default' => 6],
                    self::background(),
                ]),
            ],
            'testimonials' => [
                'label' => 'Testimonials',
                'description' => 'A rotating slider of customer quotes.',
                'fields' => array_merge(self::heading(), [
                    self::background(),
                    ['key' => 'items', 'label' => 'Testimonials', 'kind' => 'repeater', 'item_label' => 'name', 'fields' => [
                        ['key' => 'name', 'label' => 'Customer name', 'kind' => 'text', 'max' => 80],
                        ['key' => 'role', 'label' => 'Role / note', 'kind' => 'text', 'max' => 80],
                        ['key' => 'text', 'label' => 'Quote', 'kind' => 'textarea', 'max' => 500],
                    ]],
                ]),
            ],
            'quote' => [
                'label' => 'Big quote',
                'description' => 'A single large highlighted statement.',
                'fields' => [
                    ['key' => 'text', 'label' => 'Quote', 'kind' => 'textarea', 'max' => 500],
                    ['key' => 'author', 'label' => 'Who said it', 'kind' => 'text', 'max' => 100],
                    self::background('dark'),
                ],
            ],
            'faq' => [
                'label' => 'FAQ',
                'description' => 'Questions that expand to show the answer.',
                'fields' => array_merge(self::heading(), [
                    self::background('oat'),
                    ['key' => 'items', 'label' => 'Questions', 'kind' => 'repeater', 'item_label' => 'q', 'fields' => [
                        ['key' => 'q', 'label' => 'Question', 'kind' => 'text', 'max' => 200],
                        ['key' => 'a', 'label' => 'Answer', 'kind' => 'textarea', 'max' => 1000],
                    ]],
                ]),
            ],
            'info_card' => [
                'label' => 'Info card',
                'description' => 'A titled card with an icon and formatted text — good for policies.',
                'fields' => [
                    ['key' => 'icon', 'label' => 'Icon', 'kind' => 'icon', 'default' => 'info'],
                    ['key' => 'title', 'label' => 'Title', 'kind' => 'text', 'max' => 200],
                    ['key' => 'content', 'label' => 'Content', 'kind' => 'richtext'],
                    ['key' => 'anchor', 'label' => 'Link anchor (letters only, e.g. returns → /policies#returns)', 'kind' => 'text', 'max' => 40],
                ],
            ],
            'gallery' => [
                'label' => 'Image gallery',
                'description' => 'A responsive grid of pictures with optional captions.',
                'fields' => array_merge(self::heading(), [
                    ['key' => 'columns', 'label' => 'Columns on desktop', 'kind' => 'select', 'default' => '3', 'options' => ['2' => '2', '3' => '3', '4' => '4']],
                    self::background(),
                    ['key' => 'items', 'label' => 'Pictures', 'kind' => 'repeater', 'item_label' => 'caption', 'fields' => [
                        ['key' => 'image', 'label' => 'Image', 'kind' => 'image'],
                        ['key' => 'caption', 'label' => 'Caption', 'kind' => 'text', 'max' => 120],
                    ]],
                ]),
            ],
            'cta' => [
                'label' => 'Call-to-action banner',
                'description' => 'A highlighted banner with one or two buttons.',
                'fields' => [
                    ['key' => 'title', 'label' => 'Title', 'kind' => 'text', 'max' => 200],
                    ['key' => 'text', 'label' => 'Text', 'kind' => 'textarea', 'max' => 400],
                    ['key' => 'primary_text', 'label' => 'Main button text', 'kind' => 'text', 'max' => 60],
                    ['key' => 'primary_url', 'label' => 'Main button link', 'kind' => 'url'],
                    ['key' => 'secondary_text', 'label' => 'Second button text', 'kind' => 'text', 'max' => 60],
                    ['key' => 'secondary_url', 'label' => 'Second button link', 'kind' => 'url'],
                ],
            ],
            'divider' => [
                'label' => 'Stitched divider',
                'description' => 'A small decorative stitch line between sections.',
                'fields' => [],
            ],
        ];
    }

    public static function type(string $type): ?array
    {
        return self::types()[$type] ?? null;
    }

    /** Fresh data for a newly added block: every field with its default. */
    public static function blankData(string $type): array
    {
        $data = [];

        foreach (self::type($type)['fields'] ?? [] as $field) {
            $data[$field['key']] = $field['kind'] === 'repeater' ? [] : ($field['default'] ?? ($field['kind'] === 'toggle' ? false : ''));
        }

        return $data;
    }

    /** Payload handed to the admin editor's JavaScript. */
    public static function forEditor(): array
    {
        return collect(self::types())->map(fn ($def, $type) => [
            'type' => $type,
            'label' => $def['label'],
            'description' => $def['description'],
            'fields' => $def['fields'],
            'blank' => self::blankData($type),
        ])->values()->all();
    }
}
