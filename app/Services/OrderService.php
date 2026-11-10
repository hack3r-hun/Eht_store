<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
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
            // Lock the row so two admins (or the expiry job) can't restock the same order twice.
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $previous = $order->status;

            // Cancelling returns the stock; reviving a cancelled order would ship goods that
            // were already put back on the shelf (and a second cancel would restock them again).
            if ($previous === OrderStatus::Cancelled && $status !== OrderStatus::Cancelled) {
                throw new \RuntimeException('A cancelled order cannot be reopened. Ask the customer to place a new order.');
            }

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
        foreach ($order->items()->with(['product', 'variant'])->get() as $item) {
            if (! $item->product) {
                continue;
            }

            $this->inventoryService->restoreVariantStock($item->variant, $item->product, $item->quantity);
        }
    }

    public function generateInvoicePdf(Order $order): Response
    {
        return app(InvoiceBuilder::class)->build($order)->download("invoice-{$order->order_number}.pdf");
    }

    public function streamInvoicePdf(Order $order): Response
    {
        return app(InvoiceBuilder::class)->build($order)->stream("invoice-{$order->order_number}.pdf");
    }
}
