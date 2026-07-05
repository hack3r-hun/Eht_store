<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected CheckoutService $checkoutService,
        protected PaymentService $paymentService
    ) {}

    public function index()
    {
        $items = $this->cartService->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $totals = $this->checkoutService->calculateTotals();

        return view('storefront.checkout.index', compact('items', 'totals'));
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        try {
            $paymentMethod = PaymentMethod::from($request->payment_method);
            $order = $this->checkoutService->placeOrder($request->validated(), $paymentMethod);

            if ($paymentMethod === PaymentMethod::Stripe) {
                if ($this->paymentService->isConfigured()) {
                    return redirect()->route('checkout.pay', $order);
                }

                return redirect()->route('checkout.success', $order)
                    ->with('info', 'Order saved. Configure Stripe keys in .env to enable card payments.');
            }

            try {
                $this->paymentService->sendOrderConfirmation($order);
            } catch (\Throwable $e) {
                report($e);
            }

            return redirect()->route('checkout.success', $order)
                ->with('success', 'Order placed successfully! Pay cash on delivery.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', 'Something went wrong while placing your order. Please try again.')
                ->withInput();
        }
    }

    public function pay(Order $order): View|RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($order->payment_method !== PaymentMethod::Stripe) {
            return redirect()->route('checkout.success', $order);
        }

        if ($order->payment_status === PaymentStatus::Paid) {
            return redirect()->route('checkout.success', $order);
        }

        if (! $this->paymentService->isConfigured()) {
            return redirect()->route('checkout.success', $order)
                ->with('info', 'Stripe is not configured. Order saved as pending.');
        }

        try {
            $payment = $this->paymentService->createPaymentIntent($order);
        } catch (\Throwable) {
            return redirect()->route('checkout.success', $order)
                ->with('error', 'Could not initialize payment. Please try again or contact support.');
        }

        return view('storefront.checkout.pay', [
            'order' => $order,
            'stripeKey' => config('services.stripe.key'),
            'clientSecret' => $payment['client_secret'],
        ]);
    }

    public function confirmPayment(Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        $intentId = $order->payment?->stripe_payment_intent_id;

        if (! $intentId || ! $this->paymentService->verifyAndMarkPaid($order, $intentId)) {
            return redirect()->route('checkout.pay', $order)
                ->with('error', 'Payment not completed. Please try again.');
        }

        try {
            $this->paymentService->sendOrderConfirmation($order->fresh());
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('checkout.success', $order)
            ->with('success', 'Payment successful! Thank you for your order.');
    }

    public function success(Order $order)
    {
        $this->authorizeOrder($order);

        return view('storefront.checkout.success', compact('order'));
    }

    protected function authorizeOrder(Order $order): void
    {
        if ($order->user_id) {
            if (! auth()->check() || auth()->id() !== $order->user_id) {
                abort(403);
            }

            return;
        }

        $sessionToken = session("guest_order_access.{$order->id}");

        if (! $sessionToken || ! $order->guest_access_token) {
            abort(403);
        }

        if (! hash_equals($order->guest_access_token, hash('sha256', $sessionToken))) {
            abort(403);
        }
    }
}
