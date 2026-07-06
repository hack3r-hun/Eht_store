<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'items' => CartItemResource::collection($this->resource['items']),
            'count' => $this->resource['count'],
            'subtotal' => $this->resource['subtotal'],
            'subtotal_label' => shop_money($this->resource['subtotal']),
            'guest_cart_token' => $this->resource['guest_cart_token'] ?? null,
        ];
    }
}
