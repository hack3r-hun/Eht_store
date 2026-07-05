<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = [
            'shop_name' => Setting::get('shop_name', config('shop.name')),
            'shop_tagline' => Setting::get('shop_tagline', config('shop.tagline')),
            'tax_rate' => Setting::get('tax_rate', config('shop.tax_rate')),
            'shipping_flat' => Setting::get('shipping_flat', config('shop.shipping_flat')),
            'free_shipping_threshold' => Setting::get('free_shipping_threshold', config('shop.free_shipping_threshold')),
            'contact_email' => Setting::get('contact_email', config('shop.contact_email')),
            'contact_phone' => Setting::get('contact_phone', config('shop.contact_phone')),
            'contact_address' => Setting::get('contact_address', config('shop.contact_address')),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shop_name' => ['required', 'string', 'max:255'],
            'shop_tagline' => ['nullable', 'string', 'max:500'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'shipping_flat' => ['nullable', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_address' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Settings saved.');
    }
}
