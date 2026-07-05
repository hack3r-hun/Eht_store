<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    public function index(Request $request): View
    {
        [$start, $end, $period] = $this->dateRange($request);

        $ordersInRange = Order::whereBetween('created_at', [$start, $end]);
        $paidOrdersInRange = (clone $ordersInRange)->where('payment_status', PaymentStatus::Paid);

        $summary = [
            'orders' => (clone $ordersInRange)->count(),
            'paid_revenue' => (float) (clone $paidOrdersInRange)->sum('total'),
            'average_order' => (float) (clone $ordersInRange)->avg('total'),
            'new_customers' => User::role('customer')->whereBetween('created_at', [$start, $end])->count(),
            'products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'low_stock' => Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            'messages' => ContactMessage::whereBetween('created_at', [$start, $end])->count(),
        ];

        $statusBreakdown = (clone $ordersInRange)
            ->select('status', DB::raw('count(*) as orders_count'), DB::raw('sum(total) as revenue'))
            ->groupBy('status')
            ->orderByDesc('orders_count')
            ->get();

        $paymentBreakdown = (clone $ordersInRange)
            ->select('payment_method', 'payment_status', DB::raw('count(*) as orders_count'), DB::raw('sum(total) as revenue'))
            ->groupBy('payment_method', 'payment_status')
            ->orderByDesc('orders_count')
            ->get();

        $topProducts = OrderItem::query()
            ->whereHas('order', fn ($query) => $query->whereBetween('created_at', [$start, $end]))
            ->select('product_name', 'product_sku', DB::raw('sum(quantity) as units_sold'), DB::raw('sum(line_total) as revenue'))
            ->groupBy('product_name', 'product_sku')
            ->orderByDesc('revenue')
            ->take(10)
            ->get();

        $bucketExpression = $this->bucketExpression($period);

        $trend = Order::whereBetween('created_at', [$start, $end])
            ->selectRaw("{$bucketExpression} as bucket")
            ->selectRaw('count(*) as orders_count, sum(total) as revenue')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return view('admin.statistics.index', compact(
            'summary',
            'statusBreakdown',
            'paymentBreakdown',
            'topProducts',
            'trend',
            'start',
            'end',
            'period',
        ));
    }

    protected function dateRange(Request $request): array
    {
        $period = $request->get('period', 'month');
        $now = now();

        return match ($period) {
            'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'day'],
            'year' => [
                Carbon::create((int) $request->input('year', $now->year), 1, 1)->startOfYear(),
                Carbon::create((int) $request->input('year', $now->year), 1, 1)->endOfYear(),
                'year',
            ],
            'custom' => [
                Carbon::parse($request->input('date_from', $now->copy()->startOfMonth()->toDateString()))->startOfDay(),
                Carbon::parse($request->input('date_to', $now->copy()->endOfMonth()->toDateString()))->endOfDay(),
                'custom',
            ],
            default => [
                Carbon::parse($request->input('month', $now->format('Y-m')).'-01')->startOfMonth(),
                Carbon::parse($request->input('month', $now->format('Y-m')).'-01')->endOfMonth(),
                'month',
            ],
        };
    }

    protected function bucketExpression(string $period): string
    {
        $driver = DB::connection()->getDriverName();

        if ($period === 'year') {
            return $driver === 'sqlite'
                ? "strftime('%Y-%m', created_at)"
                : "DATE_FORMAT(created_at, '%Y-%m')";
        }

        return $driver === 'sqlite'
            ? 'date(created_at)'
            : 'DATE(created_at)';
    }
}
