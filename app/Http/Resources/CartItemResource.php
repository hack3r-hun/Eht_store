<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'line_total' => $this->line_total,
            'line_total_label' => shop_money($this->line_total),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
