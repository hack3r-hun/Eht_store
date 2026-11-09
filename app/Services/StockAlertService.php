<?php

namespace App\Services;

use App\Mail\BackInStockMail;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class StockAlertService
{
    public static function notifyRestocked(ProductVariant $variant): void
    {
        if (! module_enabled('back_in_stock')) {
            return;
        }

        $product = $variant->product;

        if (! $product || ! $product->is_active) {
            return;
        }

        $alerts = StockAlert::where('product_id', $variant->product_id)
            ->whereNull('notified_at')
            ->where(fn ($q) => $q->whereNull('product_variant_id')->orWhere('product_variant_id', $variant->id))
            ->get();

        foreach ($alerts as $alert) {
            try {
                Mail::to($alert->email)->send(new BackInStockMail($product, $variant));
                $alert->update(['notified_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning('Back-in-stock email failed: '.$e->getMessage(), ['alert_id' => $alert->id]);
            }
        }
    }
}
