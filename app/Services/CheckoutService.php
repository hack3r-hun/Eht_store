<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function calculateTotals(): array
    {
        $subtotal = $this->cartService->subtotal();
        $tax = round($subtotal * (shop_config('tax_rate', 0) / 100), 2);
        $shipping = $subtotal > 0 ? (float) shop_config('shipping_flat', 0) : 0;

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

            if ($item->quantity > $item->product->stock_quantity) {
                throw new \RuntimeException("Not enough stock for {$item->product->name}.");
            }
        }

        $totals = $this->calculateTotals();

        return DB::transaction(function () use ($data, $paymentMethod, $items, $totals) {
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
                'user_id' => Auth::id(),
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

            if (! Auth::id()) {
                $guestToken = Str::random(40);
                $order->update(['guest_access_token' => hash('sha256', $guestToken)]);
                session()->put("guest_order_access.{$order->id}", $guestToken);
            }

            foreach ($items as $item) {
                // Atomic guarded decrement: fails (0 rows) if concurrent checkouts
                // already consumed the stock, rolling back the whole order.
                $affected = Product::whereKey($item->product_id)
                    ->where('is_active', true)
                    ->where('stock_quantity', '>=', $item->quantity)
                    ->decrement('stock_quantity', $item->quantity);

                if ($affected === 0) {
                    throw new \RuntimeException("Not enough stock for {$item->product->name}.");
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->product->effective_price,
                    'line_total' => $item->line_total,
                ]);
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
            $orderNumber = 'EM-'.strtoupper(Str::random(8));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
