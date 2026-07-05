@extends('layouts.admin')

@section('page-title', 'Dashboard')

@section('content')
    <x-reveal type="fade-up" class="mb-8 !pointer-events-auto">
        <div class="relative overflow-hidden rounded-3xl bg-white border border-slate-100 shadow-card p-8 text-slate-900">
            <div class="absolute inset-0 opacity-20 pointer-events-none">
                <div class="absolute -top-10 -right-10 w-64 h-64 bg-white rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-1/3 w-48 h-48 bg-brand-300 rounded-full blur-3xl animate-pulse-soft"></div>
            </div>
            <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold">Welcome back, {{ auth()->user()->name }}</h2>
                    <p class="text-slate-500 mt-2">Here's what's happening at {{ shop_name() }} today.</p>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="btn-primary !py-2.5 !px-5 text-sm inline-flex items-center gap-2">
                    View All Orders <x-icon name="arrow-right" class="w-4 h-4" />
                </a>
            </div>
        </div>
    </x-reveal>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        @foreach([
            ['label' => 'Total Revenue', 'value' => shop_money($stats['revenue']), 'gradient' => 'from-brand-500 to-brand-700', 'icon' => 'currency'],
            ['label' => 'Total Orders', 'value' => $stats['total_orders'], 'gradient' => 'from-indigo-500 to-indigo-700', 'icon' => 'cart'],
            ['label' => 'Orders Today', 'value' => $stats['orders_today'], 'gradient' => 'from-violet-500 to-violet-700', 'icon' => 'calendar'],
            ['label' => 'Customers', 'value' => $stats['total_customers'], 'gradient' => 'from-emerald-500 to-emerald-700', 'icon' => 'users'],
        ] as $i => $card)
            <x-reveal type="fade-up" :delay="$i * 80">
                <div class="admin-stat-card bg-gradient-to-br {{ $card['gradient'] }} shadow-lg">
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-white/80 text-sm font-medium">{{ $card['label'] }}</span>
                            <x-icon :name="$card['icon']" class="w-6 h-6 text-white/90" />
                        </div>
                        <p class="text-3xl font-bold">{{ $card['value'] }}</p>
                    </div>
                </div>
            </x-reveal>
        @endforeach
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        @foreach([
            ['label' => 'Pending Orders', 'value' => $stats['pending_orders'], 'bg' => 'bg-brand-100', 'text' => 'text-brand-700', 'icon' => 'clock'],
            ['label' => 'Products', 'value' => $stats['active_products'].' / '.$stats['total_products'], 'bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'icon' => 'cube'],
            ['label' => 'Archived', 'value' => $stats['archived_products'], 'bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'icon' => 'inbox'],
            ['label' => 'Unread Messages', 'value' => $stats['unread_messages'], 'bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'icon' => 'mail'],
            ['label' => 'Low Stock', 'value' => $stats['low_stock'], 'bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => 'warning'],
        ] as $i => $item)
            <x-reveal type="scale" :delay="$i * 60">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-5 card-hover group">
                    <div class="flex items-center gap-3">
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
            <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden h-full relative z-10">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-brand-100 text-brand-700 flex items-center justify-center"><x-icon name="cart" class="w-4 h-4" /></span>
                        Recent Orders
                    </h2>
                    <a href="{{ route('admin.orders.index') }}" class="text-sm text-brand-600 font-semibold hover:text-brand-700 transition-colors">View all →</a>
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
                                   class="flex justify-between items-center p-4 rounded-xl border border-slate-50 hover:border-brand-200 hover:bg-brand-50/50 transition-all duration-300 group">
                                    <div>
                                        <p class="font-semibold text-slate-900 group-hover:text-brand-700 transition-colors">{{ $order->order_number }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $order->user?->name ?? 'Guest' }} · {{ $order->created_at->diffForHumans() }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-slate-900">{{ shop_money($order->total) }}</p>
                                        <span class="inline-block mt-1 text-xs font-semibold px-2 py-0.5 rounded-full
                                            @if($order->status->value === 'delivered') bg-green-100 text-green-700
                                            @elseif(in_array($order->status->value, ['pending', 'awaiting_cod'])) bg-brand-100 text-brand-700
                                            @else bg-blue-100 text-blue-700 @endif">
                                            {{ $order->status->label() }}
                                        </span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </x-reveal>

        <x-reveal type="fade-left" delay="100">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden h-full relative z-10">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-red-100 text-red-600 flex items-center justify-center"><x-icon name="warning" class="w-4 h-4" /></span>
                        Low Stock Alert
                    </h2>
                    <a href="{{ route('admin.products.index', ['stock' => 'low']) }}" class="text-sm text-brand-600 font-semibold hover:text-brand-700 transition-colors">Manage -></a>
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
                                    <span class="shrink-0 text-xs font-bold px-3 py-1.5 rounded-lg {{ $product->stock_quantity === 0 ? 'bg-red-100 text-red-700' : 'bg-brand-100 text-brand-700' }}">
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
        <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6">
            <h2 class="font-bold text-slate-900 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach([
                    ['route' => 'admin.products.create', 'label' => 'Add Product', 'icon' => 'plus'],
                    ['route' => 'admin.orders.index', 'label' => 'View Orders', 'icon' => 'clipboard'],
                    ['route' => 'admin.messages.index', 'label' => 'Messages', 'icon' => 'chat'],
                    ['route' => 'admin.settings.index', 'label' => 'Settings', 'icon' => 'cog'],
                ] as $action)
                    <a href="{{ route($action['route']) }}"
                       class="group flex flex-col items-center gap-2 p-5 rounded-2xl border border-slate-100 hover:border-brand-200 hover:bg-brand-50/50 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg text-center">
                        <span class="w-12 h-12 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center group-hover:bg-brand-600 group-hover:text-white transition-all duration-300 group-hover:scale-110">
                            <x-icon :name="$action['icon']" class="w-6 h-6" />
                        </span>
                        <span class="text-sm font-semibold text-slate-700 group-hover:text-brand-700">{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </x-reveal>
@endsection
