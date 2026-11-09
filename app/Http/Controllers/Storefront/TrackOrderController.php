<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Page;
use Illuminate\Http\Request;

class TrackOrderController extends Controller
{
    public function form()
    {
        return view('storefront.track-order', ['order' => null, 'page' => Page::forSlug('track-order')]);
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:40'],
            'contact' => ['required', 'string', 'max:255'],
        ]);

        $order = Order::with(['items', 'user'])
            ->where('order_number', strtoupper(trim($data['order_number'])))
            ->first();

        // Same message whether the order is missing or the contact detail doesn't match,
        // so the form can't be used to probe which order numbers exist.
        if (! $order || ! $this->contactMatches($order, $data['contact'])) {
            return back()->withInput()->withErrors(['order_number' => 'We could not find an order matching those details.']);
        }

        return view('storefront.track-order', ['order' => $order, 'page' => Page::forSlug('track-order')]);
    }

    protected function contactMatches(Order $order, string $contact): bool
    {
        $contact = strtolower(trim($contact));
        $address = $order->shipping_address ?? [];

        $emails = array_filter([strtolower((string) ($address['email'] ?? '')), strtolower((string) $order->user?->email)]);

        if (in_array($contact, $emails, true)) {
            return true;
        }

        $digits = preg_replace('/\D+/', '', $contact);
        $phone = preg_replace('/\D+/', '', (string) ($address['phone'] ?? ''));

        return strlen($digits) >= 7 && $phone !== '' && substr($digits, -9) === substr($phone, -9);
    }
}
