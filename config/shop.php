<?php

return [
    'name' => env('SHOP_NAME', 'EK Yarn Co.'),
    'tagline' => env('SHOP_TAGLINE', 'Handcrafted crochet, knitted gifts, and cozy yarn creations'),
    'currency' => env('SHOP_CURRENCY', 'PKR'),
    'currency_symbol' => env('SHOP_CURRENCY_SYMBOL', 'Rs.'),
    'tax_rate' => (float) env('SHOP_TAX_RATE', 0),
    'shipping_flat' => (float) env('SHOP_SHIPPING_FLAT', 250),
    'free_shipping_threshold' => (float) env('SHOP_FREE_SHIPPING_THRESHOLD', 5000),
    'low_stock_threshold' => (int) env('SHOP_LOW_STOCK', 5),
    'contact_email' => env('SHOP_CONTACT_EMAIL', 'hello@ekyarnco.local'),
    'contact_phone' => env('SHOP_CONTACT_PHONE', '+92 300 1234567'),
    'contact_address' => env('SHOP_CONTACT_ADDRESS', 'Karachi, Pakistan'),
];
