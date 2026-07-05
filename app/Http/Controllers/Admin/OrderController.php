<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    public function index(Request $request): View
    {
        $query = Order::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhere('shipping_address->full_name', 'like', "%{$search}%")
                    ->orWhere('shipping_address->email', 'like', "%{$search}%")
                    ->orWhere('shipping_address->phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        if ($request->filled('min_total') && is_numeric($request->min_total)) {
            $query->where('total', '>=', (float) $request->min_total);
        }

        if ($request->filled('max_total') && is_numeric($request->max_total)) {
            $query->where('total', '<=', (float) $request->max_total);
        }

        $perPage = $this->perPage($request);
        $orders = $query->paginate($perPage)->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'perPage' => $perPage,
            'orderStatuses' => OrderStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'user', 'payment']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:'.implode(',', array_column(OrderStatus::cases(), 'value'))],
        ]);

        $this->orderService->updateStatus($order, OrderStatus::from($request->status));

        return back()->with('success', 'Order status updated.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $orderNumber = $order->order_number;
        $this->orderService->deleteOrder($order);

        return redirect()->route('admin.orders.index')
            ->with('success', "Order {$orderNumber} deleted and stock returned.");
    }

    public function invoice(Order $order): Response
    {
        return $this->orderService->streamInvoicePdf($order);
    }

    protected function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 20);

        return in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;
    }
}
