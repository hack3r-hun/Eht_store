@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')
    <x-reveal type="fade-up" class="mb-8 !pointer-events-auto">
        <div class="relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 shadow-sm p-6 md:p-8 text-slate-900">
            <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold tracking-tight">Welcome back, {{ auth()->user()->name }}</h2>
                    <p class="text-slate-500 mt-2">Here's what's happening at {{ shop_name() }} today.</p>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="btn-primary !py-2.5 !px-5 text-sm inline-flex items-center gap-2">
                    View All Orders <x-icon name="arrow-right" class="w-4 h-4" />
                </a>
            </div>
        </div>
    </x-reveal>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        @foreach([
            ['label' => 'Total Revenue', 'count' => (int) $stats['revenue'], 'prefix' => 'Rs. ', 'icon' => 'currency', 'tint' => 'bg-sage-50 text-sage-700', 'sparkline' => 'revenue'],
            ['label' => 'Total Orders', 'count' => $stats['total_orders'], 'icon' => 'cart', 'tint' => 'bg-slate-100 text-slate-700', 'sparkline' => 'orders'],
            ['label' => 'Orders Today', 'count' => $stats['orders_today'], 'icon' => 'calendar', 'tint' => 'bg-gold-50 text-gold-700'],
            ['label' => 'Customers', 'count' => $stats['total_customers'], 'icon' => 'users', 'tint' => 'bg-terracotta-50 text-terracotta-600'],
        ] as $i => $card)
            <x-reveal type="fade-up" :delay="$i * 80">
                <div class="admin-stat-card h-full">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <span class="admin-stat-label">{{ $card['label'] }}</span>
                        <span class="admin-stat-icon {{ $card['tint'] }}">
                            <x-icon :name="$card['icon']" class="w-5 h-5" />
                        </span>
                    </div>
                    <p class="admin-stat-value" data-count="{{ $card['count'] }}" data-count-prefix="{{ $card['prefix'] ?? '' }}">0</p>
                    @if($card['sparkline'] ?? null)
                        <div class="admin-stat-sparkline">
                            <canvas id="spark-{{ $card['sparkline'] }}"></canvas>
                        </div>
                    @endif
                </div>
            </x-reveal>
        @endforeach
    </div>

    <script type="application/json" id="dashboard-chart-data">{!! json_encode($sparklines) !!}</script>

    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 mb-8">
        @foreach([
            ['label' => 'Pending Orders', 'value' => $stats['pending_orders'], 'bg' => 'bg-sage-100', 'text' => 'text-sage-700', 'icon' => 'clock'],
            ['label' => 'Products', 'value' => $stats['active_products'].' / '.$stats['total_products'], 'bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'icon' => 'cube'],
            ['label' => 'Archived', 'value' => $stats['archived_products'], 'bg' => 'bg-oat-100', 'text' => 'text-oat-700', 'icon' => 'inbox'],
            ['label' => 'Unread Messages', 'value' => $stats['unread_messages'], 'bg' => 'bg-terracotta-50', 'text' => 'text-terracotta-600', 'icon' => 'mail'],
            ['label' => 'Low Stock', 'value' => $stats['low_stock'], 'bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => 'warning'],
        ] as $i => $item)
            <x-reveal type="scale" :delay="$i * 60">
                <div class="admin-card admin-card-pad card-hover group h-full">
                    <div class="flex items-center gap-3 h-full">
                        <div class="w-11 h-11 rounded-xl {{ $item['bg'] }} {{ $item['text'] }} flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <x-icon :name="$item['icon']" class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 font-medium">{{ $item['label'] }}</p>
                            <p class="text-xl font-bold text-slate-900">{{ $item['value'] }}</p>
                        </div>
                    </div>
                </div>
            </x-reveal>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <x-reveal type="fade-right">
            <div class="admin-card overflow-hidden h-full relative z-10">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-sage-100 text-sage-700 flex items-center justify-center"><x-icon name="cart" class="w-4 h-4" /></span>
                        Recent Orders
                    </h2>
                    <a href="{{ route('admin.orders.index') }}" class="admin-action-link">View all →</a>
                </div>
                <div class="p-6">
                    @if($recentOrders->isEmpty())
                        <div class="text-center py-10 text-slate-500">
                            <x-icon name="inbox" class="w-12 h-12 mx-auto mb-3 text-slate-300" />
                            <p class="text-sm">No orders yet. They'll appear here.</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($recentOrders as $order)
                                <a href="{{ route('admin.orders.show', $order) }}"
                                   class="flex justify-between items-center p-4 rounded-xl border border-slate-50 hover:border-sage-200 hover:bg-sage-50/50 transition-all duration-300 group">
                                    <div>
                                        <p class="font-semibold text-slate-900 group-hover:text-sage-700 transition-colors">{{ $order->order_number }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $order->user?->name ?? 'Guest' }} · {{ $order->created_at->diffForHumans() }}</p>
                                    </div>
                                    <div class="text-right">
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

        <x-reveal type="fade-left" delay="100">
            <div class="admin-card overflow-hidden h-full relative z-10">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-red-100 text-red-600 flex items-center justify-center"><x-icon name="warning" class="w-4 h-4" /></span>
                        Low Stock Alert
                    </h2>
                    <a href="{{ route('admin.products.index', ['stock' => 'low']) }}" class="admin-action-link">Manage -></a>
                </div>
                <div class="p-6">
                    @if($lowStockProducts->isEmpty())
                        <div class="text-center py-10 text-slate-500">
                            <x-icon name="check-circle" class="w-12 h-12 mx-auto mb-3 text-emerald-400" />
                            <p class="text-sm">All products are well stocked!</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($lowStockProducts as $product)
                                <div class="flex justify-between items-center p-4 rounded-xl bg-slate-50 hover:bg-red-50/50 transition-colors duration-300 group">
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
    </div>

    <x-reveal type="fade-up">
        <div class="admin-card admin-card-pad">
            <h2 class="font-bold text-slate-900 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach([
                    ['route' => 'admin.products.create', 'label' => 'Add Product', 'icon' => 'plus'],
                    ['route' => 'admin.orders.index', 'label' => 'View Orders', 'icon' => 'clipboard'],
                    ['route' => 'admin.messages.index', 'label' => 'Messages', 'icon' => 'chat'],
                    ['route' => 'admin.settings.index', 'label' => 'Settings', 'icon' => 'cog'],
                ] as $action)
                    <a href="{{ route($action['route']) }}"
                       class="group flex flex-col items-center gap-2 p-5 rounded-2xl border border-slate-100 hover:border-sage-200 hover:bg-sage-50/50 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg text-center">
                        <span class="w-12 h-12 rounded-xl bg-sage-50 text-sage-600 flex items-center justify-center group-hover:bg-sage-600 group-hover:text-white transition-all duration-300 group-hover:scale-110">
                            <x-icon :name="$action['icon']" class="w-6 h-6" />
                        </span>
                        <span class="text-sm font-semibold text-slate-700 group-hover:text-sage-700">{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </x-reveal>
@endsection
