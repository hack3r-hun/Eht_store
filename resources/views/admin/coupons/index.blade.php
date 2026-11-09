@extends('layouts.admin')

@section('page-title', 'Coupons')

@section('content')
    <x-admin-page-header :count="$coupons->total()" count-label="coupons" subtitle="Discount codes customers can apply in the cart. WELCOME10 is given to newsletter subscribers." />

    <form method="POST" action="{{ route('admin.coupons.store') }}" class="admin-card admin-card-pad mb-6 grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
        @csrf
        <div class="col-span-2 md:col-span-1">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Code</label>
            <input name="code" value="{{ old('code') }}" required placeholder="SPRING15" class="input-field uppercase">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Type</label>
            <select name="type" class="input-field">
                <option value="percent" @selected(old('type') === 'percent')>Percent %</option>
                <option value="fixed" @selected(old('type') === 'fixed')>Fixed amount</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Value</label>
            <input type="number" step="0.01" min="0.01" name="value" value="{{ old('value') }}" required class="input-field">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Min. subtotal</label>
            <input type="number" step="1" min="0" name="min_subtotal" value="{{ old('min_subtotal') }}" class="input-field">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Max uses</label>
            <input type="number" min="1" name="max_uses" value="{{ old('max_uses') }}" placeholder="∞" class="input-field">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Expires</label>
            <input type="date" name="expires_at" value="{{ old('expires_at') }}" class="input-field">
        </div>
        <div class="col-span-2 md:col-span-6 flex items-center gap-3">
            <button type="submit" class="btn-primary !py-2.5 !px-5 text-sm">Create coupon</button>
            @if($errors->any())<span class="text-sm text-red-600">{{ $errors->first() }}</span>@endif
        </div>
    </form>

    <div class="admin-card overflow-x-auto">
        <table class="admin-table">
            <thead><tr><th>Code</th><th>Discount</th><th>Min. subtotal</th><th>Used</th><th>Expires</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($coupons as $coupon)
                    <tr>
                        <td class="font-mono font-bold">{{ $coupon->code }}</td>
                        <td>{{ $coupon->label() }}</td>
                        <td>{{ $coupon->min_subtotal > 0 ? shop_money($coupon->min_subtotal) : '—' }}</td>
                        <td>{{ $coupon->used_count }}{{ $coupon->max_uses ? ' / '.$coupon->max_uses : '' }}</td>
                        <td>{{ $coupon->expires_at?->format('d M Y') ?? '—' }}</td>
                        <td><span class="admin-badge {{ $coupon->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">{{ $coupon->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-right whitespace-nowrap">
                            <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="admin-action-link">{{ $coupon->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                            <x-confirm-delete :action="route('admin.coupons.destroy', $coupon)" title="Delete coupon?" item="{{ $coupon->code }}" message="This coupon will stop working immediately." confirm-label="Delete" class="inline-block">
                                <span class="sr-only">Delete</span>
                            </x-confirm-delete>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-slate-500 py-10">No coupons yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $coupons->links() }}</div>
@endsection
