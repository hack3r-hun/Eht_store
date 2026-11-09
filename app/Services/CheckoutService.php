<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\CartIdentity;
use App\Support\ContactNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected ShippingService $shippingService
    ) {}

    /**
     * The coupon stored in the visitor's session (web storefront only — the stateless
     * API has no session), or null when absent or no longer valid for this subtotal.
     */
    public function activeCoupon(?float $subtotal = null): ?Coupon
    {
        if (! module_enabled('coupons') || ! request()->hasSession() || request()->is('api/*')) {
            return null;
        }

        $code = session('coupon_code');

        if (! $code) {
            return null;
        }

        $subtotal ??= $this->cartService->subtotal();
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || $coupon->problemFor($subtotal) !== null) {
            session()->forget('coupon_code');

            return null;
        }

        return $coupon;
    }

    public function calculateTotals(?string $city = null, bool $giftWrap = false): array
    {
        $subtotal = $this->cartService->subtotal();
        $coupon = $this->activeCoupon($subtotal);
        $discount = $coupon ? $coupon->discountFor($subtotal) : 0.0;
        $taxable = $subtotal - $discount;

        $tax = round($taxable * (shop_config('tax_rate', 0) / 100), 2);
        $shipping = $subtotal > 0 ? $this->shippingService->rateForCity($city) : 0;

        $freeShippingThreshold = (float) shop_config('free_shipping_threshold', 0);

        if ($freeShippingThreshold > 0 && $taxable >= $freeShippingThreshold) {
            $shipping = 0;
        }

        $giftFee = $giftWrap && module_enabled('gift_options') && $subtotal > 0
            ? (float) module_value('gift_options', 'fee', config('shop.gift_wrap_fee', 0))
            : 0.0;
        $total = $taxable + $tax + $shipping + $giftFee;
        $couponCode = $coupon?->code;

        return compact('subtotal', 'discount', 'tax', 'shipping', 'total', 'couponCode') + [
            'gift_fee' => $giftFee,
            'free_shipping_threshold' => $freeShippingThreshold,
            'taxable' => $taxable,
        ];
    }

    public function placeOrder(array $data, PaymentMethod $paymentMethod): Order
    {
        // Dashboard → Modules can pause ordering, close guest checkout or switch a payment method off.
        if (! module_enabled('accepting_orders')) {
            throw new \RuntimeException((string) module_value('accepting_orders', 'closed_message'));
        }

        if (! CartIdentity::resolve()->userId && ! module_enabled('guest_checkout')) {
            throw new \RuntimeException('Please sign in or create an account to place your order.');
        }

        if (! self::paymentMethodEnabled($paymentMethod)) {
            throw new \RuntimeException('That payment method is not available right now. Please choose another.');
        }

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

        $giftWrap = module_enabled('gift_options') && (bool) ($data['gift_wrap'] ?? false);
        $totals = $this->calculateTotals($data['city'] ?? null, $giftWrap);
        $userId = CartIdentity::resolve()->userId;

        return DB::transaction(function () use ($data, $paymentMethod, $items, $totals, $userId, $giftWrap) {
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
                'discount' => $totals['discount'],
                'coupon_code' => $totals['couponCode'],
                'gift_wrap' => $giftWrap,
                'gift_wrap_fee' => $totals['gift_fee'],
                'gift_message' => $giftWrap ? ($data['gift_message'] ?? null) : null,
                'gift_hide_prices' => $giftWrap && ! empty($data['gift_hide_prices']),
                'shipping' => $totals['shipping'],
                'total' => $totals['total'],
                'shipping_address' => $shippingAddress,
                'notes' => $data['notes'] ?? null,
                'contact_name' => $data['full_name'],
                'contact_email' => ContactNormalizer::email($data['email'] ?? ($userId ? \App\Models\User::whereKey($userId)->value('email') : null)),
                'contact_phone' => ContactNormalizer::phone($data['phone'] ?? null),
                'marketing_opt_in' => (bool) ($data['marketing_opt_in'] ?? false),
            ]);

            if ($totals['couponCode']) {
                // Atomic claim so two simultaneous checkouts can't exceed max_uses.
                $claimed = Coupon::where('code', $totals['couponCode'])
                    ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'))
                    ->increment('used_count');

                if ($claimed === 0) {
                    throw new \RuntimeException('This coupon has reached its usage limit.');
                }
            }

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

            if (request()->hasSession()) {
                session()->forget('coupon_code');
            }

            return $order->load('items');
        });
    }

    public static function paymentMethodEnabled(PaymentMethod $method): bool
    {
        return module_enabled($method === PaymentMethod::Cod ? 'payment_cod' : 'payment_stripe');
    }

    /** @return array<int, string> values of the payment methods currently switched on */
    public static function enabledPaymentMethods(): array
    {
        return array_values(array_filter(
            array_map(fn (PaymentMethod $m) => $m->value, PaymentMethod::cases()),
            fn (string $value) => self::paymentMethodEnabled(PaymentMethod::from($value))
        ));
    }

    protected function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'EKY-'.strtoupper(Str::random(8));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
