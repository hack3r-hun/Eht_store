@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')
    @php
        $a = $stats['attention'];
        $todos = collect([
            ['count' => $stats['to_confirm'], 'label' => 'new orders to confirm', 'route' => route('admin.orders.index'), 'icon' => 'cart', 'tone' => 'bg-sage-100 text-sage-700'],
            ['count' => $stats['to_ship'], 'label' => 'orders ready to ship', 'route' => route('admin.orders.index', ['status' => 'processing']), 'icon' => 'truck', 'tone' => 'bg-blue-100 text-blue-700'],
            ['count' => $a['new_custom_orders'], 'label' => 'custom order requests to quote', 'route' => route('admin.custom-orders.index', ['status' => 'new']), 'icon' => 'clipboard', 'tone' => 'bg-terracotta-50 text-terracotta-600'],
            ['count' => $a['unread_messages'], 'label' => 'unread messages', 'route' => route('admin.messages.index', ['status' => 'unread']), 'icon' => 'mail', 'tone' => 'bg-terracotta-50 text-terracotta-600'],
            ['count' => $a['pending_reviews'], 'label' => 'reviews waiting for approval', 'route' => route('admin.reviews.index', ['status' => 'pending']), 'icon' => 'sparkles', 'tone' => 'bg-amber-100 text-amber-700'],
            ['count' => $a['sold_out_sizes'], 'label' => 'sizes sold out', 'route' => route('admin.products.index', ['stock' => 'low']), 'icon' => 'warning', 'tone' => 'bg-red-100 text-red-700'],
            ['count' => $a['low_stock'], 'label' => 'products running low', 'route' => route('admin.products.index', ['stock' => 'low']), 'icon' => 'cube', 'tone' => 'bg-amber-100 text-amber-700'],
            ['count' => $a['restock_requests'], 'label' => 'customers waiting for a restock', 'route' => route('admin.subscribers.index'), 'icon' => 'users', 'tone' => 'bg-slate-100 text-slate-700'],
        ])->filter(fn ($row) => $row['count'] > 0)->values();
        $delta = $stats['sales_delta'];
    @endphp

    <div x-data="dashboardLive()">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500" role="status" aria-live="polite">
                <span class="inline-flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span x-show="auto" class="absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-60 animate-ping"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full" :class="auto ? 'bg-green-500' : 'bg-slate-300'"></span>
                    </span>
                    <span x-text="label"></span>
                </span>
                <span x-show="error" x-cloak class="ml-2 text-red-600" x-text="error"></span>
            </p>
            <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-slate-600 cursor-pointer select-none" title="Check for new orders every 45 seconds while this tab is open">
                    <input type="checkbox" class="sr-only peer" :checked="auto" @change="toggleAuto()">
                    <span class="relative inline-block w-9 h-5 rounded-full bg-slate-300 peer-checked:bg-sage-600 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-4 after:h-4 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-4"></span>
                    Auto-refresh
                </label>
                <button type="button" @click="refresh(true)" :disabled="loading" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:border-sage-300 hover:text-sage-700 active:scale-95 transition disabled:opacity-60" aria-label="Refresh dashboard">
                    <svg class="w-4 h-4" :class="loading && 'animate-spin'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356M2.985 19.644v-4.992h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
                    <span x-text="loading ? 'Refreshing…' : 'Refresh'"></span>
                </button>
            </div>
        </div>

        <div id="dashboard-live" data-orders-total="{{ $stats['orders_total'] }}">
    <x-reveal type="fade-up" class="mb-6 !pointer-events-auto">
        <div class="relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 shadow-sm p-5 md:p-8 text-slate-900">
            <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl md:text-3xl font-bold tracking-tight">Welcome back, {{ auth()->user()->name }}</h2>
                    <p class="text-slate-500 mt-1.5 text-sm md:text-base">Here's what needs your attention at {{ shop_name() }} today.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.orders.index') }}" class="btn-primary !py-2.5 !px-5 text-sm inline-flex items-center gap-2">Orders <x-icon name="arrow-right" class="w-4 h-4" /></a>
                    <a href="{{ route('admin.statistics.index') }}" class="btn-outline !py-2.5 !px-5 text-sm inline-flex items-center gap-2"><x-icon name="chart" class="w-4 h-4" /> Statistics</a>
                </div>
            </div>
        </div>
    </x-reveal>

    {{-- Today at a glance --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 md:gap-4 mb-6">
        <x-reveal type="fade-up">
            <div class="admin-stat-card h-full">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <span class="admin-stat-label">Sales today</span>
                    <span class="admin-stat-icon bg-sage-50 text-sage-700"><x-icon name="currency" class="w-5 h-5" /></span>
                </div>
                <p class="admin-stat-value">{{ shop_money($stats['sales_today']) }}</p>
                <p class="mt-1.5 text-xs">
                    @if($delta['dir'] === 'up')<span class="font-semibold text-green-600">▲ {{ abs($delta['pct']) }}%</span>
                    @elseif($delta['dir'] === 'down')<span class="font-semibold text-red-600">▼ {{ abs($delta['pct']) }}%</span>
                    @elseif($delta['dir'] === 'new')<span class="font-semibold text-green-600">New today</span>
                    @else<span class="font-semibold text-slate-400">Same</span>@endif
                    <span class="text-slate-400">vs yesterday</span>
                </p>
            </div>
        </x-reveal>
        @foreach([
            ['Orders today', $stats['orders_today'], 'calendar', 'bg-gold-50 text-gold-700', 'placed since midnight'],
            ['To confirm', $stats['to_confirm'], 'clock', 'bg-sage-50 text-sage-700', 'waiting for your OK'],
            ['To ship', $stats['to_ship'], 'truck', 'bg-blue-50 text-blue-700', 'paid / being made'],
        ] as $i => [$label, $value, $icon, $tint, $note])
            <x-reveal type="fade-up" :delay="($i + 1) * 70">
                <div class="admin-stat-card h-full">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <span class="admin-stat-label">{{ $label }}</span>
                        <span class="admin-stat-icon {{ $tint }}"><x-icon :name="$icon" class="w-5 h-5" /></span>
                    </div>
                    <p class="admin-stat-value">{{ number_format($value) }}</p>
                    <p class="mt-1.5 text-xs text-slate-400">{{ $note }}</p>
                </div>
            </x-reveal>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
        {{-- To-do list --}}
        <x-reveal type="fade-up" class="xl:col-span-1">
            <div class="admin-card h-full">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-terracotta-50 text-terracotta-600 flex items-center justify-center"><x-icon name="bolt" class="w-4 h-4" /></span>
                        Needs your attention
                    </h2>
                </div>
                <div class="p-3">
                    @forelse($todos as $todo)
                        <a href="{{ $todo['route'] }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition-colors group">
                            <span class="w-9 h-9 rounded-lg {{ $todo['tone'] }} flex items-center justify-center shrink-0"><x-icon :name="$todo['icon']" class="w-4 h-4" /></span>
                            <span class="flex-1 text-sm text-slate-700"><strong class="text-slate-900">{{ $todo['count'] }}</strong> {{ $todo['label'] }}</span>
                            <span class="text-slate-300 group-hover:text-sage-600 transition-colors">→</span>
                        </a>
                    @empty
                        <div class="text-center py-10 text-slate-500">
                            <x-icon name="check-circle" class="w-12 h-12 mx-auto mb-3 text-emerald-400" />
                            <p class="text-sm font-medium">You're all caught up. Nothing needs attention.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </x-reveal>

        {{-- Recent orders --}}
        <x-reveal type="fade-up" delay="60" class="xl:col-span-2">
            <div class="admin-card overflow-hidden h-full">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-sage-100 text-sage-700 flex items-center justify-center"><x-icon name="cart" class="w-4 h-4" /></span>
                        Recent orders
                    </h2>
                    <a href="{{ route('admin.orders.index') }}" class="admin-action-link">View all →</a>
                </div>
                <div class="p-4">
                    @if($recentOrders->isEmpty())
                        <div class="text-center py-10 text-slate-500">
                            <x-icon name="inbox" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                            <p class="text-sm">No orders yet. They'll appear here.</p>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($recentOrders as $order)
                                <a href="{{ route('admin.orders.show', $order) }}"
                                   class="flex justify-between items-center gap-3 p-3 sm:p-4 rounded-xl border border-slate-50 hover:border-sage-200 hover:bg-sage-50/50 transition-all duration-300 group">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-900 group-hover:text-sage-700 transition-colors truncate">{{ $order->order_number }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $order->user?->name ?? ($order->shipping_address['full_name'] ?? 'Guest') }} · {{ $order->created_at->diffForHumans() }}</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="font-bold text-slate-900">{{ shop_money($order->total) }}</p>
                                        <x-order-status-badge :status="$order->status" class="mt-1" />
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </x-reveal>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Stock --}}
        <x-reveal type="fade-up">
            <div class="admin-card overflow-hidden h-full">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-red-100 text-red-600 flex items-center justify-center"><x-icon name="warning" class="w-4 h-4" /></span>
                        Low stock
                    </h2>
                    <a href="{{ route('admin.products.index', ['stock' => 'low']) }}" class="admin-action-link">Manage →</a>
                </div>
                <div class="p-4">
                    @if($lowStockProducts->isEmpty())
                        <div class="text-center py-10 text-slate-500">
                            <x-icon name="check-circle" class="w-12 h-12 mx-auto mb-3 text-emerald-400" />
                            <p class="text-sm">All products are well stocked!</p>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($lowStockProducts as $product)
                                <div class="flex justify-between items-center gap-3 p-3 sm:p-4 rounded-xl bg-slate-50 hover:bg-red-50/50 transition-colors duration-300 group">
                                    <div class="min-w-0">
                                        <p class="font-medium text-slate-900 text-sm truncate group-hover:text-red-700 transition-colors">{{ $product->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $product->sku }}</p>
                                    </div>
                                    <span class="shrink-0 text-xs font-bold px-3 py-1.5 rounded-lg {{ $product->stock_quantity === 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $product->stock_quantity === 0 ? 'Out of stock' : $product->stock_quantity.' left' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </x-reveal>

        {{-- 14 day teaser --}}
        <x-reveal type="fade-up" delay="60">
            <div class="admin-card admin-card-pad h-full flex flex-col">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-slate-900">Last 14 days</h2>
                        <p class="text-sm text-slate-500 mt-0.5">A quick look at the trend. Open Statistics for the full picture.</p>
                    </div>
                    <a href="{{ route('admin.statistics.index', ['period' => 'week']) }}" class="admin-action-link shrink-0">Statistics →</a>
                </div>
                <div class="grid grid-cols-2 gap-4 mt-5 flex-1">
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Sales</p>
                        <p class="text-lg font-bold text-slate-900">{{ shop_money($sparklines['revenue_total']) }}</p>
                        <div class="admin-stat-sparkline"><canvas id="spark-revenue"></canvas></div>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Orders</p>
                        <p class="text-lg font-bold text-slate-900">{{ number_format($sparklines['orders_total']) }}</p>
                        <div class="admin-stat-sparkline"><canvas id="spark-orders"></canvas></div>
                    </div>
                </div>
            </div>
        </x-reveal>
    </div>

    <script type="application/json" id="dashboard-chart-data">{!! json_encode($sparklines) !!}</script>

    <x-reveal type="fade-up">
        <div class="admin-card admin-card-pad">
            <h2 class="font-bold text-slate-900 mb-4">Quick actions</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 md:gap-4">
                @foreach([
                    ['route' => 'admin.products.create', 'label' => 'Add product', 'icon' => 'plus'],
                    ['route' => 'admin.orders.index', 'label' => 'Orders', 'icon' => 'clipboard'],
                    ['route' => 'admin.pages.index', 'label' => 'Edit pages', 'icon' => 'document'],
                    ['route' => 'admin.messages.index', 'label' => 'Messages', 'icon' => 'chat'],
                    ['route' => 'admin.statistics.index', 'label' => 'Statistics', 'icon' => 'chart'],
                    ['route' => 'admin.settings.index', 'label' => 'Settings & password', 'icon' => 'cog'],
                ] as $action)
                    <a href="{{ route($action['route']) }}"
                       class="group flex flex-col items-center gap-2 p-4 rounded-2xl border border-slate-100 hover:border-sage-200 hover:bg-sage-50/50 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg text-center">
                        <span class="w-11 h-11 rounded-xl bg-sage-50 text-sage-600 flex items-center justify-center group-hover:bg-sage-600 group-hover:text-white transition-all duration-300 group-hover:scale-110">
                            <x-icon :name="$action['icon']" class="w-5 h-5" />
                        </span>
                        <span class="text-sm font-semibold text-slate-700 group-hover:text-sage-700">{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </x-reveal>
        </div>
    </div>
@endsection
