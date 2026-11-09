@extends('layouts.storefront')

@section('title', 'Shopping Cart')

@section('content')
    <section class="hero-section py-10 md:py-14">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="fade-up">
                <span class="hero-eyebrow">Your Cart</span>
                <h1 class="hero-title !text-3xl md:!text-4xl !mb-0">Shopping Cart</h1>
            </x-reveal>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 {{ $items->isEmpty() ? '' : 'pb-28 md:pb-10' }}">
        @if($items->isEmpty())
            <div class="text-center py-20 bg-white rounded-2xl border border-slate-100 shadow-card animate-fade-in">
                <x-icon name="cart" class="w-16 h-16 mx-auto mb-4 text-slate-300" />
                <h2 class="text-xl font-semibold text-charcoal-700 mb-2">Your cart is empty</h2>
                <p class="text-slate-500 mb-8">Add some handmade pieces to get started.</p>
                <a href="{{ route('products.index') }}" class="btn-primary">Browse Products</a>
            </div>
        @else
            <div
                x-data="{
                    totals: @js($presented),
                    lineTotals: {
                        @foreach($items as $item)
                            {{ $item->id }}: @js(shop_money($item->line_total)),
                        @endforeach
                    },
                    removedItems: {},
                }"
                @cart-updated.window="
                    if ($event.detail.totals) { totals = $event.detail.totals; }
                    if ($event.detail.item) { lineTotals[$event.detail.item.id] = $event.detail.item.line_total; }
                    if ($event.detail.removed && $event.detail.item_id) { removedItems[$event.detail.item_id] = true; }
                "
            >
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-4 animate-fade-in-up">
                        @if($totals['free_shipping_threshold'] > 0)
                            <div class="rounded-2xl border border-oat-200 bg-white p-4 shadow-card">
                                <p class="text-sm font-semibold text-charcoal-700 flex items-center gap-2">
                                    <x-icon name="truck" class="w-5 h-5 text-sage-600" />
                                    <span x-text="totals.shipMessage"></span>
                                </p>
                                <div class="mt-3 h-2.5 rounded-full bg-oat-200 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="totals.shipProgress">
                                    <div class="h-full rounded-full bg-gradient-to-r from-terracotta-400 to-sage-500 transition-all duration-700 ease-out" :style="'width: ' + totals.shipProgress + '%'"></div>
                                </div>
                            </div>
                        @endif
                        @foreach($items as $item)
                            <div
                                x-show="!removedItems[{{ $item->id }}]"
                                x-transition:leave="transition ease-in duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="flex gap-4 p-4 bg-white rounded-2xl border border-oat-200 shadow-card card-hover"
                            >
                                <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" class="w-24 h-24 rounded-xl object-cover shrink-0">
                                <div class="flex-1 min-w-0">
                                    <a href="{{ route('products.show', $item->product->slug) }}" class="font-semibold text-charcoal-700 hover:text-sage-700 transition-colors">{{ $item->product->name }}</a>
                                    @if($item->variant)
                                        <span class="ml-2 inline-flex items-center rounded-lg bg-oat-100 px-2 py-0.5 text-xs font-semibold text-sage-800">Size {{ $item->variant->size }}</span>
                                    @endif
                                    <p class="text-sm text-slate-500 mt-1">{{ shop_money($item->unit_price) }} each</p>
                                    <div class="flex items-center justify-between mt-3">
                                        <form action="{{ route('cart.update', $item) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <x-quantity-stepper
                                                name="quantity"
                                                :value="$item->quantity"
                                                :min="1"
                                                :max="$item->variant?->stock_quantity ?? $item->product->stock_quantity"
                                                :ajax="true"
                                            />
                                        </form>
                                        <span class="font-bold text-charcoal-700" x-text="lineTotals[{{ $item->id }}]"></span>
                                    </div>
                                </div>
                                <x-confirm-delete
                                    :action="route('cart.destroy', $item)"
                                    title="Remove from cart?"
                                    :item="$item->product->name"
                                    message="This item will be removed from your shopping cart."
                                    confirm-label="Remove"
                                    class="inline-block"
                                >
                                    <span class="sr-only">Remove</span>
                                </x-confirm-delete>
                            </div>
                        @endforeach
                    </div>

                    <div class="animate-fade-in-up animation-delay-200">
                        <div class="bg-white rounded-2xl border border-oat-200 shadow-card p-6 sticky top-24">
                            <h2 class="font-semibold text-charcoal-700 text-lg mb-6">Order Summary</h2>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="font-medium" x-text="totals.subtotal"></span></div>
                                <div class="flex justify-between text-sage-700" x-show="totals.hasDiscount" x-cloak>
                                    <span>Coupon{{ $couponCode ? ' '.$couponCode : '' }}</span><span class="font-medium" x-text="totals.discount"></span>
                                </div>
                                @if($totals['tax'] > 0)
                                    <div class="flex justify-between"><span class="text-slate-500">Tax</span><span class="font-medium" x-text="totals.tax"></span></div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Shipping (estimated)</span>
                                    <span class="font-medium" x-text="totals.shipping"></span>
                                </div>
                                <p class="text-xs text-slate-400">Final shipping is calculated from your delivery city at checkout.</p>
                                <div class="border-t border-slate-100 pt-3 flex justify-between text-lg font-bold">
                                    <span>Total</span>
                                    <span class="text-sage-700" x-text="totals.total"></span>
                                </div>
                            </div>
                            @if(module_enabled('coupons'))
                            <div class="mt-6 border-t border-slate-100 pt-5">
                                @if($couponCode)
                                    <form action="{{ route('cart.coupon.destroy') }}" method="POST" class="flex items-center justify-between rounded-xl bg-sage-50 px-4 py-3 text-sm" data-no-loading>
                                        @csrf @method('DELETE')
                                        <span class="font-semibold text-sage-800">🎟 {{ $couponCode }} applied</span>
                                        <button type="submit" class="text-slate-500 hover:text-red-600 font-medium">Remove</button>
                                    </form>
                                @else
                                    <form action="{{ route('cart.coupon.store') }}" method="POST" class="flex gap-2">
                                        @csrf
                                        <input type="text" name="code" placeholder="Coupon code" maxlength="50" class="input-field !py-2.5 uppercase" aria-label="Coupon code" required>
                                        <button type="submit" class="btn-accent !py-2.5 !px-4 shrink-0">Apply</button>
                                    </form>
                                @endif
                            </div>
                            @endif
                            <a href="{{ route('checkout.index') }}" class="btn-primary w-full mt-6 text-center">Proceed to Checkout</a>
                            <a href="{{ route('products.index') }}" class="block text-center text-sm text-sage-700 font-medium mt-4 hover:text-sage-800">Continue Shopping</a>
                        </div>
                    </div>
                </div>

                {{-- Mobile-only sticky total bar so checkout is always reachable without scrolling --}}
                <div class="sticky-order-bar md:hidden">
                    <div>
                        <p class="text-xs text-slate-500">Total</p>
                        <p class="text-lg font-bold text-sage-700" x-text="totals.total"></p>
                    </div>
                    <a href="{{ route('checkout.index') }}" class="btn-primary shrink-0">Checkout</a>
                </div>
            </div>
        @endif
    </section>
@endsection
