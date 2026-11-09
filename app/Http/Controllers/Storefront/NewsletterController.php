<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);

        NewsletterSubscriber::firstOrCreate(['email' => strtolower($data['email'])]);

        // Hand out the welcome code only while the coupon module is on.
        $coupon = module_enabled('coupons')
            ? Coupon::where('code', 'WELCOME10')->where('is_active', true)->first()
            : null;

        return response()->json([
            'message' => 'Thanks for subscribing!',
            'coupon' => $coupon?->code,
            'coupon_label' => $coupon ? $coupon->label().($coupon->min_subtotal > 0 ? ' orders over '.shop_money($coupon->min_subtotal) : '') : null,
        ]);
    }
}
