@extends('layouts.storefront')

@section('title', 'Checkout')

@section('content')
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="section-title mb-10 animate-fade-in-up">Checkout</h1>

        @if($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm animate-fade-in">
                <p class="font-semibold mb-1">Please fix the following before placing your order:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ route('checkout.store') }}"
            method="POST"
            class="grid grid-cols-1 lg:grid-cols-3 gap-8"
            x-data="{
                submitting: false,
                shippingLabel: '{{ $totals['shipping'] > 0 ? shop_money($totals['shipping']) : 'Free' }}',
                totalLabel: '{{ shop_money($totals['total']) }}',
                zoneLabel: '',
                quote(city) {
                    if (!city.trim()) { return; }
                    fetch('{{ route('checkout.shipping-quote') }}?city=' + encodeURIComponent(city), { headers: { 'Accept': 'application/json' } })
                        .then(response => response.ok ? response.json() : null)
                        .then(data => {
                            if (!data) { return; }
                            this.shippingLabel = data.shipping_label;
                            this.totalLabel = data.total_label;
                            this.zoneLabel = data.zone_label;
                        })
                        .catch(() => {});
                }
            }"
            @submit="if (submitting) { $event.preventDefault(); return; } submitting = true"
        >
            @csrf
            <div class="lg:col-span-2 space-y-6 animate-fade-in-up">
                <div class="bg-white rounded-2xl border border-oat-200 shadow-card p-6">
                    <h2 class="font-semibold text-charcoal-700 text-lg mb-6">Shipping Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Full Name *</label>
                            <input type="text" name="full_name" value="{{ old('full_name', auth()->user()?->name) }}" required class="input-field">
                            @error('full_name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Phone *</label>
                            <input type="text" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" required class="input-field">
                            @error('phone')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        @guest
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Email *</label>
                            <input type="email" name="email" value="{{ old('email') }}" required class="input-field" placeholder="For order confirmation">
                            @error('email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        @endguest
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">City *</label>
                            <input type="text" name="city" value="{{ old('city') }}" required class="input-field" placeholder="e.g. Karachi, Lahore" @input.debounce.400ms="quote($event.target.value)">
                            @error('city')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                            <p class="text-xs text-sage-700 mt-1" x-show="zoneLabel" x-cloak x-text="zoneLabel"></p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Address *</label>
                            <input type="text" name="address_line" value="{{ old('address_line') }}" required class="input-field" placeholder="House no, street, area">
                            @error('address_line')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">State</label>
                            <input type="text" name="state" value="{{ old('state') }}" class="input-field">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Postal Code</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code') }}" class="input-field">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Order Notes</label>
                            <textarea name="notes" rows="3" class="input-field" placeholder="Any special instructions...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-oat-200 shadow-card p-6">
                    <h2 class="font-semibold text-charcoal-700 text-lg mb-6">Payment Method</h2>
                    <div class="space-y-3">
                        <label class="flex items-center gap-4 p-4 rounded-xl border-2 border-oat-200 cursor-pointer hover:border-sage-300 transition-colors has-[:checked]:border-sage-600 has-[:checked]:bg-oat-100">
                            <input type="radio" name="payment_method" value="cod" {{ old('payment_method', 'cod') === 'cod' ? 'checked' : '' }} class="text-sage-600 focus:ring-sage-500">
                            <div>
                                <span class="font-semibold text-charcoal-700">Cash on Delivery</span>
                                <p class="text-sm text-charcoal-500">Pay when your order arrives</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-4 p-4 rounded-xl border-2 border-oat-200 cursor-pointer hover:border-sage-300 transition-colors has-[:checked]:border-sage-600 has-[:checked]:bg-oat-100">
                            <input type="radio" name="payment_method" value="stripe" {{ old('payment_method') === 'stripe' ? 'checked' : '' }} class="text-sage-600 focus:ring-sage-500">
                            <div>
                                <span class="font-semibold text-charcoal-700">Card Payment</span>
                                <p class="text-sm text-charcoal-500">Pay securely with credit/debit card</p>
                            </div>
                        </label>
                    </div>
                    @error('payment_method')<p class="text-red-500 text-sm mt-2">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="animate-fade-in-up animation-delay-200">
                <div class="bg-white rounded-2xl border border-oat-200 shadow-card p-6 sticky top-24">
                    <h2 class="font-semibold text-charcoal-700 text-lg mb-6">Your Order</h2>
                    <div class="space-y-3 mb-6 max-h-60 overflow-y-auto">
                        @foreach($items as $item)
                            <div class="flex justify-between text-sm">
                                <span class="text-charcoal-600">{{ $item->product->name }} x {{ $item->quantity }}</span>
                                <span class="font-medium">{{ shop_money($item->line_total) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="space-y-2 text-sm border-t border-slate-100 pt-4">
                        <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span>{{ shop_money($totals['subtotal']) }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Tax</span><span>{{ shop_money($totals['tax']) }}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Shipping</span><span x-text="shippingLabel">{{ $totals['shipping'] > 0 ? shop_money($totals['shipping']) : 'Free' }}</span></div>
                        <p class="text-xs text-slate-400">Shipping is calculated from your delivery city.</p>
                        <div class="flex justify-between text-lg font-bold pt-2">
                            <span>Total</span>
                            <span class="text-sage-700" x-text="totalLabel">{{ shop_money($totals['total']) }}</span>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary w-full mt-6" :disabled="submitting" :class="submitting && 'opacity-60 cursor-not-allowed'">
                        <span x-show="!submitting">Place Order</span>
                        <span x-show="submitting" x-cloak>Placing Order...</span>
                    </button>
                </div>
            </div>
        </form>
    </section>
@endsection
