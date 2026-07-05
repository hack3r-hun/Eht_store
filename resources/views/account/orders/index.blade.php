@extends('layouts.storefront')

@section('title', 'Order History')

@section('content')
    <section class="max-w-4xl mx-auto px-4 py-12">
        <h1 class="section-title mb-8">My Orders</h1>

        @if($orders->isEmpty())
            <div class="text-center py-16 bg-white rounded-2xl border border-slate-100">
                <p class="text-slate-500 mb-4">You haven't placed any orders yet.</p>
                <a href="{{ route('products.index') }}" class="btn-primary">Start Shopping</a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($orders as $order)
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6 card-hover flex flex-wrap justify-between items-center gap-4">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $order->order_number }}</p>
                            <p class="text-sm text-slate-500">{{ $order->created_at->format('M d, Y') }} · {{ $order->items->count() }} item(s)</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-brand-700">{{ shop_money($order->total) }}</p>
                            <p class="text-sm capitalize text-slate-500">{{ $order->status->label() }}</p>
                        </div>
                        <a href="{{ route('account.orders.show', $order) }}" class="btn-outline !py-2 !px-4 text-sm">View Details</a>
                    </div>
                @endforeach
            </div>
            <div class="mt-8">{{ $orders->links() }}</div>
        @endif
    </section>
@endsection
