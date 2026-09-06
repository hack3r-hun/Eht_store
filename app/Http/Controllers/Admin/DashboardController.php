<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function index()
    {
        $stats = Cache::remember('admin.dashboard.stats', 60, function () {
            $todayStart = today()->startOfDay();
            $tomorrowStart = today()->addDay()->startOfDay();

            $orderCounts = Order::query()
                ->selectRaw('count(*) as total_orders')
                ->selectRaw('sum(case when created_at >= ? and created_at < ? then 1 else 0 end) as orders_today', [
                    $todayStart,
                    $tomorrowStart,
                ])
                ->selectRaw("sum(case when status in ('pending', 'awaiting_cod') then 1 else 0 end) as pending_orders")
                ->selectRaw("sum(case when payment_status = 'paid' then total else 0 end) as revenue")
                ->first();

            $productCounts = Product::query()
                ->selectRaw('count(*) as total_products')
                ->selectRaw('sum(case when is_active = 1 then 1 else 0 end) as active_products')
                ->first();

            return [
                'total_orders' => (int) ($orderCounts->total_orders ?? 0),
                'orders_today' => (int) ($orderCounts->orders_today ?? 0),
                'pending_orders' => (int) ($orderCounts->pending_orders ?? 0),
                'total_products' => (int) ($productCounts->total_products ?? 0),
                'active_products' => (int) ($productCounts->active_products ?? 0),
                'archived_products' => Product::onlyTrashed()->count(),
                'total_customers' => User::role('customer')->count(),
                'unread_messages' => ContactMessage::where('is_read', false)->count(),
                // Per-variant aware: Product.stock_quantity is a rolled-up sum across sizes,
                // so a whereColumn comparison here would miss a single low-stock size.
                'low_stock' => $this->inventoryService->lowStockCount(),
                'revenue' => (float) ($orderCounts->revenue ?? 0),
            ];
        });

        $recentOrders = Order::with('user')->latest()->take(5)->get();
        $lowStockProducts = $this->inventoryService->lowStockProducts(5);
        $sparklines = Cache::remember('admin.dashboard.sparklines', 60, fn () => $this->trendSparklines());

        return view('admin.dashboard', compact('stats', 'recentOrders', 'lowStockProducts', 'sparklines'));
    }

    /**
     * Last 14 days of daily revenue/order counts for the dashboard stat card
     * sparklines. Mirrors StatisticsController's day-level bucket expression
     * (only the "day" branch is needed here — dashboard sparklines are always
     * a 14-day daily trend, never year-bucketed).
     */
    protected function trendSparklines(): array
    {
        $start = today()->subDays(13)->startOfDay();
        $end = today()->endOfDay();
        $bucket = DB::connection()->getDriverName() === 'sqlite' ? 'date(created_at)' : 'DATE(created_at)';

        $rows = Order::whereBetween('created_at', [$start, $end])
            ->selectRaw("{$bucket} as bucket")
            ->selectRaw('count(*) as orders_count, sum(total) as revenue')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->keyBy(fn ($row) => (string) $row->bucket);

        $revenue = [];
        $orders = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $row = $rows->get($day->toDateString());
            $revenue[] = (float) ($row->revenue ?? 0);
            $orders[] = (int) ($row->orders_count ?? 0);
        }

        return ['revenue' => $revenue, 'orders' => $orders];
    }
}
