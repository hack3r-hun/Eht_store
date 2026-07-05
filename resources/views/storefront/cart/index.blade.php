@extends('layouts.storefront')

@section('title', 'Shopping Cart')

@section('content')
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="section-title mb-10 animate-fade-in-up">Shopping Cart</h1>

        @if($items->isEmpty())
            <div class="text-center py-20 bg-white rounded-2xl border border-slate-100 shadow-card animate-fade-in">
                <x-icon name="cart" class="w-16 h-16 mx-auto mb-4 text-slate-300" />
                <h2 class="text-xl font-semibold text-charcoal-700 mb-2">Your cart is empty</h2>
                <p class="text-charcoal-500 mb-8">Add some handmade pieces to get started.</p>
                <a href="{{ route('products.index') }}" class="btn-primary">Browse Products</a>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-4 animate-fade-in-up">
                    @foreach($items as $item)
                        <div class="flex gap-4 p-4 bg-white rounded-2xl border border-oat-200 shadow-card card-hover">
                            <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" class="w-24 h-24 rounded-xl object-cover shrink-0">
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('products.show', $item->product->slug) }}" class="font-semibold text-charcoal-700 hover:text-sage-700 transition-colors">{{ $item->product->name }}</a>
                                <p class="text-sm text-charcoal-500 mt-1">{{ shop_money($item->product->effective_price) }} each</p>
                                <div class="flex items-center justify-between mt-3">
                                    <form action="{{ route('cart.update', $item) }}" method="POST" class="flex items-center gap-2">
                                        @csrf @method('PATCH')
                                        <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->product->stock_quantity }}" class="w-16 px-2 py-1 rounded-lg border border-slate-200 text-sm" onchange="this.form.submit()">
                                    </form>
                                    <span class="font-bold text-charcoal-700">{{ shop_money($item->line_total) }}</span>
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
                            <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="font-medium">{{ shop_money($totals['subtotal']) }}</span></div>
                            @if($totals['tax'] > 0)
                                <div class="flex justify-between"><span class="text-slate-500">Tax</span><span class="font-medium">{{ shop_money($totals['tax']) }}</span></div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-slate-500">Shipping (estimated)</span>
                                <span class="font-medium">{{ $totals['shipping'] > 0 ? shop_money($totals['shipping']) : 'Free' }}</span>
                            </div>
                            <p class="text-xs text-slate-400">Final shipping is calculated from your delivery city at checkout.</p>
                            <div class="border-t border-slate-100 pt-3 flex justify-between text-lg font-bold">
                                <span>Total</span>
                                <span class="text-sage-700">{{ shop_money($totals['total']) }}</span>
                            </div>
                        </div>
                        <a href="{{ route('checkout.index') }}" class="btn-primary w-full mt-6 text-center">Proceed to Checkout</a>
                        <a href="{{ route('products.index') }}" class="block text-center text-sm text-sage-700 font-medium mt-4 hover:text-sage-800">Continue Shopping</a>
                    </div>
                </div>
            </div>
        @endif
    </section>
@endsection
