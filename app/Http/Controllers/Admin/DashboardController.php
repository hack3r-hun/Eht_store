<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\CustomOrder;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\StockAlert;
use App\Services\InventoryService;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The dashboard answers "what do I need to do right now?" (today's numbers, a to-do list,
 * live stock). Trends, comparisons and breakdowns live on the Statistics page.
 */
class DashboardController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function index(Request $request)
    {
        // The refresh button asks for live numbers, so skip the short-lived caches.
        if ($request->boolean('fresh')) {
            Cache::forget('admin.dashboard.stats');
            Cache::forget('admin.dashboard.sparklines');
            Cache::forget('admin.sidebar.badges');
        }

        $stats = Cache::remember('admin.dashboard.stats', 60, function () {
            $nonCancelled = fn ($query) => $query->where('status', '!=', 'cancelled');

            $salesToday = (float) Order::whereBetween('created_at', [today()->startOfDay(), today()->endOfDay()])->where($nonCancelled)->sum('total');
            $salesYesterday = (float) Order::whereBetween('created_at', [today()->subDay()->startOfDay(), today()->subDay()->endOfDay()])->where($nonCancelled)->sum('total');

            return [
                'sales_today' => $salesToday,
                'sales_delta' => StatisticsService::delta($salesToday, $salesYesterday),
                'orders_total' => Order::count(),
                'orders_today' => Order::whereBetween('created_at', [today()->startOfDay(), today()->endOfDay()])->count(),
                'to_confirm' => Order::whereIn('status', ['pending', 'awaiting_cod'])->count(),
                'to_ship' => Order::whereIn('status', ['paid', 'processing'])->count(),
                'attention' => [
                    'unread_messages' => ContactMessage::where('is_read', false)->count(),
                    'new_custom_orders' => CustomOrder::where('status', 'new')->count(),
                    'pending_reviews' => Review::where('is_approved', false)->count(),
                    // Per-variant aware: Product.stock_quantity is a rolled-up sum across sizes.
                    'low_stock' => $this->inventoryService->lowStockCount(),
                    'sold_out_sizes' => ProductVariant::where('product_variants.is_active', true)
                        ->join('products', 'products.id', '=', 'product_variants.product_id')
                        ->whereNull('products.deleted_at')
                        ->where('product_variants.stock_quantity', '<=', 0)
                        ->count(),
                    'restock_requests' => StockAlert::whereNull('notified_at')->count(),
                ],
            ];
        });

        $recentOrders = Order::with('user')->latest()->take(6)->get();
        $lowStockProducts = $this->inventoryService->lowStockProducts(5);
        $sparklines = Cache::remember('admin.dashboard.sparklines', 60, fn () => $this->trendSparklines());

        return view('admin.dashboard', compact('stats', 'recentOrders', 'lowStockProducts', 'sparklines'));
    }

    /**
     * Last 14 days of daily revenue/order counts for the "last 14 days" sparklines.
     * (Mirrors the day bucketing used by the statistics page.)
     */
    protected function trendSparklines(): array
    {
        $start = today()->subDays(13)->startOfDay();
        $end = today()->endOfDay();
        $bucket = DB::connection()->getDriverName() === 'sqlite' ? 'date(created_at)' : 'DATE(created_at)';

        $rows = Order::whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'cancelled')
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

        return ['revenue' => $revenue, 'orders' => $orders, 'revenue_total' => array_sum($revenue), 'orders_total' => array_sum($orders)];
    }
}
