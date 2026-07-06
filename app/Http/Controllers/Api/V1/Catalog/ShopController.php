<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\ShopSettingsResource;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class ShopController extends ApiController
{
    public function show(): JsonResponse
    {
        $settings = [
            'name' => Setting::get('shop_name', config('shop.name')),
            'tagline' => Setting::get('shop_tagline', config('shop.tagline')),
            'tax_rate' => Setting::get('tax_rate', config('shop.tax_rate')),
            'free_shipping_threshold' => Setting::get('free_shipping_threshold', config('shop.free_shipping_threshold')),
            'contact_email' => Setting::get('contact_email', config('shop.contact_email')),
            'contact_phone' => Setting::get('contact_phone', config('shop.contact_phone')),
            'contact_address' => Setting::get('contact_address', config('shop.contact_address')),
        ];

        return $this->success(new ShopSettingsResource($settings));
    }
}
