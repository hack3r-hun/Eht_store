<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'shop_name' => config('shop.name'),
            'shop_tagline' => config('shop.tagline'),
            'tax_rate' => config('shop.tax_rate'),
            'shipping_flat' => config('shop.shipping_flat'),
            'contact_email' => config('shop.contact_email'),
            'contact_phone' => config('shop.contact_phone'),
            'contact_address' => config('shop.contact_address'),
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => (string) $value]);
        }
    }
}
