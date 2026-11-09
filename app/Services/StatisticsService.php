<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\ContactMessage;
use App\Models\CustomOrder;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\StockAlert;
use App\Models\User;
use App\Models\WishlistItem;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds everything the Statistics page shows for one date range, plus the
 * immediately preceding range of equal length so every headline number can say
 * "up/down X% vs the previous period".
 *
 * Definitions (also explained on the page):
 *  - Order value  = total of all orders that were not cancelled
 *  - Paid revenue = total of orders whose payment is marked paid
 *  - Average order = order value / non-cancelled orders
 */
class StatisticsService
{
    public const PRESETS = [
        'day' => 'Today',
        'week' => 'Last 7 days',
        'days30' => 'Last 30 days',
        'month' => 'This month',
        'last_month' => 'Last month',
        'year' => 'This year',
        'custom' => 'Custom',
    ];

    public function __construct(protected InventoryService $inventory) {}

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    public function resolveRange(Request $request): array
    {
        $now = now();
        $period = (string) $request->get('period', 'month');

        [$start, $end] = match ($period) {
            'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'days30' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'year' => [
                Carbon::create(max(2000, min(2100, (int) $request->input('year', $now->year))), 1, 1)->startOfYear(),
                Carbon::create(max(2000, min(2100, (int) $request->input('year', $now->year))), 1, 1)->endOfYear(),
            ],
            'custom' => $this->customRange($request),
            default => $this->monthRange($request),
        };

