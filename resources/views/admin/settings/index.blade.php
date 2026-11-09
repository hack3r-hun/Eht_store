@extends('layouts.admin')

@section('page-title', 'Shop Settings')

@section('content')
    <form action="{{ route('admin.settings.update') }}" method="POST" class="max-w-xl space-y-6">
        @csrf @method('PUT')

        <div class="admin-card admin-card-pad space-y-4">
            @foreach([
                'shop_name' => 'Store Name',
                'shop_tagline' => 'Tagline',
                'contact_email' => 'Contact Email',
                'contact_phone' => 'Contact Phone',
                'contact_address' => 'Address',
            ] as $key => $label)
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ $label }}</label>
                    <input type="text" name="{{ $key }}" value="{{ old($key, $settings[$key]) }}" class="input-field">
                </div>
            @endforeach
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Tax Rate (%)</label>
                <input type="number" step="0.01" name="tax_rate" value="{{ old('tax_rate', $settings['tax_rate']) }}" class="input-field">
            </div>
            <div>
                <p class="block text-sm font-semibold text-slate-700 mb-2">Shipping Charges (by delivery zone)</p>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Karachi (Local)</label>
                        <input type="number" step="0.01" name="shipping_local" value="{{ old('shipping_local', $settings['shipping_local']) }}" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Major Cities</label>
                        <input type="number" step="0.01" name="shipping_standard" value="{{ old('shipping_standard', $settings['shipping_standard']) }}" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Rest of Pakistan</label>
                        <input type="number" step="0.01" name="shipping_remote" value="{{ old('shipping_remote', $settings['shipping_remote']) }}" class="input-field">
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-1">Auto-applied at checkout from the customer's city. Major cities: Lahore, Islamabad, Rawalpindi, Faisalabad, Multan, Hyderabad, Peshawar, Quetta, Sialkot, Gujranwala.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Free Shipping Above</label>
                <input type="number" step="0.01" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $settings['free_shipping_threshold']) }}" class="input-field">
                <p class="text-xs text-slate-500 mt-1">Orders at or above this subtotal ship free. Set to 0 to disable.</p>
            </div>
            <p class="text-xs text-slate-500">Stripe keys are configured via .env (STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET)</p>
        </div>

        <button type="submit" class="btn-primary">Save Settings</button>
    </form>

    {{-- Change password (separate form so it never submits the shop settings) --}}
    <form action="{{ route('admin.settings.password') }}" method="POST" class="max-w-xl mt-10" x-data="{ show: false }">
        @csrf @method('PUT')
        <div class="admin-card admin-card-pad space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-900">Change your password</h2>
                    <p class="text-sm text-slate-500 mt-0.5">Use a password you don't use anywhere else. {{ \App\Http\Controllers\Admin\AdminPasswordController::requirementsHint() }}</p>
                </div>
                <span class="w-10 h-10 shrink-0 rounded-xl bg-sage-50 text-sage-700 flex items-center justify-center"><x-icon name="shield" class="w-5 h-5" /></span>
            </div>

            @if($errors->updatePassword->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc list-inside">@foreach($errors->updatePassword->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @foreach([
                'current_password' => ['Current password', 'current-password'],
                'password' => ['New password', 'new-password'],
                'password_confirmation' => ['Confirm new password', 'new-password'],
            ] as $name => [$label, $autocomplete])
                <div>
                    <label for="{{ $name }}" class="block text-sm font-medium text-slate-700 mb-2">{{ $label }}</label>
                    <input id="{{ $name }}" name="{{ $name }}" :type="show ? 'text' : 'password'" autocomplete="{{ $autocomplete }}" required class="input-field">
                </div>
            @endforeach

            <label class="inline-flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                <input type="checkbox" x-model="show" class="rounded text-sage-600 focus:ring-sage-500"> Show passwords
            </label>

            <div><button type="submit" class="btn-primary">Update password</button></div>
        </div>
    </form>
@endsection
