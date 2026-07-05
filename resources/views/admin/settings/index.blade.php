@extends('layouts.admin')

@section('page-title', 'Shop Settings')

@section('content')
    <form action="{{ route('admin.settings.update') }}" method="POST" class="max-w-xl space-y-6">
        @csrf @method('PUT')

        <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6 space-y-4">
            @foreach([
                'shop_name' => 'Store Name',
                'shop_tagline' => 'Tagline',
                'contact_email' => 'Contact Email',
                'contact_phone' => 'Contact Phone',
                'contact_address' => 'Address',
            ] as $key => $label)
                <div>
                    <label class="block text-sm font-medium mb-2">{{ $label }}</label>
                    <input type="text" name="{{ $key }}" value="{{ old($key, $settings[$key]) }}" class="input-field">
                </div>
            @endforeach
            <div>
                <label class="block text-sm font-medium mb-2">Tax Rate (%)</label>
                <input type="number" step="0.01" name="tax_rate" value="{{ old('tax_rate', $settings['tax_rate']) }}" class="input-field">
            </div>
            <div>
                <p class="block text-sm font-semibold mb-2">Shipping Charges (by delivery zone)</p>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Karachi (Local)</label>
                        <input type="number" step="0.01" name="shipping_local" value="{{ old('shipping_local', $settings['shipping_local']) }}" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Major Cities</label>
                        <input type="number" step="0.01" name="shipping_standard" value="{{ old('shipping_standard', $settings['shipping_standard']) }}" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Rest of Pakistan</label>
                        <input type="number" step="0.01" name="shipping_remote" value="{{ old('shipping_remote', $settings['shipping_remote']) }}" class="input-field">
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-1">Auto-applied at checkout from the customer's city. Major cities: Lahore, Islamabad, Rawalpindi, Faisalabad, Multan, Hyderabad, Peshawar, Quetta, Sialkot, Gujranwala.</p>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Free Shipping Above</label>
                <input type="number" step="0.01" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $settings['free_shipping_threshold']) }}" class="input-field">
                <p class="text-xs text-slate-500 mt-1">Orders at or above this subtotal ship free. Set to 0 to disable.</p>
            </div>
            <p class="text-xs text-slate-500">Stripe keys are configured via .env (STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET)</p>
        </div>

        <button type="submit" class="btn-primary">Save Settings</button>
    </form>
@endsection
