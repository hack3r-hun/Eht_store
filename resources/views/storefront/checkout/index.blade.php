@extends('layouts.storefront')

@section('title', 'Checkout')

@section('content')
    <section class="hero-section py-10 md:py-14">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="fade-up">
                <span class="hero-eyebrow">Almost There</span>
                <h1 class="hero-title !text-3xl md:!text-4xl !mb-0">Checkout</h1>
            </x-reveal>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 pb-28 md:pb-10">
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
                city: @js(old('city', '')),
                gift: @js((bool) old('gift_wrap')),
                giftFeeLabel: @js($totals['gift_fee'] > 0 ? shop_money($totals['gift_fee']) : ''),
                quote() {
                    fetch('{{ route('checkout.shipping-quote') }}?city=' + encodeURIComponent(this.city.trim()) + '&gift_wrap=' + (this.gift ? 1 : 0), { headers: { 'Accept': 'application/json' } })
                        .then(response => response.ok ? response.json() : null)
                        .then(data => {
                            if (!data) { return; }
                            this.shippingLabel = data.shipping_label;
                            this.totalLabel = data.total_label;
                            this.zoneLabel = data.city_zone_label ?? data.zone_label;
                            this.giftFeeLabel = data.gift_fee_label;
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
                            <input type="text" name="city" value="{{ old('city') }}" required class="input-field" placeholder="e.g. Karachi, Lahore" @input.debounce.400ms="city = $event.target.value; quote()">
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
                        <div class="md:col-span-2">
                            <input type="hidden" name="marketing_opt_in" value="0">
                            <label class="flex items-start gap-3 cursor-pointer text-sm text-slate-600">
                                <input type="checkbox" name="marketing_opt_in" value="1" @checked(old('marketing_opt_in', '1') === '1' || old('marketing_opt_in') === true) class="mt-0.5 rounded text-sage-600 focus:ring-sage-500">
                                <span>Send me news and special offers by email / SMS. <span class="text-slate-400">You can say stop at any time.</span></span>
                            </label>
                        </div>
                    </div>
                </div>

                @if(module_enabled('gift_options'))
                <div class="bg-white rounded-2xl border border-oat-200 shadow-card p-6">
                    <h2 class="font-semibold text-charcoal-700 text-lg mb-4">🎁 Gift Options</h2>
                    <input type="hidden" name="gift_wrap" value="0">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="gift_wrap" value="1" x-model="gift" @change="quote()" class="rounded text-sage-600 focus:ring-sage-500">
                        <span class="font-medium text-charcoal-700">Gift wrap this order <span class="text-slate-500 font-normal">(+ {{ shop_money(module_value('gift_options', 'fee', config('shop.gift_wrap_fee'))) }})</span></span>
                    </label>
                    <div x-show="gift" x-cloak x-transition class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Gift message</label>
                            <textarea name="gift_message" rows="3" maxlength="500" class="input-field" placeholder="Written on a handmade card inside the parcel…">{{ old('gift_message') }}</textarea>
                        </div>
                        <input type="hidden" name="gift_hide_prices" value="0">
                        <label class="flex items-center gap-3 cursor-pointer text-sm text-slate-600">
                            <input type="checkbox" name="gift_hide_prices" value="1" @checked(old('gift_hide_prices')) class="rounded text-sage-600 focus:ring-sage-500">
                            Hide prices on the packing slip
                        </label>
                    </div>
                </div>
                @endif

                <div class="bg-white rounded-2xl border border-oat-200 shadow-card p-6">
                    <h2 class="font-semibold text-charcoal-700 text-lg mb-6">Payment Method</h2>
                    @php
                        $methods = [
                            'cod' => [module_value('payment_cod', 'label', 'Cash on Delivery'), module_value('payment_cod', 'description', 'Pay when your order arrives')],
                            'stripe' => [module_value('payment_stripe', 'label', 'Card Payment'), module_value('payment_stripe', 'description', 'Pay securely with credit/debit card')],
                        ];
                        $enabledMethods = array_values(array_filter(array_keys($methods), fn ($m) => in_array($m, \App\Services\CheckoutService::enabledPaymentMethods(), true)));
                        $selectedMethod = old('payment_method', $enabledMethods[0] ?? 'cod');
                    @endphp
                    <div class="space-y-3">
                        @forelse($enabledMethods as $method)
                            <label class="flex items-center gap-4 p-4 rounded-xl border-2 border-oat-200 cursor-pointer hover:border-sage-300 transition-colors has-[:checked]:border-sage-600 has-[:checked]:bg-oat-100">
                                <input type="radio" name="payment_method" value="{{ $method }}" {{ $selectedMethod === $method ? 'checked' : '' }} class="text-sage-600 focus:ring-sage-500">
                                <div>
                                    <span class="font-semibold text-charcoal-700">{{ $methods[$method][0] }}</span>
                                    <p class="text-sm text-slate-500">{{ $methods[$method][1] }}</p>
                                </div>
                            </label>
                        @empty
                            <p class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">Online ordering is unavailable right now. Please contact us to place your order.</p>
                        @endforelse
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
                                <span class="text-slate-600">{{ $item->product->name }} x {{ $item->quantity }}</span>
                                <span class="font-medium">{{ shop_money($item->line_total) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="space-y-2 text-sm border-t border-slate-100 pt-4">
                        <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span>{{ shop_money($totals['subtotal']) }}</span></div>
                        @if($totals['discount'] > 0)
                            <div class="flex justify-between text-sage-700"><span>Coupon {{ $totals['couponCode'] }}</span><span>− {{ shop_money($totals['discount']) }}</span></div>
                        @endif
                        <div class="flex justify-between"><span class="text-slate-500">Tax</span><span>{{ shop_money($totals['tax']) }}</span></div>
                        <div class="flex justify-between" x-show="giftFeeLabel" x-cloak><span class="text-slate-500">Gift wrap</span><span x-text="giftFeeLabel"></span></div>
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

            {{-- Mobile-only sticky total bar, bound to the same live shipping-quote state as the summary above --}}
            <div class="sticky-order-bar md:hidden">
                <div>
                    <p class="text-xs text-slate-500">Total</p>
                    <p class="text-lg font-bold text-sage-700" x-text="totalLabel">{{ shop_money($totals['total']) }}</p>
                </div>
                <button type="submit" class="btn-primary shrink-0" :disabled="submitting" :class="submitting && 'opacity-60 cursor-not-allowed'">
                    <span x-show="!submitting">Place Order</span>
                    <span x-show="submitting" x-cloak>Placing...</span>
                </button>
            </div>
        </form>
    </section>
@endsection
