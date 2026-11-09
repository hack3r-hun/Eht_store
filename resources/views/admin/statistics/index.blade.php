@extends('layouts.admin')

@section('page-title', 'Statistics')

@php
    $kpis = $data['kpis'];
    $t = $data['totals'];
    $inv = $data['inventory'];

    $sum = fn ($rows, $key) => array_sum(array_column($rows, $key));
    $has = [
        'trend' => array_sum($data['trend']['orders']) > 0,
        'weekday' => array_sum($data['weekday']['orders']) > 0,
        'hours' => array_sum($data['hours']['orders']) > 0,
        'status' => count($data['status']['rows']) > 0,
        'method' => count($data['method']['rows']) > 0,
        'paymentStatus' => count($data['paymentStatus']['rows']) > 0,
        'topProducts' => count($data['topProducts']['rows']) > 0,
        'categories' => count($data['categories']['rows']) > 0,
        'cities' => count($data['cities']['rows']) > 0,
        'customers' => ($data['customers']['returning'] + $data['customers']['first_time']) > 0,
        'buyerType' => ($data['customers']['guests'] + $data['customers']['accounts']) > 0,
        'audience' => array_sum($data['audience']['customers']) + array_sum($data['audience']['subscribers']) > 0,
        'inventory' => $inv['total'] > 0,
        'wishlist' => count($data['wishlist']['rows']) > 0,
        'customOrders' => $data['customOrders']['in_range'] > 0,
        'ratings' => $data['reviews']['count'] > 0,
    ];

    $chartPayload = [
        'currency' => config('shop.currency_symbol', 'Rs.'),
        'trend' => $data['trend'],
        'weekday' => $data['weekday'],
        'hours' => $data['hours'],
        'status' => ['labels' => array_column($data['status']['rows'], 'label'), 'counts' => array_column($data['status']['rows'], 'count'), 'colors' => array_column($data['status']['rows'], 'color')],
        'method' => ['labels' => array_column($data['method']['rows'], 'label'), 'counts' => array_column($data['method']['rows'], 'count')],
        'paymentStatus' => ['labels' => array_column($data['paymentStatus']['rows'], 'label'), 'counts' => array_column($data['paymentStatus']['rows'], 'count')],
        'topProducts' => ['labels' => array_map(fn ($l) => \Illuminate\Support\Str::limit($l, 22), array_column($data['topProducts']['rows'], 'label')), 'revenue' => array_column($data['topProducts']['rows'], 'value'), 'units' => array_column($data['topProducts']['rows'], 'units')],
        'categories' => ['labels' => array_column($data['categories']['rows'], 'label'), 'revenue' => array_column($data['categories']['rows'], 'value')],
        'cities' => ['labels' => array_column($data['cities']['rows'], 'label'), 'orders' => array_column($data['cities']['rows'], 'count')],
        'customers' => ['labels' => ['First-time buyers', 'Returning buyers'], 'counts' => [$data['customers']['first_time'], $data['customers']['returning']]],
        'buyerType' => ['labels' => ['Guest checkout', 'Signed-in account'], 'counts' => [$data['customers']['guests'], $data['customers']['accounts']]],
        'audience' => $data['audience'],
        'inventory' => ['labels' => ['Healthy', 'Running low', 'Sold out'], 'counts' => [$inv['healthy'], $inv['low'], $inv['out']]],
        'wishlist' => ['labels' => array_map(fn ($l) => \Illuminate\Support\Str::limit($l, 22), array_column($data['wishlist']['rows'], 'label')), 'counts' => array_column($data['wishlist']['rows'], 'count')],
        'customOrders' => ['labels' => array_column($data['customOrders']['rows'], 'label'), 'counts' => array_column($data['customOrders']['rows'], 'count')],
        'ratings' => ['labels' => ['1★', '2★', '3★', '4★', '5★'], 'counts' => $data['reviews']['counts']],
    ];

    $format = fn ($value, $type) => match ($type) {
        'money' => shop_money($value),
        'percent' => number_format($value, 1).'%',
        default => number_format($value),
    };
@endphp

