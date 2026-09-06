<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\OrderResource;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends ApiController
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function index(): JsonResponse
    {
        // Matches the web admin dashboard's Cache::remember('admin.dashboard.stats', ...) —
        // previously this endpoint ran ~9 uncached count/sum queries on every single call.
        $stats = Cache::remember('admin.dashboard.stats.api', 60, function () {
            return [
                'total_orders' => Order::count(),
                'orders_today' => Order::whereDate('created_at', today())->count(),
                'pending_orders' => Order::whereIn('status', ['pending', 'awaiting_cod'])->count(),
                'total_products' => Product::count(),
                'active_products' => Product::where('is_active', true)->count(),
                'archived_products' => Product::onlyTrashed()->count(),
                'total_customers' => User::role('customer')->count(),
                'unread_messages' => ContactMessage::where('is_read', false)->count(),
                // Per-variant aware: Product.stock_quantity is a rolled-up sum across sizes,
                // so a whereColumn comparison here would miss a single low-stock size.
                'low_stock' => $this->inventoryService->lowStockCount(),
                'revenue' => Order::where('payment_status', 'paid')->sum('total'),
            ];
        });

        $recentOrders = Order::with('user')->latest()->take(5)->get();
        $lowStockProducts = $this->inventoryService->lowStockProducts(5);

        return $this->success([
            'stats' => $stats,
            'recent_orders' => OrderResource::collection($recentOrders),
            'low_stock_products' => $lowStockProducts,
        ]);
    }
}
