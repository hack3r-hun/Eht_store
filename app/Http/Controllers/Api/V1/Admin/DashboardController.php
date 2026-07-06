<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\OrderResource;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends ApiController
{
    public function index(): JsonResponse
    {
        $stats = [
            'total_orders' => Order::count(),
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'pending_orders' => Order::whereIn('status', ['pending', 'awaiting_cod'])->count(),
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'archived_products' => Product::onlyTrashed()->count(),
            'total_customers' => User::role('customer')->count(),
            'unread_messages' => ContactMessage::where('is_read', false)->count(),
            'low_stock' => Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            'revenue' => Order::where('payment_status', 'paid')->sum('total'),
        ];

        $recentOrders = Order::with('user')->latest()->take(5)->get();
        $lowStockProducts = Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->take(5)
            ->get(['id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold']);

        return $this->success([
            'stats' => $stats,
            'recent_orders' => OrderResource::collection($recentOrders),
            'low_stock_products' => $lowStockProducts,
        ]);
    }
}
