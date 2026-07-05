@extends('layouts.admin')

@section('page-title', 'Orders')

@section('content')
    <x-reveal type="fade-up">
        <x-admin-page-header :count="$orders->total()" count-label="orders" subtitle="Filter by customer, status, payment, date, and amount" />
    </x-reveal>

    <x-reveal type="fade-up" delay="40">
        <form method="GET" class="admin-card mb-6 grid grid-cols-1 md:grid-cols-4 xl:grid-cols-8 gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Order, customer, email, phone" class="input-field md:col-span-2">
            <select name="status" class="input-field">
                <option value="">All statuses</option>
                @foreach($orderStatuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <select name="payment_status" class="input-field">
                <option value="">Payment status</option>
                @foreach($paymentStatuses as $status)
                    <option value="{{ $status->value }}" @selected(request('payment_status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <select name="payment_method" class="input-field">
                <option value="">Payment method</option>
                @foreach($paymentMethods as $method)
                    <option value="{{ $method->value }}" @selected(request('payment_method') === $method->value)>{{ $method->label() }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="input-field">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="input-field">
            <select name="per_page" class="input-field" onchange="this.form.submit()">
                @foreach([10, 20, 50, 100] as $size)
                    <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / page</option>
                @endforeach
            </select>
            <input type="number" step="0.01" name="min_total" value="{{ request('min_total') }}" placeholder="Min total" class="input-field">
            <input type="number" step="0.01" name="max_total" value="{{ request('max_total') }}" placeholder="Max total" class="input-field">
            <div class="md:col-span-2 xl:col-span-6 flex flex-wrap gap-2">
                <button type="submit" class="btn-primary !py-2.5 !px-5 text-sm">Filter</button>
                <a href="{{ route('admin.orders.index') }}" class="btn-outline !py-2.5 !px-5 text-sm">Reset</a>
                <a href="{{ route('admin.orders.index', ['status' => 'awaiting_cod']) }}" class="admin-action-link">Awaiting COD</a>
                <a href="{{ route('admin.orders.index', ['payment_status' => 'paid']) }}" class="admin-action-link">Paid</a>
                <a href="{{ route('admin.orders.index', ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]) }}" class="admin-action-link">Today</a>
            </div>
        </form>
    </x-reveal>

    <x-reveal type="fade-up" delay="80">
        <div class="admin-card">
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
                                <td class="font-semibold text-slate-900 group-hover:text-brand-700 transition-colors">{{ $order->order_number }}</td>
                                <td>
                                    <span class="block font-medium text-slate-800">{{ $order->user?->name ?? ($order->shipping_address['full_name'] ?? 'Guest') }}</span>
                                    <span class="block text-xs text-slate-400">{{ $order->user?->email ?? ($order->shipping_address['email'] ?? $order->shipping_address['phone'] ?? '') }}</span>
                                </td>
                                <td class="font-bold text-slate-900">{{ shop_money($order->total) }}</td>
                                <td>
                                    <span class="admin-badge bg-slate-100 text-slate-700">{{ $order->payment_method->label() }}</span>
                                    <span class="admin-badge {{ $order->payment_status->value === 'paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $order->payment_status->label() }}</span>
                                </td>
                                <td>
                                    <span class="admin-badge capitalize
                                        @if($order->status->value === 'delivered') bg-green-100 text-green-700
                                        @elseif(in_array($order->status->value, ['pending', 'awaiting_cod'])) bg-brand-100 text-brand-700
                                        @elseif($order->status->value === 'cancelled') bg-red-100 text-red-700
                                        @else bg-blue-100 text-blue-700 @endif">
                                        {{ $order->status->label() }}
                                    </span>
                                </td>
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

    <div class="mt-6">{{ $orders->links() }}</div>
@endsection
