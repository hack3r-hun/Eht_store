<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'payment_method' => $this->payment_method->value,
            'payment_status' => $this->payment_status->value,
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'shipping' => (float) $this->shipping,
            'total' => (float) $this->total,
            'total_label' => shop_money($this->total),
            'shipping_address' => $this->shipping_address,
            'notes' => $this->notes,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'user' => new UserResource($this->whenLoaded('user')),
            'payment' => $this->when($this->relationLoaded('payment') && $this->payment, fn () => [
                'status' => $this->payment->status->value,
                'amount' => (float) $this->payment->amount,
                'stripe_payment_intent_id' => $this->payment->stripe_payment_intent_id,
            ]),
            'guest_access_token' => $this->when(
                $this->plain_guest_access_token_for_api,
                $this->plain_guest_access_token_for_api
            ),
            'payment_intent' => $this->when(isset($this->payment_intent), $this->payment_intent),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