@section('content')
    <x-admin-page-header subtitle="Sales and shop activity for {{ $start->format('d M Y') }} – {{ $end->format('d M Y') }} ({{ $data['range']['days'] }} {{ \Illuminate\Support\Str::plural('day', $data['range']['days']) }}). Every number is compared with the {{ $data['range']['days'] }} days before it.">
        <x-slot:action>
            <a href="{{ route('admin.statistics.export', request()->query()) }}" class="btn-outline !py-2 !px-4 text-sm">⬇ Download orders (CSV)</a>
        </x-slot:action>
    </x-admin-page-header>

    {{-- Period picker --}}
    <div class="admin-card admin-card-pad mb-6" x-data="{ custom: {{ $period === 'custom' ? 'true' : 'false' }} }">
        <div class="flex flex-wrap items-center gap-2">
            @foreach($presets as $key => $label)
                @if($key === 'custom')
                    <button type="button" @click="custom = !custom"
                            class="product-filter-pill {{ $period === 'custom' ? 'product-filter-pill-active' : 'product-filter-pill-inactive' }}">{{ $label }} range</button>
                @else
                    <a href="{{ route('admin.statistics.index', ['period' => $key]) }}"
                       class="product-filter-pill {{ $period === $key ? 'product-filter-pill-active' : 'product-filter-pill-inactive' }}">{{ $label }}</a>
                @endif
            @endforeach
        </div>
        <form method="GET" action="{{ route('admin.statistics.index') }}" x-show="custom" x-cloak class="mt-4 grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-3 items-end">
            <input type="hidden" name="period" value="custom">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">From</label>
                <input type="date" name="date_from" value="{{ $start->toDateString() }}" class="input-field">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">To</label>
                <input type="date" name="date_to" value="{{ $end->toDateString() }}" class="input-field">
            </div>
            <button type="submit" class="btn-primary !py-3 !px-6 text-sm">Apply</button>
        </form>
    </div>

    {{-- Plain-language summary --}}
    <div class="rounded-2xl border border-sage-200 bg-sage-50/60 p-5 md:p-6 mb-6">
        <h2 class="font-bold text-sage-900 flex items-center gap-2 mb-3"><x-icon name="light-bulb" class="w-5 h-5 text-sage-700" /> In plain words</h2>
        <ul class="space-y-1.5 text-sm text-slate-700 list-disc list-inside">
            @foreach($data['insights'] as $line)<li>{{ $line }}</li>@endforeach
        </ul>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-5 mb-8">
        @foreach($kpis as $card)
            @php
                $d = $card['delta'];
                $good = $d['dir'] === 'flat' || $d['dir'] === 'new' ? null : (($d['dir'] === 'up') === $card['good_when_up']);
                $tone = $good === null ? 'bg-slate-100 text-slate-600' : ($good ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
            @endphp
            <div class="admin-stat-card h-full">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <span class="admin-stat-label">{{ $card['label'] }}</span>
                    <span class="admin-stat-icon {{ $card['tint'] }}"><x-icon :name="$card['icon']" class="w-5 h-5" /></span>
                </div>
                <p class="admin-stat-value">{{ $format($card['value'], $card['format']) }}</p>
                <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 font-semibold {{ $tone }}">
                        @if($d['dir'] === 'new') New
                        @elseif($d['dir'] === 'flat') No change
                        @else {{ $d['dir'] === 'up' ? '▲' : '▼' }} {{ abs($d['pct']) }}%
                        @endif
                    </span>
                    <span class="text-slate-400">before: {{ $format($card['previous'], $card['format']) }}</span>
                </p>
                <p class="mt-2 text-xs text-slate-500">{{ $card['help'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Sales over time --}}
    <h2 class="text-lg font-bold text-slate-900 mb-3">Sales</h2>
    <div class="mb-6">
        <x-admin.chart-card id="chart-trend" title="Sales over time" :empty="! $has['trend']" height="h-72"
            help="Lines show money (total order value and paid revenue); bars show how many orders came in. {{ $data['trend']['granularity'] === 'month' ? 'Each point is one month.' : 'Each point is one day.' }}" />
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <x-admin.chart-card id="chart-weekday" title="Best days of the week" :empty="! $has['weekday']"
            help="Which weekdays bring the most orders. Use it to time posts, offers and restocking." />
        <x-admin.chart-card id="chart-hours" title="Busiest hours" :empty="! $has['hours']"
            help="What time of day customers order. Post new items just before the busy hours." />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-admin.chart-card id="chart-status" title="Order status" :empty="! $has['status']" height="h-56"
            help="Where your orders are right now: waiting, being made, shipped, delivered or cancelled." />
        <x-admin.chart-card id="chart-method" title="How customers pay" :empty="! $has['method']" height="h-56"
            help="Cash on delivery versus card payments (cancelled orders left out)." />
        <x-admin.chart-card id="chart-payment-status" title="Payment status" :empty="! $has['paymentStatus']" height="h-56"
            help="Paid means the money is confirmed. Pending is still to be collected." />
    </div>

    {{-- Products --}}
    <h2 class="text-lg font-bold text-slate-900 mb-3">Products &amp; places</h2>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
        <x-admin.chart-card id="chart-top-products" title="Best sellers (by money earned)" :empty="! $has['topProducts']" height="h-72"
            help="The pieces that earned the most. Keep these in stock and feature them on the home page.">
            <x-slot:footer>
                @if($has['topProducts'])
                    <div class="overflow-x-auto -mx-1">
                        <table class="admin-table">
                            <thead><tr><th>Product</th><th>Sold</th><th>Earned</th></tr></thead>
                            <tbody>
                                @foreach($data['topProducts']['rows'] as $row)
                                    <tr>
                                        <td><span class="block font-semibold text-slate-900">{{ $row['label'] }}</span><span class="block text-xs text-slate-400">{{ $row['sku'] }}</span></td>
                                        <td>{{ $row['units'] }}</td>
                                        <td>{{ shop_money($row['value']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-slot:footer>
        </x-admin.chart-card>

        <div class="grid grid-cols-1 gap-6">
            <x-admin.chart-card id="chart-categories" title="Sales by collection" :empty="! $has['categories']" height="h-60"
                help="Which collections (categories) bring in the money." />
            <x-admin.chart-card id="chart-cities" title="Where orders are going" :empty="! $has['cities']" height="h-60"
                help="Top delivery cities by number of orders — useful for delivery planning and local promotions." />
        </div>
    </div>

    {{-- Customers --}}
    <h2 class="text-lg font-bold text-slate-900 mb-3">Customers</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
        <x-admin.chart-card id="chart-customers" title="New vs returning buyers" :empty="! $has['customers']" height="h-60"
            help="First-time buyers versus people who had ordered before. Guests are counted too — they are matched by email (or phone) across orders." />
        <x-admin.chart-card id="chart-buyer-type" title="Guest vs account" :empty="! $has['buyerType']" height="h-60"
            help="How buyers check out. Every guest's email and phone is saved with their order so you can reach them." >
            <x-slot:footer>
                <a href="{{ route('admin.customers.buyers') }}" class="admin-action-link">See everyone who ordered →</a>
            </x-slot:footer>
        </x-admin.chart-card>
        <x-admin.chart-card id="chart-audience" title="Audience growth" :empty="! $has['audience']" height="h-60" class="md:col-span-2 xl:col-span-1"
            help="New customer accounts and newsletter sign-ups over time." />
    </div>

    {{-- Stock & demand --}}
    <h2 class="text-lg font-bold text-slate-900 mb-3">Stock &amp; demand</h2>
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
        <x-admin.chart-card id="chart-inventory" title="Stock health" :empty="! $has['inventory']" height="h-56"
            help="Every active size of every product: healthy, running low (at or under its low-stock limit) or sold out.">
            <x-slot:footer>
                @if($inv['attention']->isNotEmpty())
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Needs restocking</p>
                    <ul class="space-y-1.5 text-sm">
                        @foreach($inv['attention'] as $product)
                            <li class="flex justify-between gap-3"><span class="truncate text-slate-700">{{ $product->name }}</span><span class="shrink-0 font-semibold {{ $product->stock_quantity === 0 ? 'text-red-600' : 'text-amber-600' }}">{{ $product->stock_quantity === 0 ? 'sold out' : $product->stock_quantity.' left' }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </x-slot:footer>
        </x-admin.chart-card>

        <x-admin.chart-card id="chart-wishlist" title="Most saved to wishlists" :empty="! $has['wishlist']" height="h-56"
            help="Pieces people love but haven't bought yet — strong candidates for a promotion or a restock.">
            <x-slot:footer>
                @if(count($data['restock']['rows']))
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Customers waiting for restock</p>
                    <ul class="space-y-1.5 text-sm">
                        @foreach($data['restock']['rows'] as $row)
                            <li class="flex justify-between gap-3"><span class="truncate text-slate-700">{{ $row['label'] }}</span><span class="shrink-0 font-semibold text-terracotta-600">{{ $row['count'] }} waiting</span></li>
                        @endforeach
                    </ul>
                @endif
            </x-slot:footer>
        </x-admin.chart-card>

        <x-admin.chart-card id="chart-custom-orders" title="Custom order requests" :empty="! $has['customOrders']" height="h-56"
            help="Requests received this period by stage. {{ $data['customOrders']['open'] }} are still open (new or quoted).">
            <x-slot:footer>
                @if($data['customOrders']['quoted_value'] > 0)
                    <p class="text-sm text-slate-600">Quoted value this period: <strong>{{ shop_money($data['customOrders']['quoted_value']) }}</strong></p>
                @endif
            </x-slot:footer>
        </x-admin.chart-card>
    </div>

    {{-- Feedback --}}
    <h2 class="text-lg font-bold text-slate-900 mb-3">Feedback</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <x-admin.chart-card id="chart-ratings" title="Review ratings" :empty="! $has['ratings']" height="h-56"
            :help="$data['reviews']['count'] ? 'Average '.$data['reviews']['average'].' out of 5 from '.$data['reviews']['count'].' approved '.\Illuminate\Support\Str::plural('review', $data['reviews']['count']).'.' : 'Approved customer reviews written in this period.'">
            <x-slot:footer>
                @if($data['reviews']['pending'] > 0)
                    <a href="{{ route('admin.reviews.index', ['status' => 'pending']) }}" class="admin-action-link">{{ $data['reviews']['pending'] }} review(s) waiting for approval →</a>
                @endif
            </x-slot:footer>
        </x-admin.chart-card>
        <div class="admin-card admin-card-pad">
            <h3 class="font-bold text-slate-900">Messages</h3>
            <p class="text-sm text-slate-500 mt-1 mb-4">Questions from the contact form.</p>
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-xl bg-slate-50 p-4"><p class="text-2xl font-bold text-slate-900">{{ $data['messages']['in_range'] }}</p><p class="text-xs text-slate-500 mt-1">received this period</p></div>
                <div class="rounded-xl bg-terracotta-50 p-4"><p class="text-2xl font-bold text-terracotta-600">{{ $data['messages']['unread'] }}</p><p class="text-xs text-slate-500 mt-1">unread right now</p></div>
            </div>
            <a href="{{ route('admin.messages.index') }}" class="admin-action-link mt-4">Open messages →</a>
        </div>
    </div>

    {{-- Whole-shop totals (moved here from the dashboard) --}}
    <h2 class="text-lg font-bold text-slate-900 mb-3">Whole shop, all time</h2>
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        @foreach([
            ['Paid revenue', shop_money($t['paid_revenue']), 'currency'],
            ['Orders', number_format($t['orders']), 'cart'],
            ['Customers (guests included)', number_format($t['customers']), 'users'],
            ['Active products', $t['active_products'].' / '.$t['products'], 'cube'],
            ['Archived products', $t['archived_products'], 'inbox'],
        ] as [$label, $value, $icon])
            <div class="admin-card admin-card-pad">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-sage-50 text-sage-700 flex items-center justify-center shrink-0"><x-icon :name="$icon" class="w-5 h-5" /></div>
                    <div class="min-w-0"><p class="text-xs text-slate-500 font-medium">{{ $label }}</p><p class="text-lg font-bold text-slate-900 truncate">{{ $value }}</p></div>
                </div>
            </div>
        @endforeach
    </div>

    <details class="admin-card admin-card-pad mb-8">
        <summary class="font-bold text-slate-900 cursor-pointer">What do these words mean?</summary>
        <dl class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 text-sm">
            @foreach([
                'Paid revenue' => 'Money from orders whose payment is marked “paid”. Cash-on-delivery orders count once you mark them paid.',
                'Total order value' => 'The value of every order that was not cancelled, whether or not it is paid yet.',
                'Average order' => 'Total order value divided by the number of orders. Bundles, gift sets and free-shipping targets raise it.',
                'Returning customers' => 'Buyers in this period — guests included — who also bought from you before it started. People are matched by email, or by phone when there is no email.',
                'Cancellation rate' => 'Cancelled orders as a share of all orders placed.',
                'Compared with before' => 'The previous period is the same number of days immediately before the one you picked.',
            ] as $term => $meaning)
                <div><dt class="font-semibold text-slate-800">{{ $term }}</dt><dd class="text-slate-500">{{ $meaning }}</dd></div>
            @endforeach
        </dl>
    </details>

    {{-- Inert data blob read by admin-charts.js --}}
    <script type="application/json" id="stats-chart-data">@json($chartPayload)</script>
@endsection
