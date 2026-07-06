<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource['name'] ?? shop_name(),
            'tagline' => $this->resource['tagline'] ?? shop_config('tagline'),
            'currency' => config('shop.currency', 'PKR'),
            'currency_symbol' => config('shop.currency_symbol', 'Rs.'),
            'tax_rate' => (float) ($this->resource['tax_rate'] ?? shop_config('tax_rate', 0)),
            'free_shipping_threshold' => (float) ($this->resource['free_shipping_threshold'] ?? shop_config('free_shipping_threshold', 0)),
            'contact_email' => $this->resource['contact_email'] ?? shop_config('contact_email'),
            'contact_phone' => $this->resource['contact_phone'] ?? shop_config('contact_phone'),
            'contact_address' => $this->resource['contact_address'] ?? shop_config('contact_address'),
        ];
    }
}
