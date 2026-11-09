@extends('layouts.storefront')

@section('title', 'Track Your Order')

@section('content')
    <x-cms.sections :page="$page" part="top" />

    <section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <form action="{{ route('track-order.lookup') }}" method="POST" class="store-card p-6 md:p-8 grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Order number</label>
                <input type="text" name="order_number" value="{{ old('order_number', $order?->order_number) }}" required placeholder="EKY-XXXXXXXX" class="input-field uppercase">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Email or phone used at checkout</label>
                <input type="text" name="contact" value="{{ old('contact') }}" required class="input-field">
            </div>
            @error('order_number')<p class="md:col-span-2 text-red-500 text-sm">{{ $message }}</p>@enderror
            <div class="md:col-span-2"><button type="submit" class="btn-primary">Track Order</button></div>
        </form>

        @if($order)
            @php
                $steps = [
                    ['key' => 'placed', 'label' => 'Order placed'],
                    ['key' => 'processing', 'label' => 'Being handmade'],
                    ['key' => 'shipped', 'label' => 'Shipped'],
                    ['key' => 'delivered', 'label' => 'Delivered'],
                ];
                $current = match ($order->status->value) {
                    'processing', 'paid' => 1,
                    'shipped' => 2,
                    'delivered' => 3,
                    default => 0,
                };
                $cancelled = $order->status->value === 'cancelled';
            @endphp
            <div class="store-card p-6 md:p-8 mt-6 animate-fade-in-up">
                <div class="flex flex-wrap justify-between gap-2 mb-6">
                    <div>
                        <p class="text-sm text-slate-500">Order</p>
                        <p class="text-xl font-bold text-charcoal-700">{{ $order->order_number }}</p>
                    </div>
                    <x-order-status-badge :status="$order->status" class="self-start" />
                </div>

                @if($cancelled)
                    <p class="p-4 rounded-xl bg-red-50 text-red-700 text-sm">This order was cancelled. Contact us if you have questions.</p>
                @else
                    <ol class="relative grid grid-cols-4 gap-2 text-center">
                        @foreach($steps as $i => $step)
                            <li class="relative">
                                @unless($loop->last)
                                    <span class="absolute top-4 left-1/2 w-full h-1 rounded {{ $i < $current ? 'bg-sage-500' : 'bg-oat-200' }}"></span>
                                @endunless
                                <span class="relative z-10 mx-auto flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold {{ $i <= $current ? 'bg-sage-600 text-white' : 'bg-oat-200 text-slate-500' }} {{ $i === $current ? 'ring-4 ring-sage-200 animate-pulse-soft' : '' }}">
                                    {{ $i < $current ? '✓' : $i + 1 }}
                                </span>
                                <span class="mt-2 block text-xs font-medium {{ $i <= $current ? 'text-charcoal-700' : 'text-slate-400' }}">{{ $step['label'] }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif

                <div class="mt-8 border-t border-slate-100 pt-4 space-y-2 text-sm">
                    @foreach($order->items as $item)
                        <div class="flex justify-between"><span>{{ $item->product_name }}@if($item->size) ({{ $item->size }})@endif × {{ $item->quantity }}</span></div>
                    @endforeach
                    <p class="text-slate-500 pt-2">Placed {{ $order->created_at->format('d M Y') }} · Delivering to {{ $order->shipping_address['city'] ?? '' }}</p>
                </div>
                @if(module_enabled('whatsapp'))<a href="{{ whatsapp_url('Hi! I have a question about order '.$order->order_number) }}" target="_blank" rel="noopener" class="btn-outline mt-6">Ask about this order on WhatsApp</a>@endif
            </div>
        @endif
    </section>
    <x-cms.sections :page="$page" part="bottom" />
@endsection
