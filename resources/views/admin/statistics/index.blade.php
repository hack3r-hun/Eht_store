@extends('layouts.admin')

@section('page-title', 'Statistics')

@php
    $statusChartData = $statusBreakdown->map(function ($row) {
        $statusValue = is_object($row->status) ? $row->status->value : $row->status;
        $enum = \App\Enums\OrderStatus::tryFrom($statusValue);

        return [
            'label' => $enum?->label() ?? str_replace('_', ' ', (string) $statusValue),
            'count' => (int) $row->orders_count,
            'color' => $enum?->color() ?? 'slate',
        ];
    })->values();

    $paymentChartData = $paymentBreakdown->map(function ($row) {
        $method = is_object($row->payment_method) ? $row->payment_method->value : $row->payment_method;
        $status = is_object($row->payment_status) ? $row->payment_status->value : $row->payment_status;

        return [
            'label' => strtoupper((string) $method).' · '.ucfirst((string) $status),
            'count' => (int) $row->orders_count,
        ];
    })->values();

    $topProductsChartData = $topProducts->map(fn ($row) => [
        'label' => \Illuminate\Support\Str::limit($row->product_name, 20),
        'count' => (int) $row->units_sold,
    ])->values();

    $trendChartData = $trend->map(fn ($row) => [
        'label' => (string) $row->bucket,
        'revenue' => (float) ($row->revenue ?? 0),
    ])->values();

    $chartPayload = [
        'statusBreakdown' => $statusChartData,
        'paymentBreakdown' => $paymentChartData,
        'topProducts' => $topProductsChartData,
        'trend' => $trendChartData,
    ];
@endphp

@section('content')
    <x-reveal type="fade-up">
        <x-admin-page-header subtitle="Performance overview for {{ $start->format('M d, Y') }} to {{ $end->format('M d, Y') }}" />
    </x-reveal>

    <x-reveal type="fade-up" delay="40">
        <form method="GET" class="admin-filter-bar grid grid-cols-1 md:grid-cols-4 gap-3 items-center" x-data="{ period: '{{ $period }}' }">
            <select name="period" x-model="period" class="input-field" @change="if (period !== 'custom') $el.form.submit()">
                <option value="day">Today</option>
                <option value="month">This Month</option>
                <option value="year">This Year</option>
                <option value="custom">Custom Range</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from', $start->toDateString()) }}" class="input-field" x-show="period === 'custom'">
            <input type="date" name="date_to" value="{{ request('date_to', $end->toDateString()) }}" class="input-field" x-show="period === 'custom'">
            <div class="flex gap-2" x-show="period === 'custom'">
                <button type="submit" class="btn-primary !py-2.5 !px-5 text-sm flex-1">Apply</button>
                <a href="{{ route('admin.statistics.index') }}" class="btn-outline !py-2.5 !px-5 text-sm">Reset</a>
            </div>
        </form>
    </x-reveal>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        @foreach([
            ['label' => 'Paid Revenue', 'value' => shop_money($summary['paid_revenue']), 'icon' => 'currency', 'tint' => 'bg-sage-50 text-sage-700'],
            ['label' => 'Orders', 'value' => $summary['orders'], 'icon' => 'cart', 'tint' => 'bg-slate-100 text-slate-700'],
            ['label' => 'Average Order', 'value' => shop_money($summary['average_order'] ?? 0), 'icon' => 'chart', 'tint' => 'bg-gold-50 text-gold-700'],
            ['label' => 'New Customers', 'value' => $summary['new_customers'], 'icon' => 'users', 'tint' => 'bg-terracotta-50 text-terracotta-600'],
        ] as $i => $card)
            <x-reveal type="fade-up" :delay="$i * 60">
                <div class="admin-stat-card h-full">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <span class="admin-stat-label">{{ $card['label'] }}</span>
                        <span class="admin-stat-icon {{ $card['tint'] }}">
                            <x-icon :name="$card['icon']" class="w-5 h-5" />
                        </span>
                    </div>
                    <p class="admin-stat-value">{{ $card['value'] }}</p>
                </div>
            </x-reveal>
        @endforeach
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach([
            ['label' => 'Active Products', 'value' => $summary['active_products'].' / '.$summary['products'], 'icon' => 'cube', 'bg' => 'bg-slate-100', 'text' => 'text-slate-700'],
            ['label' => 'Low Stock', 'value' => $summary['low_stock'], 'icon' => 'warning', 'bg' => 'bg-red-100', 'text' => 'text-red-700'],
            ['label' => 'Messages', 'value' => $summary['messages'], 'icon' => 'mail', 'bg' => 'bg-terracotta-50', 'text' => 'text-terracotta-600'],
            ['label' => 'Range', 'value' => $period, 'icon' => 'calendar', 'bg' => 'bg-oat-100', 'text' => 'text-oat-700'],
        ] as $item)
            <div class="admin-card admin-card-pad h-full">
                <div class="flex items-center gap-3 h-full">
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
            <div class="admin-card-pad pb-0">
                <h2 class="font-bold text-slate-900 mb-4">Order Status Breakdown</h2>
                @if($statusChartData->isNotEmpty())
                    <div class="h-56 mb-2"><canvas id="chart-status-breakdown"></canvas></div>
                @endif
            </div>
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
            <div class="admin-card-pad pb-0">
                <h2 class="font-bold text-slate-900 mb-4">Payment Breakdown</h2>
                @if($paymentChartData->isNotEmpty())
                    <div class="h-56 mb-2"><canvas id="chart-payment-breakdown"></canvas></div>
                @endif
            </div>
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
            <div class="admin-card-pad pb-0">
                <h2 class="font-bold text-slate-900 mb-4">Top Products</h2>
                @if($topProductsChartData->isNotEmpty())
                    <div class="h-56 mb-2"><canvas id="chart-top-products"></canvas></div>
                @endif
            </div>
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
            <div class="admin-card-pad pb-0">
                <h2 class="font-bold text-slate-900 mb-4">{{ $period === 'year' ? 'Monthly' : 'Daily' }} Trend</h2>
                @if($trendChartData->isNotEmpty())
                    <div class="h-56 mb-2"><canvas id="chart-trend"></canvas></div>
                @endif
            </div>
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

    {{-- Inert data blob (not executed as script) read by admin-charts.js — no CSP nonce needed. --}}
    <script type="application/json" id="stats-chart-data">@json($chartPayload)</script>
@endsection
