@extends('layouts.storefront')

@section('title', 'Pay Order')

@section('content')
    <section class="max-w-lg mx-auto px-4 py-12">
        <h1 class="section-title mb-2 text-center">Complete Payment</h1>
        <p class="text-center text-charcoal-500 mb-8">Order {{ $order->order_number }} - {{ shop_money($order->total) }}</p>

        <div class="bg-white rounded-2xl border border-oat-200 shadow-card p-6">
            <div id="payment-element" class="mb-6"></div>
            <button id="submit-payment" class="btn-primary w-full">Pay Now</button>
            <p id="payment-error" class="text-red-600 text-sm mt-3 hidden"></p>
        </div>
    </section>

    @if($stripeKey && $clientSecret)
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe(@json($stripeKey));
        const elements = stripe.elements({ clientSecret: @json($clientSecret) });
        const paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');

        document.getElementById('submit-payment').addEventListener('click', async () => {
            const btn = document.getElementById('submit-payment');
            const errEl = document.getElementById('payment-error');
            btn.disabled = true;
            btn.textContent = 'Processing...';

            const { error } = await stripe.confirmPayment({
                elements,
                confirmParams: { return_url: @json(route('checkout.pay.confirm', $order)) },
                redirect: 'if_required',
            });

            if (error) {
                errEl.textContent = error.message;
                errEl.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = 'Pay Now';
            } else {
                window.location.href = @json(route('checkout.pay.confirm', $order));
            }
        });
    </script>
    @endif
@endsection
