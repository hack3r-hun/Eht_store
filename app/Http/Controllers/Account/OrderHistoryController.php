<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class OrderHistoryController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    public function index(): View
    {
        $orders = auth()->user()->orders()->with('items')->latest()->paginate(10);

        return view('account.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);
        $order->load('items');

        return view('account.orders.show', compact('order'));
    }

    public function invoice(Order $order): Response
    {
        $this->authorize('view', $order);

        return $this->orderService->generateInvoicePdf($order);
    }
}
