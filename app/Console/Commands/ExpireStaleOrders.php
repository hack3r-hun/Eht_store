<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;

class ExpireStaleOrders extends Command
{
    protected $signature = 'orders:expire-stale {--minutes=60 : Age in minutes before an unpaid Stripe order is cancelled}';

    protected $description = 'Cancel unpaid Stripe orders that were never completed and restore their stock';

    public function handle(OrderService $orderService): int
    {
        $cutoff = now()->subMinutes((int) $this->option('minutes'));

        $stale = Order::where('status', OrderStatus::Pending)
            ->where('payment_method', PaymentMethod::Stripe)
            ->where('payment_status', PaymentStatus::Pending)
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($stale as $order) {
            $orderService->updateStatus($order, OrderStatus::Cancelled);
            $this->info("Cancelled stale order {$order->order_number} and restored stock.");
        }

        $this->info("Expired {$stale->count()} stale order(s).");

        return self::SUCCESS;
    }
}
