<?php

namespace App\Http\Controllers\Api\V1\Checkout;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\ApiCheckoutRequest;
use App\Http\Requests\Api\V1\ShippingQuoteRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PaymentService;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends ApiController
{
    public function __construct(
        protected CartService $cartService,
        protected CheckoutService $checkoutService,
        protected PaymentService $paymentService,
        protected ShippingService $shippingService
    ) {}

    public function shippingQuote(ShippingQuoteRequest $request): JsonResponse
    {
        if ($this->cartService->items()->isEmpty()) {
            return $this->error('Your cart is empty.', 422);
        }

        $city = $request->string('city')->toString();
        $totals = $this->checkoutService->calculateTotals($city);

        return $this->success([
            'shipping' => $totals['shipping'],
            'shipping_label' => $totals['shipping'] > 0 ? shop_money($totals['shipping']) : 'Free',
            'subtotal' => $totals['subtotal'],
            'tax' => $totals['tax'],
            'total' => $totals['total'],
            'total_label' => shop_money($totals['total']),
            'zone_label' => $this->shippingService->zoneLabel($city),
        ]);
    }

    public function store(ApiCheckoutRequest $request): JsonResponse
    {
        try {
            $paymentMethod = PaymentMethod::from($request->payment_method);
            $order = $this->checkoutService->placeOrder($request->validated(), $paymentMethod);

            $response = ['order' => new OrderResource($order)];

            if ($paymentMethod === PaymentMethod::Stripe && $this->paymentService->isConfigured()) {
                try {
                    $payment = $this->paymentService->createPaymentIntentForApi($order);
                    $order->payment_intent = [
                        'client_secret' => $payment['client_secret'],
                        'payment_intent_id' => $payment['payment_intent_id'],
                        'publishable_key' => config('services.stripe.key'),
                    ];
                    $response['order'] = new OrderResource($order);
                } catch (\Throwable $e) {
                    report($e);

                    return $this->error('Order created but payment could not be initialized.', 502);
                }
            }

            if ($paymentMethod === PaymentMethod::Cod) {
                try {
                    $this->paymentService->sendOrderConfirmation($order);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return $this->success($response, 201);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            report($e);

            return $this->error('Something went wrong while placing your order. Please try again.', 500);
        }
    }

    public function confirmPayment(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);

        if ($order->payment_method !== PaymentMethod::Stripe) {
            return $this->error('This order does not use card payment.', 422);
        }

        $intentId = $order->payment?->stripe_payment_intent_id
            ?? $request->string('payment_intent_id')->toString();

        if (! $intentId || ! $this->paymentService->verifyAndMarkPaid($order, $intentId)) {
            return $this->error('Payment not completed. Please try again.', 422);
        }

        try {
            $this->paymentService->sendOrderConfirmation($order->fresh());
        } catch (\Throwable $e) {
            report($e);
        }

        return $this->success([
            'message' => 'Payment successful.',
            'order' => new OrderResource($order->fresh(['items', 'payment'])),
        ]);
    }

    protected function authorizeOrder(Request $request, Order $order): void
    {
        if ($order->user_id) {
            if (! $request->user() || $request->user()->id !== $order->user_id) {
                abort(403);
            }

            return;
        }

        $guestToken = $request->header('X-Guest-Order-Token')
            ?? $request->string('guest_access_token')->toString();

        if (! $guestToken || ! $order->guest_access_token) {
            abort(403);
        }

        if (! hash_equals($order->guest_access_token, hash('sha256', $guestToken))) {
            abort(403);
        }
    }
}
