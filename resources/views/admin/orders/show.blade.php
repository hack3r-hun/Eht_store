@extends('layouts.admin')

@section('page-title', 'Order '.$order->order_number)

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6">
                <h2 class="font-semibold mb-4">Items</h2>
                <div class="space-y-3">
                    @foreach($order->items as $item)
                        <div class="flex justify-between text-sm border-b border-slate-50 pb-2">
                            <span>{{ $item->product_name }} x {{ $item->quantity }}</span>
                            <span class="font-medium">{{ shop_money($item->line_total) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 pt-4 border-t space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span>{{ shop_money($order->subtotal) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Tax</span><span>{{ shop_money($order->tax) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Shipping</span><span>{{ $order->shipping > 0 ? shop_money($order->shipping) : 'Free' }}</span></div>
                    <div class="flex justify-between font-bold text-base pt-2 border-t border-slate-50">
                        <span>Total</span>
                        <span class="text-brand-700">{{ shop_money($order->total) }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6">
                <h2 class="font-semibold mb-4">Shipping Address</h2>
                <p class="text-sm text-slate-600">
                    {{ $order->shipping_address['full_name'] ?? '' }}<br>
                    {{ $order->shipping_address['phone'] ?? '' }}<br>
                    {{ $order->shipping_address['address_line'] ?? '' }}<br>
                    {{ $order->shipping_address['city'] ?? '' }}
                </p>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6">
                <h2 class="font-semibold mb-4">Update Status</h2>
                <form
                    action="{{ route('admin.orders.status', $order) }}"
                    method="POST"
                    class="space-y-3"
                    onsubmit="return confirm('Update the order status? Marking a COD order as Delivered also marks it as Paid, and cancelling restores stock.')"
                >
                    @csrf @method('PATCH')
                    <select name="status" class="input-field">
                        @foreach(\App\Enums\OrderStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected($order->status === $status)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-primary w-full">Update</button>
                </form>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6">
                <h2 class="font-semibold mb-4">Shipping Charge</h2>
                @if($order->payment_status === \App\Enums\PaymentStatus::Paid)
                    <p class="text-sm text-slate-500">This order is already paid — the shipping charge can no longer be changed.</p>
                @else
                    <form action="{{ route('admin.orders.shipping', $order) }}" method="POST" class="space-y-3">
                        @csrf @method('PATCH')
                        <input type="number" step="0.01" min="0" name="shipping" value="{{ old('shipping', $order->shipping) }}" class="input-field" required>
                        <p class="text-xs text-slate-500">Overrides the auto-calculated charge; the order total is recalculated.</p>
                        <button type="submit" class="btn-outline w-full">Update Shipping</button>
                    </form>
                @endif
            </div>
            <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="btn-outline w-full text-center block">Print Invoice</a>

            <div class="bg-white rounded-2xl border border-red-100 shadow-card p-6">
                <h2 class="font-semibold text-red-700 mb-2">Danger Zone</h2>
                <p class="text-sm text-slate-500 mb-4">Deleting removes the order permanently and returns its stock (unless already cancelled).</p>
                <x-confirm-delete
                    :action="route('admin.orders.destroy', $order)"
                    title="Delete order?"
                    :item="$order->order_number"
                    message="The order and its items will be permanently deleted. Stock for its products will be returned unless the order was already cancelled."
                >
                    Delete Order
                </x-confirm-delete>
            </div>
        </div>
    </div>
@endsection
