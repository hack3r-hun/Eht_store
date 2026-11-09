<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Mail\CustomOrderReceived;
use App\Models\CustomOrder;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CustomOrderController extends Controller
{
    public function create()
    {
        return view('storefront.custom-order', ['page' => Page::forSlug('custom-order')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'item_type' => ['required', 'string', 'max:120'],
            'colors' => ['nullable', 'string', 'max:200'],
            'size' => ['nullable', 'string', 'max:120'],
            'budget' => ['nullable', 'string', 'max:60'],
            'needed_by' => ['nullable', 'date', 'after:today'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'reference_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if ($request->hasFile('reference_image')) {
            $data['reference_image'] = $request->file('reference_image')->store('custom-orders', 'public');
        }

        $order = CustomOrder::create($data + ['user_id' => $request->user()?->id]);

        if ($adminEmail = shop_config('contact_email')) {
            try {
                Mail::to($adminEmail)->send(new CustomOrderReceived($order));
            } catch (\Throwable $e) {
                Log::warning('Custom order email failed: '.$e->getMessage(), ['custom_order_id' => $order->id]);
            }
        }

        return redirect()->route('custom-order.create')
            ->with('success', 'Request received! We will review your idea and send you a quote soon.');
    }
}