        return [$start, $end, array_key_exists($period, self::PRESETS) ? $period : 'month'];
    }

    protected function monthRange(Request $request): array
    {
        try {
            $month = Carbon::createFromFormat('!Y-m', (string) $request->input('month', now()->format('Y-m')));
        } catch (\Throwable) {
            $month = now()->startOfMonth();
        }

        return [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()];
    }

    protected function customRange(Request $request): array
    {
        try {
            $from = Carbon::parse($request->input('date_from', now()->startOfMonth()->toDateString()))->startOfDay();
            $to = Carbon::parse($request->input('date_to', now()->endOfMonth()->toDateString()))->endOfDay();
        } catch (\Throwable) {
            return [now()->startOfMonth(), now()->endOfMonth()];
        }

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        // Keep the page fast and the charts readable.
        if ($from->diffInDays($to) > 1100) {
            $from = $to->copy()->subDays(1100)->startOfDay();
        }

        return [$from, $to];
    }

    public function build(Carbon $start, Carbon $end): array
    {
        $days = max(1, (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $prevEnd = $start->copy()->subSecond();
        $prevStart = $start->copy()->subDays($days)->startOfDay();

        $orders = $this->orders($start, $end);
        $current = $this->metrics($orders, $start, $end);
        $previous = $this->metrics($this->orders($prevStart, $prevEnd), $prevStart, $prevEnd);

        $valid = $orders->filter(fn (Order $o) => $o->status !== OrderStatus::Cancelled);
        $granularity = $days > 92 ? 'month' : 'day';

        $data = [
            'range' => ['start' => $start, 'end' => $end, 'days' => $days, 'granularity' => $granularity, 'previous_start' => $prevStart, 'previous_end' => $prevEnd],
            'kpis' => $this->kpiCards($current, $previous),
            'trend' => $this->trend($orders, $start, $end, $granularity),
            'weekday' => $this->weekday($valid),
            'hours' => $this->hours($valid),
            'status' => $this->statusBreakdown($orders),
            'method' => $this->methodBreakdown($valid),
            'paymentStatus' => $this->paymentStatusBreakdown($orders),
            'cities' => $this->cities($valid),
            'topProducts' => $this->topProducts($start, $end),
            'categories' => $this->categories($start, $end),
            'customers' => [
                'first_time' => $current['new_customers'],
                'returning' => $current['returning_customers'],
                'guests' => $current['guest_buyers'],
                'accounts' => $current['account_buyers'],
            ],
            'audience' => $this->audience($start, $end, $granularity),
            'inventory' => $this->inventoryHealth(),
            'wishlist' => $this->wishlistDemand(),
            'restock' => $this->restockWaiting(),
            'customOrders' => $this->customOrders($start, $end),
            'reviews' => $this->reviews($start, $end),
            'messages' => [
                'in_range' => ContactMessage::whereBetween('created_at', [$start, $end])->count(),
                'unread' => ContactMessage::where('is_read', false)->count(),
            ],
            'totals' => $this->lifetimeTotals(),
            'current' => $current,
            'previous' => $previous,
        ];

        $data['insights'] = $this->insights($data);

        return $data;
    }

    // ------------------------------------------------------------------ building blocks

    protected function orders(Carbon $start, Carbon $end): Collection
    {
        return Order::whereBetween('created_at', [$start, $end])
            ->get(['id', 'user_id', 'order_number', 'status', 'payment_method', 'payment_status', 'total', 'created_at', 'shipping_address', 'contact_email', 'contact_phone']);
    }

    /** Core numbers for one period. */
    protected function metrics(Collection $orders, Carbon $start, Carbon $end): array
    {
        $valid = $orders->filter(fn (Order $o) => $o->status !== OrderStatus::Cancelled);
        $paid = $orders->filter(fn (Order $o) => $o->payment_status === PaymentStatus::Paid);

        // A "customer" is a person, whether or not they made an account: identified by email,
        // or by phone when no email was given. Guests therefore count like anyone else.
        $keyOf = fn (Order $o) => $o->contact_email ?: $o->contact_phone;
        $buyerKeys = $valid->map($keyOf)->filter()->unique()->values();
        $accountKeys = $valid->filter(fn (Order $o) => $o->user_id)->map($keyOf)->filter()->unique();
        $returning = $this->returningBuyers($valid, $start);

        $items = (int) OrderItem::whereHas('order', fn ($q) => $q->whereBetween('created_at', [$start, $end])->where('status', '!=', OrderStatus::Cancelled->value))->sum('quantity');

        return [
            'orders' => $orders->count(),
            'valid_orders' => $valid->count(),
            'order_value' => (float) $valid->sum('total'),
            'paid_revenue' => (float) $paid->sum('total'),
            'aov' => $valid->count() ? (float) $valid->sum('total') / $valid->count() : 0.0,
            'cancelled' => $orders->count() - $valid->count(),
            'cancel_rate' => $orders->count() ? ($orders->count() - $valid->count()) / $orders->count() * 100 : 0.0,
            'items_sold' => $items,
            'buyers' => $buyerKeys->count(),
            'new_customers' => max(0, $buyerKeys->count() - $returning),
            'returning_customers' => $returning,
            'account_buyers' => $accountKeys->count(),
            'guest_buyers' => $buyerKeys->diff($accountKeys)->count(),
        ];
    }

    /** Buyers in this period (guests included) who already had a non-cancelled order before it started. */
    protected function returningBuyers(Collection $valid, Carbon $start): int
    {
        $emails = $valid->pluck('contact_email')->filter()->unique()->values();
        $phones = $valid->filter(fn (Order $o) => ! $o->contact_email && $o->contact_phone)->pluck('contact_phone')->unique()->values();

        $before = fn () => Order::where('created_at', '<', $start)->where('status', '!=', OrderStatus::Cancelled->value);
        $count = 0;

        foreach ($emails->chunk(500) as $chunk) {
            $count += $before()->whereIn('contact_email', $chunk->all())->distinct()->count('contact_email');
        }

        foreach ($phones->chunk(500) as $chunk) {
            $count += $before()->whereNull('contact_email')->whereIn('contact_phone', $chunk->all())->distinct()->count('contact_phone');
        }

        return $count;
    }

    /** @return array{pct: ?float, dir: string} */
    public static function delta(float|int $current, float|int $previous): array
    {
        if ($previous == 0) {
            return ['pct' => null, 'dir' => $current > 0 ? 'new' : 'flat'];
        }

        $pct = ($current - $previous) / abs($previous) * 100;

        return ['pct' => round($pct, 1), 'dir' => abs($pct) < 0.05 ? 'flat' : ($pct > 0 ? 'up' : 'down')];
    }

    protected function kpiCards(array $cur, array $prev): array
    {
        $card = fn (string $key, string $label, string $format, string $help, string $icon, string $tint, bool $goodWhenUp = true) => [
            'key' => $key, 'label' => $label, 'format' => $format, 'help' => $help, 'icon' => $icon, 'tint' => $tint,
            'value' => $cur[$key], 'previous' => $prev[$key], 'good_when_up' => $goodWhenUp,
            'delta' => self::delta($cur[$key], $prev[$key]),
        ];

        return [
            $card('paid_revenue', 'Paid revenue', 'money', 'Money from orders marked as paid.', 'currency', 'bg-sage-50 text-sage-700'),
            $card('order_value', 'Total order value', 'money', 'Everything customers ordered, except cancelled orders.', 'banknotes', 'bg-gold-50 text-gold-700'),
            $card('orders', 'Orders placed', 'number', 'Number of orders received (including cancelled).', 'cart', 'bg-slate-100 text-slate-700'),
            $card('aov', 'Average order', 'money', 'Order value ÷ orders. Higher means customers buy more per order.', 'chart', 'bg-terracotta-50 text-terracotta-600'),
            $card('items_sold', 'Items sold', 'number', 'Total pieces sold in non-cancelled orders.', 'cube', 'bg-sage-50 text-sage-700'),
            $card('new_customers', 'New customers', 'number', 'People buying from you for the first time (guests included).', 'users', 'bg-slate-100 text-slate-700'),
            $card('returning_customers', 'Returning customers', 'number', 'Buyers (guests included) who had ordered before this period.', 'heart', 'bg-terracotta-50 text-terracotta-600'),
            $card('cancel_rate', 'Cancellation rate', 'percent', 'Share of orders that were cancelled. Lower is better.', 'x-circle', 'bg-red-50 text-red-600', false),
        ];
    }

    /** Continuous (zero-filled) series so quiet days still show on the chart. */
    protected function trend(Collection $orders, Carbon $start, Carbon $end, string $granularity): array
    {
        $key = fn (CarbonInterface $d) => $granularity === 'month' ? $d->format('Y-m') : $d->format('Y-m-d');
        $labelFor = fn (CarbonInterface $d) => $granularity === 'month' ? $d->format('M Y') : $d->format('d M');

        $buckets = [];
        $cursor = $granularity === 'month' ? $start->copy()->startOfMonth() : $start->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $buckets[$key($cursor)] = ['label' => $labelFor($cursor), 'order_value' => 0.0, 'paid' => 0.0, 'orders' => 0];
            $granularity === 'month' ? $cursor->addMonthNoOverflow() : $cursor->addDay();
        }

        foreach ($orders as $order) {
            $k = $key($order->created_at);

            if (! isset($buckets[$k])) {
                continue;
            }

            $buckets[$k]['orders']++;

            if ($order->status !== OrderStatus::Cancelled) {
                $buckets[$k]['order_value'] += (float) $order->total;
            }

            if ($order->payment_status === PaymentStatus::Paid) {
                $buckets[$k]['paid'] += (float) $order->total;
            }
        }

        $rows = array_values($buckets);

        return [
            'labels' => array_column($rows, 'label'),
            'order_value' => array_map(fn ($v) => round($v, 2), array_column($rows, 'order_value')),
            'paid' => array_map(fn ($v) => round($v, 2), array_column($rows, 'paid')),
            'orders' => array_column($rows, 'orders'),
            'granularity' => $granularity,
        ];
    }

    protected function weekday(Collection $valid): array
    {
        $names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $orders = array_fill(0, 7, 0);
        $value = array_fill(0, 7, 0.0);

        foreach ($valid as $order) {
            $i = $order->created_at->dayOfWeekIso - 1;
            $orders[$i]++;
            $value[$i] += (float) $order->total;
        }

        return ['labels' => $names, 'orders' => $orders, 'value' => array_map(fn ($v) => round($v, 2), $value)];
    }

    protected function hours(Collection $valid): array
    {
        $orders = array_fill(0, 24, 0);

        foreach ($valid as $order) {
            $orders[(int) $order->created_at->format('G')]++;
        }

        return [
            'labels' => array_map(fn ($h) => date('ga', mktime($h, 0, 0, 1, 1, 2000)), range(0, 23)),
            'orders' => $orders,
        ];
    }

    protected function statusBreakdown(Collection $orders): array
    {
        $rows = $orders->groupBy(fn (Order $o) => $o->status->value)->map(fn ($group, $status) => [
            'label' => OrderStatus::tryFrom($status)?->label() ?? ucfirst($status),
            'color' => OrderStatus::tryFrom($status)?->color() ?? 'slate',
            'count' => $group->count(),
            'value' => (float) $group->sum('total'),
        ])->sortByDesc('count')->values();

        return ['rows' => $rows->all()];
    }

    protected function methodBreakdown(Collection $valid): array
    {
        $rows = $valid->groupBy(fn (Order $o) => $o->payment_method->value)->map(fn ($group, $method) => [
            'label' => PaymentMethod::tryFrom($method)?->label() ?? ucfirst($method),
            'count' => $group->count(),
            'value' => (float) $group->sum('total'),
        ])->sortByDesc('count')->values();

        return ['rows' => $rows->all()];
    }

    protected function paymentStatusBreakdown(Collection $orders): array
    {
        $rows = $orders->groupBy(fn (Order $o) => $o->payment_status->value)->map(fn ($group, $status) => [
            'label' => PaymentStatus::tryFrom($status)?->label() ?? ucfirst($status),
            'count' => $group->count(),
            'value' => (float) $group->sum('total'),
        ])->sortByDesc('count')->values();

        return ['rows' => $rows->all()];
    }

    protected function cities(Collection $valid): array
    {
        $rows = $valid
            ->groupBy(fn (Order $o) => ucwords(strtolower(trim((string) ($o->shipping_address['city'] ?? 'Unknown')))) ?: 'Unknown')
            ->map(fn ($group, $city) => ['label' => $city, 'count' => $group->count(), 'value' => (float) $group->sum('total')])
            ->sortByDesc('count')
            ->take(8)
            ->values();

        return ['rows' => $rows->all()];
    }

    protected function topProducts(Carbon $start, Carbon $end): array
    {
        $rows = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->whereBetween('created_at', [$start, $end])->where('status', '!=', OrderStatus::Cancelled->value))
            ->select('product_name', 'product_sku', DB::raw('sum(quantity) as units'), DB::raw('sum(line_total) as revenue'))
            ->groupBy('product_name', 'product_sku')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['label' => $r->product_name, 'sku' => $r->product_sku, 'units' => (int) $r->units, 'value' => (float) $r->revenue]);

        return ['rows' => $rows->all()];
    }

    protected function categories(Carbon $start, Carbon $end): array
    {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->groupBy('categories.id', 'categories.name')
            ->selectRaw('categories.name as name, sum(order_items.line_total) as revenue, sum(order_items.quantity) as units')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get()
            ->map(fn ($r) => ['label' => $r->name, 'units' => (int) $r->units, 'value' => (float) $r->revenue]);

        return ['rows' => $rows->all()];
    }

    protected function audience(Carbon $start, Carbon $end, string $granularity): array
    {
        $key = fn (CarbonInterface $d) => $granularity === 'month' ? $d->format('Y-m') : $d->format('Y-m-d');
        $label = fn (CarbonInterface $d) => $granularity === 'month' ? $d->format('M Y') : $d->format('d M');

        $buckets = [];
        $cursor = $granularity === 'month' ? $start->copy()->startOfMonth() : $start->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $buckets[$key($cursor)] = ['label' => $label($cursor), 'customers' => 0, 'subscribers' => 0];
            $granularity === 'month' ? $cursor->addMonthNoOverflow() : $cursor->addDay();
        }

        User::role('customer')->whereBetween('created_at', [$start, $end])->pluck('created_at')
            ->each(function ($at) use (&$buckets, $key) {
                if (isset($buckets[$key($at)])) {
                    $buckets[$key($at)]['customers']++;
                }
            });

        NewsletterSubscriber::whereBetween('created_at', [$start, $end])->pluck('created_at')
            ->each(function ($at) use (&$buckets, $key) {
                if (isset($buckets[$key($at)])) {
                    $buckets[$key($at)]['subscribers']++;
                }
            });

        $rows = array_values($buckets);

        return [
            'labels' => array_column($rows, 'label'),
            'customers' => array_column($rows, 'customers'),
            'subscribers' => array_column($rows, 'subscribers'),
        ];
    }

    protected function inventoryHealth(): array
    {
        $row = ProductVariant::query()
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->whereNull('products.deleted_at')
            ->where('product_variants.is_active', true)
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when product_variants.stock_quantity <= 0 then 1 else 0 end) as out_count')
            ->selectRaw('sum(case when product_variants.stock_quantity > 0 and product_variants.stock_quantity <= products.low_stock_threshold then 1 else 0 end) as low_count')
            ->first();

        $total = (int) ($row->total ?? 0);
        $out = (int) ($row->out_count ?? 0);
        $low = (int) ($row->low_count ?? 0);

        return [
            'total' => $total,
            'out' => $out,
            'low' => $low,
            'healthy' => max(0, $total - $out - $low),
            'attention' => $this->inventory->lowStockProducts(6),
        ];
    }

    protected function wishlistDemand(): array
    {
        $rows = WishlistItem::query()
            ->join('products', 'products.id', '=', 'wishlist_items.product_id')
            ->whereNull('products.deleted_at')
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.name as name, count(*) as saves')
            ->orderByDesc('saves')
            ->limit(8)
            ->get()
            ->map(fn ($r) => ['label' => $r->name, 'count' => (int) $r->saves]);

        return ['rows' => $rows->all()];
    }

    protected function restockWaiting(): array
    {
        $rows = StockAlert::query()
            ->join('products', 'products.id', '=', 'stock_alerts.product_id')
            ->whereNull('stock_alerts.notified_at')
            ->groupBy('products.id', 'products.name')
            ->selectRaw('products.name as name, count(*) as waiting')
            ->orderByDesc('waiting')
            ->limit(6)
            ->get()
            ->map(fn ($r) => ['label' => $r->name, 'count' => (int) $r->waiting]);

        return ['rows' => $rows->all()];
    }

    protected function customOrders(Carbon $start, Carbon $end): array
    {
        $counts = CustomOrder::whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status');

        $rows = [];
        foreach (CustomOrder::STATUSES as $status => $label) {
            $rows[] = ['label' => $label, 'count' => (int) ($counts[$status] ?? 0)];
        }

        return [
            'rows' => $rows,
            'in_range' => array_sum(array_column($rows, 'count')),
            'open' => CustomOrder::whereIn('status', ['new', 'quoted'])->count(),
            'quoted_value' => (float) CustomOrder::whereBetween('created_at', [$start, $end])->whereIn('status', ['quoted', 'accepted', 'completed'])->sum('quote_amount'),
        ];
    }

    protected function reviews(Carbon $start, Carbon $end): array
    {
        $distribution = Review::where('is_approved', true)->whereBetween('created_at', [$start, $end])
            ->select('rating', DB::raw('count(*) as c'))->groupBy('rating')->pluck('c', 'rating');

        $count = (int) $distribution->sum();

        return [
            'counts' => array_map(fn ($star) => (int) ($distribution[$star] ?? 0), [1, 2, 3, 4, 5]),
            'count' => $count,
            'average' => $count ? round(array_sum(array_map(fn ($s) => $s * (int) ($distribution[$s] ?? 0), [1, 2, 3, 4, 5])) / $count, 2) : null,
            'pending' => Review::where('is_approved', false)->count(),
        ];
    }

    protected function lifetimeTotals(): array
    {
        $orders = Order::query()
            ->selectRaw('count(*) as orders_count')
            ->selectRaw("sum(case when payment_status = 'paid' then total else 0 end) as paid_revenue")
            ->first();

        return [
            'paid_revenue' => (float) ($orders->paid_revenue ?? 0),
            'orders' => (int) ($orders->orders_count ?? 0),
            // Everyone who has ever ordered: one per email (or phone when there is no email), guests included.
            'customers' => (int) Order::whereNotNull('contact_email')->distinct()->count('contact_email')
                + (int) Order::whereNull('contact_email')->whereNotNull('contact_phone')->distinct()->count('contact_phone'),
            'accounts' => User::role('customer')->count(),
            'products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'archived_products' => Product::onlyTrashed()->count(),
        ];
    }

    /** Plain-language takeaways shown at the top of the page. */
    protected function insights(array $d): array
    {
        $cur = $d['current'];
        $prev = $d['previous'];
        $lines = [];

        if ($cur['orders'] === 0) {
            return ['No orders were placed in this period yet. Pick a longer range, or share your shop to get the first order in.'];
        }

        $revenueDelta = self::delta($cur['order_value'], $prev['order_value']);
        $lines[] = match (true) {
            $revenueDelta['dir'] === 'new' => 'You took '.shop_money($cur['order_value']).' in orders — nothing was sold in the previous period to compare with.',
            $revenueDelta['dir'] === 'up' => 'Sales are up '.$revenueDelta['pct'].'% compared with the previous period ('.shop_money($cur['order_value']).' vs '.shop_money($prev['order_value']).').',
            $revenueDelta['dir'] === 'down' => 'Sales are down '.abs($revenueDelta['pct']).'% compared with the previous period ('.shop_money($cur['order_value']).' vs '.shop_money($prev['order_value']).').',
            default => 'Sales are steady compared with the previous period ('.shop_money($cur['order_value']).').',
        };

        $weekdayOrders = $d['weekday']['orders'];
        if (max($weekdayOrders) > 0) {
            $best = array_search(max($weekdayOrders), $weekdayOrders, true);
            $lines[] = 'Your busiest day is '.['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$best].' ('.max($weekdayOrders).' '.str('order')->plural(max($weekdayOrders)).').';
        }

        if ($top = ($d['topProducts']['rows'][0] ?? null)) {
            $lines[] = 'Best seller: '.$top['label'].' — '.$top['units'].' sold for '.shop_money($top['value']).'.';
        }

        if ($city = ($d['cities']['rows'][0] ?? null)) {
            $lines[] = 'Most orders ship to '.$city['label'].' ('.$city['count'].').';
        }

        if ($cur['buyers'] > 0) {
            $lines[] = $cur['returning_customers'] === 0
                ? 'All '.$cur['buyers'].' '.str('buyer')->plural($cur['buyers']).' were new to you ('.$cur['guest_buyers'].' checked out as guests).'
                : $cur['returning_customers'].' of '.$cur['buyers'].' buyers had ordered from you before ('.$cur['guest_buyers'].' checked out as guests).';
        }

        if ($cur['cancel_rate'] >= 10) {
            $lines[] = 'Heads up: '.round($cur['cancel_rate'], 1).'% of orders were cancelled. Check the Orders page for the reasons.';
        }

        if ($d['inventory']['out'] > 0 || $d['inventory']['low'] > 0) {
            $lines[] = $d['inventory']['out'].' sizes are sold out and '.$d['inventory']['low'].' are running low — restock before your next busy day.';
        }

        return $lines;
    }
}
