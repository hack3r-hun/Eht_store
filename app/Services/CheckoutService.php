<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\CartIdentity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected ShippingService $shippingService
    ) {}

    public function calculateTotals(?string $city = null): array
    {
        $subtotal = $this->cartService->subtotal();
        $tax = round($subtotal * (shop_config('tax_rate', 0) / 100), 2);
        $shipping = $subtotal > 0 ? $this->shippingService->rateForCity($city) : 0;

        $freeShippingThreshold = (float) shop_config('free_shipping_threshold', 0);

        if ($freeShippingThreshold > 0 && $subtotal >= $freeShippingThreshold) {
            $shipping = 0;
        }

        $total = $subtotal + $tax + $shipping;

        return compact('subtotal', 'tax', 'shipping', 'total');
    }

    public function placeOrder(array $data, PaymentMethod $paymentMethod): Order
    {
        $items = $this->cartService->items();

        if ($items->isEmpty()) {
            throw new \RuntimeException('Your cart is empty.');
        }

        foreach ($items as $item) {
            if (! $item->product || ! $item->product->is_active) {
                $productName = $item->product?->name ?? 'A product';

                throw new \RuntimeException("{$productName} is no longer available.");
            }

            if (! $item->variant || ! $item->variant->is_active) {
                throw new \RuntimeException("{$item->product->name} size is no longer available.");
            }

            if ($item->quantity > $item->variant->stock_quantity) {
                throw new \RuntimeException("Not enough stock for {$item->product->name} ({$item->variant->size}).");
            }
        }

        $totals = $this->calculateTotals($data['city'] ?? null);
        $userId = CartIdentity::resolve()->userId;

        return DB::transaction(function () use ($data, $paymentMethod, $items, $totals, $userId) {
            $shippingAddress = [
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'address_line' => $data['address_line'],
                'city' => $data['city'],
                'state' => $data['state'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
            ];

            if (! empty($data['email'])) {
                $shippingAddress['email'] = $data['email'];
            }

            $order = Order::create([
                'user_id' => $userId,
                'order_number' => $this->generateOrderNumber(),
                'status' => $paymentMethod === PaymentMethod::Cod
                    ? OrderStatus::AwaitingCod
                    : OrderStatus::Pending,
                'payment_method' => $paymentMethod,
                'payment_status' => PaymentStatus::Pending,
                'subtotal' => $totals['subtotal'],
                'tax' => $totals['tax'],
                'shipping' => $totals['shipping'],
                'total' => $totals['total'],
                'shipping_address' => $shippingAddress,
                'notes' => $data['notes'] ?? null,
            ]);

            if (! $userId) {
                $guestToken = Str::random(40);
                $order->update(['guest_access_token' => hash('sha256', $guestToken)]);
                $order->plain_guest_access_token_for_api = $guestToken;

                if (request()->hasSession()) {
                    session()->put("guest_order_access.{$order->id}", $guestToken);
                }
            }

            foreach ($items as $item) {
                $variant = $item->variant;

                $affected = ProductVariant::whereKey($variant->id)
                    ->where('is_active', true)
                    ->where('stock_quantity', '>=', $item->quantity)
                    ->decrement('stock_quantity', $item->quantity);

                if ($affected === 0) {
                    throw new \RuntimeException("Not enough stock for {$item->product->name} ({$variant->size}).");
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $item->product->name,
                    'product_sku' => $variant->sku ?: $item->product->sku,
                    'size' => $variant->size,
                    'quantity' => $item->quantity,
                    'unit_price' => $variant->effective_price,
                    'line_total' => $item->line_total,
                ]);
            }

            $productIds = $items->pluck('product_id')->unique();
            foreach ($productIds as $productId) {
                $product = Product::with('variants')->find($productId);
                if ($product) {
                    app(ProductVariantService::class)->refreshProductAggregates($product);
                }
            }

            Payment::create([
                'order_id' => $order->id,
                'status' => PaymentStatus::Pending,
                'amount' => $totals['total'],
            ]);

            if ($paymentMethod === PaymentMethod::Cod) {
                $order->update(['payment_status' => PaymentStatus::Pending]);
            }

            $this->cartService->clear();

            return $order->load('items');
        });
    }

    protected function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'EKY-'.strtoupper(Str::random(8));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
