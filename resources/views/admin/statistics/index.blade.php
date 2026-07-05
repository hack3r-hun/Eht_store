@extends('layouts.admin')

@section('page-title', 'Statistics')

@section('content')
    <x-reveal type="fade-up">
        <x-admin-page-header subtitle="Performance overview for {{ $start->format('M d, Y') }} to {{ $end->format('M d, Y') }}" />
    </x-reveal>

    <x-reveal type="fade-up" delay="40">
        <form method="GET" class="admin-card mb-6 grid grid-cols-1 md:grid-cols-6 gap-3">
            <select name="period" class="input-field" onchange="this.form.submit()">
                <option value="day" @selected($period === 'day')>Today</option>
                <option value="month" @selected($period === 'month')>Month</option>
                <option value="year" @selected($period === 'year')>Year</option>
                <option value="custom" @selected($period === 'custom')>Custom</option>
            </select>
            <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}" class="input-field">
            <input type="number" name="year" value="{{ request('year', now()->year) }}" min="2000" max="{{ now()->year + 1 }}" class="input-field">
            <input type="date" name="date_from" value="{{ request('date_from', $start->toDateString()) }}" class="input-field">
            <input type="date" name="date_to" value="{{ request('date_to', $end->toDateString()) }}" class="input-field">
            <div class="flex gap-2">
                <button type="submit" class="btn-primary !py-2.5 !px-5 text-sm flex-1">Apply</button>
                <a href="{{ route('admin.statistics.index') }}" class="btn-outline !py-2.5 !px-5 text-sm">Reset</a>
            </div>
        </form>
    </x-reveal>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        @foreach([
            ['label' => 'Paid Revenue', 'value' => shop_money($summary['paid_revenue']), 'icon' => 'currency', 'bg' => 'from-brand-500 to-brand-700'],
            ['label' => 'Orders', 'value' => $summary['orders'], 'icon' => 'cart', 'bg' => 'from-blue-500 to-blue-700'],
            ['label' => 'Average Order', 'value' => shop_money($summary['average_order'] ?? 0), 'icon' => 'chart', 'bg' => 'from-emerald-500 to-emerald-700'],
            ['label' => 'New Customers', 'value' => $summary['new_customers'], 'icon' => 'users', 'bg' => 'from-violet-500 to-violet-700'],
        ] as $i => $card)
            <x-reveal type="fade-up" :delay="$i * 60">
                <div class="admin-stat-card bg-gradient-to-br {{ $card['bg'] }} shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-white/80 text-sm font-medium">{{ $card['label'] }}</span>
                        <x-icon :name="$card['icon']" class="w-6 h-6 text-white/90" />
                    </div>
                    <p class="text-3xl font-bold">{{ $card['value'] }}</p>
                </div>
            </x-reveal>
        @endforeach
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach([
            ['label' => 'Active Products', 'value' => $summary['active_products'].' / '.$summary['products'], 'icon' => 'cube', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
            ['label' => 'Low Stock', 'value' => $summary['low_stock'], 'icon' => 'warning', 'bg' => 'bg-red-100', 'text' => 'text-red-700'],
            ['label' => 'Messages', 'value' => $summary['messages'], 'icon' => 'mail', 'bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
            ['label' => 'Range', 'value' => $period, 'icon' => 'calendar', 'bg' => 'bg-slate-100', 'text' => 'text-slate-700'],
        ] as $item)
            <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-5">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl {{ $item['bg'] }} {{ $item['text'] }} flex items-center justify-center">
                        <x-icon :name="$item['icon']" class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">{{ $item['label'] }}</p>
                        <p class="text-xl font-bold text-slate-900 capitalize">{{ $item['value'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
        <div class="admin-card">
            <h2 class="font-bold text-slate-900 mb-4">Order Status Breakdown</h2>
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead><tr><th>Status</th><th>Orders</th><th>Revenue</th></tr></thead>
                    <tbody>
                        @forelse($statusBreakdown as $row)
                            <tr>
                                <td class="capitalize">{{ str_replace('_', ' ', $row->status->value ?? $row->status) }}</td>
                                <td>{{ $row->orders_count }}</td>
                                <td>{{ shop_money($row->revenue ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-10 text-slate-500">No order data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-card">
            <h2 class="font-bold text-slate-900 mb-4">Payment Breakdown</h2>
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead><tr><th>Method</th><th>Status</th><th>Orders</th><th>Revenue</th></tr></thead>
                    <tbody>
                        @forelse($paymentBreakdown as $row)
                            <tr>
                                <td class="uppercase">{{ $row->payment_method->value ?? $row->payment_method }}</td>
                                <td class="capitalize">{{ $row->payment_status->value ?? $row->payment_status }}</td>
                                <td>{{ $row->orders_count }}</td>
                                <td>{{ shop_money($row->revenue ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-10 text-slate-500">No payment data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="admin-card">
            <h2 class="font-bold text-slate-900 mb-4">Top Products</h2>
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead><tr><th>Product</th><th>Units</th><th>Revenue</th></tr></thead>
                    <tbody>
                        @forelse($topProducts as $product)
                            <tr>
                                <td>
                                    <span class="block font-semibold text-slate-900">{{ $product->product_name }}</span>
                                    <span class="block text-xs text-slate-400">{{ $product->product_sku }}</span>
                                </td>
                                <td>{{ $product->units_sold }}</td>
                                <td>{{ shop_money($product->revenue ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-10 text-slate-500">No product sales yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-card">
            <h2 class="font-bold text-slate-900 mb-4">{{ $period === 'year' ? 'Monthly' : 'Daily' }} Trend</h2>
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead><tr><th>Period</th><th>Orders</th><th>Revenue</th></tr></thead>
                    <tbody>
                        @forelse($trend as $row)
                            <tr>
                                <td>{{ $row->bucket }}</td>
                                <td>{{ $row->orders_count }}</td>
                                <td>{{ shop_money($row->revenue ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-10 text-slate-500">No trend data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
