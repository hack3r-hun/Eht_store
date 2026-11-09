<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:50']]);

        $subtotal = $this->cartService->subtotal();

        if ($subtotal <= 0) {
            return back()->with('error', 'Add something to your cart before applying a coupon.');
        }

        $coupon = Coupon::where('code', strtoupper(trim($request->code)))->first();

        if (! $coupon) {
            return back()->with('error', 'That coupon code is not valid.');
        }

        if ($problem = $coupon->problemFor($subtotal)) {
            return back()->with('error', $problem);
        }

        session(['coupon_code' => $coupon->code]);

        return back()->with('success', "Coupon {$coupon->code} applied — {$coupon->label()}.");
    }

    public function destroy(): RedirectResponse
    {
        session()->forget('coupon_code');

        return back()->with('success', 'Coupon removed.');
    }
}
