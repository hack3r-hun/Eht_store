<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderController extends ApiController
{
    public function __construct(protected OrderService $orderService) {}

    public function index(Request $request): JsonResponse
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

        $perPage = $this->perPage($request);
        $orders = $query->paginate($perPage);

        return $this->paginated($orders, OrderResource::collection($orders));
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['items.product', 'user', 'payment']);

        return $this->success(new OrderResource($order));
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:'.implode(',', array_column(OrderStatus::cases(), 'value'))],
        ]);

        $this->orderService->updateStatus($order, OrderStatus::from($request->status));

        return $this->success(new OrderResource($order->fresh(['items', 'payment', 'user'])));
    }

    public function updateShipping(Request $request, Order $order): JsonResponse
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            return $this->error('Shipping cannot be changed on a paid order.', 422);
        }

        $data = $request->validate([
            'shipping' => ['required', 'numeric', 'min:0', 'max:100000'],
        ]);

        $shipping = round((float) $data['shipping'], 2);
        $total = round((float) $order->subtotal + (float) $order->tax + $shipping, 2);

        $order->update(['shipping' => $shipping, 'total' => $total]);
        $order->payment?->update(['amount' => $total]);

        return $this->success(new OrderResource($order->fresh(['items', 'payment', 'user'])));
    }

    public function destroy(Order $order): JsonResponse
    {
        $orderNumber = $order->order_number;
        $this->orderService->deleteOrder($order);

        return $this->success(['message' => "Order {$orderNumber} deleted and stock returned."]);
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
