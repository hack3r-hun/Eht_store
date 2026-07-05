@extends('layouts.storefront')

@section('title', 'Order '.$order->order_number)

@section('back_href', route('account.orders.index'))
@section('back_label', 'Back to orders')

@section('content')
    <section class="max-w-3xl mx-auto px-4 py-12">
        <div class="flex flex-wrap justify-between items-start gap-4 mb-8">
            <div>
                <h1 class="section-title">{{ $order->order_number }}</h1>
                <p class="text-slate-500 mt-1">{{ $order->created_at->format('F d, Y') }}</p>
            </div>
            <a href="{{ route('account.orders.invoice', $order) }}" class="btn-outline !py-2 !px-4 text-sm">Download Invoice</a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6 mb-6">
            <div class="grid grid-cols-2 gap-4 text-sm mb-6">
                <div><span class="text-slate-500">Status</span><p class="font-medium">{{ $order->status->label() }}</p></div>
                <div><span class="text-slate-500">Payment</span><p class="font-medium">{{ $order->payment_method->label() }}</p></div>
            </div>
            <div class="space-y-3 border-t border-slate-100 pt-4">
                @foreach($order->items as $item)
                    <div class="flex justify-between text-sm">
                        <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                        <span class="font-medium">{{ shop_money($item->line_total) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="border-t border-slate-100 mt-4 pt-4 flex justify-between font-bold text-lg">
                <span>Total</span>
                <span class="text-brand-700">{{ shop_money($order->total) }}</span>
            </div>
        </div>
    </section>
@endsection
