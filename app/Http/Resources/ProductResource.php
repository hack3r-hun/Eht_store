<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price !== null ? (float) $this->sale_price : null,
            'effective_price' => $this->effective_price,
            'from_price' => $this->from_price,
            'price_label' => shop_money($this->from_price),
            'is_on_sale' => $this->is_on_sale,
            'stock_quantity' => $this->stock_quantity,
            'is_in_stock' => $this->is_in_stock,
            'is_low_stock' => $this->is_low_stock,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'specifications' => $this->specifications,
            'image_url' => $this->image_url,
            'thumb_url' => $this->thumb_url,
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('activeVariants')),
            'all_variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
