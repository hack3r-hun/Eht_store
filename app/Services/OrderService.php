<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        DB::transaction(function () use ($order, $status) {
            $previous = $order->status;

            $order->update(['status' => $status]);

            if ($status === OrderStatus::Delivered && $order->payment_method->value === 'cod') {
                $order->update(['payment_status' => PaymentStatus::Paid]);
            }

            if ($status === OrderStatus::Cancelled && $previous !== OrderStatus::Cancelled) {
                $this->restockItems($order);
            }
        });

        return $order->fresh();
    }

    public function deleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            // Return stock unless a cancellation already restored it.
            if ($order->status !== OrderStatus::Cancelled) {
                $this->restockItems($order);
            }

            $order->delete();
        });
    }

    protected function restockItems(Order $order): void
    {
        foreach ($order->items()->with('product')->get() as $item) {
            if ($item->product) {
                $this->inventoryService->restoreStock($item->product, $item->quantity);
            }
        }
    }

    public function generateInvoicePdf(Order $order): Response
    {
        $order->load(['items', 'user']);

        $pdf = Pdf::loadView('invoices.order', compact('order'));

        return $pdf->download("invoice-{$order->order_number}.pdf");
    }

    public function streamInvoicePdf(Order $order): Response
    {
        $order->load(['items', 'user']);

        $pdf = Pdf::loadView('invoices.order', compact('order'));

        return $pdf->stream("invoice-{$order->order_number}.pdf");
    }
}
