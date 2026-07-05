@extends('layouts.storefront')

@section('title', 'Order Confirmed')

@section('content')
    <section class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center animate-fade-in-up">
        <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-green-100 flex items-center justify-center">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-3xl font-bold text-charcoal-700 mb-4">Thank You!</h1>
        <p class="text-charcoal-500 mb-2">Your order has been placed successfully.</p>
        <p class="text-lg font-semibold text-sage-700 mb-8">Order #{{ $order->order_number }}</p>

        <div class="bg-white rounded-2xl border border-oat-200 shadow-card p-6 text-left mb-8">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-slate-500">Status</span><p class="font-medium capitalize">{{ $order->status->label() }}</p></div>
                <div><span class="text-slate-500">Payment</span><p class="font-medium">{{ $order->payment_method->label() }}</p></div>
                <div><span class="text-slate-500">Total</span><p class="font-bold text-sage-700">{{ shop_money($order->total) }}</p></div>
                <div><span class="text-slate-500">Items</span><p class="font-medium">{{ $order->items->count() }} product(s)</p></div>
            </div>
        </div>

        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ route('products.index') }}" class="btn-primary">Continue Shopping</a>
            @auth
                <a href="{{ route('profile.edit') }}" class="btn-outline">View Account</a>
            @endauth
        </div>
    </section>
@endsection
