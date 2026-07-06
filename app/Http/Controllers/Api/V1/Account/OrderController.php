<?php

namespace App\Http\Controllers\Api\V1\Account;

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
        $orders = $request->user()->orders()->with('items')->latest()->paginate(10);

        return $this->paginated($orders, OrderResource::collection($orders));
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        $order->load(['items', 'payment']);

        return $this->success(new OrderResource($order));
    }

    public function invoice(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);

        return $this->orderService->streamInvoicePdf($order);
    }
}
