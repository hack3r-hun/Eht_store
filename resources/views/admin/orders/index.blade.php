@extends('layouts.admin')

@section('page-title', 'Orders')

@section('content')
    <x-reveal type="fade-up">
        <x-admin-page-header :count="$orders->total()" count-label="orders" subtitle="Search orders and filter by status or payment" />
    </x-reveal>

    <x-reveal type="fade-up" delay="40">
        <form method="GET" class="admin-filter-bar">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Order, customer, email, phone" class="input-field admin-filter-search">
            <select name="status" class="input-field admin-filter-control">
                <option value="">All statuses</option>
                @foreach($orderStatuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <select name="payment_status" class="input-field admin-filter-control">
                <option value="">Payment status</option>
                @foreach($paymentStatuses as $status)
                    <option value="{{ $status->value }}" @selected(request('payment_status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <select name="per_page" class="input-field admin-filter-control" onchange="this.form.submit()">
                @foreach([10, 20, 50, 100] as $size)
                    <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / page</option>
                @endforeach
            </select>
            <div class="admin-filter-actions">
                <button type="submit" class="btn-primary !py-2.5 !px-5 text-sm">Filter</button>
                <a href="{{ route('admin.orders.index') }}" class="btn-outline !py-2.5 !px-5 text-sm">Reset</a>
            </div>
        </form>
    </x-reveal>

    <x-reveal type="fade-up" delay="80">
        <div class="admin-card hidden md:block">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr class="group">
                                <td class="font-semibold text-slate-900 group-hover:text-sage-700 transition-colors">{{ $order->order_number }}</td>
                                <td>
                                    <span class="block font-medium text-slate-800">{{ $order->user?->name ?? ($order->shipping_address['full_name'] ?? 'Guest') }}</span>
                                    <span class="block text-xs text-slate-400">{{ $order->user?->email ?? ($order->shipping_address['email'] ?? $order->shipping_address['phone'] ?? '') }}</span>
                                </td>
                                <td class="font-bold text-slate-900">{{ shop_money($order->total) }}</td>
                                <td>
                                    <span class="admin-badge bg-slate-100 text-slate-700">{{ $order->payment_method->label() }}</span>
                                    <span class="admin-badge {{ $order->payment_status->value === 'paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $order->payment_status->label() }}</span>
                                </td>
                                <td><x-order-status-badge :status="$order->status" /></td>
                                <td class="text-slate-500">{{ $order->created_at->format('M d, Y') }}</td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="admin-action-link">View</a>
                                        <x-confirm-delete
                                            :action="route('admin.orders.destroy', $order)"
                                            title="Delete order?"
                                            :item="$order->order_number"
                                            message="The order and its items will be permanently deleted. Stock for its products will be returned unless the order was already cancelled."
                                        >
                                            Delete
                                        </x-confirm-delete>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-16 text-slate-500">
                                    <x-icon name="cart" class="w-14 h-14 mx-auto mb-3 text-slate-300" />
                                    No orders found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-reveal>

    {{-- Mobile card list — same $orders collection, stacked layout instead of a horizontal-scroll table --}}
    <div class="md:hidden space-y-3">
        @forelse($orders as $order)
            <div class="admin-card admin-card-pad">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900 truncate">{{ $order->order_number }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $order->created_at->format('M d, Y') }}</p>
                    </div>
                    <x-order-status-badge :status="$order->status" class="shrink-0" />
                </div>
                <div class="text-sm mb-3">
                    <span class="block font-medium text-slate-800">{{ $order->user?->name ?? ($order->shipping_address['full_name'] ?? 'Guest') }}</span>
                    <span class="block text-xs text-slate-400">{{ $order->user?->email ?? ($order->shipping_address['email'] ?? $order->shipping_address['phone'] ?? '') }}</span>
                </div>
                <div class="flex items-center gap-2 mb-4">
                    <span class="admin-badge bg-slate-100 text-slate-700">{{ $order->payment_method->label() }}</span>
                    <span class="admin-badge {{ $order->payment_status->value === 'paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $order->payment_status->label() }}</span>
                    <span class="ml-auto font-bold text-slate-900">{{ shop_money($order->total) }}</span>
                </div>
                <div class="flex items-center justify-end gap-4 pt-3 border-t border-slate-100">
                    <a href="{{ route('admin.orders.show', $order) }}" class="admin-action-link">View</a>
                    <x-confirm-delete
                        :action="route('admin.orders.destroy', $order)"
                        title="Delete order?"
                        :item="$order->order_number"
                        message="The order and its items will be permanently deleted. Stock for its products will be returned unless the order was already cancelled."
                    >
                        Delete
                    </x-confirm-delete>
                </div>
            </div>
        @empty
            <div class="admin-card admin-card-pad text-center py-16 text-slate-500">
                <x-icon name="cart" class="w-14 h-14 mx-auto mb-3 text-slate-300" />
                No orders found.
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
@endsection
